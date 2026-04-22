import os
import sys
import json
import logging
from pathlib import Path
from typing import Optional, Dict, Any
import weaviate
from weaviate.classes.config import Configure, Property, DataType
import requests

logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)


class Config:
    """Configuration management for the document chat system"""

    def __init__(self, config_path: Optional[str] = None):
        self.config_path = config_path or "config.json"
        self.config = self._load_config()

    def _load_config(self) -> Dict[str, Any]:
        """Load configuration from file or use defaults"""
        default_config = {
            "documents_path": "docs",
            "weaviate_data_path": "data/weaviate",
            "ollama_host": "http://localhost:11434",
            "ollama_model": "llama3.1",
            "telegram_token": "",  # Set via environment or config
            "collection_name": "DocumentChunks",
            "chunk_size": 500,
            "chunk_overlap": 50,
            "embedding_dim": 4096  # For llama3.1
        }

        if os.path.exists(self.config_path):
            try:
                with open(self.config_path, 'r') as f:
                    user_config = json.load(f)
                default_config.update(user_config)
            except Exception as e:
                logger.warning(f"Failed to load config: {e}, using defaults")

        return default_config

    def get(self, key: str, default: Any = None) -> Any:
        return self.config.get(key, default)

    def save(self, config: Dict[str, Any]):
        """Save configuration to file"""
        with open(self.config_path, 'w') as f:
            json.dump(config, f, indent=2)


def get_ollama_embedding(text: str, model: str = "llama3.1", host: str = "http://localhost:11434") -> list:
    """Get embedding from Ollama"""
    try:
        response = requests.post(
            f"{host}/api/embeddings",
            json={"model": model, "prompt": text}
        )
        response.raise_for_status()
        return response.json()["embedding"]
    except Exception as e:
        logger.error(f"Failed to get embedding: {e}")
        raise


def init_weaviate(config: Config) -> weaviate.WeaviateClient:
    """Initialize Weaviate embedded client"""
    try:
        client = weaviate.connect_to_embedded(
            persistence_data_path=config.get("weaviate_data_path")
        )
        logger.info("Weaviate embedded client connected")
        return client
    except Exception as e:
        logger.error(f"Failed to initialize Weaviate: {e}")
        logger.info("Make sure Weaviate is installed: visit weaviate.io for installation")
        raise


def create_collection(client: weaviate.WeaviateClient, collection_name: str, embedding_dim: int):
    """Create vector collection if it doesn't exist"""
    try:
        collections = client.collections.list_all()
        collection_names = [c.name for c in collections]
        if collection_name in collection_names:
            logger.info(f"Collection {collection_name} already exists")
            return

        collection = client.collections.create(
            name=collection_name,
            properties=[
                Property(name="content", data_type=DataType.TEXT),
                Property(name="source", data_type=DataType.TEXT),
                Property(name="chunk_index", data_type=DataType.INT),
                Property(name="metadata", data_type=DataType.OBJECT),
            ],
            vector_index_config=Configure.VectorIndex.hnsw(
                distance_metric="cosine",
                ef=128,
                ef_construct=128,
                max_connections=64,
            )
        )
        logger.info(f"Created collection: {collection_name}")
    except Exception as e:
        logger.error(f"Failed to create collection: {e}")
        raise


def check_ollama_availability(host: str, model: str) -> bool:
    """Check if Ollama is running and model is available"""
    try:
        response = requests.get(f"{host}/api/tags")
        response.raise_for_status()
        models = [m["name"] for m in response.json().get("models", [])]
        if model in models or f"{model}:latest" in models:
            logger.info(f"Ollama available with model: {model}")
            return True
        else:
            logger.warning(f"Model {model} not found in Ollama. Available: {models}")
            return False
    except Exception as e:
        logger.error(f"Ollama not available: {e}")
        return False


def setup_ollama_model(host: str, model: str):
    """Pull the specified model if not available"""
    try:
        logger.info(f"Pulling model {model} from Ollama...")
        response = requests.post(
            f"{host}/api/pull",
            json={"name": model, "stream": False}
        )
        response.raise_for_status()
        logger.info(f"Model {model} pulled successfully")
    except Exception as e:
        logger.error(f"Failed to pull model: {e}")
        raise
