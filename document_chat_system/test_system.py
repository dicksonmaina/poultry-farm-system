"""
System Health Check
Test all components before full setup
"""

import sys
import subprocess
import importlib.util
from pathlib import Path

def check_module(module_name: str, install_cmd: str = None) -> bool:
    """Check if a Python module is installed"""
    spec = importlib.util.find_spec(module_name)
    installed = spec is not None
    status = "[OK]" if installed else "[MISSING]"
    print(f"{status} {module_name}")
    if not installed and install_cmd:
        print(f"   Install with: {install_cmd}")
    return installed

def check_ollama() -> bool:
    """Check if Ollama is accessible"""
    print("\n=== Checking Ollama ===")
    try:
        import requests
        response = requests.get("http://localhost:11434/api/tags", timeout=3)
        if response.status_code == 200:
            data = response.json()
            models = [m["name"] for m in data.get("models", [])]
            print(f"[OK] Ollama connected - Models: {models}")

            if "llama3.1" not in [m.split(':')[0] for m in models]:
                print("   Warning: llama3.1 model not found!")
                print("   Run: ollama pull llama3.1")
                return False
            return True
    except Exception as e:
        print(f"[MISSING] Cannot connect to Ollama: {e}")
        print("   Make sure Ollama is running: ollama serve")
        return False

def check_telegram_token() -> bool:
    """Check if Telegram token is configured"""
    print("\n=== Checking Telegram Token ===")
    import json
    try:
        with open("config.json") as f:
            config = json.load(f)
        token = config.get("telegram_token", "").strip()
        if token and len(token) > 20:
            print("[OK] Telegram token configured")
            return True
        else:
            print("[MISSING] Telegram token not set or invalid")
            print("   Get a token from @BotFather and add to config.json")
            return False
    except Exception as e:
        print(f"[ERROR] Cannot read config.json: {e}")
        return False

def main():
    print("=" * 60)
    print("  System Health Check")
    print("=" * 60)

    all_ok = True

    # Python version
    print("\n=== Python ===")
    version = sys.version_info
    print(f"Python {version.major}.{version.minor}.{version.micro}")
    if version.major == 3 and version.minor >= 10:
        print("[OK] Python version OK")
    else:
        print("[MISSING] Python 3.10+ required")
        all_ok = False

    # Python packages
    print("\n=== Python Packages ===")
    modules = [
        ("weaviate", "pip install weaviate-client"),
        ("requests", "pip install requests"),
        ("telegram", "pip install python-telegram-bot"),
        ("fitz", "pip install pymupdf"),
    ]
    for module, cmd in modules:
        if not check_module(module, cmd):
            all_ok = False

    # Ollama
    if not check_ollama():
        all_ok = False

    # Config
    print("\n=== Configuration ===")
    import os
    if os.path.exists("config.json"):
        print("[OK] config.json exists")
        if not check_telegram_token():
            all_ok = False
    else:
        print("[MISSING] config.json not found")
        print("   Copy config.json.example or run setup.py")
        all_ok = False

    # Documents
    print("\n=== Documents ===")
    docs_path = "docs"
    if os.path.exists(docs_path):
        files = list(Path(docs_path).rglob("*"))
        docs = [f for f in files if f.suffix.lower() in ['.pdf', '.txt', '.md']]
        if docs:
            print(f"[OK] Found {len(docs)} document(s)")
            for d in docs[:5]:
                print(f"   - {d.name}")
            if len(docs) > 5:
                print(f"   ... and {len(docs)-5} more")
        else:
            print("   Warning: No documents found in docs/ folder")
            print("   Add PDF or text files to index")
    else:
        print("   Warning: docs/ folder doesn't exist")
        print("   Create it and add documents")

    # Summary
    print("\n" + "=" * 60)
    if all_ok:
        print("[SUCCESS] SYSTEM READY")
        print("\nNext steps:")
        print("1. Add documents to docs/ folder (optional but recommended)")
        print("2. Ingest: python main.py --mode ingest")
        print("3. Start bot: python main.py --mode bot")
    else:
        print("[WARNING] SETUP INCOMPLETE")
        print("\nFix the issues above, then run this check again.")
    print("=" * 60)

    return 0 if all_ok else 1

if __name__ == "__main__":
    sys.exit(main())
