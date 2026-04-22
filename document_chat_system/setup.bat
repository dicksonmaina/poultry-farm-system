@echo off
echo ================================
echo Document Chat System - Setup
echo ================================
echo.

REM Check Python
python --version >nul 2>&1
if errorlevel 1 (
    echo ERROR: Python not found!
    echo Install Python 3.10+ from python.org
    pause
    exit /b 1
)

echo Found Python. Installing dependencies...
pip install -r requirements.txt

if errorlevel 1 (
    echo ERROR: Failed to install dependencies
    pause
    exit /b 1
)

echo.
echo Creating directories...
if not exist "docs" mkdir docs
if not exist "data\weaviate" mkdir data\weaviate

echo.
echo Checking config...
if not exist "config.json" (
    echo Creating default config.json...
    python -c "import json; d={'documents_path':'docs','weaviate_data_path':'data/weaviate','ollama_host':'http://localhost:11434','ollama_model':'llama3.1','telegram_token':'','collection_name':'DocumentChunks','chunk_size':500,'chunk_overlap':50,'embedding_dim':4096}; open('config.json','w').write(json.dumps(d, indent=2))"
)

echo.
echo ================================
echo SETUP COMPLETE
echo ================================
echo.
echo NEXT STEPS:
echo 1. Add your Telegram bot token to config.json
echo 2. Put your documents in the docs\ folder
echo 3. Run: python main.py --mode ingest
echo 4. Run: python main.py --mode bot
echo.
pause
