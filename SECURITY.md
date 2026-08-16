# Security Policy

## Supported Versions

| Version | Supported          |
| ------- | ------------------ |
| 0.1.x   | :white_check_mark: |

## Reporting a Vulnerability

If you discover a security vulnerability, please email Richard Dickson Maina directly or open a confidential issue. Do not open a public issue for security vulnerabilities.

We will acknowledge receipt within 48 hours and provide a detailed response within 7 days.

## Security Practices

- All database queries use prepared statements where possible
- Authentication is handled via PHP sessions with secure cookie flags
- Sensitive configuration is kept outside the web root where possible
- `.htaccess` includes basic security headers
