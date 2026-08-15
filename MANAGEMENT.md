# MANAGEMENT.md - Poultry Farm Management System

## Purpose
This document defines how this project is maintained, upgraded, and secured.

## Stack
- PHP 8.2+ (backend)
- MySQL/MariaDB (database)
- Tailwind CSS + Alpine.js (frontend)
- No external API dependencies

## Security Rules
- Never commit `.env`, `config.php` with real credentials, or database dumps
- All user input must be sanitized (see `security.php`)
- CSRF tokens required on all POST forms
- Session-based auth, passwords hashed with `password_hash()`

## Adding Features
1. Create new page in `pages/`
2. Add route to `$allowedPages` in `index.php`
3. Add menu item in sidebar nav
4. Update database schema in `setup.php` if needed
5. Run `php -l` on all modified files before committing

## CI/CD
- GitHub Actions runs PHP lint on every push
- Secrets scanning on every push
- No automated deployment (manual FTP/cPanel deploy)

## Support
- Issues: https://github.com/dicksonmaina/poultry-farm-system/issues
- Docs: See `README.md`
