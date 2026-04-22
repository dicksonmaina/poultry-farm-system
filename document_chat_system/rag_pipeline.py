"""
RAG Pipeline
Handles retrieval and generation using Ollama LLM
"""

import logging
from typing import List, Dict, Optional
import requests
import json

from config import Config, get_ollama_embedding

logger = logging.getLogger(__name__)


class RAGPipeline:
    """Retrieval-Augmented Generation pipeline"""

    def __init__(self, config: Config, ingester):
        self.config = config
        self.ingester = ingester

    def retrieve(self, query: str, top_k: int = 3) -> List[Dict]:
        """Retrieve relevant document chunks"""
        results = self.ingester.search(query, limit=top_k)
        logger.info(f"Retrieved {len(results)} chunks for query: {query[:50]}...")
        return results

    def format_context(self, documents: List[Dict]) -> str:
        """Format retrieved documents into context string"""
        if not documents:
            return ""

        context_parts = []
        for i, doc in enumerate(documents, 1):
            source = doc.get("source", "Unknown")
            content = doc.get("content", "").strip()
            context_parts.append(f"[Document {i} - Source: {source}]\n{content}")

        return "\n\n".join(context_parts)

    def generate(self, query: str, context: str) -> str:
        """Generate answer using Ollama LLM"""
        system_prompt = """You are a helpful assistant that answers questions based on the provided document context.
Follow these rules:
1. Only use information from the provided context.
2. If the context doesn't contain relevant information, say so clearly.
3. Cite the source document numbers when referencing specific information.
4. Be concise but thorough.
5. If asked about something not in the documents, politely explain you can only answer based on the provided documents."""

        user_prompt = f"""Context from documents:
{context}

Question: {query}

Please answer based on the context above."""

        try:
            response = requests.post(
                f"{self.config.get('ollama_host')}/api/generate",
                json={
                    "model": self.config.get("ollama_model"),
                    "prompt": user_prompt,
                    "system": system_prompt,
                    "stream": False,
                    "options": {
                        "temperature": 0.7,
                        "top_p": 0.9,
                        "num_predict": 512
                    }
                }
            )
            response.raise_for_status()
            result = response.json()
            return result.get("response", "I couldn't generate an answer.")
        except Exception as e:
            logger.error(f"Generation failed: {e}")
            return f"Error generating answer: {str(e)}"

    def query(self, question: str, top_k: int = 3) -> Dict:
        """Complete RAG query: retrieve and generate"""
        documents = self.retrieve(question, top_k)
        context = self.format_context(documents)

        if not context:
            return {
                "answer": "I couldn't find any relevant information in the documents to answer your question.",
                "sources": [],
                "documents": []
            }

        answer = self.generate(question, context)

        return {
            "answer": answer,
            "sources": list(set(doc["source"] for doc in documents)),
            "documents": documents
        }


def main():
    """Test RAG pipeline"""
    import argparse
    from ingest import DocumentIngester

    parser = argparse.ArgumentParser(description="Query the RAG system")
    parser.add_argument("--query", required=True, help="Query string")
    parser.add_argument("--config", default="config.json", help="Config file path")
    args = parser.parse_args()

    config = Config(args.config)
    ingester = DocumentIngester(config)
    ingester.initialize()

    try:
        rag = RAGPipeline(config, ingester)
        result = rag.query(args.query)

        print("\n" + "=" * 60)
        print("QUESTION:", args.query)
        print("=" * 60)
        print("\nANSWER:\n", result["answer"])
        print("\nSOURCES:")
        for src in result["sources"]:
            print(f"  - {src}")
    finally:
        ingester.close()


if __name__ == "__main__":
    main()
