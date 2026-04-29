"""
Main Orchestrator
Coordinates ingestion and bot operations
"""

import argparse
import logging
import asyncio
import sys
from pathlib import Path

from config import Config, check_ollama_availability, setup_ollama_model, init_weaviate
from ingest import DocumentIngester
from rag_pipeline import RAGPipeline
from telegram_bot import DocumentChatBot

logger = logging.getLogger(__name__)


async def run_ingest_mode(config: Config):
    """Run ingestion only"""
    logger.info("=== Running in INGEST mode ===")

    # Check Ollama
    if not check_ollama_availability(config.get("ollama_host"), config.get("ollama_model")):
        logger.error(f"Ollama not available at {config.get('ollama_host')}")
        logger.info("Make sure Ollama is running and the model is available.")
        logger.info(f"Run: ollama pull {config.get('ollama_model')}")
        return False

    # Initialize ingester
    ingester = DocumentIngester(config)
    try:
        ingester.initialize()
        docs_path = config.get("documents_path")
        logger.info(f"Ingesting documents from: {docs_path}")
        count = ingester.ingest_directory(docs_path)
        logger.info(f"✅ Ingestion complete. {count} chunks indexed.")
        return True
    finally:
        ingester.close()


async def run_bot_mode(config: Config):
    """Run Telegram bot"""
    logger.info("=== Running in BOT mode ===")

    # Check prerequisites
    if not check_ollama_availability(config.get("ollama_host"), config.get("ollama_model")):
        logger.error(f"Ollama not available at {config.get('ollama_host')}")
        logger.info("Make sure Ollama is running and the model is available.")
        return False

    if not config.get("telegram_token"):
        logger.error("Telegram token not configured!")
        logger.info("Set TELEGRAM_TOKEN environment variable or add to config.json")
        return False

    # Initialize and run bot
    bot = DocumentChatBot(config)
    try:
        bot.initialize()
        logger.info("✅ Bot started. Press Ctrl+C to stop.")
        await asyncio.get_event_loop().run_in_executor(None, bot.run)
    except KeyboardInterrupt:
        logger.info("Shutting down...")
    finally:
        bot.stop()

    return True


async def run_combined_mode(config: Config):
    """Run both ingestion and bot"""
    logger.info("=== Running in COMBINED mode ===")

    # First ingest
    success = await run_ingest_mode(config)
    if not success:
        logger.error("Ingestion failed, exiting.")
        return False

    # Then start bot
    logger.info("\nStarting bot after ingestion...")
    await run_bot_mode(config)
    return True


def main():
    parser = argparse.ArgumentParser(
        description="Local Document Chat System - Ollama + Weaviate + Telegram Bot"
    )
    parser.add_argument(
        "--mode",
        choices=["ingest", "bot", "combined"],
        default="combined",
        help="Run mode: ingest (index documents only), bot (start Telegram bot), combined (both)"
    )
    parser.add_argument("--config", default="config.json", help="Path to config file")
    parser.add_argument("--docs", help="Documents directory path (overrides config)")
    parser.add_argument("--token", help="Telegram bot token (overrides config)")
    parser.add_argument("--model", help="Ollama model name (overrides config)")
    parser.add_argument("--host", help="Ollama host URL (overrides config)")

    args = parser.parse_args()

    # Setup logging
    logging.basicConfig(
        level=logging.INFO,
        format='%(asctime)s - %(name)s - %(levelname)s - %(message)s'
    )

    # Load config
    config = Config(args.config)

    # Override with CLI args
    if args.docs:
        config.config["documents_path"] = args.docs
    if args.token:
        config.config["telegram_token"] = args.token
    if args.model:
        config.config["ollama_model"] = args.model
    if args.host:
        config.config["ollama_host"] = args.host

    # Ensure documents directory exists
    docs_path = Path(config.get("documents_path"))
    if not docs_path.exists():
        logger.warning(f"Documents directory not found: {docs_path}")
        docs_path.mkdir(parents=True, exist_ok=True)
        logger.info(f"Created directory: {docs_path}")
        logger.info("Place your PDF and text files there before running ingestion.")

    # Run based on mode
    try:
        if args.mode == "ingest":
            asyncio.run(run_ingest_mode(config))
        elif args.mode == "bot":
            asyncio.run(run_bot_mode(config))
        else:  # combined
            asyncio.run(run_combined_mode(config))
    except KeyboardInterrupt:
        logger.info("Shutting down...")
    except Exception as e:
        logger.error(f"Fatal error: {e}")
        sys.exit(1)


if __name__ == "__main__":
    main()
