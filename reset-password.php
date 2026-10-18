<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';

$token = get('token', post('token'));
$row = $token !== '' ? fetch_one(
    'SELECT * FROM password_resets WHERE token_hash = ? AND used_at IS NULL AND expires_at > NOW()',
    [hash('sha256', $token)]
) : null;

if ($row && is_post()) {
    csrf_guard();
    $pw = (string)($_POST['password'] ?? '');
    if (strlen($pw) < 6 || strlen($pw) > 72) {
        flash('error', 'รหัสผ่านอย่างน้อย 6 ตัวอักษร');
    } elseif ($pw !== (string)($_POST['confirm'] ?? '')) {
        flash('error', 'รหัสผ่านไม่ตรงกัน');
    } else {
        q('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($pw, PASSWORD_DEFAULT), $row['user_id']]);
        q('DELETE FROM password_resets WHERE user_id = ?', [$row['user_id']]);
        flash('success', 'เปลี่ยนรหัสผ่านแล้ว กรุณาเข้าสู่ระบบด้วยรหัสใหม่');
        redirect('login.php');
    }
}
page_head('ตั้งรหัสผ่านใหม่');
?>
<div class="auth-bg" style="background-image:url('https://images.unsplash.com/photo-1489944440615-453fc2b6a9a9?w=1600')">
  <form method="post" class="auth-card">
    <?= csrf_field() ?>
    <input type="hidden" name="token" value="<?= h($token) ?>">
    <?= brand() ?>
    <h1>ตั้งรหัสผ่านใหม่</h1>
    <?php if (!$row): ?>
      <p class="muted" style="font-size:14px">ลิงก์นี้ไม่ถูกต้องหรือหมดอายุแล้ว</p>
      <a class="btn btn-block" style="margin-top:16px" href="<?= h(url('forgot-password.php')) ?>">ขอลิงก์ใหม่</a>
    <?php else: ?>
      <label for="password">รหัสผ่านใหม่</label>
      <input id="password" type="password" name="password" required minlength="6" autocomplete="new-password" autofocus>
      <label for="confirm">ยืนยันรหัสผ่านใหม่</label>
      <input id="confirm" type="password" name="confirm" required minlength="6" autocomplete="new-password">
      <button class="btn btn-block" style="margin-top:20px" type="submit">บันทึก</button>
    <?php endif; ?>
  </form>
</div>
<?php page_foot();
