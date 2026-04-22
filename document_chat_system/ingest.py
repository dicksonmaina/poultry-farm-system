"""
Document Ingestion Module
Handles loading, chunking, and embedding documents into ChromaDB using sentence-transformers
"""

import os
import re
from pathlib import Path
from typing import List, Tuple, Optional, Dict
import logging

from config import Config
import chromadb
from chromadb.config import Settings
from sentence_transformers import SentenceTransformer

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


class EmbeddingGenerator:
    """Generate embeddings using sentence-transformers"""

    def __init__(self, model_name: str = "all-MiniLM-L6-v2"):
        self.model_name = model_name
        self.model = None
        self._load_model()

    def _load_model(self):
        """Load the sentence transformer model"""
        try:
            self.model = SentenceTransformer(self.model_name)
            logger.info(f"Loaded embedding model: {self.model_name}")
        except Exception as e:
            logger.error(f"Failed to load embedding model {self.model_name}: {e}")
            raise

    def encode(self, texts: List[str]) -> List[List[float]]:
        """Encode texts to embeddings"""
        if not self.model:
            self._load_model()
        embeddings = self.model.encode(texts)
        return embeddings.tolist()


class DocumentIngester:
    """Main ingestion pipeline using ChromaDB"""

    def __init__(self, config: Config):
        self.config = config
        self.chunker = TextChunker(
            chunk_size=config.get("chunk_size"),
            overlap=config.get("chunk_overlap")
        )
        self.embedder = EmbeddingGenerator(
            model_name=config.get("embedding_model")
        )
        self.client = None
        self.collection = None

    def initialize(self):
        """Initialize ChromaDB client and collection"""
        try:
            # Create data directory if it doesn't exist
            chromadb_path = self.config.get("chromadb_data_path")
            Path(chromadb_path).mkdir(parents=True, exist_ok=True)
            
            # Initialize ChromaDB client
            self.client = chromadb.PersistentClient(path=chromadb_path)
            
            # Get or create collection
            collection_name = self.config.get("collection_name")
            try:
                self.collection = self.client.get_collection(name=collection_name)
                logger.info(f"Using existing collection: {collection_name}")
            except Exception:
                self.collection = self.client.create_collection(
                    name=collection_name,
                    metadata={"description": "Document chunks for RAG"}
                )
                logger.info(f"Created new collection: {collection_name}")
                
            logger.info("Document ingester initialized")
        except Exception as e:
            logger.error(f"Failed to initialize ChromaDB: {e}")
            raise

    def ingest_file(self, file_path: str) -> int:
        """Ingest a single file"""
        logger.info(f"Ingesting file: {file_path}")

        text = DocumentLoader.load_document(file_path)
        if not text:
            return 0

        chunks = self.chunker.split_text(text)
        if not chunks:
            logger.warning(f"No chunks generated from {file_path}")
            return 0

        # Generate embeddings for all chunks
        logger.info(f"Generating embeddings for {len(chunks)} chunks...")
        embeddings = self.embedder.encode(chunks)

        # Prepare data for ChromaDB
        ids = [f"{Path(file_path).stem}_{i}" for i in range(len(chunks))]
        metadatas = []
        for i, chunk in enumerate(chunks):
            metadatas.append({
                "source": str(file_path),
                "chunk_index": i,
                "total_chunks": len(chunks),
                "file_type": Path(file_path).suffix.lower(),
                "file_name": Path(file_path).name
            })

        # Add to collection
        try:
            self.collection.add(
                embeddings=embeddings,
                documents=chunks,
                metadatas=metadatas,
                ids=ids
            )
            logger.info(f"Successfully ingested {len(chunks)} chunks from {file_path}")
            return len(chunks)
        except Exception as e:
            logger.error(f"Failed to add chunks to ChromaDB: {e}")
            return 0

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
            # Generate embedding for query
            query_embedding = self.embedder.encode([query])[0]
            
            # Search in ChromaDB
            results = self.collection.query(
                query_embeddings=[query_embedding],
                n_results=limit,
                include=["documents", "metadatas", "distances"]
            )
            
            # Format results
            documents = []
            if results['documents'] and results['documents'][0]:
                for i, doc in enumerate(results['documents'][0]):
                    documents.append({
                        "content": doc,
                        "source": results['metadatas'][0][i].get("source", "Unknown"),
                        "chunk_index": results['metadatas'][0][i].get("chunk_index", 0),
                        "metadata": results['metadatas'][0][i],
                        "score": 1 - results['distances'][0][i] if results['distances'] and results['distances'][0] else 0  # Convert distance to similarity
                    })
            
            return documents
        except Exception as e:
            logger.error(f"Search failed: {e}")
            return []

    def close(self):
        """Close ChromaDB client"""
        # ChromaDB PersistentClient doesn't need explicit closing
        # but we'll keep the method for compatibility
        if self.client:
            logger.info("ChromaDB client closed")


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