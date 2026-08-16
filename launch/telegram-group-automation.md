# Telegram Group Automation — Poultry Manager Cloud

## Goal
Automate Telegram group management for pilot farm recruitment, support, and community building.

## Bot Capabilities Required
The bot must be an admin with these permissions:
- Send messages
- Send media
- Add users
- Pin messages
- Delete messages
- Manage topics

## Automation Flows

### Welcome Flow
- New member joins → bot sends welcome message with:
  - Farm name and location
  - Quick start guide link
  - Support contact
  - Community rules

### Lead Capture Flow
- New member joins → bot sends inline keyboard:
  - "Start Free Trial"
  - "Book Demo"
  - "Join WhatsApp"
  - "View Pricing"
- Click → auto-create support request in admin panel

### Support Flow
- Member sends message with "support" keyword → bot:
  - Creates support request in database
  - Notifies admin via Telegram
  - Sends auto-reply with ticket number

### Digest Flow
- Daily at 8 AM → bot posts:
  - Top 3 AUTO/APPROVE intel items
  - Daily poultry tip
  - Reminder for weekly check-in

### Poll Flow
- Weekly poll: "What feature should we build next?"
- Options: Flock tracking, Feed management, Sales, Reports, Other
- Results posted to group and admin panel

### Cleanup Flow
- Bot removes members inactive for 30 days
- Bot flags spam/off-topic messages for admin review
- Bot archives old pinned messages

## Integration Points
- `pages/support.php` → support request creation from Telegram
- `admin/support_requests.php` → admin notifications via Telegram
- `telegram_scorer.py` → intel scoring for digest posts
- OpenClaw Telegram plugin → message routing

## Implementation
- Telegram Bot API via OpenClaw plugin
- Inline keyboards for interactive flows
- Webhook or polling for message handling
- Database sync for lead and support tracking
