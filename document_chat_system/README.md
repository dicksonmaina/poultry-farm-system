# Local Document Chat System

A fully local RAG (Retrieval-Augmented Generation) system using **Ollama** (llama3.1), **Weaviate embedded** vector database, and a **Telegram bot** interface.

## Features

- **100% Local**: No external API calls, everything runs on your machine
- **Multiple Document Types**: Supports PDF, TXT, MD files
- **Smart Chunking**: Overlapping chunks with sentence-aware splitting
- **Vector Search**: Weaviate embedded vector database
- **Telegram Interface**: Chat via Telegram bot
- **RAG Pipeline**: Context-aware answers using Llama 3.1

## Architecture

```
Documents (PDF/TXT) → Chunker → Ollama Embeddings → Weaviate DB
                                              ↓
User Question (Telegram) → Query Embedding → Vector Search → Context + Llama 3.1 → Answer
```

## Prerequisites

1. **Python 3.10+**
2. **Ollama** installed and running
3. **Telegram Bot Token** from @BotFather

## Quick Start

### 1. Install Dependencies

```bash
cd document_chat_system
pip install -r requirements.txt
```

### 2. Install and Start Ollama

Download and install Ollama from [ollama.ai](https://ollama.ai)

Pull the llama3.1 model:

```bash
ollama pull llama3.1
```

Start Ollama (it usually runs as a service):

```bash
# On Windows, Ollama should be running in the background
# Check: http://localhost:11434
```

### 3. Configure the System

Edit `config.json`:

```json
{
  "documents_path": "docs",
  "weaviate_data_path": "data/weaviate",
  "ollama_host": "http://localhost:11434",
  "ollama_model": "llama3.1",
  "telegram_token": "YOUR_BOT_TOKEN_HERE",
  "collection_name": "DocumentChunks",
  "chunk_size": 500,
  "chunk_overlap": 50,
  "embedding_dim": 4096
}
```

### 4. Add Your Documents

Place PDF or text files in the `docs/` folder:

```
document_chat_system/
├── docs/
│   ├── manual.pdf
│   ├── guide.txt
│   └── notes.md
```

### 5. Ingest Documents

```bash
python main.py --mode ingest
```

This will:
- Load all documents from `docs/`
- Split them into chunks
- Create embeddings via Ollama
- Store in Weaviate embedded DB

### 6. Start the Telegram Bot

```bash
python main.py --mode bot
```

The bot will start polling. Open Telegram and start chatting with your bot.

## Usage Modes

### Ingest Only

Index documents without starting the bot:

```bash
python main.py --mode ingest --docs /path/to/documents
```

### Bot Only

Start the bot using already-indexed documents:

```bash
python main.py --mode bot --token YOUR_BOT_TOKEN
```

### Combined (Default)

Ingest documents then start the bot:

```bash
python main.py --mode combined
```

## Commands

Once the bot is running, use these Telegram commands:

| Command | Description |
|---------|-------------|
| `/start` | Welcome message |
| `/help` | Show help |
| `/status` | System status (documents count, Ollama connection) |
| `/ingest` | Re-ingest all documents (admin) |

## Configuration Options

| Option | Description | Default |
|--------|-------------|---------|
| `documents_path` | Path to document files | `docs` |
| `weaviate_data_path` | Where vector DB is stored | `data/weaviate` |
| `ollama_host` | Ollama API URL | `http://localhost:11434` |
| `ollama_model` | LLM model name | `llama3.1` |
| `telegram_token` | Telegram bot token | *(required)* |
| `collection_name` | Weaviate collection name | `DocumentChunks` |
| `chunk_size` | Text chunk size (tokens) | `500` |
| `chunk_overlap` | Overlap between chunks | `50` |

You can also use environment variables (see `.env.example`).

## API Reference

### ingest.py

```python
from ingest import DocumentIngester, Config

config = Config("config.json")
ingester = DocumentIngester(config)
ingester.initialize()
ingester.ingest_directory("/path/to/docs")
```

### rag_pipeline.py

```python
from rag_pipeline import RAGPipeline

rag = RAGPipeline(config, ingester)
result = rag.query("What is this about?")
print(result["answer"])
print(result["sources"])
```

### telegram_bot.py

```python
from telegram_bot import DocumentChatBot

bot = DocumentChatBot(config)
bot.initialize()
bot.run()
```

## Troubleshooting

### Ollama Not Connecting

- Ensure Ollama is running: `ollama serve`
- Check API: `curl http://localhost:11434/api/tags`
- Verify model installed: `ollama list`

### Telegram Bot Not Starting

- Get a bot token from [@BotFather](https://t.me/BotFather)
- Add token to `config.json` or set `TELEGRAM_TOKEN` env var
- Make sure bot is not blocked by any firewall

### Ingestion Slow

- Large PDFs take time to process
- First run creates embeddings for all chunks
- Subsequent runs only add new/changed files (currently full re-ingest)

### "Model not found" Error

Pull the model:

```bash
ollama pull llama3.1
```

Or change `ollama_model` in config to an available model:

```bash
ollama list  # See available models
```

### Out of Memory

Reduce chunk size in config:

```json
{
  "chunk_size": 300,
  "chunk_overlap": 20
}
```

Or use a smaller model:

```bash
ollama pull llama2:7b
```

## Data Storage

All data is stored locally:

- `data/weaviate/` - Vector database (SQLite + HNSW index files)
- `config.json` - Your configuration
- No cloud dependencies

## Security Notes

- Keep `config.json` private if it contains sensitive paths
- Telegram bot token is like a password - don't share
- All processing is local, no data leaves your machine
- Weaviate embedded uses local filesystem only

## Development

Run specific modules individually:

```bash
# Test ingestion on single file
python ingest.py --docs ./test_docs

# Test RAG query
python rag_pipeline.py --query "What is this document about?"
```

## Performance Tuning

For better performance:

1. **Use SSD** for Weaviate data path
2. **Increase chunk size** for fewer chunks (memory vs speed tradeoff)
3. **Adjust overlap** for context preservation
4. **Choose smaller model** if memory constrained

## Cleanup

To reset everything:

```bash
# Remove vector database
rm -rf data/weaviate/

# Re-ingest documents
python main.py --mode ingest
```

## License

MIT - Feel free to modify and use.

## Support

For issues:
1. Check logs (enable debug logging with `--log-level DEBUG`)
2. Verify all prerequisites are installed
3. Test Ollama separately: `ollama run llama3.1`
4. Check Telegram bot permissions

---

**System is fully operational when you can:**
1. ✅ `ollama list` shows `llama3.1`
2. ✅ `python main.py --mode ingest` completes without error
3. ✅ Telegram bot responds to `/start`
