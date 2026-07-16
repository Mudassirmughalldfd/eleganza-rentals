ELEGANZA RENTALS — MYSQL / HOSTINGER READY
==========================================

This is the complete website, not an update-only patch.
The live website now uses MySQL/MariaDB. Cars, exact prices, availability,
media records, contact messages, booking requests, settings and admin users
will appear as real tables inside phpMyAdmin.

LOCAL XAMPP INSTALLATION
------------------------
1. Start Apache and MySQL in XAMPP Control Panel.
2. Open http://localhost/phpmyadmin
3. Click New and create an empty database named: eleganza_rentals
   Use utf8mb4_unicode_ci if phpMyAdmin asks for a collation.
4. Open PowerShell inside this project folder and run:

   & "C:\xampp\php\php.exe" -S 127.0.0.1:8000 router.php

5. Open:
   http://127.0.0.1:8000/install.php

6. Local XAMPP values are normally:
   Database Host: 127.0.0.1
   Database Port: 3306
   Database Name: eleganza_rentals
   Database Username: root
   Database Password: leave empty unless you changed it

7. Choose a new admin password and click Install Database.
8. Open phpMyAdmin and refresh the eleganza_rentals database. You will see:
   admins, site_settings, cars, car_prices, vehicle_media,
   availability_blocks, enquiries, activity_logs and app_meta.

ADMIN
-----
URL: /admin/
Use the admin email and password you choose during install.

DATABASE ADMIN PAGE
-------------------
Admin > Database shows connection status, table counts and an SQL backup button.

HOSTINGER / GITHUB
------------------
Read GITHUB-HOSTINGER-SETUP.md for the full deployment process.

MANUAL SQL OPTION
-----------------
Instead of install.php, you may import:
   database/eleganza_rentals_full.sql

Then copy config.local.example.php to config.local.php and enter your real
Hostinger or local database credentials.

SECURITY
--------
- config.local.php contains secrets and is excluded by .gitignore.
- Do not commit config.local.php to GitHub.
- The database and includes folders are blocked from public web access.
- Change any default/manual SQL admin password immediately.

EMAIL
-----
After installation open:
Admin > Website & Email
Enter your Hostinger mailbox SMTP password and send a test email.
