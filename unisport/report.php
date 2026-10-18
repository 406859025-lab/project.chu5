<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';
$user = require_login();

$fields = fetch_all('SELECT id, name FROM fields ORDER BY code');
$v = ['field_id' => '', 'problem_type' => '', 'details' => '', 'found_date' => '', 'found_time' => ''];

if (is_post()) {
    csrf_guard();
    foreach ($v as $k => $_) {
        $v[$k] = post($k);
    }
    $err = null;
    $fieldOk = $v['field_id'] !== '' && fetch_val('SELECT COUNT(*) FROM fields WHERE id = ?', [(int)$v['field_id']]);
    if (!$fieldOk || !in_array($v['problem_type'], PROBLEM_TYPES, true)) {
        $err = 'กรุณาเลือกสนามและประเภทปัญหา';
    } elseif (mb_strlen($v['details']) > 1000) {
        $err = 'รายละเอียดยาวเกิน 1000 ตัวอักษร';
    } elseif ($v['found_date'] !== '' && (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $v['found_date']) || !strtotime($v['found_date']) || $v['found_date'] > today())) {
        $err = 'วันที่พบปัญหาไม่ถูกต้อง';
    } elseif ($v['found_time'] !== '' && !preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $v['found_time'])) {
        $err = 'เวลาที่พบปัญหาไม่ถูกต้อง';
    }
    $photo = null;
    if (!$err) {
        [$photo, $uerr] = save_upload('photo', 'reports');
        $err = $uerr;
    }
    if ($err) {
        flash('error', $err);
    } else {
        q('INSERT INTO reports (user_id, field_id, problem_type, details, found_date, found_time, photo_url) VALUES (?,?,?,?,?,?,?)',
          [$user['id'], (int)$v['field_id'], $v['problem_type'], $v['details'] ?: null, $v['found_date'] ?: null, $v['found_time'] ?: null, $photo]);
        flash('success', 'ส่งรายงานแล้ว ขอบคุณที่แจ้งปัญหา');
        redirect('report.php');
    }
}

$mine = fetch_all(
    'SELECT r.*, f.name AS field_name FROM reports r LEFT JOIN fields f ON f.id = r.field_id
      WHERE r.user_id = ? ORDER BY r.created_at DESC, r.id DESC',
    [$user['id']]
);

shell_start('รายงานปัญหา', 'report');
page_title('รายงานปัญหา');
?>
<form method="post" enctype="multipart/form-data" class="card pad">
  <?= csrf_field() ?>
  <label style="margin-top:0;font-weight:500">เลือกสนามที่พบปัญหา</label>
  <select name="field_id" required><option value="">เลือกสนาม</option>
    <?php foreach ($fields as $x): ?><option value="<?= (int)$x['id'] ?>" <?= $v['field_id'] == $x['id'] ? 'selected' : '' ?>><?= h($x['name']) ?></option><?php endforeach; ?></select>
  <label style="font-weight:500">ประเภทปัญหา</label>
  <select name="problem_type" required><option value="">เลือกประเภท</option>
    <?php foreach (PROBLEM_TYPES as $t): ?><option <?= $v['problem_type'] === $t ? 'selected' : '' ?>><?= h($t) ?></option><?php endforeach; ?></select>
  <label style="font-weight:500">รายละเอียดเพิ่มเติม</label>
  <textarea name="details" rows="3" maxlength="1000" placeholder="กรุณาระบุรายละเอียดปัญหา..."><?= h($v['details']) ?></textarea>
  <div class="grid g2">
    <div><label style="font-weight:500">วันที่พบปัญหา</label><input type="date" name="found_date" max="<?= h(today()) ?>" value="<?= h($v['found_date']) ?>"></div>
    <div><label style="font-weight:500">เวลาที่พบปัญหา</label><input type="time" name="found_time" value="<?= h($v['found_time']) ?>"></div>
  </div>
  <label style="font-weight:500">แนบรูปภาพ (ถ้ามี)</label>
  <div class="file-picker">
    <input class="file-input" id="reportPhoto" type="file" name="photo" accept="image/*" data-file-picker>
    <label class="file-label" for="reportPhoto">Choose file</label>
    <button class="file-clear" type="button" data-file-clear aria-label="ล้างรูปภาพ" title="ล้างรูปภาพ" disabled>&times;</button>
  </div>
  <div class="center" style="margin-top:20px"><button class="btn" type="submit">ส่งรายงาน</button></div>
</form>

<?php if ($mine): ?>
  <div class="card pad mt">
    <h2 class="h-sec" style="font-size:16px">รายงานของฉัน</h2>
    <?php foreach ($mine as $r): ?>
      <div class="row-line">
        <span><?= h($r['field_name'] ?? '-') ?> · <?= h($r['problem_type']) ?> · <?= h(thai_date($r['created_at'])) ?></span>
        <?= pill(REPORT_STATUS, $r['status']) ?>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif;
shell_end();
