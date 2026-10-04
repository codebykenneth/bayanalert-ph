<?php
/**
 * BayanAlert PH - Shared helper functions
 */

/** Escape output to prevent XSS */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/** Generate and store a CSRF token for the current session */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Output a hidden CSRF input field */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/** Verify a submitted CSRF token, halting the request if invalid */
function csrf_verify(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        die('Invalid or expired form submission (CSRF check failed). Please go back and try again.');
    }
}

/** Set a one-time flash message */
function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

/** Retrieve and clear flash messages */
function get_flashes(): array
{
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flashes;
}

/** Redirect helper */
function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

/** Basic phone number validation (Philippine mobile format tolerant) */
function is_valid_phone(string $phone): bool
{
    return (bool) preg_match('/^[0-9+\-\s()]{7,20}$/', $phone);
}

/** Create a notification for a user */
function create_notification(PDO $db, int $userId, string $type, string $title, string $message, ?int $relatedId = null): void
{
    $stmt = $db->prepare(
        'INSERT INTO notifications (user_id, type, title, message, related_id) VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->execute([$userId, $type, $title, $message, $relatedId]);
}

/** Notify all users with a given role */
function notify_role(PDO $db, string $role, string $type, string $title, string $message, ?int $relatedId = null): void
{
    $stmt = $db->prepare('SELECT id FROM users WHERE role = ? AND status = "active"');
    $stmt->execute([$role]);
    foreach ($stmt->fetchAll() as $row) {
        create_notification($db, (int) $row['id'], $type, $title, $message, $relatedId);
    }
}

/** Count unread notifications for a user */
function unread_notification_count(PDO $db, int $userId): int
{
    $stmt = $db->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
    $stmt->execute([$userId]);
    return (int) $stmt->fetchColumn();
}

/** Log an activity for auditing */
function log_activity(PDO $db, ?int $userId, string $action, string $details = ''): void
{
    $stmt = $db->prepare('INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)');
    $stmt->execute([$userId, $action, $details, $_SERVER['REMOTE_ADDR'] ?? null]);
}

/** Handle a validated image upload, returns stored relative path or null */
function handle_image_upload(array $file): ?string
{
    if (empty($file['name']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload failed with error code ' . $file['error']);
    }
    if ($file['size'] > MAX_UPLOAD_SIZE) {
        throw new RuntimeException('File is too large. Maximum size is 5MB.');
    }
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if (!in_array($mime, ALLOWED_UPLOAD_TYPES, true)) {
        throw new RuntimeException('Invalid file type. Only JPG, PNG, GIF, and WEBP images are allowed.');
    }
    $ext = match ($mime) {
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
        default      => 'bin',
    };
    $safeName = bin2hex(random_bytes(16)) . '.' . $ext;

    if (STORAGE_MODE === 'gcs') {
        return handle_image_upload_gcs($file['tmp_name'], $safeName, $mime);
    }
    return handle_image_upload_local($file['tmp_name'], $safeName);
}

/** Local disk storage (default; XAMPP or any host with persistent disk). */
function handle_image_upload_local(string $tmpPath, string $safeName): string
{
    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }
    if (!move_uploaded_file($tmpPath, UPLOAD_DIR . $safeName)) {
        throw new RuntimeException('Failed to save uploaded file.');
    }
    return UPLOAD_URL . $safeName;
}

/**
 * Google Cloud Storage upload (Cloud Run's local disk is ephemeral).
 * Needs GCS_BUCKET set, the google/cloud-storage Composer package, and a service
 * account with Storage Object Admin on the bucket (see DEPLOY.md).
 */
function handle_image_upload_gcs(string $tmpPath, string $safeName, string $mime): string
{
    if (GCS_BUCKET === '') {
        throw new RuntimeException('Server storage is misconfigured (GCS_BUCKET not set).');
    }
    if (!class_exists('Google\Cloud\Storage\StorageClient')) {
        throw new RuntimeException('Storage library missing. Run "composer install" (see DEPLOY.md).');
    }
    $storage = new \Google\Cloud\Storage\StorageClient(
        GCS_PROJECT_ID !== '' ? ['projectId' => GCS_PROJECT_ID] : []
    );
    $objectName = 'reports/' . $safeName;
    $handle = fopen($tmpPath, 'r');
    if ($handle === false) {
        throw new RuntimeException('Could not read uploaded file for storage.');
    }
    try {
        $storage->bucket(GCS_BUCKET)->upload($handle, [
            'name' => $objectName,
            'metadata' => ['contentType' => $mime],
        ]);
    } finally {
        fclose($handle);
    }
    return 'https://storage.googleapis.com/' . GCS_BUCKET . '/' . $objectName;
}

/** Badge color helper for status pills */
function status_badge_class(string $status): string
{
    return match ($status) {
        'Pending'            => 'bg-secondary',
        'Under Verification' => 'bg-info text-dark',
        'Verified'           => 'bg-primary',
        'Responding'         => 'bg-warning text-dark',
        'Resolved'           => 'bg-success',
        'Rejected'           => 'bg-danger',
        'Open'               => 'bg-success',
        'Full'                => 'bg-warning text-dark',
        'Closed'             => 'bg-danger',
        'Active'             => 'bg-success',
        'Inactive'           => 'bg-secondary',
        'Expired'            => 'bg-dark',
        default              => 'bg-secondary',
    };
}

/** Alert level color helper */
function alert_level_class(string $level): string
{
    return match ($level) {
        'LOW'      => 'bg-success',
        'MODERATE' => 'bg-warning text-dark',
        'HIGH'     => 'bg-orange text-white',
        'CRITICAL' => 'bg-danger',
        default    => 'bg-secondary',
    };
}

/** Human-friendly relative time */
function time_ago(string $datetime): string
{
    $diff = time() - strtotime($datetime);
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    if ($diff < 604800) return floor($diff / 86400) . 'd ago';
    return date('M j, Y', strtotime($datetime));
}

/** List of PH provinces used in dropdowns (sample set covering demo cities) */
function ph_provinces(): array
{
    return ['Metro Manila', 'Cebu', 'Davao del Sur', 'Benguet', 'Leyte', 'Misamis Oriental'];
}
