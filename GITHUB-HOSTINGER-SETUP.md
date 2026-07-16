# Eleganza Rentals: GitHub → Hostinger deployment

## 1. Upload to GitHub

Create a new private GitHub repository and upload the contents of this folder. Do **not** upload `config.local.php`; it is already excluded by `.gitignore`.

Your repository should contain `index.php`, `install.php`, `database/`, `admin/`, `assets/`, `includes/` and `uploads/` at its project root.

## 2. Deploy the repository to Hostinger

Use Hostinger's Git deployment feature or upload the files to `public_html`. The website files must be directly inside the domain's document root unless you intentionally use a subfolder.

Use PHP 8.1 or newer. Ensure the PDO MySQL extension is enabled.

## 3. Create the MySQL database in hPanel

Open **Websites → Manage → Databases → MySQL Databases** and create:

- A database
- A database user
- A strong database password

Keep the exact database name, username, password and database host shown by hPanel. Hostinger often prefixes the database and username with your account ID.

## 4. Run the guided installer

Open:

`https://your-domain.co.uk/install.php`

Enter the database credentials from hPanel and choose a new admin email/password. The installer will create and populate all tables.

After installation, open:

- Website: `https://your-domain.co.uk/`
- Admin: `https://your-domain.co.uk/admin/`

## 5. Confirm the database in phpMyAdmin

Open Hostinger's phpMyAdmin and select the new database. These tables should be visible:

- `admins`
- `site_settings`
- `cars`
- `car_prices`
- `vehicle_media`
- `availability_blocks`
- `enquiries`
- `activity_logs`
- `app_meta`

## 6. Configure Hostinger email

Open **Admin → Website & Email** and enter your mailbox settings. The project is prefilled for:

- SMTP host: `smtp.hostinger.com`
- Port: `587`
- Encryption: `TLS`
- Username: `hello@eleganzarentals.co.uk`

Enter the real mailbox password, enable SMTP, save, and send a test email.

## 7. Permissions

The following folders must be writable by PHP:

- `uploads/`
- `storage/`

Typical Hostinger permissions are `755` for folders and `644` for files. Do not use `777` unless Hostinger support specifically instructs you to.

## 8. Backups

Use **Admin → Database → Download SQL Backup** before major changes. Back up `uploads/` separately because SQL backups contain media records but not the physical image/video files.

## Manual database method

You can import `database/eleganza_rentals_full.sql` through phpMyAdmin, then create `config.local.php` from `config.local.example.php`. The guided installer is easier and lets you choose a new admin password during setup.
