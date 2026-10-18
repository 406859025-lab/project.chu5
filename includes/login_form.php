<?php
/** ฟอร์มเข้าสู่ระบบ ใช้ร่วมกันระหว่างสมาชิก (login.php) และผู้ดูแล (admin-login.php) — ตัวแปร $adminMode ถูกกำหนดโดยไฟล์ที่เรียก */
declare(strict_types=1);

$u = current_user();
if ($u && (!$adminMode || $u['role'] === 'admin')) {
    redirect($adminMode ? 'admin/index.php' : 'dashboard.php');
}

$ident = '';
if (is_post()) {
    csrf_guard();
    $ident = post('email');
    $pw    = (string)($_POST['password'] ?? '');
    $err   = null;

    if (login_blocked()) {
        $err = 'ลองเข้าสู่ระบบผิดหลายครั้งเกินไป กรุณารอประมาณ 10 นาทีแล้วลองใหม่';
    } elseif ($adminMode && !hash_equals((string)ADMIN_ACCESS_CODE, post('admin_code'))) {
        login_failed($ident);
        $err = 'รหัสผู้ดูแลไม่ถูกต้อง';
    } else {
        $row = $ident !== '' ? fetch_one('SELECT * FROM users WHERE email = ? OR username = ? LIMIT 1', [$ident, $ident]) : null;
        if (!$row || !password_verify($pw, $row['password_hash'])) {
            login_failed($ident);
            $err = 'อีเมลหรือรหัสผ่านไม่ถูกต้อง';
        } elseif ($adminMode && $row['role'] !== 'admin') {
            login_failed($ident);
            $err = 'บัญชีนี้ไม่มีสิทธิ์ผู้ดูแลระบบ';
        } else {
            if (password_needs_rehash($row['password_hash'], PASSWORD_DEFAULT)) {
                q('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($pw, PASSWORD_DEFAULT), $row['id']]);
            }
            login_user((int)$row['id']);
            $fallback = $adminMode ? 'admin/index.php' : 'dashboard.php';
            $next = safe_path($_GET['next'] ?? post('next'), $fallback);
            // ผู้ดูแลที่ล็อกอินหน้าผู้ดูแลต้องไม่ถูกส่งไปหน้าที่ไม่ใช่ /admin
            if ($adminMode && !str_contains($next, '/admin/')) {
                $next = url($fallback);
            }
            redirect($next);
        }
    }
    flash('error', $err);
}

$bg = 'https://images.unsplash.com/photo-1489944440615-453fc2b6a9a9?w=1600';
page_head($adminMode ? 'เข้าสู่ระบบผู้ดูแล' : 'เข้าสู่ระบบ');
?>
<div class="auth-bg" style="background-image:url('<?= h($bg) ?>')">
  <form method="post" class="auth-card" autocomplete="on">
    <?= csrf_field() ?>
    <input type="hidden" name="next" value="<?= h($_GET['next'] ?? '') ?>">
    <?= brand() ?>
    <h1><?= $adminMode ? 'เข้าสู่ระบบผู้ดูแล' : 'เข้าสู่ระบบสมาชิก' ?></h1>
    <p class="muted" style="margin:4px 0 0;font-size:14px">กรุณากรอกข้อมูลเพื่อเข้าสู่ระบบ</p>

    <label for="email">อีเมล</label>
    <input id="email" name="email" type="text" required autofocus autocomplete="username" value="<?= h($ident) ?>">
    <label for="password">รหัสผ่าน</label>
    <input id="password" name="password" type="password" required autocomplete="current-password">
    <?php if ($adminMode): ?>
      <label for="admin_code">รหัสผู้ดูแล</label>
      <input id="admin_code" name="admin_code" type="password" required placeholder="กรอกรหัสผู้ดูแล" autocomplete="off">
    <?php endif; ?>

    <div class="right" style="margin-top:10px;font-size:14px"><a href="<?= h(url('forgot-password.php')) ?>">ลืมรหัสผ่าน ?</a></div>
    <button class="btn btn-block" style="margin-top:20px" type="submit">เข้าสู่ระบบ</button>

    <div class="auth-links">
      <?php if ($adminMode): ?>
        <p>เป็นสมาชิก? <a href="<?= h(url('login.php')) ?>">เข้าสู่ระบบสมาชิก</a></p>
      <?php else: ?>
        <p>ยังไม่มีบัญชี? <a href="<?= h(url('register.php')) ?>">สมัครสมาชิก</a></p>
        <p>ผู้ดูแลระบบ? <a href="<?= h(url('admin-login.php')) ?>">เข้าสู่ระบบผู้ดูแล</a></p>
      <?php endif; ?>
      <p><a href="<?= h(url('index.php')) ?>" style="font-weight:400" class="muted">← กลับหน้าแรก</a></p>
    </div>
  </form>
</div>
<?php page_foot();
