# M-Pesa Integration — Poultry Manager Cloud

## Goal
Enable subscription payments via M-Pesa STK Push for Kenyan and East African farmers.

## Why M-Pesa
- 80%+ mobile money market penetration in Kenya
- Farmers already use M-Pesa for daily transactions
- Lower friction than cards or bank transfers
- STK Push is familiar and trusted

## Integration Flow

### Subscription Signup
1. Farmer selects plan on website
2. Enters phone number
3. System triggers STK Push to phone
4. Farmer enters PIN
5. Payment confirmed via callback URL
6. Account activated automatically

### Recurring Payments
- Option A: Manual renewal reminders via SMS/WhatsApp every 30 days
- Option B: Automatic STK Push on renewal date
- Option C: Pay-as-you-go with token-based access

## Technical Requirements
- Safaricom Daraja API account (Business to Consumer)
- Public key and passkey for authentication
- Callback URL for payment confirmation
- Database table: `subscriptions` with `status`, `next_payment_date`, `phone_number`
- Idempotency keys to prevent duplicate charges

## Security
- Never log full phone numbers or PINs
- Verify callback authenticity with Safaricom signature
- Rate limit STK Push requests per farm
- Handle timeouts and failures gracefully

## Pricing Mapping
- Starter: KES 750/month (~$5)
- Professional: KES 1,500/month (~$10)
- Enterprise: Custom quote

## Fallback
If M-Pesa API is unavailable, offer:
- Bank transfer instructions
- Manual payment confirmation via WhatsApp
- Paystack/Card option for diaspora users
