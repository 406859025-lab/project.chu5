<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';

if (current_user()) {
    redirect('dashboard.php');
}

$form = [];
if (is_post()) {
    csrf_guard();
    foreach (['member_code', 'first_name', 'last_name', 'email', 'username'] as $k) {
        $form[$k] = post($k);
    }
    $password = (string)($_POST['password'] ?? '');
    $confirm  = (string)($_POST['confirm'] ?? '');
    $err = null;

    if ($form['member_code'] === '' || mb_strlen($form['member_code']) > 30) {
        $err = 'กรุณากรอกรหัสนักศึกษา/บุคลากร (ไม่เกิน 30 ตัวอักษร)';
    } elseif ($form['first_name'] === '' || mb_strlen($form['first_name']) > 60) {
        $err = 'กรุณากรอกชื่อ';
    } elseif ($form['last_name'] === '' || mb_strlen($form['last_name']) > 60) {
        $err = 'กรุณากรอกนามสกุล';
    } elseif (!filter_var($form['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($form['email']) > 255) {
        $err = 'อีเมลไม่ถูกต้อง';
    } elseif (mb_strlen($form['username']) < 3 || mb_strlen($form['username']) > 30 || preg_match('/[\s@]/u', $form['username'])) {
        $err = 'ชื่อผู้ใช้ 3-30 ตัวอักษร และห้ามมีช่องว่างหรือ @';
    } elseif (strlen($password) < 6 || strlen($password) > 72) {
        $err = 'รหัสผ่านอย่างน้อย 6 ตัวอักษร (ไม่เกิน 72)';
    } elseif ($password !== $confirm) {
        $err = 'รหัสผ่านไม่ตรงกัน';
    } elseif (fetch_val('SELECT COUNT(*) FROM users WHERE email = ?', [$form['email']])) {
        $err = 'อีเมลนี้ถูกใช้สมัครแล้ว';
    } elseif (fetch_val('SELECT COUNT(*) FROM users WHERE username = ?', [$form['username']])) {
        $err = 'ชื่อผู้ใช้นี้ถูกใช้แล้ว';
    }

    if (!$err) {
        try {
            // สมัครใหม่เป็นสมาชิกทั่วไปเสมอ — การให้สิทธิ์ผู้ดูแลทำได้ที่หน้า "จัดการผู้ใช้" เท่านั้น
            q('INSERT INTO users (member_code, first_name, last_name, email, username, password_hash, role) VALUES (?,?,?,?,?,?,?)',
              [$form['member_code'], $form['first_name'], $form['last_name'], $form['email'], $form['username'],
               password_hash($password, PASSWORD_DEFAULT), 'user']);
            $id = (int)db()->lastInsertId();
            login_user($id);
            flash('success', 'สมัครสมาชิกสำเร็จ ยินดีต้อนรับ');
            redirect('dashboard.php');
        } catch (PDOException $e) {
            $err = ($e->errorInfo[1] ?? 0) === 1062 ? 'อีเมลหรือชื่อผู้ใช้นี้ถูกใช้แล้ว' : 'สมัครสมาชิกไม่สำเร็จ กรุณาลองใหม่';
            error_log('register: ' . $e->getMessage());
        }
    }
    flash('error', $err);
}

$defs = [
    ['member_code', 'รหัสนักศึกษา/รหัสบุคลากร', 'text', 'off'],
    ['first_name', 'ชื่อ', 'text', 'given-name'],
    ['last_name', 'นามสกุล', 'text', 'family-name'],
    ['email', 'อีเมล', 'email', 'email'],
    ['username', 'ชื่อผู้ใช้', 'text', 'username'],
    ['password', 'รหัสผ่าน', 'password', 'new-password'],
    ['confirm', 'ยืนยันรหัสผ่าน', 'password', 'new-password'],
];
page_head('สมัครสมาชิก');
?>
<div class="register">
  <div class="left">
    <?= brand() ?>
    <div class="mid">
            <div class="big"><img src="<?= h(asset('run.register.png')) ?>" alt="นักวิ่ง"></div>
      <p>มาเป็นส่วนหนึ่ง<br>ของการใช้ชีวิต<br>ที่ดีด้วยกีฬา</p>
    </div>
  </div>
  <div class="right-col">
    <form method="post" autocomplete="on">
      <?= csrf_field() ?>
      <h1>สมัครสมาชิก</h1>
      <p class="center muted" style="margin:4px 0 12px;font-size:14px">สร้างบัญชีเพื่อใช้งานระบบจองสนามกีฬา</p>
      <?php foreach ($defs as [$k, $label, $type, $ac]): ?>
        <label for="<?= h($k) ?>"><?= h($label) ?></label>
        <input id="<?= h($k) ?>" name="<?= h($k) ?>" type="<?= h($type) ?>" required autocomplete="<?= h($ac) ?>"
               value="<?= in_array($type, ['password'], true) ? '' : h($form[$k] ?? '') ?>">
      <?php endforeach; ?>
      <button class="btn btn-block" style="margin-top:24px" type="submit">สมัครสมาชิก</button>
      <p class="center muted" style="margin-top:12px;font-size:14px">มีบัญชีอยู่แล้ว? <a href="<?= h(url('login.php')) ?>"><b>เข้าสู่ระบบ</b></a></p>
    </form>
  </div>
</div>
<?php page_foot();
