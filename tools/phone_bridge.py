import subprocess, os, time

def adb(cmd):
    return subprocess.run(f"adb {cmd}", shell=True, capture_output=True, text=True).stdout.strip()

def phone_status():
    print(f"Device:   {adb('shell getprop ro.product.model')}")
    print(f"Battery:  {adb('shell dumpsys battery | grep level')}")
    print(f"IP:       {adb('shell ip addr show wlan0 | grep inet')}")
    print(f"Storage:  {adb('shell df /sdcard | tail -1')}")

def pull_whatsapp_media():
    os.makedirs("/mnt/c/Users/user/richiedickson/phone/whatsapp", exist_ok=True)
    adb("pull /sdcard/Android/media/com.whatsapp/WhatsApp/Media /mnt/c/Users/user/richiedickson/phone/whatsapp")
    print("WhatsApp media synced")

def push_file(local_path, remote_path="/sdcard/Download/"):
    adb(f"push {local_path} {remote_path}")
    print(f"Pushed {local_path} to phone")

def pull_screenshots():
    os.makedirs("/mnt/c/Users/user/richiedickson/phone/screenshots", exist_ok=True)
    adb("pull /sdcard/Pictures/Screenshots /mnt/c/Users/user/richiedickson/phone/screenshots")
    print("Screenshots synced")

def send_whatsapp_notification(message):
    adb(f'shell am broadcast -a android.intent.action.SEND --es android.intent.extra.TEXT "{message}"')

def keep_screen_on():
    adb("shell settings put system screen_off_timeout 2147483647")
    print("Screen stays on")

def start_auto_sync():
    print("Auto-syncing phone every 60 seconds...")
    while True:
        pull_screenshots()
        time.sleep(60)

if __name__ == "__main__":
    phone_status()
