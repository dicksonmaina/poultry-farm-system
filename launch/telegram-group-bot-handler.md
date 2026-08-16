# Telegram Group Bot Handler — Implementation Design

## Purpose
Define the bot behavior and message handling logic for the future JARVIS-managed Telegram group. This is the executable core of group automation.

## Event Handlers

### 1. New Chat Member
Trigger: `new_chat_members` update
Action:
- Wait `delay_seconds` from group config
- Send welcome message with inline keyboard
- Log member join to group state file
- Tag member as `new` for follow-up sequence

### 2. Message Received
Trigger: `message` update
Actions:
- Check for support keywords from `support_keywords` array
- If keyword matched:
  - Create support request via `pages/support.php` API
  - Send auto-reply with ticket number
  - Notify admin via Telegram
- Check for spam patterns:
  - Repeated messages
  - Links from untrusted members
  - Caps lock abuse
  - Flag for admin review

### 3. Inline Keyboard Click
Trigger: `callback_query` update
Actions:
- Parse `callback_data`
- Map to action from `lead_capture.actions`
- Execute action:
  - `start_trial` → create support request with type=trial
  - `book_demo` → create support request with type=demo
  - `join_whatsapp` → send WhatsApp invite link
  - `view_pricing` → send pricing message
- Answer callback query to remove loading state

### 4. Scheduled Digest
Trigger: cron job from `digest.schedule`
Actions:
- Read top AUTO/APPROVE items from Telegram intake pipeline
- Format using digest template
- Post to group
- Log run to group state file

### 5. Scheduled Poll
Trigger: cron job from `polls.schedule`
Actions:
- Create poll with predefined options
- Post to group
- Collect results after 24 hours
- Post results summary
- Log results to admin panel

### 6. Cleanup Job
Trigger: cron job weekly from `cleanup` config
Actions:
- Query member activity timestamps
- Identify members inactive > `inactive_days`
- If `dry_run`: log candidates only
- If live: remove inactive members
- Post cleanup summary to admin

## State Files
- `group_state.json` — member list, join dates, last activity
- `support_tickets.json` — tickets created from group
- `digest_log.jsonl` — digest post history
- `poll_log.jsonl` — poll creation and results
- `cleanup_log.jsonl` — cleanup actions

## Admin Commands
- `/status` — group stats
- `/digest now` — force digest post
- `/poll [question] [options]` — create ad-hoc poll
- `/cleanup dry-run` — preview cleanup
- `/cleanup run` — execute cleanup
