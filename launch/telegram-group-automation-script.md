# Telegram Group Automation — Implementation Script

## Bot Setup Checklist
1. Add `@Rixikibot` to the target group
2. Promote bot to admin with these permissions:
   - Send messages
   - Send media
   - Pin messages
   - Delete messages
   - Add users
3. Note the group ID for webhook/polling setup

## Message Templates

### Welcome Message
```
Welcome to Poultry Manager Community! 🐔

You're now connected with poultry farmers across Kenya and East Africa.

Quick links:
• Start free trial → [Start Free Trial]
• Book a demo → [Book Demo]
• Join WhatsApp support → [WhatsApp]
• View pricing → [Pricing]

Need help? Type "support" and we'll create a ticket for you.

Community rules:
1. Be respectful to all members
2. No spam or self-promotion without permission
3. Share only poultry-related content
4. Support fellow farmers

Let's build something great together!
```

### Lead Capture Keyboard
```
[Start Free Trial] [Book Demo]
[Join WhatsApp]    [View Pricing]
```

### Support Auto-Reply
```
✅ Support ticket created!

Ticket #: {ticket_id}
Status: Open

Our team will respond within 24 hours.
You can also reach us on WhatsApp: {whatsapp_number}

Thank you for using Poultry Manager Cloud!
```

### Daily Digest Template
```
📊 Daily Intel Digest — {date}

🤖 Top Stories:
1. {channel} — {score}/100
{message_preview}

💡 Poultry Tip of the Day:
{tip}

📈 Weekly Check-in Reminder:
How's your flock performing? Log your data in Poultry Manager Cloud.

Need help? Type "support"
```

## Automation Rules
1. **Welcome**: Trigger on `new_chat_members` event
2. **Lead Capture**: Inline keyboard clicks create support requests via API
3. **Support**: Keyword "support" or "help" creates ticket and notifies admin
4. **Digest**: Scheduled daily at 8 AM EAT
5. **Poll**: Weekly Monday poll for feature voting
6. **Cleanup**: Weekly Sunday cleanup of inactive members

## Admin Notification Format
```
🔔 New Lead from Telegram Group

Name: {user_name}
Action: {action_type}
Ticket: #{ticket_id}
Time: {timestamp}

View in admin panel: {admin_url}
```
