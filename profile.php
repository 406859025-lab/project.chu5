<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';
$user = require_login();

if (is_post()) {
    csrf_guard();
    switch (post('action')) {
        case 'info':
            $d = ['member_code' => post('member_code'), 'first_name' => post('first_name'), 'last_name' => post('last_name'), 'phone' => post('phone')];
            if ($d['first_name'] === '') {
                flash('error', 'กรุณากรอกชื่อ');
            } elseif (mb_strlen($d['first_name']) > 60 || mb_strlen($d['last_name']) > 60 || mb_strlen($d['member_code']) > 30) {
                flash('error', 'ข้อมูลยาวเกินกำหนด');
            } elseif ($d['phone'] !== '' && !preg_match('/^[0-9\-+ ]{6,15}$/', $d['phone'])) {
                flash('error', 'เบอร์โทรศัพท์ไม่ถูกต้อง');
            } else {
                q('UPDATE users SET member_code = ?, first_name = ?, last_name = ?, phone = ? WHERE id = ?',
                  [$d['member_code'] ?: null, $d['first_name'], $d['last_name'], $d['phone'] ?: null, $user['id']]);
                flash('success', 'บันทึกข้อมูลแล้ว');
            }
            break;

        case 'avatar':
            [$path, $err] = save_upload('avatar', 'avatars');
            if ($err) {
                flash('error', $err);
            } elseif (!$path) {
                flash('error', 'กรุณาเลือกรูปภาพ');
            } else {
                q('UPDATE users SET avatar_url = ? WHERE id = ?', [$path, $user['id']]);
                delete_upload($user['avatar_url']);
                flash('success', 'เปลี่ยนรูปโปรไฟล์แล้ว');
            }
            break;

        case 'password':
            $old = (string)($_POST['old_password'] ?? '');
            $new = (string)($_POST['new_password'] ?? '');
            if (!password_verify($old, $user['password_hash'])) {
                flash('error', 'รหัสผ่านปัจจุบันไม่ถูกต้อง');
            } elseif (strlen($new) < 6 || strlen($new) > 72) {
                flash('error', 'รหัสผ่านใหม่อย่างน้อย 6 ตัวอักษร');
            } elseif ($new !== (string)($_POST['confirm_password'] ?? '')) {
                flash('error', 'รหัสผ่านใหม่ไม่ตรงกัน');
            } else {
                q('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($new, PASSWORD_DEFAULT), $user['id']]);
                flash('success', 'เปลี่ยนรหัสผ่านแล้ว');
            }
            break;
    }
    redirect('profile.php');
}

shell_start('ข้อมูลส่วนตัว', 'profile');
page_title('ข้อมูลส่วนตัว');
?>
<div class="profile-grid">
  <div class="card pad" style="display:flex;flex-direction:column;align-items:center">
    <div class="avatar-lg">
      <?php if ($user['avatar_url']): ?><img src="<?= h(img_url($user['avatar_url'])) ?>" alt="avatar" style="width:100%;height:100%;object-fit:cover" onerror="this.remove()"><?php else: ?><?= nav_icon('user') ?><?php endif; ?>
    </div>
    <form method="post" enctype="multipart/form-data" style="margin-top:20px;text-align:center">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="avatar">
      <input class="avatar-file-input" id="avatarFile" type="file" name="avatar" accept="image/*" required onchange="if (this.files.length) this.form.requestSubmit()">
      <label class="btn avatar-upload-trigger" for="avatarFile">เปลี่ยนรูปโปรไฟล์</label>
    </form>
    <p class="muted" style="font-size:14px;margin-top:12px"><?= h($user['email']) ?></p>
  </div>

  <div class="stack">
    <form method="post" class="card pad">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="info">
      <label style="margin-top:0">รหัสนักศึกษา/รหัสบุคลากร</label><input name="member_code" maxlength="30" value="<?= h($user['member_code']) ?>">
      <label>ชื่อ</label><input name="first_name" maxlength="60" required value="<?= h($user['first_name']) ?>">
      <label>นามสกุล</label><input name="last_name" maxlength="60" value="<?= h($user['last_name']) ?>">
      <label>เบอร์โทรศัพท์</label><input name="phone" maxlength="15" value="<?= h($user['phone']) ?>">
      <div class="center" style="margin-top:20px"><button class="btn" type="submit">บันทึกข้อมูล</button></div>
    </form>

    <form method="post" class="card pad">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="password">
      <h3 class="h-sec" style="font-size:16px">เปลี่ยนรหัสผ่าน</h3>
      <label style="margin-top:0">รหัสผ่านปัจจุบัน</label><input type="password" name="old_password" required autocomplete="current-password">
      <label>รหัสผ่านใหม่</label><input type="password" name="new_password" minlength="6" required autocomplete="new-password">
      <label>ยืนยันรหัสผ่านใหม่</label><input type="password" name="confirm_password" minlength="6" required autocomplete="new-password">
      <div class="center" style="margin-top:20px"><button class="btn" type="submit">เปลี่ยนรหัสผ่าน</button></div>
    </form>
  </div>
</div>
<?php shell_end();
