<?php
declare(strict_types=1);

define('ROOT_DIR', dirname(__DIR__));
require __DIR__ . '/config.php';

date_default_timezone_set(APP_TIMEZONE);
mb_internal_encoding('UTF-8');
error_reporting(E_ALL);
ini_set('display_errors', APP_DEBUG ? '1' : '0');
ini_set('log_errors', '1');

/* ------------------------------------------------------------------ */
/*  URL / เส้นทาง                                                      */
/* ------------------------------------------------------------------ */
function detect_base_url(): string
{
    if (BASE_URL_OVERRIDE !== null) {
        return rtrim((string)BASE_URL_OVERRIDE, '/');
    }
    $doc  = !empty($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : false;
    $root = realpath(ROOT_DIR);
    if ($doc && $root && stripos($root, $doc) === 0) {
        return rtrim(str_replace('\\', '/', substr($root, strlen($doc))), '/');
    }
    return '';
}
define('BASE_URL', detect_base_url());

function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

function redirect(string $path)
{
    $to = (preg_match('#^https?://#i', $path) || str_starts_with($path, '/')) ? $path : url($path);
    header('Location: ' . $to);
    exit;
}

/** อนุญาตเฉพาะ path ภายในเว็บ (กัน open redirect) */
function safe_path(?string $p, string $fallback): string
{
    $p = (string)$p;
    if ($p !== '' && $p[0] === '/' && !str_starts_with($p, '//') && !str_contains($p, "\\") && !preg_match('/[\r\n]/', $p)) {
        return $p;
    }
    return url($fallback);
}

function h(mixed $s): string
{
    return htmlspecialchars((string)($s ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/* ------------------------------------------------------------------ */
/*  ฐานข้อมูล                                                          */
/* ------------------------------------------------------------------ */
function fatal_page(string $title, string $body, int $code = 500)
{
    http_response_code($code);
    echo '<!doctype html><html lang="th"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
       . '<title>' . h($title) . '</title><style>body{font-family:Tahoma,sans-serif;background:#f2f7fd;color:#1b2f66;display:grid;place-items:center;min-height:100vh;margin:0;padding:16px}'
       . '.b{max-width:560px;background:#fff;border-radius:16px;padding:28px;box-shadow:0 8px 30px #2459c722}h1{margin-top:0;font-size:22px}code{background:#eef3fc;padding:2px 6px;border-radius:6px}</style></head><body><div class="b">'
       . '<h1>' . h($title) . '</h1>' . $body . '</div></body></html>';
    exit;
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);
    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4, time_zone = '+07:00'",
        ]);
    } catch (PDOException $e) {
        error_log('DB connect failed: ' . $e->getMessage());
        $unknownDb = (int)$e->getCode() === 1049;
        $msg  = '<p>เชื่อมต่อฐานข้อมูลไม่สำเร็จ</p><ol>';
        $msg .= '<li>เปิด <b>XAMPP Control Panel</b> แล้วกด <b>Start</b> ที่ <b>MySQL</b></li>';
        $msg .= $unknownDb
            ? '<li>ยังไม่มีฐานข้อมูล <code>' . h(DB_NAME) . '</code> — เปิด <a href="' . h(url('install.php')) . '">install.php</a> หรือ import ไฟล์ <code>database.sql</code> ใน phpMyAdmin</li>'
            : '<li>ตรวจค่า <code>DB_HOST / DB_PORT / DB_USER / DB_PASS</code> ในไฟล์ <code>includes/config.php</code></li>';
        $msg .= '</ol>' . (APP_DEBUG ? '<p><code>' . h($e->getMessage()) . '</code></p>' : '');
        fatal_page('เชื่อมต่อฐานข้อมูลไม่ได้', $msg);
    }
    return $pdo;
}

function q(string $sql, array $params = []): PDOStatement
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st;
}
function fetch_all(string $sql, array $params = []): array { return q($sql, $params)->fetchAll(); }
function fetch_one(string $sql, array $params = []): ?array { $r = q($sql, $params)->fetch(); return $r === false ? null : $r; }
function fetch_val(string $sql, array $params = []): mixed { $r = q($sql, $params)->fetchColumn(); return $r === false ? null : $r; }

/* ------------------------------------------------------------------ */
/*  Session / Flash / CSRF                                             */
/* ------------------------------------------------------------------ */
session_name('UNISPORTSESSID');
session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
session_start();

function flash(string $type, string $msg): void
{
    $_SESSION['flash'][] = ['type' => $type, 'msg' => $msg];
}
function take_flashes(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}
function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . h(csrf_token()) . '">';
}
/** เรียกต้นไฟล์ของหน้าที่รับ POST — ถ้า token ไม่ถูกต้องจะหยุดทันที */
function csrf_guard(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }
    $t = (string)($_POST['_csrf'] ?? '');
    if ($t === '' || !hash_equals(csrf_token(), $t)) {
        fatal_page('หมดเวลาการใช้งาน', '<p>แบบฟอร์มหมดอายุหรือไม่ถูกต้อง กรุณากลับไปโหลดหน้าใหม่แล้วลองอีกครั้ง</p><p><a href="javascript:history.back()">← กลับ</a></p>', 419);
    }
}
function is_post(): bool { return $_SERVER['REQUEST_METHOD'] === 'POST'; }
function post(string $k, string $default = ''): string { return trim((string)($_POST[$k] ?? $default)); }
function get(string $k, string $default = ''): string { return trim((string)($_GET[$k] ?? $default)); }

/* ------------------------------------------------------------------ */
/*  การยืนยันตัวตน                                                     */
/* ------------------------------------------------------------------ */
function current_user(bool $refresh = false): ?array
{
    static $cache = false;
    if ($refresh) {
        $cache = false;
    }
    if ($cache !== false) {
        return $cache;
    }
    $id = (int)($_SESSION['uid'] ?? 0);
    $cache = $id ? fetch_one('SELECT * FROM users WHERE id = ?', [$id]) : null;
    if ($id && !$cache) {
        unset($_SESSION['uid']);
    }
    return $cache;
}
function is_admin(): bool { $u = current_user(); return $u && $u['role'] === 'admin'; }
function user_name(?array $u): string
{
    return $u ? trim($u['first_name'] . ' ' . $u['last_name']) : '';
}

function login_user(int $id): void
{
    session_regenerate_id(true);
    $_SESSION['uid'] = $id;
    current_user(true);
}
function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'] ?? '', (bool)$p['secure'], (bool)$p['httponly']);
    }
    session_destroy();
}

function require_login(): array
{
    $u = current_user();
    if (!$u) {
        $next = $_SERVER['REQUEST_URI'] ?? '';
        redirect('login.php?next=' . rawurlencode($next));
    }
    return $u;
}
function require_admin(): array
{
    $u = current_user();
    if (!$u) {
        redirect('admin-login.php?next=' . rawurlencode($_SERVER['REQUEST_URI'] ?? ''));
    }
    if ($u['role'] !== 'admin') {
        flash('error', 'หน้านี้สำหรับผู้ดูแลระบบเท่านั้น');
        redirect('dashboard.php');
    }
    return $u;
}

/* กันเดารหัสผ่าน: ผิดเกิน 8 ครั้งใน 10 นาที (ต่อ IP) จะถูกพัก */
function client_ip(): string { return substr((string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'), 0, 45); }
function login_blocked(): bool
{
    return (int)fetch_val('SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND created_at > (NOW() - INTERVAL 10 MINUTE)', [client_ip()]) >= 8;
}
function login_failed(string $identity): void
{
    q('INSERT INTO login_attempts (ip, identity) VALUES (?, ?)', [client_ip(), mb_substr($identity, 0, 255)]);
    q('DELETE FROM login_attempts WHERE created_at < (NOW() - INTERVAL 1 DAY)');
}

/* ------------------------------------------------------------------ */
/*  ป้ายสถานะ / รูปแบบข้อมูล                                           */
/* ------------------------------------------------------------------ */
const FIELD_STATUS = [
    'available'   => ['ว่าง', 'ok'],
    'unavailable' => ['ไม่ว่าง', 'bad'],
    'maintenance' => ['ปิดปรับปรุง', 'bad'],
];
const BOOKING_STATUS = [
    'pending'   => ['รออนุมัติ', 'warn'],
    'approved'  => ['อนุมัติแล้ว', 'ok'],
    'completed' => ['สำเร็จ', 'ok'],
    'rejected'  => ['ไม่อนุมัติ', 'bad'],
    'cancelled' => ['ยกเลิก', 'bad'],
];
const REPORT_STATUS = [
    'open'        => ['รอดำเนินการ', 'bad'],
    'in_progress' => ['กำลังดำเนินการ', 'warn'],
    'resolved'    => ['แก้ไขแล้ว', 'ok'],
];
const SPORT_TYPES = ['ฟุตบอล', 'บาสเกตบอล', 'วอลเลย์บอล', 'เทนนิส', 'แบดมินตัน', 'อื่นๆ'];
const FACILITIES  = ['ห้องน้ำ', 'ที่จอดรถ', 'ไฟส่องสว่าง', 'อุปกรณ์กีฬา'];
const PURPOSES    = ['ฝึกซ้อม', 'แข่งขัน', 'จัดกิจกรรม', 'ออกกำลังกาย', 'การเรียนการสอน'];
const PROBLEM_TYPES = ['อุปกรณ์ชำรุด', 'สนามเสียหาย', 'ไฟฟ้า/แสงสว่าง', 'ห้องน้ำ', 'ความสะอาด', 'อื่นๆ'];
const FACILITY_ICONS = ['ห้องน้ำ' => 'restroom', 'ที่จอดรถ' => 'parking', 'ไฟส่องสว่าง' => 'light', 'อุปกรณ์กีฬา' => 'football'];

function pill(array $map, string $key): string
{
    [$label, $tone] = $map[$key] ?? [$key, 'warn'];
    return '<span class="pill pill-' . h($tone) . '">' . h($label) . '</span>';
}

function today(): string { return date('Y-m-d'); }

/** วันที่แบบไทย dd/mm/พ.ศ. */
function thai_date(?string $d): string
{
    if (!$d || str_starts_with($d, '0000')) {
        return '-';
    }
    $ts = strtotime(substr($d, 0, 10));
    return $ts ? date('d/m/', $ts) . ((int)date('Y', $ts) + 543) : '-';
}
function hm(?string $t): string { return $t ? substr($t, 0, 5) : '-'; }

function facilities_list(?string $csv): array
{
    return array_values(array_filter(array_map('trim', explode(',', (string)$csv)), fn($x) => $x !== ''));
}

/* ------------------------------------------------------------------ */
/*  อัปโหลดรูป (ตรวจชนิดไฟล์จากเนื้อไฟล์จริง ไม่เชื่อนามสกุล)                */
/* ------------------------------------------------------------------ */
/** @return array{0:?string,1:?string} [path, error] — path เป็น path สัมพัทธ์ เช่น uploads/fields/xxx.jpg */
function save_upload(string $inputName, string $folder): array
{
    if (empty($_FILES[$inputName]) || ($_FILES[$inputName]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return [null, null];
    }
    $f = $_FILES[$inputName];
    if ($f['error'] !== UPLOAD_ERR_OK) {
        return [null, $f['error'] === UPLOAD_ERR_INI_SIZE || $f['error'] === UPLOAD_ERR_FORM_SIZE
            ? 'ไฟล์ใหญ่เกินที่เซิร์ฟเวอร์อนุญาต' : 'อัปโหลดไฟล์ไม่สำเร็จ'];
    }
    if ($f['size'] > MAX_UPLOAD_MB * 1024 * 1024) {
        return [null, 'ไฟล์รูปต้องไม่เกิน ' . MAX_UPLOAD_MB . ' MB'];
    }
    $info = @getimagesize($f['tmp_name']);
    $ext = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_GIF => 'gif', IMAGETYPE_WEBP => 'webp'][$info[2] ?? 0] ?? null;
    if (!$ext) {
        return [null, 'รองรับเฉพาะไฟล์รูป JPG, PNG, GIF, WEBP'];
    }
    if (!in_array($folder, ['fields', 'avatars', 'reports'], true)) {
        return [null, 'โฟลเดอร์ปลายทางไม่ถูกต้อง'];
    }
    $dir = ROOT_DIR . '/uploads/' . $folder;
    if (!is_dir($dir) && !@mkdir($dir, 0775, true)) {
        return [null, 'สร้างโฟลเดอร์อัปโหลดไม่ได้'];
    }
    $name = bin2hex(random_bytes(12)) . '.' . $ext;
    if (!move_uploaded_file($f['tmp_name'], $dir . '/' . $name)) {
        return [null, 'บันทึกไฟล์ไม่สำเร็จ (ตรวจสิทธิ์โฟลเดอร์ uploads)'];
    }
    return ['uploads/' . $folder . '/' . $name, null];
}

/** ลบไฟล์ที่เราอัปโหลดเอง (ไม่แตะลิงก์ภายนอก) */
function delete_upload(?string $path): void
{
    if ($path && preg_match('#^uploads/(fields|avatars|reports)/[a-f0-9]{24}\.(jpg|png|gif|webp)$#', $path)) {
        @unlink(ROOT_DIR . '/' . $path);
    }
}

/** แปลงค่า image_url ในฐานข้อมูลเป็น URL ที่ใช้แสดงได้ */
function img_url(?string $v): string
{
    $v = (string)$v;
    if ($v === '') {
        return '';
    }
    return preg_match('#^https?://#i', $v) ? $v : url($v);
}

/** ตรวจ URL รูปที่ผู้ดูแลกรอกเอง ต้องเป็น http(s) เท่านั้น */
function valid_image_link(string $v): bool
{
    return (bool)preg_match('#^https?://[^\s<>"\']+$#i', $v) && mb_strlen($v) <= 500;
}
