<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';

$lock = ROOT_DIR . '/installed.lock';
$msg = null; $ok = false;

if (is_file($lock)) {
    $msg = 'ติดตั้งเสร็จแล้ว หากต้องการติดตั้งใหม่ให้ลบไฟล์ installed.lock (และควรลบ install.php ทิ้งเมื่อใช้งานจริง)';
} elseif (is_post()) {
    csrf_guard();
    try {
        $pdo = new PDO(sprintf('mysql:host=%s;port=%d;charset=utf8mb4', DB_HOST, DB_PORT), DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $pdo->exec((string)file_get_contents(ROOT_DIR . '/database.sql'));
        @file_put_contents($lock, date('c'));
        $ok = true;
        $msg = 'ติดตั้งฐานข้อมูลสำเร็จ';
    } catch (Throwable $e) {
        error_log('install: ' . $e->getMessage());
        $msg = 'ติดตั้งไม่สำเร็จ: เชื่อมต่อ MySQL ไม่ได้ ตรวจว่ากด Start MySQL ใน XAMPP แล้ว และค่าใน includes/config.php ถูกต้อง'
             . (APP_DEBUG ? ' (' . $e->getMessage() . ')' : '');
    }
}
page_head('ติดตั้งระบบ');
?>
<div class="auth-bg">
  <div class="auth-card">
    <?= brand() ?>
    <h1>ติดตั้งฐานข้อมูล</h1>
    <?php if ($msg): ?><div class="notice" style="word-break:normal"><?= h($msg) ?></div><?php endif; ?>
    <?php if ($ok): ?>
      <p style="font-size:14px">บัญชีตัวอย่าง:<br>ผู้ดูแล <b>admin@unisport.local</b> / <b>admin1234</b> (รหัสผู้ดูแล = <b><?= h(ADMIN_ACCESS_CODE) ?></b>)<br>สมาชิก <b>student@unisport.local</b> / <b>student1234</b></p>
      <a class="btn btn-block" href="<?= h(url('index.php')) ?>">ไปหน้าแรก</a>
    <?php elseif (!is_file($lock)): ?>
      <p class="muted" style="font-size:14px">จะสร้างฐานข้อมูล <b><?= h(DB_NAME) ?></b> พร้อมตารางและสนามตัวอย่างให้อัตโนมัติ (ไม่ลบข้อมูลที่มีอยู่)</p>
      <form method="post"><?= csrf_field() ?><button class="btn btn-block" type="submit">เริ่มติดตั้ง</button></form>
    <?php endif; ?>
  </div>
</div>
<?php page_foot();
