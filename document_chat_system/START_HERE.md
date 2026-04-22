# Document Chat System - Quick Start

## Option 1: Automated Setup (Recommended)

Run the setup wizard:

```powershell
cd document_chat_system
python setup.py
```

This will:
- Check Python version
- Verify Ollama installation
- Install Python dependencies
- Create config file
- Create directories
- Show instructions for Telegram token

## Option 2: Manual Setup

### Step 1: Install Python Dependencies

```powershell
cd document_chat_system
python -m pip install -r requirements.txt
```

### Step 2: Start Ollama

Make sure Ollama is running:

```powershell
ollama serve
```

In another terminal, pull the model:

```powershell
ollama pull llama3.1
```

### Step 3: Configure

Edit `config.json` and add your Telegram bot token:

```json
{
  "telegram_token": "123456:ABC-DEFghijklmnopqrstuvwxyz"
}
```

### Step 4: Add Documents

Copy PDF or text files to the `docs/` folder:

```powershell
copy C:\path\to\your\document.pdf docs\
```

### Step 5: Ingest Documents

```powershell
python main.py --mode ingest
```

Wait for completion (check output for chunk count).

### Step 6: Start the Bot

```powershell
python main.py --mode bot
```

The bot will start. Go to Telegram and send `/start` to your bot.

## Quick Commands

| What | Command |
|------|---------|
| Ingest only | `python main.py --mode ingest` |
| Start bot only | `python main.py --mode bot` |
| Ingest then bot | `python main.py --mode combined` |
| Custom docs path | `python main.py --mode ingest --docs C:\my\docs` |
| Test query | `python rag_pipeline.py --query "test question"` |
| Status check | `python main.py --mode bot` then `/status` in Telegram |

## Teleport Bot Commands

Once bot is running, use in Telegram:

- `/start` - Welcome message
- `/help` - Help
- `/status` - System status
- `/ingest` - Re-index all documents

## Still Need Help?

1. Check Ollama: `curl http://localhost:11434/api/tags`
2. Verify Python 3.10+: `python --version`
3. Ensure Telegram token is set in config.json
4. Check docs folder has files (PDF/TXT/MD)
5. Read full README.md

## Uninstall

```powershell
# Remove data
Remove-Item -Recurse -Force data\weaviate
Remove-Item config.json
# Keep your docs if you want
```
