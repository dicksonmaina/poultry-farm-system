## Session: April 21 2026 — POULTRY FARM COMPLETE

- connector.py running at localhost:5050
- System live at localhost:8080/poultry-farm/
- Login: admin / admin123
- Database: poultry_farm (14 tables)
- 12 modules complete
- Diploma project Word doc generated
- XAMPP: Apache=8080, MySQL=3306
- Next: RAG update, GitHub push, second laptop for OpenClaw
- Add this entry to C:\Users\user\richiedickson\documents_registry.json:
{
  "id": "poultry-farm-2026",
  "title": "Poultry Farm Management System",
  "type": "diploma_project",
  "date": "2026-04-21",
  "status": "complete",
  "url": "localhost:8080/poultry-farm/",
  "login": "admin / admin123",
  "database": "poultry_farm",
  "tables": 14,
  "modules": 12,
  "connector": "localhost:5050",
  "github": "dicksonmaina/poultry-farm-system",
  "tech": ["PHP","MySQL","Tailwind CSS","XAMPP","JavaScript"],
  "documentation": "Poultry_Farm_Management_System_Richard_Murimi_Maina.docx"
}

## ::NIM_PROVIDER
ACCOUNT    = "richiedickson5@gmail.com"
BASE_URL   = "https://integrate.api.nvidia.com/v1"
FREE_TIER  = "40 req/min — NO COST"
STATUS     = "CONNECTED"

PROVIDER_ORDER:
  1. nvidia_nim     FREE  — primary
  2. groq           FREE  — fallback
  3. ollama         LOCAL — offline
  4. openrouter     FREE models available
  5. github_copilot CONNECTED via Kilo Code

MODELS:
  FAST    = "moonshotai/kimi-k2-thinking"
  SMART   = "nvidia_nim/z-ai/glm4.7"
  CHEAP   = "nvidia_nim/stepfun-ai/step-3.5-flash"

VSCODE_REMOTE = "ssh kali → 100.97.115.38 | alias: kali"

::SYSTEM_SERVICES
PHONE_BRIDGE = {
  "tool": "ADB",
  "status": "CONNECTED",
  "script": "/mnt/c/Users/user/richiedickson/tools/phone_bridge.py",
  "capabilities": [
    "pull screenshots from phone",
    "sync WhatsApp media",
    "push files to phone",
    "monitor battery and storage",
    "keep screen on for automation"
  ]
}