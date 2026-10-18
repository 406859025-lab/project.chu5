<?php
declare(strict_types=1);

function asset(string $p): string
{
    $file = ROOT_DIR . '/assets/' . ltrim($p, '/');
    return url('assets/' . ltrim($p, '/')) . (is_file($file) ? '?v=' . filemtime($file) : '');
}

function page_head(string $title, string $bodyClass = ''): void
{
    ?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($title) ?> — <?= h(APP_NAME) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap">
<link rel="stylesheet" href="<?= h(asset('css/style.css')) ?>">
<link rel="icon" type="image/png" href="<?= h(asset('logo.png')) ?>">
</head>
<body class="<?= h($bodyClass) ?>">
<?php
}

function render_flashes(): void
{
    $fl = take_flashes();
    if (!$fl) {
        return;
    }
    echo '<div class="toasts" role="status" aria-live="polite">';
    foreach ($fl as $f) {
        echo '<div class="toast toast-' . h($f['type']) . '">' . h($f['msg']) . '</div>';
    }
    echo '</div>';
}

function page_foot(): void
{
    render_flashes();
    ?>
<script src="<?= h(asset('js/app.js')) ?>"></script>
</body>
</html>
<?php
}

function brand(string $title = APP_NAME, string $sub = 'มหาวิทยาลัย'): string
{
  return '<div class="brand"><img class="brand-logo" src="' . h(asset('logo.png')) . '" alt="โลโก้"><div class="brand-text"><div class="brand-title">'
        . h($title) . '</div><div class="brand-sub">' . h($sub) . '</div></div></div>';
}

/** รูปสนาม: มีพื้นหลังสำรองกรณีโหลดรูปไม่ได้ (เช่น ออฟไลน์) */
function thumb(?string $src, string $alt, string $cls = ''): string
{
    $s = img_url($src);
  $available = $s !== '' && (
    preg_match('#^https?://#i', (string)$src)
    || is_file(ROOT_DIR . '/' . ltrim((string)$src, '/'))
  );
    return '<div class="thumb ' . h($cls) . '"><span class="thumb-ph">🏟️</span>'
    . ($available ? '<img src="' . h($s) . '" alt="' . h($alt) . '" loading="lazy" onerror="this.remove()">' : '')
        . '</div>';
}

function nav_icon(string $name): string
{
  $paths = [
    'home' => '<path d="m3 10 9-7 9 7"/><path d="M5 9v12h14V9"/><path d="M9 21v-7h6v7"/>',
    'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 11h18"/><path d="M8 15h.01M12 15h.01M16 15h.01"/>',
    'checklist' => '<rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4V3h6v1"/><path d="m8 14 2.5 2.5L16 11"/>',
    'history' => '<rect x="3" y="4" width="18" height="17" rx="2"/><path d="M3 9h18M8 2v4M16 2v4"/><path d="M8 13h3M8 17h7"/>',
    'user' => '<circle cx="12" cy="12" r="10"/><circle cx="12" cy="9" r="3"/><path d="M6.5 20c.8-3 2.7-4.5 5.5-4.5s4.7 1.5 5.5 4.5"/>',
    'folder' => '<path d="M3 7a2 2 0 0 1 2-2h5l2 2h7a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Z"/>',
    'check-circle' => '<circle cx="12" cy="12" r="10"/><path d="m8 12 2.5 2.5L16 9"/>',
    'bell' => '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/><path d="M10 21h4"/>',
    'edit' => '<path d="m16 5 3 3M4 20l4-.8L20 7a2.1 2.1 0 0 0-3-3L5 16Z"/>',
    'cancel' => '<circle cx="12" cy="12" r="9"/><path d="M8 12h8"/>',
    'restroom' => '<circle cx="8" cy="4.5" r="2"/><circle cx="16" cy="4.5" r="2"/><path d="M8 8v5m0-2H5m3 2-2 7m2-7 3 7m5-12v5m0-2h3m-3 2 2 7m-2-7-3 7"/>',
    'parking' => '<circle cx="12" cy="12" r="9"/><path d="M10 17V7h4a3 3 0 0 1 0 6h-4"/>',
    'light' => '<path d="M9 18h6m-5 4h4m-5-7a7 7 0 1 1 6 0c-.8.7-1 1.7-1 3h-4c0-1.3-.2-2.3-1-3Z"/>',
    'football' => '<circle cx="12" cy="12" r="9"/><path d="m12 7 3 2.2-1.1 3.6h-3.8L9 9.2 12 7Zm-3 2.2-3.5-1.1M9 12.8l-2.2 3m9-3 2.4 2m-.2-5.6 2.6-2m-8.6 0V3"/>',
    'basketball' => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a15 15 0 0 1 0 18M12 3a15 15 0 0 0 0 18"/>',
    'volleyball' => '<circle cx="12" cy="12" r="9"/><path d="M4 7c4 0 8 1 12 5M8 20c0-4 2-8 6-12m6 3c-4 0-8 2-11 6"/>',
    'tennis' => '<ellipse cx="11" cy="10" rx="6" ry="8" transform="rotate(30 11 10)"/><path d="m7 4 8 12M6 9l8-5m-9 9 8-5m2 8 5 5"/>',
    'plus' => '<path d="M12 5v14M5 12h14"/>',
    'alert' => '<path d="m10.3 3.9-8 14a2 2 0 0 0 1.7 3h16a2 2 0 0 0 1.7-3l-8-14a2 2 0 0 0-3.4 0Z"/><path d="M12 9v4M12 17h.01"/>',
    'overview' => '<rect x="3" y="3" width="8" height="8" rx="1"/><rect x="13" y="3" width="8" height="5" rx="1"/><rect x="13" y="10" width="8" height="11" rx="1"/><rect x="3" y="13" width="8" height="8" rx="1"/>',
    'pin' => '<path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/>',
    'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
    'shield' => '<path d="M12 22s8-4 8-11V5l-8-3-8 3v6c0 7 8 11 8 11Z"/><path d="m9 12 2 2 4-4"/>',
    'logout' => '<path d="m10 17 5-5-5-5M15 12H3"/><path d="M12 3h7a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-7"/>',
  ];

  return '<svg class="side-icon" aria-hidden="true" viewBox="0 0 24 24">' . ($paths[$name] ?? '') . '</svg>';
}

function member_nav(): array
{
    return [
    'dashboard' => ['dashboard.php', 'home', 'หน้าหลัก'],
    'book'      => ['book.php', 'calendar', 'จองสนาม'],
    'bookings'  => ['bookings.php', 'checklist', 'การรออนุมัติ'],
    'history'   => ['history.php', 'history', 'ประวัติการจอง'],
    'profile'   => ['profile.php', 'user', 'โปรไฟล์'],
    'report'    => ['report.php', 'alert', 'รายงานปัญหา'],
    ];
}
function admin_nav(): array
{
    return [
    'overview' => ['admin/index.php', 'overview', 'ภาพรวม'],
    'fields'   => ['admin/fields.php', 'pin', 'จัดการสนาม'],
    'bookings' => ['admin/bookings.php', 'calendar', 'รายการจอง'],
    'reports'  => ['admin/reports.php', 'alert', 'รายงานปัญหา'],
    'users'    => ['admin/users.php', 'users', 'จัดการผู้ใช้'],
    ];
}

/** เปิดหน้าแบบมีแถบเมนูด้านข้าง (หน้าสมาชิก/ผู้ดูแล) */
function shell_start(string $title, string $active, bool $admin = false): void
{
    $u   = current_user();
    $nav = $admin ? admin_nav() : member_nav();
    page_head($title);
    ?>
<div class="shell">
  <aside class="sidebar" id="sidebar">
    <?= brand($admin ? 'ผู้ดูแลระบบ' : APP_NAME, $admin ? APP_NAME : 'มหาวิทยาลัย') ?>
    <nav class="side-nav">
      <?php foreach ($nav as $key => [$href, $icon, $label]): ?>
        <a href="<?= h(url($href)) ?>" class="side-link pressable <?= $key === $active ? 'active' : '' ?>"><span class="ico"><?= nav_icon($icon) ?></span> <?= h($label) ?></a>
      <?php endforeach; ?>
    </nav>
    <div class="side-bottom">
      <?php if ($u && $u['role'] === 'admin'): ?>
        <a class="side-link" href="<?= h(url($admin ? 'dashboard.php' : 'admin/index.php')) ?>"><span class="ico"><?= nav_icon('shield') ?></span> <?= $admin ? 'ไปหน้าสมาชิก' : 'หน้าผู้ดูแลระบบ' ?></a>
      <?php endif; ?>
      <form method="post" action="<?= h(url('logout.php')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="to" value="<?= $admin ? 'admin-login.php' : 'login.php' ?>">
        <button class="side-link side-btn" type="submit"><span class="ico"><?= nav_icon('logout') ?></span> ออกจากระบบ</button>
      </form>
    </div>
  </aside>
  <div class="side-backdrop" id="sideBackdrop"></div>
  <main class="main">
    <header class="topbar">
      <button class="menu-btn" id="menuBtn" type="button" aria-label="เมนู">☰</button>
      <div class="topbar-user">
        <?php if (!empty($u['avatar_url'])): ?>
          <img class="avatar-sm" src="<?= h(img_url($u['avatar_url'])) ?>" alt="" onerror="this.remove()">
        <?php else: ?><span class="ico"><?= nav_icon('user') ?></span><?php endif; ?>
        <?= h(user_name($u) ?: ($u['email'] ?? '')) ?>
        <?php if ($u && $u['role'] === 'admin'): ?><span class="badge-admin">แอดมิน</span><?php endif; ?>
      </div>
    </header>
    <div class="content">
<?php
}

function shell_end(): void
{
    echo "    </div>\n  </main>\n</div>\n";
    page_foot();
}

function page_title(string $title, string $sub = '', string $actionHtml = ''): void
{
    echo '<div class="page-title"><div><h1>' . h($title) . '</h1>'
        . ($sub !== '' ? '<p class="muted">' . $sub . '</p>' : '')
        . '</div>' . $actionHtml . '</div>';
}
