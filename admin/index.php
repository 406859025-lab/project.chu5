<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/layout.php';
require_admin();

$c = fn(string $sql, array $p = []) => (int)fetch_val($sql, $p);
$stats = [
    ['สนามทั้งหมด', $c('SELECT COUNT(*) FROM fields'), 'admin/fields.php'],
    ['รออนุมัติ', $c("SELECT COUNT(*) FROM bookings WHERE status='pending'"), 'admin/bookings.php?status=pending'],
    ['การจองวันนี้', $c('SELECT COUNT(*) FROM bookings WHERE booking_date = ?', [today()]), 'admin/bookings.php?from=' . today() . '&to=' . today()],
    ['ปัญหาที่ยังไม่แก้', $c("SELECT COUNT(*) FROM reports WHERE status <> 'resolved'"), 'admin/reports.php'],
    ['ผู้ใช้งาน', $c('SELECT COUNT(*) FROM users'), 'admin/users.php'],
];
$bookings = fetch_all('SELECT b.*, f.name AS field_name FROM bookings b JOIN fields f ON f.id=b.field_id ORDER BY b.created_at DESC, b.id DESC LIMIT 6');
$reports  = fetch_all('SELECT r.*, f.name AS field_name FROM reports r LEFT JOIN fields f ON f.id=r.field_id ORDER BY r.created_at DESC, r.id DESC LIMIT 6');

shell_start('ภาพรวมผู้ดูแลระบบ', 'overview', true);
page_title('แดชบอร์ดผู้ดูแลระบบ', 'ภาพรวมระบบจองสนามกีฬา');
?>
<div class="grid g5">
  <?php foreach ($stats as [$l, $v, $to]): ?>
    <a class="card stat-link pressable" href="<?= h(url($to)) ?>"><div class="muted" style="font-size:14px"><?= h($l) ?></div><div class="v"><?= $v ?></div></a>
  <?php endforeach; ?>
</div>
<div class="grid g2 mt" style="gap:20px">
  <div class="card pad">
    <div class="row-line" style="border:0"><h2 class="h-sec" style="margin:0">การจองล่าสุด</h2><a href="<?= h(url('admin/bookings.php')) ?>" style="font-size:14px">ดูทั้งหมด</a></div>
    <?php if (!$bookings): ?><p class="muted" style="font-size:14px">ยังไม่มีการจอง</p><?php endif; ?>
    <?php foreach ($bookings as $b): ?>
      <div class="row-line"><span><?= h($b['code']) ?> · <?= h($b['field_name']) ?> · <?= h(thai_date($b['booking_date'])) ?> <?= h(hm($b['start_time'])) ?></span><?= pill(BOOKING_STATUS, $b['status']) ?></div>
    <?php endforeach; ?>
  </div>
  <div class="card pad">
    <div class="row-line" style="border:0"><h2 class="h-sec" style="margin:0">รายงานปัญหาล่าสุด</h2><a href="<?= h(url('admin/reports.php')) ?>" style="font-size:14px">ดูทั้งหมด</a></div>
    <?php if (!$reports): ?><p class="muted" style="font-size:14px">ยังไม่มีรายงาน</p><?php endif; ?>
    <?php foreach ($reports as $r): ?>
      <div class="row-line"><span><?= h($r['field_name'] ?? '-') ?> · <?= h($r['problem_type']) ?></span><?= pill(REPORT_STATUS, $r['status']) ?></div>
    <?php endforeach; ?>
  </div>
</div>
<?php shell_end();
