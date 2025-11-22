<?php
// collect.php - Visitor Data Collector

// 1. Prevent Caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// 2. CORS (Allow all origins for the tracker to work on any domain)
// Use dynamic origin instead of wildcard to avoid credentials flag error
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '*';
header("Access-Control-Allow-Origin: $origin");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Credentials: true");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// 3. Database Setup
require_once 'config.php';

// Create visits table if not exists
$query = "CREATE TABLE IF NOT EXISTS visits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    visitor_id VARCHAR(50),
    page_view_id VARCHAR(50),
    ip_address VARCHAR(45),
    page_url TEXT,
    referrer TEXT,
    user_agent TEXT,
    screen_res VARCHAR(20),
    viewport VARCHAR(20),
    language VARCHAR(10),
    platform VARCHAR(50),
    timezone VARCHAR(50),
    country VARCHAR(50),
    city VARCHAR(50),
    region VARCHAR(50),
    isp VARCHAR(100),
    os VARCHAR(50),
    browser VARCHAR(50),
    device_model VARCHAR(50),
    device_memory VARCHAR(10),
    cpu_cores VARCHAR(10),
    connection_type VARCHAR(20),
    duration INT DEFAULT 0,
    visit_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
$pdo->exec($query);

// 4. Collect Data
$data = json_decode(file_get_contents('php://input'), true);

// If GET request (fallback for simple image pixel if needed, but we use JS beacon),
// or just empty POST, try to get from $_POST or query params if JSON failed.
if (!$data) {
    $data = $_POST;
}

$type = isset($data['type']) ? $data['type'] : 'pageview';
$pageViewId = isset($data['page_view_id']) ? $data['page_view_id'] : '';
$visitorId = isset($data['visitor_id']) ? $data['visitor_id'] : '';
$duration = isset($data['duration']) ? (int) $data['duration'] : 0;

// If it's a heartbeat, we just update the duration for the existing page view
if ($type === 'heartbeat' && !empty($pageViewId)) {
    try {
        $stmt = $pdo->prepare("UPDATE visits SET duration = :duration WHERE page_view_id = :pvid");
        $stmt->execute([':duration' => $duration, ':pvid' => $pageViewId]);
        echo "updated";
        exit;
    } catch (PDOException $e) {
        // If update fails (maybe column missing?), we might need to fall through or just log it.
        // For now, let's just exit, as we don't want to insert a duplicate partial record.
        exit("error updating");
    }
}

$ip = $_SERVER['REMOTE_ADDR'];
// Handle proxy headers if behind a CDN/Proxy (like Cloudflare)
if (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
    $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
} elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
}

$pageUrl = isset($data['url']) ? $data['url'] : '';
$referrer = isset($data['referrer']) ? $data['referrer'] : '';
$screenRes = isset($data['screen']) ? $data['screen'] : '';
$viewport = isset($data['viewport']) ? $data['viewport'] : '';
$language = isset($data['language']) ? $data['language'] : '';
$platform = isset($data['platform']) ? $data['platform'] : '';
$timezone = isset($data['timezone']) ? $data['timezone'] : '';
$deviceMemory = isset($data['device_memory']) ? $data['device_memory'] : '';
$cpuCores = isset($data['cpu_cores']) ? $data['cpu_cores'] : '';
$connectionType = isset($data['connection_type']) ? $data['connection_type'] : '';
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

// Simple validation
if (empty($pageUrl)) {
    // Maybe it's a direct access or bot
    exit('No URL provided');
}

// --- Geolocation (ipinfo.io) ---
$country = '';
$city = '';
$region = '';
$isp = '';

if ($ipinfo_token && $ip !== '127.0.0.1' && $ip !== '::1') {
    $geoUrl = "https://ipinfo.io/{$ip}?token={$ipinfo_token}";
    $geoData = @file_get_contents($geoUrl);
    if ($geoData) {
        $geo = json_decode($geoData, true);
        $country = $geo['country'] ?? '';
        $city = $geo['city'] ?? '';
        $region = $geo['region'] ?? '';
        $isp = $geo['org'] ?? '';
    }
}

// --- Simple UA Parsing ---
$os = 'Unknown';
$browser = 'Unknown';
$deviceModel = 'Desktop';

// OS Detection
if (preg_match('/windows/i', $userAgent))
    $os = 'Windows';
elseif (preg_match('/macintosh|mac os x/i', $userAgent))
    $os = 'macOS';
elseif (preg_match('/linux/i', $userAgent))
    $os = 'Linux';
elseif (preg_match('/android/i', $userAgent))
    $os = 'Android';
elseif (preg_match('/iphone|ipad|ipod/i', $userAgent))
    $os = 'iOS';

// Browser Detection
if (preg_match('/msie|trident/i', $userAgent))
    $browser = 'IE';
elseif (preg_match('/firefox/i', $userAgent))
    $browser = 'Firefox';
elseif (preg_match('/chrome/i', $userAgent))
    $browser = 'Chrome';
elseif (preg_match('/safari/i', $userAgent))
    $browser = 'Safari';
elseif (preg_match('/opera|opr/i', $userAgent))
    $browser = 'Opera';
elseif (preg_match('/edge/i', $userAgent))
    $browser = 'Edge';

// Device Model (Simple)
if (preg_match('/mobile/i', $userAgent)) {
    $deviceModel = 'Mobile';
    if (preg_match('/iphone/i', $userAgent))
        $deviceModel = 'iPhone';
    elseif (preg_match('/ipad/i', $userAgent))
        $deviceModel = 'iPad';
    elseif (preg_match('/android/i', $userAgent))
        $deviceModel = 'Android Device';
}

// 5. Insert into Database
// Note: If table exists but lacks new columns, this might fail. 
// For simplicity in this "drop-in" script, we'll try to alter table if insert fails, or just assume fresh install/manual update.
// To be robust, let's try to add columns if they don't exist (quick hack for dev).
try {
    $stmt = $pdo->prepare("INSERT INTO visits (visitor_id, page_view_id, ip_address, page_url, referrer, user_agent, screen_res, viewport, language, platform, timezone, country, city, region, isp, os, browser, device_model, device_memory, cpu_cores, connection_type, duration) VALUES (:vid, :pvid, :ip, :url, :ref, :ua, :screen, :vp, :lang, :plat, :tz, :country, :city, :region, :isp, :os, :browser, :model, :mem, :cpu, :conn, :dur)");
    $stmt->execute([
        ':vid' => $visitorId,
        ':pvid' => $pageViewId,
        ':ip' => $ip,
        ':url' => $pageUrl,
        ':ref' => $referrer,
        ':ua' => $userAgent,
        ':screen' => $screenRes,
        ':vp' => $viewport,
        ':lang' => $language,
        ':plat' => $platform,
        ':tz' => $timezone,
        ':country' => $country,
        ':city' => $city,
        ':region' => $region,
        ':isp' => $isp,
        ':os' => $os,
        ':browser' => $browser,
        ':model' => $deviceModel,
        ':mem' => $deviceMemory,
        ':cpu' => $cpuCores,
        ':conn' => $connectionType,
        ':dur' => $duration
    ]);
} catch (PDOException $e) {
    // If error is about missing column, try to add them
    if (strpos($e->getMessage(), 'Unknown column') !== false) {
        $columns = [
            'visitor_id' => 'VARCHAR(50)',
            'page_view_id' => 'VARCHAR(50)',
            'viewport' => 'VARCHAR(20)',
            'duration' => 'INT DEFAULT 0',
            'language' => 'VARCHAR(10)',
            'platform' => 'VARCHAR(50)',
            'timezone' => 'VARCHAR(50)',
            'country' => 'VARCHAR(50)',
            'city' => 'VARCHAR(50)',
            'region' => 'VARCHAR(50)',
            'isp' => 'VARCHAR(100)',
            'os' => 'VARCHAR(50)',
            'browser' => 'VARCHAR(50)',
            'device_model' => 'VARCHAR(50)',
            'device_memory' => 'VARCHAR(10)',
            'cpu_cores' => 'VARCHAR(10)',
            'connection_type' => 'VARCHAR(20)'
        ];
        foreach ($columns as $col => $type) {
            try {
                $pdo->exec("ALTER TABLE visits ADD COLUMN $col $type");
            } catch (Exception $ex) {
            }
        }
        // Retry insert
        $stmt = $pdo->prepare("INSERT INTO visits (visitor_id, page_view_id, ip_address, page_url, referrer, user_agent, screen_res, viewport, language, platform, timezone, country, city, region, isp, os, browser, device_model, device_memory, cpu_cores, connection_type, duration) VALUES (:vid, :pvid, :ip, :url, :ref, :ua, :screen, :vp, :lang, :plat, :tz, :country, :city, :region, :isp, :os, :browser, :model, :mem, :cpu, :conn, :dur)");
        $stmt->execute([
            ':vid' => $visitorId,
            ':pvid' => $pageViewId,
            ':ip' => $ip,
            ':url' => $pageUrl,
            ':ref' => $referrer,
            ':ua' => $userAgent,
            ':screen' => $screenRes,
            ':vp' => $viewport,
            ':lang' => $language,
            ':plat' => $platform,
            ':tz' => $timezone,
            ':country' => $country,
            ':city' => $city,
            ':region' => $region,
            ':isp' => $isp,
            ':os' => $os,
            ':browser' => $browser,
            ':model' => $deviceModel,
            ':mem' => $deviceMemory,
            ':cpu' => $cpuCores,
            ':conn' => $connectionType,
            ':dur' => $duration
        ]);
    } else {
        throw $e;
    }
}

echo "ok";
