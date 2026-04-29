"""
Telegram Bot Module
Handles Telegram bot interface for document chat
"""

import asyncio
import logging
from typing import Optional
from telegram import Update, InlineKeyboardButton, InlineKeyboardMarkup
from telegram.ext import (
    Application,
    CommandHandler,
    MessageHandler,
    filters,
    CallbackQueryHandler,
    ContextTypes
)

from config import Config
from ingest import DocumentIngester
from rag_pipeline import RAGPipeline

logger = logging.getLogger(__name__)


class DocumentChatBot:
    """Telegram bot for document chat"""

    def __init__(self, config: Config):
        self.config = config
        self.ingester = None
        self.rag = None
        self.application = None

    def initialize(self):
        """Initialize ingester and RAG pipeline"""
        self.ingester = DocumentIngester(self.config)
        self.ingester.initialize()
        self.rag = RAGPipeline(self.config, self.ingester)
        logger.info("Bot initialized successfully")

    async def start_command(self, update: Update, context: ContextTypes.DEFAULT_TYPE):
        """Handle /start command"""
        welcome_message = """
🤖 *Document Chat Bot*

Welcome! I'm your local document assistant. I can answer questions based on documents stored in your local database.

📚 *How to use:*
1. Simply send me a question about your documents
2. I'll search through the documents and provide an answer with sources

📋 *Commands:*
/start - Show this welcome message
/help - Show help information
/status - Check system status
/ask <question> - Ask a specific question about your documents
/ingest - Re-ingest documents (admin)

🔍 *Example questions:*
- "What are the key points from the documents?"
- "Summarize the main topics"
- "Find information about X"

Start chatting now! Send me a question about your documents.
"""
        await update.message.reply_text(welcome_message, parse_mode='Markdown')

    async def help_command(self, update: Update, context: ContextTypes.DEFAULT_TYPE):
        """Handle /help command"""
        help_text = """
*Help*

I use Retrieval-Augmented Generation (RAG) to answer questions from your local documents.

*How it works:*
1. Your documents are split into chunks
2. Chunks are converted to vector embeddings using sentence-transformers
3. When you ask a question, I find the most relevant chunks
4. I use Groq's Llama 3 to generate an answer based on those chunks

*Supported file types:*
- PDF (.pdf)
- Text files (.txt, .md, .text)

*Tips:*
- Be specific in your questions
- I'll always cite which documents I used
- If I can't find relevant info, I'll let you know

Need more help? Contact the system administrator.
"""
        await update.message.reply_text(help_text, parse_mode='Markdown')

    async def status_command(self, update: Update, context: ContextTypes.DEFAULT_TYPE):
        """Handle /status command"""
        try:
            # Check documents count
            doc_count = self._get_document_count()

            status_msg = f"""
🤖 *Bot Status*

📄 *Documents in DB:* {doc_count}
💾 *Storage:* {self.config.get('chromadb_data_path')}

*Embedding Model:* {self.config.get('embedding_model')}
*LLM:* {self.config.get('groq_model')}
*Vector DB:* ChromaDB (embedded)

✅ All systems operational!
"""
            await update.message.reply_text(status_msg, parse_mode='Markdown')
        except Exception as e:
            await update.message.reply_text(f"❌ Status check failed: {str(e)}")

    def _get_document_count(self) -> int:
        """Get total document chunks in database"""
        try:
            count = self.ingester.collection.count()
            return count
        except:
            return 0

    async def ingest_command(self, update: Update, context: ContextTypes.DEFAULT_TYPE):
        """Handle /ingest command - re-ingest all documents"""
        await update.message.reply_text("🔄 Starting document ingestion... This may take a while.")

        try:
            docs_path = self.config.get("documents_path")
            count = self.ingester.ingest_directory(docs_path)
            await update.message.reply_text(f"✅ Ingestion complete! Indexed {count} document chunks.")
        except Exception as e:
            await update.message.reply_text(f"❌ Ingestion failed: {str(e)}")

    async def ask_command(self, update: Update, context: ContextTypes.DEFAULT_TYPE):
        """Handle /ask command - answer a specific question"""
        # Extract the question from the command
        query = update.message.text.partition(' ')[2].strip()
        
        if not query:
            await update.message.reply_text(
                "Please provide a question after the /ask command.\n"
                "Example: /ask What are the main topics discussed in the documents?"
            )
            return

        # Show typing indicator
        await update.message.chat.send_action(action="typing")

        try:
            # Process query through RAG
            result = self.rag.query(query, top_k=3)

            # Format response
            response = self._format_response(result)
            await update.message.reply_text(response, parse_mode='Markdown')

        except Exception as e:
            logger.error(f"Query processing failed: {e}")
            await update.message.reply_text(
                f"❌ Sorry, I encountered an error: {str(e)}\n\nPlease try again later."
            )

    async def handle_message(self, update: Update, context: ContextTypes.DEFAULT_TYPE):
        """Handle incoming text messages (backward compatibility)"""
        query = update.message.text.strip()
        user = update.effective_user

        logger.info(f"Received query from {user.first_name}: {query[:50]}")

        # Show typing indicator
        await update.message.chat.send_action(action="typing")

        try:
            # Process query through RAG
            result = self.rag.query(query, top_k=3)

            # Format response
            response = self._format_response(result)
            await update.message.reply_text(response, parse_mode='Markdown')

        except Exception as e:
            logger.error(f"Query processing failed: {e}")
            await update.message.reply_text(
                f"❌ Sorry, I encountered an error: {str(e)}\n\nPlease try again later."
            )

    def _format_response(self, result: Dict) -> str:
        """Format RAG result into Telegram-friendly message"""
        answer = result.get("answer", "No answer generated")
        sources = result.get("sources", [])

        response = f"💬 *Answer:*\n{answer}\n\n"

        if sources:
            response += "📚 *Sources:*\n"
            for src in sources:
                # Extract filename only
                import os
                filename = os.path.basename(src)
                response += f"  • `{filename}`\n"
        else:
            response += "⚠️ No source documents referenced."

        return response

    async def error_handler(self, update: Update, context: ContextTypes.DEFAULT_TYPE):
        """Handle errors"""
        logger.error(f"Update {update} caused error: {context.error}")
        if update and update.effective_message:
            await update.effective_message.reply_text(
                "❌ An error occurred. Please try again."
            )

    def run(self):
        """Start the bot"""
        # Build application
        self.application = Application.builder().token(
            self.config.get("telegram_token")
        ).build()

        # Register handlers
        self.application.add_handler(CommandHandler("start", self.start_command))
        self.application.add_handler(CommandHandler("help", self.help_command))
        self.application.add_handler(CommandHandler("status", self.status_command))
        self.application.add_handler(CommandHandler("ask", self.ask_command))
        self.application.add_handler(CommandHandler("ingest", self.ingest_command))
        self.application.add_handler(MessageHandler(filters.TEXT & ~filters.COMMAND, self.handle_message))

        # Error handler
        self.application.add_error_handler(self.error_handler)

        logger.info("Starting Telegram bot...")
        self.application.run_polling(allowed_updates=Update.ALL_TYPES)

    def stop(self):
        """Stop the bot"""
        if self.application:
            self.application.stop()
            logger.info("Bot stopped")
        if self.ingester:
            self.ingester.close()


def main():
    """Run the Telegram bot"""
    import argparse

    parser = argparse.ArgumentParser(description="Telegram Document Chat Bot")
    parser.add_argument("--config", default="config.json", help="Config file path")
    args = parser.parse_args()

    config = Config(args.config)

    # Validate Telegram token
    if not config.get("telegram_token"):
        logger.error("Telegram token not set. Add it to config.json or set TELEGRAM_TOKEN environment variable.")
        return

    bot = DocumentChatBot(config)
    try:
        bot.initialize()
        bot.run()
    except KeyboardInterrupt:
        logger.info("Bot shutting down...")
    finally:
        bot.stop()


if __name__ == "__main__":
    main()