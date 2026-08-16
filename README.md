# Poultry Farm Management System

A complete web-based system for managing poultry farms — from flock health and production to sales, finance, and client orders.

## What this is

If you run a poultry farm, this replaces scattered spreadsheets and manual logs with one system. It covers:

- **Flock management** — batches, mortality, growth tracking
- **Production** — egg production, feed conversion, daily records
- **Health** — vaccinations, medications, vet visits
- **Sales** — orders, invoices, customers, delivery tracking
- **Finance** — expenses, income, profitability
- **Client portal** — order placement, tracking, history
- **Users & roles** — admin, farm manager, staff
- **Support** — in-app support and subscription requests

## Tech stack

- **Backend:** PHP 8+
- **Database:** MySQL / MariaDB
- **Frontend:** HTML, CSS, JavaScript
- **Server:** Apache with `.htaccess`

## Quick start

```bash
git clone https://github.com/dicksonmaina/poultry-farm-system.git
cd poultry-farm-system
cp .env.example .env
# Edit .env with your DB credentials
# Import database schema from database/support_requests.sql and seed.php/setup.php
php -S localhost:8000
```

## Setup

1. Clone the repo
2. Copy `.env.example` to `.env` and update credentials
3. Create a MySQL database
4. Import schema and seed data
5. Serve with Apache or PHP built-in server
6. Access via browser and create admin user via `setup.php`

## Support

Use the in-app **Support** page to request setup help, custom modules, or an ongoing support subscription.

Typical engagements:
- Installation and setup: from $50
- Custom module development: from $100
- Ongoing support: from $20/month

Contact: Richard Dickson Maina

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md)

## Changelog

See [CHANGELOG.md](CHANGELOG.md)

## License

MIT — see [LICENSE](LICENSE)
