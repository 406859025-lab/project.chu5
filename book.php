<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';
require __DIR__ . '/includes/bookings.php';
$user = require_login();

if (is_post()) {
    csrf_guard();
}

$fieldId  = (int)(post('field_id') ?: get('field'));
$field    = $fieldId ? fetch_one('SELECT * FROM fields WHERE id = ?', [$fieldId]) : null;
if ($field && $field['status'] !== 'available') {
    flash('error', 'สนามนี้ยังไม่เปิดให้จองในขณะนี้');
    $field = null;
}

$f = ['date' => '', 'start' => '', 'end' => '', 'participants' => '1', 'purpose' => '', 'notes' => ''];
$step = $field ? 2 : 1;

if (is_post() && $field) {
    foreach ($f as $k => $_) {
        $f[$k] = post($k, $f[$k]);
    }
    $action = post('action');
    if ($action !== 'back') {
        [$d, $err] = parse_booking_input($f, false, true);
        if (!$d) {
            flash('error', (string)$err);
        } elseif ($action === 'check') {
            $step = 3;
        } elseif ($action === 'confirm') {
            [$ok, $res] = create_booking((int)$user['id'], (int)$field['id'], $d);
            if ($ok) {
                flash('success', 'ส่งคำขอจองแล้ว รอผู้ดูแลอนุมัติ');
                redirect('bookings.php');
            }
            flash('error', (string)$res);
        }
    }
}

$sport = get('sport');
$fields = [];
$taken  = [];
$bookedSlots = [];
if ($step === 1) {
    $fields = $sport !== ''
        ? fetch_all('SELECT * FROM fields WHERE sport_type = ? ORDER BY code', [$sport])
        : fetch_all('SELECT * FROM fields ORDER BY code');
} else {
    $bookedSlots = fetch_all(
        "SELECT booking_date, start_time, end_time, status FROM bookings
          WHERE field_id = ? AND booking_date >= CURDATE() AND status IN ('pending','approved')
          ORDER BY booking_date, start_time",
        [$field['id']]
    );
    $taken = array_slice($bookedSlots, 0, 8);
}

shell_start('จองสนามกีฬา', 'book');
page_title('จองสนามกีฬา');
?>
<div class="card pad mb">
  <div class="steps">
    <?php foreach (['เลือกสนาม', 'เลือกวันที่/เวลา', 'ยืนยันการจอง'] as $i => $label): ?>
      <div class="step <?= $step > $i ? 'done' : '' ?>"><div class="n"><?= $i + 1 ?></div><div class="l"><?= h($label) ?></div></div>
      <?php if ($i < 2): ?><div class="step-line"></div><?php endif; ?>
    <?php endforeach; ?>
  </div>
</div>

<?php if ($step === 1): ?>
  <?php if ($sport !== ''): ?><p class="muted">กำลังแสดงเฉพาะ: <b><?= h($sport) ?></b> · <a href="<?= h(url('book.php')) ?>">ดูทั้งหมด</a></p><?php endif; ?>
  <div class="grid g3">
    <?php foreach ($fields as $x): $ok = $x['status'] === 'available'; ?>
      <?php if ($ok): ?><a class="card field-card pressable" href="<?= h(url('book.php?field=' . (int)$x['id'])) ?>">
      <?php else: ?><div class="card field-card disabled"><?php endif; ?>
        <?= thumb($x['image_url'], $x['name'], 'h32') ?>
        <div class="info"><span class="nm"><?= h($x['name']) ?></span><?= pill(FIELD_STATUS, $x['status']) ?></div>
      <?php if ($ok): ?></a><?php else: ?></div><?php endif; ?>
    <?php endforeach; ?>
  </div>
  <?php if (!$fields): ?><div class="card pad center muted">ไม่พบสนามในหมวดนี้</div><?php endif; ?>

<?php else: ?>
  <div class="card book-grid">
    <div class="book-side">
      <p style="margin:0 0 8px;font-size:14px;color:var(--primary)">สนามกีฬาที่เลือก</p>
      <?= thumb($field['image_url'], $field['name'], 'h28 rounded') ?>
      <p style="margin:8px 0 0;font-weight:600;color:var(--primary)"><?= h($field['name']) ?></p>
      <?php if ($step === 2): ?><a class="small" href="<?= h(url('book.php')) ?>">เปลี่ยนสนาม</a><?php endif; ?>
      <?php if ($taken): ?>
        <div style="margin-top:16px;text-align:left;font-size:12px">
          <b style="color:var(--primary)">ช่วงที่มีคนจองแล้ว</b>
          <?php foreach ($taken as $t): ?>
            <div class="muted"><?= h(thai_date($t['booking_date'])) ?> <?= h(hm($t['start_time'])) ?>-<?= h(hm($t['end_time'])) ?></div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <?php if ($step === 2): ?>
      <form method="post" class="book-main" data-booked-slots="<?= h(json_encode($bookedSlots, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="field_id" value="<?= (int)$field['id'] ?>">
        <input type="hidden" name="action" value="check">
        <div class="grid g2">
          <div><label for="date">เลือกวันที่</label><input id="date" type="date" name="date" min="<?= h(today()) ?>" value="<?= h($f['date']) ?>" required></div>
          <div><label>เลือกเวลา</label>
            <div class="field-row">
              <select name="start" required><option value="">เริ่ม</option>
                <?php foreach (booking_hours() as $t): if ($t === sprintf('%02d:00', CLOSE_HOUR)) continue; ?><option <?= $f['start'] === $t ? 'selected' : '' ?>><?= h($t) ?></option><?php endforeach; ?></select>
              -
              <select name="end" required><option value="">สิ้นสุด</option>
                <?php foreach (booking_hours() as $t): if ($t === sprintf('%02d:00', OPEN_HOUR)) continue; ?><option <?= $f['end'] === $t ? 'selected' : '' ?>><?= h($t) ?></option><?php endforeach; ?></select>
            </div>
          </div>
          <div><label for="participants">จำนวนผู้ใช้งาน</label><input id="participants" type="number" name="participants" min="1" max="200" value="<?= h($f['participants']) ?>" required></div>
          <div><label for="purpose">วัตถุประสงค์</label>
            <select id="purpose" name="purpose" required><option value="">เลือก</option>
              <?php foreach (PURPOSES as $p): ?><option <?= $f['purpose'] === $p ? 'selected' : '' ?>><?= h($p) ?></option><?php endforeach; ?></select></div>
        </div>
        <label for="notes">รายละเอียดเพิ่มเติม (ถ้ามี)</label>
        <textarea id="notes" name="notes" rows="3" maxlength="500"><?= h($f['notes']) ?></textarea>
        <div style="display:flex;justify-content:space-between;margin-top:20px">
          <a class="btn-outline" href="<?= h(url('dashboard.php')) ?>">ยกเลิก</a>
          <button class="btn" type="submit">ตรวจสอบการจอง</button>
        </div>
      </form>
    <?php else: ?>
      <form method="post" class="book-main kv">
        <?= csrf_field() ?>
        <input type="hidden" name="field_id" value="<?= (int)$field['id'] ?>">
        <?php foreach ($f as $k => $v): ?><input type="hidden" name="<?= h($k) ?>" value="<?= h($v) ?>"><?php endforeach; ?>
        <h3 class="h-sec" style="font-size:18px">ยืนยันข้อมูลการจอง</h3>
        <p><b>วันที่:</b> <?= h(thai_date($f['date'])) ?></p>
        <p><b>เวลา:</b> <?= h($f['start']) ?> - <?= h($f['end']) ?></p>
        <p><b>จำนวนผู้ใช้งาน:</b> <?= h($f['participants']) ?> คน</p>
        <p><b>วัตถุประสงค์:</b> <?= h($f['purpose']) ?></p>
        <p><b>รายละเอียด:</b> <?= h($f['notes'] !== '' ? $f['notes'] : '-') ?></p>
        <div style="display:flex;justify-content:space-between;margin-top:20px">
          <button class="btn-outline" type="submit" name="action" value="back" formnovalidate>ย้อนกลับ</button>
          <button class="btn" type="submit" name="action" value="confirm">ยืนยันการจอง</button>
        </div>
      </form>
    <?php endif; ?>
  </div>
<?php endif;
shell_end();
