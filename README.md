# Nauti-Connect
NauticalNet - The ultimate boating community platform. Connect with fellow boaters, buy/sell vessels and gear, share voyages, and access expert navigation tips, marina reviews, and maintenance guides. Your all-in-one harbor for everything on the water.

## Requirements

- **PHP 8.0 or higher** (uses PHP 8.0+ features: `match` expressions, union types)
- **MySQL 5.7.22 or higher** (uses JSON data type and `JSON_ARRAY()`)
- **Apache** with `mod_rewrite` enabled and `AllowOverride All`

## Local Setup with MAMP

1. **Install MAMP** from [https://www.mamp.info/](https://www.mamp.info/)

2. **Select PHP 8.0+**  
   In MAMP: go to **MAMP → Preferences → PHP** and choose PHP 8.0 or higher.

3. **Place the project** in your MAMP document root (e.g. `/Applications/MAMP/htdocs/Nauti-Connect`).

4. **Create the database**  
   Open **phpMyAdmin** (via MAMP start page → phpMyAdmin), create a new database named `maritime_db`, then import `install.sql`.

5. **Edit `config.php`** and set your values:
   ```php
   // Use 127.0.0.1 (not localhost) to avoid MAMP MySQL socket issues
   define('DB_HOST', '127.0.0.1');
   define('DB_USER', 'root');        // MAMP default
   define('DB_PASS', 'root');        // MAMP default
   define('DB_NAME', 'maritime_db');

   // Set to your local URL (no trailing slash)
   define('SITE_URL', 'http://localhost:8888/Nauti-Connect');
   ```
   > **Why `127.0.0.1`?** MAMP's MySQL listens on a custom Unix socket (`/Applications/MAMP/tmp/mysql/mysql.sock`), which PHP won't find when you specify `localhost`. Using `127.0.0.1` forces a TCP connection that works reliably.

6. **Start MAMP** and visit `http://localhost:8888/Nauti-Connect/` in your browser.

## File Permissions

The `uploads/` directory must be writable by the web server:
```bash
chmod -R 755 uploads/
```
