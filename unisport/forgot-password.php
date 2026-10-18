<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';

$link = null;
$sent = false;
if (is_post()) {
    csrf_guard();
    $email = post('email');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash('error', 'กรุณากรอกอีเมลให้ถูกต้อง');
    } elseif (login_blocked()) {
        flash('error', 'ทำรายการบ่อยเกินไป กรุณารอสักครู่');
    } else {
        $sent = true;
        login_failed('reset:' . $email); // นับรวมกับตัวกันเดา เพื่อกันการยิงขอรีเซ็ตรัวๆ
        $u = fetch_one('SELECT id, email FROM users WHERE email = ?', [$email]);
        if ($u) {
            $token = bin2hex(random_bytes(32));
            q('DELETE FROM password_resets WHERE user_id = ?', [$u['id']]);
            q('INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?, ?, NOW() + INTERVAL 1 HOUR)', [$u['id'], hash('sha256', $token)]);
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $link = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . url('reset-password.php?token=' . $token);
            if (!SHOW_RESET_LINK) {
                @mail($u['email'], '=?UTF-8?B?' . base64_encode('รีเซ็ตรหัสผ่าน - ' . APP_NAME) . '?=',
                    "กดลิงก์เพื่อตั้งรหัสผ่านใหม่ (ลิงก์ใช้ได้ 1 ชั่วโมง):\n" . $link,
                    "Content-Type: text/plain; charset=UTF-8\r\n");
                $link = null;
            }
        }
    }
}
page_head('ลืมรหัสผ่าน');
?>
<div class="auth-bg" style="background-image:url('https://images.unsplash.com/photo-1489944440615-453fc2b6a9a9?w=1600')">
  <form method="post" class="auth-card">
    <?= csrf_field() ?>
    <?= brand() ?>
    <h1>ลืมรหัสผ่าน</h1>
    <?php if ($sent): ?>
      <p class="muted" style="font-size:14px">หากมีอีเมลนี้ในระบบ เราได้สร้างลิงก์สำหรับตั้งรหัสผ่านใหม่แล้ว (ใช้ได้ 1 ชั่วโมง)</p>
      <?php if ($link): ?>
        <div class="notice"><b>โหมดทดสอบ (XAMPP):</b> ยังไม่ได้ตั้งค่าส่งอีเมล จึงแสดงลิงก์ที่นี่<br><a href="<?= h($link) ?>"><?= h($link) ?></a></div>
      <?php endif; ?>
    <?php else: ?>
      <p class="muted" style="font-size:14px">กรอกอีเมลที่ใช้สมัคร เพื่อรับลิงก์ตั้งรหัสผ่านใหม่</p>
      <label for="email">อีเมล</label>
      <input id="email" type="email" name="email" required autofocus>
      <button class="btn btn-block" style="margin-top:20px" type="submit">ขอลิงก์ตั้งรหัสผ่านใหม่</button>
    <?php endif; ?>
    <div class="auth-links"><p><a href="<?= h(url('login.php')) ?>">← กลับหน้าเข้าสู่ระบบ</a></p></div>
  </form>
</div>
<?php page_foot();
