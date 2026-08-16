# Poultry Manager Cloud — Hosted Architecture

## Goal
Build a hosted, multi-tenant SaaS version of the poultry farm system so farms don’t need to self-host. This is the primary revenue engine.

## Target Market
- Small to medium poultry farms in Kenya and East Africa
- 500–5,000 birds per farm
- Currently using spreadsheets, paper, or WhatsApp
- Willing to pay $5–10/farm/month for a managed system

## Pricing Tiers

### Free Tier
- 1 farm, 500 birds max
- Basic flock tracking
- Egg production logs
- Email support
- Purpose: lead generation and trial conversion

### Starter — $5/farm/month
- Up to 2,000 birds
- Full flock, production, health, feed modules
- Basic reports
- WhatsApp/email support
- 7-day data backup

### Professional — $10/farm/month
- Unlimited birds
- All modules including sales, finance, client portal
- Advanced reports and FCR tracking
- Priority support
- 30-day data backup
- API access

### Enterprise — Custom pricing
- Multi-farm management
- Custom integrations
- Dedicated support
- SLA guarantees
- On-premise deployment option

## Technical Architecture

### Frontend
- Hosted web app at `app.poultrymanager.cloud`
- Responsive design for desktop and mobile
- Progressive Web App for offline field use
- Client portal embedded as `/client` route

### Backend
- PHP 8+ API layer
- MySQL/MariaDB per tenant with shared schema + `farm_id` isolation
- JWT-based authentication
- Role-based access: admin, manager, staff, client

### Hosting
- Primary: Hetzner Cloud or DigitalOcean droplet in Frankfurt/Singapore
- CDN: Cloudflare for static assets and DDoS protection
- Database: Managed MariaDB or Percona
- Backups: Daily snapshots to S3-compatible storage
- Monitoring: UptimeRobot + custom health checks

### Multi-Tenancy Strategy
- Shared database with `farm_id` column on every table
- Row-level security enforced at the API layer
- Separate uploads directory per farm
- Tenant isolation tested in CI

### Security
- TLS 1.3 everywhere
- Rate limiting per farm
- CSRF protection on all forms
- Input sanitization and prepared statements
- Regular penetration testing
- GDPR-ready data export/deletion

### Integrations
- M-Pesa STK Push for subscription payments
- WhatsApp Business API for alerts and support
- SMS gateway for vaccination reminders
- Email via SendGrid or Mailgun
- Payment webhooks for subscription lifecycle

### Deployment
- Docker Compose for local/dev
- Ansible or GitHub Actions for production deploys
- Blue-green deployment via load balancer
- Database migrations managed in `database/migrations/`

### Migration Path
1. Launch free tier to build user base
2. Convert self-hosted users to paid tiers
3. Offer migration tool: `poultry-farm-system` → `Poultry Manager Cloud`
4. Maintain open-source core; cloud features in separate repo

## Revenue Model
- Subscription: $5–10/farm/month
- Setup/onboarding: $50–200 one-time
- Custom modules: $100–500
- Support subscriptions: $20–50/month
- Partnerships with feed suppliers and agritech platforms

## Launch Checklist
- [ ] Domain and hosting provisioned
- [ ] Multi-tenant schema designed
- [ ] Subscription and payment flow built
- [ ] Free tier launched with 10 pilot farms
- [ ] M-Pesa integration tested
- [ ] Marketing site at poultrymanager.cloud
- [ ] Support and onboarding workflow documented
