# PipraPay V4 — Bangla QR Plugin

This repository contains the PipraPay application files and the Bangla QR payment plugin customization included in the supplied project archive.

> **Important:** This repository is source code, not a ready-to-run hosted service. Do not upload production database backups, customer uploads, logs, or server credentials to GitHub.

## Requirements

- A PHP-enabled web server (use the PHP version supported by your PipraPay V4 release; PHP 7.4+ is stated in the supplied project documentation).
- MySQL or MariaDB.
- Required PHP extensions enabled by your hosting provider (typically `mysqli`/PDO MySQL, cURL, OpenSSL, JSON, mbstring, fileinfo, and GD where required).
- A PipraPay V2/V4 installation compatible with this plugin. Confirm compatibility with your actual PipraPay release before deploying.

## Repository layout

- `index.php` — application front controller
- `pp-content/` — application code, admin interface, modules, gateways, and installer
- `assets/` — public application assets
- `views/` — Bangla QR plugin views
- `pp-media/sdk/` — bundled SDK/helper libraries
- `pp-media/storage/` — runtime uploads and generated files (intentionally not committed)
- `pp-config.php` — local database configuration (not committed; create on the server during installation)

## Setup

1. Create a MySQL/MariaDB database and a dedicated database user on your hosting account.
2. Upload the project files to your PHP hosting document root.
3. Make sure `pp-media/storage/` exists and is writable by PHP. The application may create it automatically; if not, create it manually and set permissions appropriate for your host (normally `755`, or the hosting provider's recommended writable permission).
4. Open your site in a browser and follow the application's installer.
5. Enter the database host, port, database name, username, and password in the installer. It should create the local `pp-config.php` file. If the installer does not do this, create `pp-config.php` from `pp-config.example.php` and fill in your own database details.
6. Complete the installation, create a strong administrator password, and enable two-factor authentication if available.
7. Install/enable the Bangla QR plugin from the PipraPay plugin management area, then configure its payment settings.
8. Test a small payment in the provider's test/sandbox mode before accepting live payments.

## Local database configuration

The real `pp-config.php` file is intentionally excluded from Git. Use `pp-config.example.php` only as a template. Never commit real database passwords, API keys, SMTP credentials, private keys, or payment-provider secrets.



## License

See [`LICENSE`](LICENSE). Check the license and any third-party notices before redistributing the bundled application or vendor libraries.
