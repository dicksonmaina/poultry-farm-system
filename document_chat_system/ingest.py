"""
Document Ingestion Module
Handles loading, chunking, and embedding documents into Weaviate
"""

import os
import re
from pathlib import Path
from typing import List, Tuple, Optional, Dict
import logging

from config import get_ollama_embedding, Config, init_weaviate, create_collection
from weaviate.classes.query import MetadataQuery

logger = logging.getLogger(__name__)


class DocumentLoader:
    """Load documents from various formats"""

    @staticmethod
    def load_pdf(file_path: str) -> str:
        """Load PDF file"""
        try:
            import fitz  # PyMuPDF
            doc = fitz.open(file_path)
            text = ""
            for page in doc:
                text += page.get_text()
            doc.close()
            return text
        except ImportError:
            logger.error("PyMuPDF not installed. Install with: pip install pymupdf")
            raise
        except Exception as e:
            logger.error(f"Failed to load PDF {file_path}: {e}")
            raise

    @staticmethod
    def load_text(file_path: str) -> str:
        """Load text file"""
        try:
            with open(file_path, 'r', encoding='utf-8') as f:
                return f.read()
        except UnicodeDecodeError:
            with open(file_path, 'r', encoding='latin-1') as f:
                return f.read()
        except Exception as e:
            logger.error(f"Failed to load text file {file_path}: {e}")
            raise

    @staticmethod
    def load_document(file_path: str) -> Optional[str]:
        """Load document based on file extension"""
        path = Path(file_path)
        if not path.exists():
            logger.warning(f"File not found: {file_path}")
            return None

        suffix = path.suffix.lower()
        try:
            if suffix == '.pdf':
                return DocumentLoader.load_pdf(file_path)
            elif suffix in ['.txt', '.md', '.text']:
                return DocumentLoader.load_text(file_path)
            else:
                logger.warning(f"Unsupported file type: {suffix}")
                return None
        except Exception as e:
            logger.error(f"Error loading {file_path}: {e}")
            return None


class TextChunker:
    """Split text into overlapping chunks"""

    def __init__(self, chunk_size: int = 500, overlap: int = 50):
        self.chunk_size = chunk_size
        self.overlap = overlap

    def split_text(self, text: str) -> List[str]:
        """Split text into chunks with overlap"""
        if len(text) <= self.chunk_size:
            return [text]

        chunks = []
        start = 0

        while start < len(text):
            end = start + self.chunk_size

            # Try to break at sentence boundary
            if end < len(text):
                # Look for sentence end markers
                for marker in ['. ', '! ', '? ', '\n\n']:
                    pos = text.rfind(marker, start, end)
                    if pos > start + self.chunk_size // 2:
                        end = pos + len(marker)
                        break

            chunk = text[start:end].strip()
            if chunk:
                chunks.append(chunk)

            start = end - self.overlap

        return chunks


class DocumentIngester:
    """Main ingestion pipeline"""

    def __init__(self, config: Config):
        self.config = config
        self.chunker = TextChunker(
            chunk_size=config.get("chunk_size"),
            overlap=config.get("chunk_overlap")
        )
        self.client = None
        self.collection = None

    def initialize(self):
        """Initialize Weaviate client and collection"""
        self.client = init_weaviate(self.config)
        create_collection(
            self.client,
            self.config.get("collection_name"),
            self.config.get("embedding_dim")
        )
        self.collection = self.client.collections.get(self.config.get("collection_name"))
        logger.info("Document ingester initialized")

    def ingest_file(self, file_path: str) -> int:
        """Ingest a single file"""
        logger.info(f"Ingesting file: {file_path}")

        text = DocumentLoader.load_document(file_path)
        if not text:
            return 0

        chunks = self.chunker.split_text(text)
        count = 0

        for idx, chunk in enumerate(chunks):
            try:
                embedding = get_ollama_embedding(
                    chunk,
                    model=self.config.get("ollama_model"),
                    host=self.config.get("ollama_host")
                )

                self.collection.data.insert({
                    "content": chunk,
                    "source": str(file_path),
                    "chunk_index": idx,
                    "metadata": {
                        "total_chunks": len(chunks),
                        "file_type": Path(file_path).suffix.lower(),
                        "file_name": Path(file_path).name
                    }
                }, vector=embedding)

                count += 1
                if count % 10 == 0:
                    logger.info(f"Ingested {count} chunks...")

            except Exception as e:
                logger.error(f"Failed to ingest chunk {idx}: {e}")

        logger.info(f"Successfully ingested {count} chunks from {file_path}")
        return count

    def ingest_directory(self, directory: str) -> int:
        """Ingest all supported documents in directory"""
        total = 0
        dir_path = Path(directory)

        if not dir_path.exists():
            logger.error(f"Directory not found: {directory}")
            return 0

        supported_extensions = {'.pdf', '.txt', '.md', '.text'}

        for file_path in dir_path.rglob('*'):
            if file_path.is_file() and file_path.suffix.lower() in supported_extensions:
                count = self.ingest_file(str(file_path))
                total += count

        logger.info(f"Total chunks ingested: {total}")
        return total

    def search(self, query: str, limit: int = 3) -> List[Dict]:
        """Search for relevant document chunks"""
        try:
            query_embedding = get_ollama_embedding(
                query,
                model=self.config.get("ollama_model"),
                host=self.config.get("ollama_host")
            )

            collection = self.client.collections.get(self.config.get("collection_name"))
            results = collection.query.near_vector(
                near_vector=query_embedding,
                limit=limit,
                return_properties=["content", "source", "chunk_index", "metadata"],
                return_metadata=MetadataQuery(distance=True)
            )

            return [
                {
                    "content": obj.properties["content"],
                    "source": obj.properties["source"],
                    "chunk_index": obj.properties["chunk_index"],
                    "metadata": obj.properties.get("metadata", {}),
                    "score": obj.metadata.distance if obj.metadata else 0
                }
                for obj in results.objects
            ]
        except Exception as e:
            logger.error(f"Search failed: {e}")
            return []

    def close(self):
        """Close Weaviate client"""
        if self.client:
            self.client.close()
            logger.info("Weaviate client closed")


def main():
    """Run ingestion from command line"""
    import argparse
    parser = argparse.ArgumentParser(description="Ingest documents into vector database")
    parser.add_argument("--docs", default="docs", help="Documents directory path")
    parser.add_argument("--config", default="config.json", help="Config file path")
    args = parser.parse_args()

    config = Config(args.config)
    config.config["documents_path"] = args.docs

    ingester = DocumentIngester(config)
    try:
        ingester.initialize()
        total = ingester.ingest_directory(args.docs)
        logger.info(f"Ingestion complete. Total chunks: {total}")
    finally:
        ingester.close()


if __name__ == "__main__":
    main()
