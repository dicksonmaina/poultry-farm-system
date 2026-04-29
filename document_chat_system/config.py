import os
import sys
import json
import logging
from pathlib import Path
from typing import Optional, Dict, Any

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
            "chromadb_data_path": "data/chromadb",
            "groq_api_key": "",  # Set via environment or config
            "groq_model": "llama3-8b-8192",
            "collection_name": "DocumentChunks",
            "chunk_size": 500,
            "chunk_overlap": 50,
            "embedding_model": "all-MiniLM-L6-v2"
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