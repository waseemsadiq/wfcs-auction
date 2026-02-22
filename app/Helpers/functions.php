<?php
declare(strict_types=1);

/**
 * Escape a value for safe HTML output.
 */
function e(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Read a dot-notation config value, e.g. config('app.jwt_secret').
 * The file portion maps to config/<file>.php.
 */
function config(string $key, mixed $default = null): mixed
{
    static $cache = [];

    [$file, $subkey] = array_pad(explode('.', $key, 2), 2, null);

    if (!isset($cache[$file])) {
        $path = dirname(__DIR__, 2) . '/config/' . $file . '.php';
        $cache[$file] = file_exists($path) ? require $path : [];
    }

    if ($subkey === null) {
        return $cache[$file] ?? $default;
    }

    return $cache[$file][$subkey] ?? $default;
}

/**
 * Retrieve the authenticated user from a JWT.
 *
 * Token lookup order:
 *   1. auth_token cookie (web sessions)
 *   2. token POST field  (API with form-body token)
 *   3. token query param (API GET requests)
 */
function getAuthUser(): ?array
{
    $token = null;

    if (!empty($_COOKIE['auth_token'])) {
        $token = $_COOKIE['auth_token'];
    } elseif (!empty($_POST['token'])) {
        $token = $_POST['token'];
    } elseif (!empty($_GET['token'])) {
        $token = $_GET['token'];
    }

    if (!$token) {
        return null;
    }

    $secret = config('app.jwt_secret');
    if (!$secret) {
        return null;
    }

    return \Core\JWT::decode($token, $secret);
}

/**
 * Require an authenticated user. Redirects to /login on failure.
 *
 * Rolling refresh: if the token was issued more than 60 minutes ago, a fresh
 * 2-hour JWT is issued and the auth cookie is overwritten. This creates a
 * sliding 120-minute inactivity window for all HTML page routes.
 * API routes use their own auth checks and are not affected.
 */
function requireAuth(): array
{
    global $basePath;
    $user = getAuthUser();
    if (!$user) {
        header('Location: ' . $basePath . '/login');
        exit;
    }

    if (!empty($user['iat']) && (time() - (int)$user['iat']) > 3600) {
        $secret  = config('app.jwt_secret');
        $payload = $user;
        $payload['exp'] = time() + 7200;
        $token = \Core\JWT::encode($payload, $secret);
        setcookie('auth_token', $token, [
            'expires'  => time() + 7200,
            'path'     => '/',
            'httponly' => true,
            'secure'   => isset($_SERVER['HTTPS']),
            'samesite' => 'Strict',
        ]);
    }

    return $user;
}

/**
 * Map a role string to its numeric hierarchy level.
 * Use >= comparisons: >= 2 = admin or above, >= 3 = super_admin only.
 */
function roleLevel(string $role): int
{
    return match($role) {
        'super_admin' => 3,
        'admin'       => 2,
        'donor'       => 1,
        'bidder'      => 0,
        default       => 0,
    };
}

/**
 * Require an admin user. Renders 403 on failure.
 */
function requireAdmin(): array
{
    global $basePath;
    $user = requireAuth();
    if (roleLevel($user['role'] ?? '') < 2) {
        http_response_code(403);
        $errorView = dirname(__DIR__, 2) . '/app/Views/errors/403.php';
        if (file_exists($errorView)) {
            require $errorView;
        } else {
            echo 'Forbidden';
        }
        exit;
    }
    return $user;
}

/**
 * Require a super_admin user. Renders 403 on failure.
 */
function requireSuperAdmin(): array
{
    global $basePath;
    $user = requireAuth();
    if (roleLevel($user['role'] ?? '') < 3) {
        http_response_code(403);
        $errorView = dirname(__DIR__, 2) . '/app/Views/errors/403.php';
        if (file_exists($errorView)) {
            require $errorView;
        } else {
            echo 'Forbidden';
        }
        exit;
    }
    return $user;
}

/**
 * Validate the CSRF token from POST body or query string.
 * Exits with 403 on mismatch.
 */
function validateCsrf(): void
{
    $token = $_POST['_csrf_token'] ?? $_GET['_csrf_token'] ?? null;
    global $csrfToken;
    if (!$token || !hash_equals($csrfToken, $token)) {
        http_response_code(403);
        echo 'Invalid CSRF token';
        exit;
    }
}

/**
 * Generate a URL-friendly slug from a string.
 */
function slugify(string $text): string
{
    $text = mb_strtolower($text, 'UTF-8');
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', trim($text));
    return $text;
}

/**
 * Format an amount in GBP (£).
 */
function formatCurrency(float $amount): string
{
    return '£' . number_format($amount, 2);
}

/**
 * Render a view atom (small reusable snippet) and return its HTML.
 * Usage: atom('badge', ['label' => 'Active'])
 */
function atom(string $name, array $props = []): string
{
    ob_start();
    extract($props);
    $path = dirname(__DIR__, 2) . '/app/Views/atoms/' . $name . '.php';
    if (file_exists($path)) {
        require $path;
    }
    return (string)ob_get_clean();
}

/**
 * Render a view partial in-place.
 * Usage: partial('nav/header', ['user' => $user])
 */
function partial(string $name, array $data = []): void
{
    extract($data);
    global $basePath, $csrfToken;
    require dirname(__DIR__, 2) . '/app/Views/partials/' . $name . '.php';
}

/**
 * Generate a unique slug for a given DB table column.
 * Appends -2, -3, … until the slug is free.
 */
function uniqueSlug(string $table, string $text, \Core\Database $db): string
{
    $base = slugify($text);
    $slug = $base;
    $i    = 2;
    while ($db->queryOne("SELECT id FROM `{$table}` WHERE slug = ?", [$slug])) {
        $slug = $base . '-' . $i++;
    }
    return $slug;
}

/**
 * Validate an email address.
 */
function isValidEmail(string $email): bool
{
    return (bool)filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Validate password strength: min 8 chars, at least 1 uppercase, at least 1 digit.
 */
function isValidPassword(string $password): bool
{
    return strlen($password) >= 8
        && (bool)preg_match('/[A-Z]/', $password)
        && (bool)preg_match('/[0-9]/', $password);
}

/**
 * Store a one-shot flash message in a short-lived cookie.
 */
function flash(string $message, string $type = 'success'): void
{
    setcookie('flash_message', (string)json_encode(['msg' => $message, 'type' => $type]), [
        'expires'  => time() + 10,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
}

/**
 * Retrieve and clear the stored flash message.
 */
function getFlash(): ?array
{
    if (empty($_COOKIE['flash_message'])) {
        return null;
    }
    $data = json_decode($_COOKIE['flash_message'], true);
    // Clear the cookie immediately
    setcookie('flash_message', '', ['expires' => time() - 3600, 'path' => '/']);
    return is_array($data) ? $data : null;
}

/**
 * Generate or retrieve the CSRF token stored in a same-site cookie.
 * No PHP sessions — the token lives in a cookie (httponly, SameSite=Strict).
 */
function getCsrfToken(): string
{
    if (!empty($_COOKIE['csrf_token'])) {
        return $_COOKIE['csrf_token'];
    }

    $token = bin2hex(random_bytes(32));
    setcookie('csrf_token', $token, [
        'expires'  => 0,          // session cookie
        'path'     => '/',
        'httponly' => false,      // JS must NOT read it (form only)
        'samesite' => 'Strict',
    ]);
    return $token;
}

/**
 * Return true when running inside Galvani (checks for Galvani-specific env).
 */
function isGalvani(): bool
{
    return !empty($_SERVER['GALVANI']) || !empty($_ENV['GALVANI']);
}

/**
 * Encrypt a sensitive setting value for DB storage.
 * Uses AES-256-GCM with a random IV. Stored as "enc:<base64(iv|tag|ciphertext)>".
 * Key comes from config('app.key') — set APP_KEY in .env.
 */
function encryptSetting(string $plaintext): string
{
    $key = config('app.key');
    if (empty($key)) {
        throw new \RuntimeException('APP_KEY is not set. Cannot encrypt sensitive settings.');
    }
    $keyBytes = hex2bin($key);
    $iv       = random_bytes(12); // 96-bit IV for GCM
    $tag      = '';
    $cipher   = openssl_encrypt($plaintext, 'aes-256-gcm', $keyBytes, OPENSSL_RAW_DATA, $iv, $tag, '', 16);
    if ($cipher === false) {
        throw new \RuntimeException('Encryption failed.');
    }
    return 'enc:' . base64_encode($iv . $tag . $cipher);
}

/**
 * Decrypt a setting value from the DB.
 * Values not starting with "enc:" are treated as legacy plaintext and returned as-is.
 */
function decryptSetting(string $value): string
{
    if (strncmp($value, 'enc:', 4) !== 0) {
        return $value; // legacy plaintext — backward compatible
    }
    $key = config('app.key');
    if (empty($key)) {
        error_log('decryptSetting: APP_KEY is not set — returning empty string');
        return '';
    }
    $keyBytes = @hex2bin($key);
    if ($keyBytes === false) {
        error_log('decryptSetting: APP_KEY is not valid hex — returning empty string');
        return '';
    }
    $decoded = base64_decode(substr($value, 4), true);
    if ($decoded === false || strlen($decoded) < 28) { // 12 IV + 16 tag minimum
        error_log('decryptSetting: invalid encrypted value — returning empty string');
        return '';
    }
    $iv         = substr($decoded, 0, 12);
    $tag        = substr($decoded, 12, 16);
    $ciphertext = substr($decoded, 28);
    $plaintext  = openssl_decrypt($ciphertext, 'aes-256-gcm', $keyBytes, OPENSSL_RAW_DATA, $iv, $tag);
    if ($plaintext === false) {
        error_log('decryptSetting: decryption failed — wrong APP_KEY or corrupted data');
        return '';
    }
    return $plaintext;
}

/**
 * Mask a full name to "First L." for privacy (e.g. "Jane Doe" → "Jane D.").
 * Single-word names are returned unchanged.
 */
function maskName(string $name): string
{
    $name  = trim($name);
    $parts = preg_split('/\s+/', $name);

    if ($name === '') {
        return '';
    }

    if (count($parts) === 1) {
        return $parts[0];
    }

    $first = $parts[0];
    $last  = $parts[count($parts) - 1];

    return $first . ' ' . mb_strtoupper(mb_substr($last, 0, 1)) . '.';
}
