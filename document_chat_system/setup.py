"""
Setup wizard for Windows
Checks prerequisites and installs dependencies
"""

import subprocess
import sys
import os
from pathlib import Path

def run_command(cmd, shell=True):
    """Run shell command"""
    print(f"> {cmd}")
    result = subprocess.run(cmd, shell=shell, capture_output=True, text=True)
    if result.stdout:
        print(result.stdout)
    if result.stderr:
        print(result.stderr, file=sys.stderr)
    return result.returncode == 0

def check_python():
    """Check Python version"""
    print("\n=== Checking Python ===")
    version = sys.version_info
    print(f"Python {version.major}.{version.minor}.{version.micro}")
    if version.major < 3 or (version.major == 3 and version.minor < 10):
        print("❌ Python 3.10+ required!")
        return False
    print("✅ Python version OK")
    return True

def check_ollama():
    """Check if Ollama is installed and running"""
    print("\n=== Checking Ollama ===")
    try:
        import requests
        response = requests.get("http://localhost:11434/api/tags", timeout=3)
        if response.status_code == 200:
            data = response.json()
            models = [m["name"] for m in data.get("models", [])]
            print(f"✅ Ollama running! Models: {models}")

            if "llama3.1" not in models and "llama3.1:latest" not in models:
                print("⚠️  llama3.1 model not found.")
                print("   Run: ollama pull llama3.1")
            return True
    except Exception as e:
        print(f"❌ Ollama not reachable: {e}")
        print("   1. Install Ollama from https://ollama.ai")
        print("   2. Run: ollama serve")
        print("   3. Pull model: ollama pull llama3.1")
    return False

def install_dependencies():
    """Install Python packages"""
    print("\n=== Installing Dependencies ===")
    req_file = Path("requirements.txt")
    if not req_file.exists():
        print("❌ requirements.txt not found!")
        return False

    success = run_command(f"{sys.executable} -m pip install -r {req_file}")
    if success:
        print("✅ Dependencies installed")
    else:
        print("❌ Failed to install dependencies")
    return success

def create_config():
    """Create config.json if it doesn't exist"""
    print("\n=== Configuring ===")
    config_path = Path("config.json")
    if config_path.exists():
        print("✅ config.json already exists")
        return True

    default_config = {
        "documents_path": "docs",
        "weaviate_data_path": "data/weaviate",
        "ollama_host": "http://localhost:11434",
        "ollama_model": "llama3.1",
        "telegram_token": "",
        "collection_name": "DocumentChunks",
        "chunk_size": 500,
        "chunk_overlap": 50,
        "embedding_dim": 4096
    }

    import json
    with open(config_path, 'w') as f:
        json.dump(default_config, f, indent=2)

    print("✅ Created config.json")
    print("📝 Edit config.json and add your Telegram bot token!")
    return True

def create_directories():
    """Create required directories"""
    print("\n=== Creating Directories ===")
    dirs = ["docs", "data/weaviate"]
    for d in dirs:
        Path(d).mkdir(parents=True, exist_ok=True)
        print(f"✅ Created {d}/")
    return True

def get_telegram_token():
    """Guide user to get Telegram token"""
    print("\n=== Telegram Bot Token ===")
    print("You need a Telegram bot token from @BotFather")
    print("1. Open Telegram and search for @BotFather")
    print("2. Send /newbot command")
    print("3. Follow instructions to create a bot")
    print("4. Copy the token (format: 123456:ABC-DEF...)")
    print("5. Add it to config.json under 'telegram_token'")
    return True

def main():
    print("=" * 60)
    print("  Document Chat System - Setup Wizard")
    print("=" * 60)

    results = []

    # Checks
    results.append(("Python", check_python()))
    results.append(("Ollama", check_ollama()))

    # Setup
    results.append(("Directories", create_directories()))
    results.append(("Config", create_config()))
    results.append(("Dependencies", install_dependencies()))
    results.append(("Telegram Token", get_telegram_token()))

    # Summary
    print("\n" + "=" * 60)
    print("  SETUP SUMMARY")
    print("=" * 60)
    for name, status in results:
        status_icon = "✅" if status else "❌"
        print(f"{status_icon} {name}")

    print("\n📌 NEXT STEPS:")
    print("1. Add your Telegram token to config.json")
    print("2. Place documents in the docs/ folder")
    print("3. Run ingestion: python main.py --mode ingest")
    print("4. Start bot: python main.py --mode bot")

    all_ok = all(status for _, status in results)
    if all_ok:
        print("\n✅ Setup complete! Ready to configure Telegram token.")

    return 0 if all_ok else 1

if __name__ == "__main__":
    sys.exit(main())
