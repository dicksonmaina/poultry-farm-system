#!/usr/bin/env python3
"""
Quick launcher for Windows
Double-click this file to start the bot
"""

import subprocess
import sys
import os
from pathlib import Path

def main():
    # Get directory of this script
    base_dir = Path(__file__).parent

    # Check if we should ingest first
    config_file = base_dir / "config.json"
    docs_dir = base_dir / "docs"

    print("Document Chat System Launcher")
    print("=" * 40)

    # Quick checks
    if not config_file.exists():
        print("⚠️  config.json not found. Run setup.py first!")
        input("Press Enter to exit...")
        return

    # Check for documents
    if not any(docs_dir.iterdir()):
        print("⚠️  docs/ folder is empty!")
        print("   Add PDF or text files before starting.")
        print("\nStart anyway? (y/n): ", end="")
        if input().lower() != 'y':
            return

    # Ask what to do
    print("\nSelect mode:")
    print("1. Ingest documents (index files)")
    print("2. Start Telegram bot")
    print("3. Both (ingest then bot)")
    print("4. Exit")
    choice = input("Enter 1-4: ").strip()

    if choice == "1":
        cmd = [sys.executable, "main.py", "--mode", "ingest"]
    elif choice == "2":
        cmd = [sys.executable, "main.py", "--mode", "bot"]
    elif choice == "3":
        cmd = [sys.executable, "main.py", "--mode", "combined"]
    else:
        return

    # Run command
    print(f"\nRunning: {' '.join(cmd)}")
    print("-" * 40)
    try:
        subprocess.run(cmd, cwd=base_dir, check=True)
    except KeyboardInterrupt:
        print("\n\nShutting down...")
    except Exception as e:
        print(f"\n❌ Error: {e}")
        input("\nPress Enter to exit...")

if __name__ == "__main__":
    main()
