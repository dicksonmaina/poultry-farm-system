# Setup Guide

## Requirements

- PHP 8.0 or higher
- MySQL 5.7+ or MariaDB 10.2+
- Apache with mod_rewrite enabled
- Composer (optional, for dependencies)

## Installation

1. Clone the repository:
```bash
git clone https://github.com/dicksonmaina/poultry-farm-system.git
cd poultry-farm-system
```

2. Create a MySQL database:
```sql
CREATE DATABASE poultry_farm;
```

3. Import the database schema:
```bash
# Via PHP
php seed.php
# Or via MySQL
mysql -u root -p poultry_farm < seed.sql
```

4. Configure database credentials in `config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'poultry_farm');
```

5. Set up the admin user:
```
Visit /setup.php in your browser
```

6. Start the server:
```bash
php -S localhost:8000
# Or use Apache with the existing .htaccess
```

## Troubleshooting

- **404 errors:** Ensure `mod_rewrite` is enabled and `.htaccess` is being read
- **Database connection errors:** Verify credentials in `config.php`
- **Permission errors:** Ensure `write` permissions on `logs/` and `uploads/` directories
