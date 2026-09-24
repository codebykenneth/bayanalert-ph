<?php
/**
 * BayanAlert PH - Database Configuration
 * Update these values to match your XAMPP / MySQL setup.
 */

define('DB_HOST', 'sql104.infinityfree.com');
define('DB_NAME', 'if0_43001609_bayanalert');
define('DB_USER', 'if0_43001609');
define('DB_PASS', 'hgWM7x94VFcLF');
define('DB_CHARSET', 'utf8mb4');


// App-wide settings
define('APP_NAME', 'BayanAlert PH');
define('APP_URL', 'http://bayanalertph.page.gd');
define('UPLOAD_DIR', __DIR__ . '/../uploads/reports/');
define('UPLOAD_URL', 'uploads/reports/');
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024);
define('ALLOWED_UPLOAD_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('SESSION_TIMEOUT_SECONDS', 30 * 60);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_MINUTES', 15);

function getDB(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log('Database connection failed: ' . $e->getMessage());
            die('<div style="font-family:sans-serif;max-width:600px;margin:60px auto;padding:24px;border:1px solid #f3c1c1;background:#fff5f5;border-radius:8px;">
                    <h2 style="color:#b91c1c;">Database Connection Error</h2>
                    <p>BayanAlert PH could not connect to the database. Please check:</p>
                    <ul>
                        <li>MySQL is running in XAMPP Control Panel</li>
                        <li>The database <code>bayanalert_ph</code> has been created and imported</li>
                        <li>Credentials in <code>config/database.php</code> are correct</li>
                    </ul>
                  </div>');
        }
    }
    return $pdo;
}
