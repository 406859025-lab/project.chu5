<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';

$user   = current_user();
$authed = (bool)$user;
$fields = fetch_all('SELECT * FROM fields ORDER BY code LIMIT 8');

$hero = 'https://images.unsplash.com/photo-1522778119026-d647f0596c20?w=1600';
$go   = url($authed ? 'book.php' : 'login.php');
$sports = [['football', 'สนามฟุตบอล', 'ฟุตบอล'], ['basketball', 'สนามบาสเกตบอล', 'บาสเกตบอล'], ['volleyball', 'สนามวอลเลย์บอล', 'วอลเลย์บอล'], ['tennis', 'สนามเทนนิส', 'เทนนิส']];

page_head('จองง่าย สะดวก รวดเร็ว');
?>
<header class="pub-header">
  <div class="in">
    <?= brand() ?>
    <nav class="pub-nav">
      <a href="<?= h(url('index.php')) ?>">หน้าแรก</a>
      <a href="<?= h($go) ?>">สนามกีฬา</a>
      <a href="#about">เกี่ยวกับเรา</a>
    </nav>
    <div class="pub-actions">
      <a class="btn-outline btn-ghost-light" style="font-size:12px" href="<?= h(url('admin-login.php')) ?>">🛡️ เข้าสู่ระบบผู้ดูแล</a>
      <?php if ($authed): ?>
        <a class="btn btn-light" href="<?= h(url($user['role'] === 'admin' ? 'admin/index.php' : 'dashboard.php')) ?>">เข้าสู่แดชบอร์ด</a>
      <?php else: ?>
        <a class="btn-outline btn-ghost-light" href="<?= h(url('login.php')) ?>">เข้าสู่ระบบ</a>
        <a class="btn btn-light" href="<?= h(url('register.php')) ?>">สมัครสมาชิก</a>
      <?php endif; ?>
    </div>
  </div>
</header>

<section class="hero" style="background-image:url('<?= h($hero) ?>')">
  <div class="in">
    <h1>จองสนามกีฬา<br>ได้ง่าย สะดวก รวดเร็ว</h1>
    <p>ระบบจองสนามกีฬาในมหาวิทยาลัย<br>เพื่อใช้งานที่สะดวกและปลอดภัย</p>
    <a class="btn" style="margin-top:24px" href="<?= h($go) ?>">จองสนามกีฬา</a>
  </div>
</section>

<div class="wrap">
  <div class="sports">
    <?php foreach ($sports as [$icon, $label, $type]): ?>
      <a class="pressable" href="<?= h($authed ? url('book.php?sport=' . rawurlencode($type)) : $go) ?>"><span class="e"><?= nav_icon($icon) ?></span><?= h($label) ?></a>
    <?php endforeach; ?>
    <a class="pressable" href="<?= h($go) ?>"><span class="e"><?= nav_icon('plus') ?></span>อื่นๆ</a>
  </div>

  <div class="section-head">
    <h2>สนามกีฬายอดนิยม</h2>
    <a href="<?= h($go) ?>" style="font-size:14px">ดูทั้งหมด &gt;</a>
  </div>
  <div class="grid g4">
    <?php foreach ($fields as $f): ?>
      <a class="field-card pressable" href="<?= h($authed ? url('field.php?id=' . (int)$f['id']) : url('login.php')) ?>">
        <?= thumb($f['image_url'], $f['name'], 'h32') ?>
        <div class="info">
          <div><div class="nm"><?= h($f['name']) ?></div><div class="sp"><?= h($f['sport_type']) ?></div></div>
          <?= pill(FIELD_STATUS, $f['status']) ?>
        </div>
      </a>
    <?php endforeach; ?>
    <?php if (!$fields): ?><p class="muted">ยังไม่มีข้อมูลสนาม</p><?php endif; ?>
  </div>
</div>

<footer id="about" class="pub-footer">
  ระบบจองสนามกีฬามหาวิทยาลัย — บริการนักศึกษาและบุคลากร · <a href="<?= h(url('admin-login.php')) ?>">สำหรับผู้ดูแลระบบ</a>
</footer>
<?php page_foot();
