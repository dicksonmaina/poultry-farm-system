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

## Tech stack

- **Backend:** PHP 8+
- **Database:** MySQL / MariaDB
- **Frontend:** HTML, CSS, JavaScript
- **Server:** Apache with `.htaccess`

## Quick start

```bash
git clone https://github.com/dicksonmaina/poultry-farm-system.git
cd poultry-farm-system
# Import database schema from seed.php / db_update.php
# Configure database credentials in config.php
# Serve with Apache or PHP built-in server
php -S localhost:8000
```

## Setup

1. Clone the repo
2. Create a MySQL database
3. Run `seed.php` or `db_update.php` to initialize schema
4. Edit `config.php` with your DB credentials
5. Access via browser and create admin user via `setup.php`

## Roadmap

- Local installation and self-hosting (current)
- Cloud-hosted version with managed updates
- Mobile app for field staff
- Integration with feed suppliers and payment gateways

## Paid support & customization

This project is open source. If you need help deploying it, customizing modules, or training staff, reach out. Typical engagements:

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
