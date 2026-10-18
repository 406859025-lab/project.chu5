<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';
require_login();

$f = fetch_one('SELECT * FROM fields WHERE id = ?', [(int)get('id')]);
shell_start('ข้อมูลสนามกีฬา', 'book');
page_title('ข้อมูลสนามกีฬา', '<a href="' . h(url('book.php')) . '">สนามกีฬา</a> &gt; รายละเอียดสนาม');
if (!$f): ?>
  <div class="card pad center muted">ไม่พบสนามนี้ <a href="<?= h(url('book.php')) ?>">กลับไปเลือกสนาม</a></div>
<?php else: ?>
  <div class="card pad">
    <div class="grid g2" style="gap:24px">
      <?= thumb($f['image_url'], $f['name'], 'h64 rounded') ?>
      <div class="detail-box">
        <div class="row-line" style="border:0"><h2 style="font-size:24px;color:var(--primary)"><?= h($f['name']) ?></h2><?= pill(FIELD_STATUS, $f['status']) ?></div>
        <p><b>ประเภทสนาม:</b> <?= h($f['sport_type']) ?></p>
        <p><b>สถานที่:</b> <?= h($f['location'] ?: '-') ?></p>
        <p><b>รายละเอียด:</b> <?= h($f['description'] ?: '-') ?></p>
      </div>
    </div>
    <span class="tag">สิ่งอำนวยความสะดวก</span>
    <div style="display:flex;flex-wrap:wrap;gap:12px;margin-top:12px">
      <?php $fac = facilities_list($f['facilities']); foreach ($fac as $x): ?>
        <span class="facility-chip"><?= nav_icon(FACILITY_ICONS[$x] ?? 'check-circle') ?> <?= h($x) ?></span>
      <?php endforeach; ?>
      <?php if (!$fac): ?><span class="muted">-</span><?php endif; ?>
    </div>
    <div class="right" style="margin-top:24px">
      <?php if ($f['status'] === 'available'): ?>
        <a class="btn" href="<?= h(url('book.php?field=' . (int)$f['id'])) ?>">จองสนามนี้</a>
      <?php else: ?>
        <span style="color:var(--danger);font-size:14px">สนามนี้ยังไม่เปิดให้จองในขณะนี้</span>
      <?php endif; ?>
    </div>
  </div>
<?php endif;
shell_end();
