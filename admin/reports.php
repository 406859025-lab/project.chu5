<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/layout.php';
require_admin();

if (is_post()) {
    csrf_guard();
    $id = (int)post('id');
    $r  = fetch_one('SELECT * FROM reports WHERE id = ?', [$id]);
    if (!$r) {
        flash('error', 'ไม่พบรายงานนี้');
    } elseif (post('action') === 'status' && isset(REPORT_STATUS[post('to')])) {
        q('UPDATE reports SET status = ? WHERE id = ?', [post('to'), $id]);
        flash('success', 'อัปเดตสถานะแล้ว');
    } elseif (post('action') === 'delete') {
        q('DELETE FROM reports WHERE id = ?', [$id]);
        delete_upload($r['photo_url']);
        flash('success', 'ลบรายงานแล้ว');
    }
    redirect(safe_path(post('return'), 'admin/reports.php'));
}

$status = get('status', 'all');
if ($status !== 'all' && !isset(REPORT_STATUS[$status])) {
    $status = 'all';
}
$rows = fetch_all(
    'SELECT r.*, f.name AS field_name, CONCAT(u.first_name, " ", u.last_name) AS who
       FROM reports r LEFT JOIN fields f ON f.id = r.field_id JOIN users u ON u.id = r.user_id'
    . ($status !== 'all' ? ' WHERE r.status = ?' : '') . ' ORDER BY r.created_at DESC, r.id DESC LIMIT 300',
    $status !== 'all' ? [$status] : []
);

ob_start(); ?>
<form method="get"><select name="status" style="width:192px" onchange="this.form.submit()">
  <option value="all">สถานะทั้งหมด</option>
  <?php foreach (REPORT_STATUS as $k => [$l]): ?><option value="<?= h($k) ?>" <?= $status === $k ? 'selected' : '' ?>><?= h($l) ?></option><?php endforeach; ?>
</select></form>
<?php $filter = ob_get_clean();

shell_start('รายงานปัญหา', 'reports', true);
page_title('รายงานปัญหา', '', $filter);
if (!$rows): ?><div class="card pad center muted">ไม่มีรายงานปัญหา</div><?php endif; ?>
<div class="grid g2">
  <?php foreach ($rows as $r): ?>
    <div class="card report-card">
      <?php if ($r['photo_url']): ?><a href="<?= h(img_url($r['photo_url'])) ?>" target="_blank" rel="noopener"><img src="<?= h(img_url($r['photo_url'])) ?>" alt=""></a><?php endif; ?>
      <div class="body">
        <div class="row-line" style="border:0;padding:0"><b style="color:var(--primary)"><?= h($r['field_name'] ?? '-') ?></b><?= pill(REPORT_STATUS, $r['status']) ?></div>
        <p><b>ประเภท:</b> <?= h($r['problem_type']) ?></p>
        <p class="muted" style="word-break:break-word"><?= h($r['details'] ?: '-') ?></p>
        <p class="muted small">ผู้แจ้ง: <?= h($r['who']) ?> · พบเมื่อ <?= h(thai_date($r['found_date'])) ?> <?= h(hm($r['found_time'])) ?></p>
        <div class="actions" style="margin-top:8px">
          <form method="post" class="inline">
            <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="action" value="status">
            <input type="hidden" name="return" value="<?= h($_SERVER['REQUEST_URI']) ?>">
            <select name="to" style="width:176px" onchange="this.form.submit()">
              <?php foreach (REPORT_STATUS as $k => [$l]): ?><option value="<?= h($k) ?>" <?= $r['status'] === $k ? 'selected' : '' ?>><?= h($l) ?></option><?php endforeach; ?>
            </select>
          </form>
          <form method="post" class="inline" data-confirm="ลบรายงานนี้?">
            <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="action" value="delete">
            <input type="hidden" name="return" value="<?= h($_SERVER['REQUEST_URI']) ?>">
            <button class="icon-btn icon-bad" title="ลบ" type="submit">🗑</button>
          </form>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<?php shell_end();
