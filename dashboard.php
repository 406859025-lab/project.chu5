<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';
$user = require_login();

$fields   = fetch_all('SELECT * FROM fields ORDER BY code');
$avail    = count(array_filter($fields, fn($f) => $f['status'] === 'available'));
$bookings = fetch_all(
    'SELECT b.*, f.name AS field_name FROM bookings b JOIN fields f ON f.id = b.field_id
      WHERE b.user_id = ? ORDER BY b.created_at DESC, b.id DESC LIMIT 5',
    [$user['id']]
);
$notices = array_slice(array_filter($bookings, fn($b) => $b['status'] !== 'pending'), 0, 3);

shell_start('หน้าหลัก', 'dashboard');
?>
<div class="welcome">
  <h1>สวัสดี, <?= h(user_name($user) ?: 'ผู้ใช้') ?></h1>
  <p class="muted" style="margin:4px 0 0;font-size:14px">ยินดีต้อนรับสู่ระบบจองสนามกีฬา</p>
</div>

<div class="grid g4 mt">
  <div class="card stat pressable"><div class="big-ico"><?= nav_icon('folder') ?></div><div><div class="lbl">สนามทั้งหมด</div><div class="val"><?= count($fields) ?> สนาม</div></div></div>
  <div class="card stat pressable"><div class="big-ico"><?= nav_icon('check-circle') ?></div><div><div class="lbl">สนามว่าง</div><div class="val"><?= $avail ?> สนาม</div></div></div>
  <div class="card stat pressable"><div class="big-ico"><?= nav_icon('bell') ?></div><div><div class="lbl">สนามไม่ว่าง</div><div class="val"><?= count($fields) - $avail ?> สนาม</div></div></div>
  <div class="card pad" style="display:grid;place-items:center"><a class="btn" href="<?= h(url('book.php')) ?>">จองสนามกีฬา</a></div>
</div>

<div class="grid split mt">
  <div class="card pad">
    <h2 class="h-sec">สนามกีฬายอดนิยม</h2>
    <div class="grid g3">
      <?php foreach ($fields as $f): ?>
        <a class="field-card pressable" href="<?= h(url('field.php?id=' . (int)$f['id'])) ?>">
          <?= thumb($f['image_url'], $f['name'], 'h24') ?>
          <div class="info"><span class="nm"><?= h($f['name']) ?></span><?= pill(FIELD_STATUS, $f['status']) ?></div>
        </a>
      <?php endforeach; ?>
      <?php if (!$fields): ?><p class="muted">ยังไม่มีข้อมูลสนาม</p><?php endif; ?>
    </div>
  </div>
  <div class="stack">
    <div class="card pad pressable">
      <h3 class="h-sec" style="font-size:16px;margin-bottom:8px">การแจ้งเตือน</h3>
      <?php foreach ($notices as $b): ?>
        <p style="margin:4px 0;font-size:14px">• การจอง <?= h($b['code']) ?> <?= h(BOOKING_STATUS[$b['status']][0]) ?></p>
      <?php endforeach; ?>
      <?php if (!$notices): ?><p class="muted" style="margin:0;font-size:14px">ไม่มีการแจ้งเตือนใหม่</p><?php endif; ?>
    </div>
    <div class="card pad">
      <div class="row-line" style="border:0;padding-top:0"><h3 class="h-sec" style="font-size:16px;margin:0">รายการจองล่าสุด</h3><a href="<?= h(url('history.php')) ?>" style="font-size:12px">ดูทั้งหมด</a></div>
      <?php if (!$bookings): ?><p class="muted" style="font-size:14px;margin:0">ยังไม่มีการจอง</p><?php endif; ?>
      <?php foreach ($bookings as $b): ?>
        <div class="row-line">
          <div><div style="font-weight:500"><?= h($b['field_name']) ?></div>
            <div class="muted small"><?= h(thai_date($b['booking_date'])) ?> <?= h(hm($b['start_time'])) ?>-<?= h(hm($b['end_time'])) ?></div></div>
          <?= pill(BOOKING_STATUS, $b['status']) ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php shell_end();
