@echo off
wsl -d Ubuntu -e bash -c "
cd /mnt/c/Users/user/richiedickson
source .env
python connector.py &
python whatsapp_handler.py &
python kilo_jarvis.py &
start python C:\Users\user\richiedickson\tools\phone_bridge.py
" &
exit
