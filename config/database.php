<?php
/**
 * BayanAlert PH - Database & App Configuration
 *
 * Works in two modes, auto-detected via environment variables:
 *  - LOCAL / XAMPP: no env vars set -> uses the hardcoded defaults below (localhost MySQL, local file uploads)
 *  - CLOUD RUN + CLOUD SQL: env vars set by the deploy step -> connects via Cloud SQL Unix socket
 *    and stores uploaded photos in Google Cloud Storage instead of local (ephemeral) disk.
 *
 * See DEPLOY.md for how these environment variables are set during deployment.
 */

// ---- Database connection ----
// DB_SOCKET is set in Cloud Run to something like: /cloudsql/PROJECT:REGION:INSTANCE
// When present, we connect via Unix socket (required for Cloud SQL from Cloud Run).
// When absent, we fall back to host/port (used for local XAMPP MySQL).
define('DB_SOCKET', getenv('DB_SOCKET') ?: '');
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'bayanalert_ph');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: ''); // Default XAMPP MySQL root password is empty
define('DB_CHARSET', 'utf8mb4');

// ---- App-wide settings ----
define('APP_NAME', 'BayanAlert PH');
define('APP_URL', getenv('APP_URL') ?: 'http://localhost/bayanalert-ph');

// ---- File upload storage ----
// STORAGE_MODE = 'local' (default, XAMPP-friendly) or 'gcs' (Google Cloud Storage, for Cloud Run)
// Cloud Run's local filesystem is EPHEMERAL - files written to disk disappear when a new
// container instance starts. STORAGE_MODE=gcs must be used in that environment or uploaded
// incident photos will silently be lost. See DEPLOY.md.
define('STORAGE_MODE', getenv('STORAGE_MODE') ?: 'local');
define('GCS_BUCKET', getenv('GCS_BUCKET') ?: '');           // e.g. "bayanalert-ph-uploads"
define('GCS_PROJECT_ID', getenv('GCS_PROJECT_ID') ?: '');   // e.g. "my-gcp-project-id"

define('UPLOAD_DIR', __DIR__ . '/../uploads/reports/');     // used only when STORAGE_MODE=local
define('UPLOAD_URL', 'uploads/reports/');                   // used only when STORAGE_MODE=local
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_UPLOAD_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('SESSION_TIMEOUT_SECONDS', 30 * 60); // 30 minutes idle timeout
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_MINUTES', 15);

function getDB(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        if (DB_SOCKET !== '') {
            // Cloud SQL via Unix socket (Cloud Run deployment)
            $dsn = 'mysql:unix_socket=' . DB_SOCKET . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        } else {
            // Local / standard TCP connection (XAMPP or any normal MySQL host)
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        }
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
                        <li>MySQL is running (XAMPP) or Cloud SQL instance is reachable (Cloud Run)</li>
                        <li>The database <code>bayanalert_ph</code> has been created and imported</li>
                        <li>Credentials/environment variables in <code>config/database.php</code> are correct</li>
                    </ul>
                  </div>');
        }
    }
    return $pdo;
}
