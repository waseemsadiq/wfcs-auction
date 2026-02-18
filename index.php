<?php
declare(strict_types=1);
ob_start(); // Buffer all output so setcookie()/header() work after any output (Galvani keep-alive reuses thread header state)

/**
 * WFCS Auction — Single Entry Point
 *
 * Load order:
 *   .env → config → helpers → core classes → router dispatch
 *
 * basePath is computed dynamically so the app works at any subpath
 * (Galvani dev: /auction/ | production LAMP: configured vhost root).
 */

// ---------------------------------------------------------------------------
// 1. Base path — strip trailing slash, handle root case
// ---------------------------------------------------------------------------
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$basePath   = rtrim(dirname($scriptName), '/\\');
if ($basePath === '.') {
    $basePath = '';
}

// ---------------------------------------------------------------------------
// 2. Load environment — config/env.php (shared hosting) OR .env (Galvani dev)
// ---------------------------------------------------------------------------
$envPhp = __DIR__ . '/config/env.php';
if (file_exists($envPhp)) {
    require_once $envPhp;
}

$dotenv = __DIR__ . '/.env';
if (file_exists($dotenv)) {
    $lines = file($dotenv, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') {
            continue;
        }
        if (strpos($line, '=') !== false) {
            [$key, $value] = explode('=', $line, 2);
            $key   = trim($key);
            $value = trim(trim($value), '"\'');
            if ($key !== '') {
                $_ENV[$key] = $value;
                if (getenv($key) === false) {
                    putenv("$key=$value");
                }
            }
        }
    }
}

// ---------------------------------------------------------------------------
// 3. Timezone
// ---------------------------------------------------------------------------
$appConfig = require __DIR__ . '/config/app.php';
date_default_timezone_set($appConfig['timezone'] ?? 'Europe/London');

// ---------------------------------------------------------------------------
// 4. Composer autoloader
// ---------------------------------------------------------------------------
require_once __DIR__ . '/vendor/autoload.php';

// ---------------------------------------------------------------------------
// 5. Helper functions (global, procedural)
// ---------------------------------------------------------------------------
require_once __DIR__ . '/app/Helpers/functions.php';
require_once __DIR__ . '/app/Helpers/validation.php';

// ---------------------------------------------------------------------------
// 6. Parse request path (strip basePath for internal routing)
// ---------------------------------------------------------------------------
// CORS headers for API routes (must be set before any output)
$rawUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$rawPath = $basePath !== ''
    ? preg_replace('#^' . preg_quote($basePath, '#') . '#', '', (string)$rawUri)
    : (string)$rawUri;
if (str_starts_with($rawPath, '/api/')) {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}
unset($rawUri, $rawPath);
// ---------------------------------------------------------------------------
$fullUri    = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$requestUri = $basePath !== ''
    ? preg_replace('#^' . preg_quote($basePath, '#') . '#', '', (string)$fullUri)
    : (string)$fullUri;
if ($requestUri === '') {
    $requestUri = '/';
}
$requestMethod = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

// ---------------------------------------------------------------------------
// 7. CSRF token — stored in a cookie (no sessions; JWT-based auth)
// ---------------------------------------------------------------------------
$csrfToken = getCsrfToken();

// ---------------------------------------------------------------------------
// 8. Validate CSRF on HTML form POSTs (not API routes)
// ---------------------------------------------------------------------------
if ($requestMethod === 'POST' && strpos($requestUri, '/api/') !== 0 && $requestUri !== '/webhook/stripe') {
    $submittedToken = $_POST['_csrf_token'] ?? $_GET['_csrf_token'] ?? '';
    if (!hash_equals($csrfToken, $submittedToken)) {
        // Redirect back to the same page so the user gets a fresh token
        header('Location: ' . $basePath . $requestUri . '?csrf_retry=1');
        exit;
    }
}

// ---------------------------------------------------------------------------
// 9. Instantiate controllers (once, here — NOT in constructor bodies)
//    Per Galvani rule 9: no auth checks inside constructors.
// ---------------------------------------------------------------------------
$homeController    = new \App\Controllers\HomeController();
$authController    = new \App\Controllers\AuthController();
$accountController = new \App\Controllers\AccountController();
$eventController   = new \App\Controllers\EventController();
$itemController    = new \App\Controllers\ItemController();
$bidController     = new \App\Controllers\BidController();
$paymentController = new \App\Controllers\PaymentController();
$adminController      = new \App\Controllers\AdminController();
$apiController        = new \App\Controllers\ApiController();
$auctioneerController = new \App\Controllers\AuctioneerController();
$legalController      = new \App\Controllers\LegalController();
$donorController      = new \App\Controllers\DonorController();

// ---------------------------------------------------------------------------
// 10. Router
// ---------------------------------------------------------------------------
$router = new \Core\Router();

// ---- Public: home ----------------------------------------------------------
$router->get('/',            [$homeController, 'index']);

// ---- Legal -----------------------------------------------------------------
$router->get('/terms',   [$legalController, 'terms']);
$router->get('/privacy', [$legalController, 'privacy']);

// ---- Auth ------------------------------------------------------------------
$router->get('/login',               [$authController, 'showLogin']);
$router->post('/login',              [$authController, 'login']);
$router->get('/logout',              [$authController, 'logout']);
$router->get('/register',            [$authController, 'showRegister']);
$router->post('/register',           [$authController, 'register']);
$router->get('/verify-email',        [$authController, 'verifyEmail']);
$router->get('/forgot-password',     [$authController, 'showForgot']);
$router->post('/forgot-password',    [$authController, 'forgot']);
$router->get('/reset-password',      [$authController, 'showReset']);
$router->post('/reset-password',     [$authController, 'reset']);
$router->get('/resend-verification', [$authController, 'showResend']);
$router->post('/resend-verification',[$authController, 'resend']);

// ---- Account ---------------------------------------------------------------
$router->get('/account',            [$accountController, 'index']);
$router->get('/account/profile',    [$accountController, 'showProfile']);
$router->post('/account/profile',        [$accountController, 'updateProfile']);
$router->post('/account/notifications',  [$accountController, 'updateNotifications']);
$router->get('/account/password',        [$accountController, 'showPassword']);
$router->post('/account/password',  [$accountController, 'updatePassword']);

// ---- Auctions (public browse) ----------------------------------------------
$router->get('/auctions',           [$eventController, 'index']);
$router->get('/auctions/:slug',     [$eventController, 'show']);

// ---- Items -----------------------------------------------------------------
$router->get('/items/:slug',        [$itemController, 'show']);
$router->get('/donate',             [$itemController, 'showSubmit']);
$router->post('/donate',            [$itemController, 'submit']);

// ---- Bids ------------------------------------------------------------------
$router->get('/my-bids',            [$bidController, 'myBids']);
$router->post('/bids',              [$bidController, 'place']);

// ---- Donor -----------------------------------------------------------------
$router->get('/my-donations',       [$donorController, 'myDonations']);

// ---- Payments --------------------------------------------------------------
$router->get('/payment/:slug',      [$paymentController, 'show']);
$router->post('/payment/:slug',     [$paymentController, 'process']);
$router->post('/webhook/stripe',    [$paymentController, 'webhook']);

// ---- Admin -----------------------------------------------------------------
$router->get('/admin',              [$adminController, 'index']);
$router->get('/admin/dashboard',    [$adminController, 'dashboard']);

// Admin — Auctions
$router->get('/admin/auctions',                    [$adminController, 'auctions']);
$router->get('/admin/auctions/create',             [$adminController, 'createAuction']);
$router->post('/admin/auctions',                   [$adminController, 'createAuction']);
$router->get('/admin/auctions/:slug/edit',         [$adminController, 'editAuction']);
$router->post('/admin/auctions/:slug/edit',        [$adminController, 'updateAuction']);
$router->post('/admin/auctions/:slug/publish',     [$adminController, 'publishAuction']);
$router->post('/admin/auctions/:slug/open',        [$adminController, 'openAuction']);
$router->post('/admin/auctions/:slug/end',         [$adminController, 'endAuction']);

// Admin — Items
$router->get('/admin/items',                       [$adminController, 'items']);
$router->get('/admin/items/:slug/edit',            [$adminController, 'editItem']);
$router->post('/admin/items/:slug/edit',           [$adminController, 'updateItem']);

// Admin — Users
$router->get('/admin/users',                       [$adminController, 'users']);
$router->get('/admin/users/:slug',                 [$adminController, 'showUser']);
$router->post('/admin/users/:slug',                [$adminController, 'updateUser']);

// Admin — Payments
$router->get('/admin/payments',                    [$adminController, 'payments']);

// Admin — Gift Aid
$router->get('/admin/gift-aid',                    [$adminController, 'giftAid']);

// Admin — Live Events
$router->get('/admin/live-events',                 [$adminController, 'liveEvents']);
$router->post('/admin/live-events/start',          [$adminController, 'startLiveEvent']);
$router->post('/admin/live-events/stop',           [$adminController, 'stopLiveEvent']);

// Admin — Settings
$router->get('/admin/settings',                    [$adminController, 'settings']);
$router->post('/admin/settings',                   [$adminController, 'saveSettings']);

// ---- REST API: Auth --------------------------------------------------------
$router->post('/api/auth/login',                   [$apiController, 'apiLogin']);

// ---- REST API v1 -----------------------------------------------------------
$router->get('/api/v1/items',                      [$apiController, 'listItems']);
$router->get('/api/v1/items/:slug',                [$apiController, 'showItem']);
$router->get('/api/v1/events',                     [$apiController, 'listEvents']);
$router->get('/api/v1/events/:slug',               [$apiController, 'showEvent']);
$router->get('/api/v1/events/:slug/items',         [$apiController, 'eventItems']);
$router->post('/api/v1/bids',                      [$apiController, 'placeBid']);
$router->get('/api/v1/users/me',                   [$apiController, 'me']);
$router->get('/api/v1/users/me/bids',              [$apiController, 'myBids']);
$router->get('/api/v1/token',                      [$apiController, 'generateToken']);

// ---- Auctioneer panel (admin) + Projector (public) -------------------------
$router->get('/auctioneer',              [$auctioneerController, 'panel']);
$router->post('/auctioneer/set-item',    [$auctioneerController, 'setItem']);
$router->post('/auctioneer/open',        [$auctioneerController, 'openBidding']);
$router->post('/auctioneer/close',       [$auctioneerController, 'closeBidding']);
$router->post('/auctioneer/pause',       [$auctioneerController, 'pauseBidding']);
$router->post('/auctioneer/resume',      [$auctioneerController, 'resumeBidding']);
$router->get('/projector',               [$auctioneerController, 'projector']);
$router->get('/api/live-status',         [$auctioneerController, 'liveStatus']);
$router->get('/api/event-bids',          [$auctioneerController, 'eventBids']);

// ---- Internal AJAX polling -------------------------------------------------
$router->get('/api/current-bid/:slug',             [$apiController, 'currentBid']);

// ---- 404 fallback ----------------------------------------------------------
$router->notFound(function () use ($basePath): void {
    http_response_code(404);
    $errorView = __DIR__ . '/app/Views/errors/404.php';
    if (file_exists($errorView)) {
        require $errorView;
    } else {
        echo 'Not Found';
    }
});

// ---------------------------------------------------------------------------
// 11. Check for expired auctions (lightweight — runs on every non-API request)
//     This replaces the need for a cron job in the Galvani single-process dev env.
// ---------------------------------------------------------------------------
if (!str_starts_with($requestUri, '/api/') && $requestUri !== '/webhook/stripe') {
    try {
        (new \App\Services\AuctionService())->processExpired();
    } catch (\Throwable $e) {
        error_log('AuctionService::processExpired failed: ' . $e->getMessage());
    }
}

// ---------------------------------------------------------------------------
// 12. Dispatch
// ---------------------------------------------------------------------------
$router->dispatch($requestMethod, $requestUri);
