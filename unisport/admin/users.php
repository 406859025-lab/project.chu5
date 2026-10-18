<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/layout.php';
$me = require_admin();

if (is_post()) {
    csrf_guard();
    $id = (int)post('id');
    $target = fetch_one('SELECT id FROM users WHERE id = ?', [$id]);
    if (!$target) {
        flash('error', 'ไม่พบผู้ใช้');
    } elseif ($id === (int)$me['id']) {
        flash('error', 'ไม่สามารถเปลี่ยนสิทธิ์ของตัวเองได้');
    } else {
        $make = post('role') === 'admin';
        q('UPDATE users SET role = ? WHERE id = ?', [$make ? 'admin' : 'user', $id]);
        flash('success', $make ? 'ให้สิทธิ์ผู้ดูแลแล้ว' : 'ยกเลิกสิทธิ์ผู้ดูแลแล้ว');
    }
    redirect(safe_path(post('return'), 'admin/users.php'));
}

$s = get('q');
$like = '%' . addcslashes($s, '%_\\') . '%';
$rows = fetch_all(
    'SELECT * FROM users' . ($s !== '' ? ' WHERE member_code LIKE ? OR first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR username LIKE ?' : '') . ' ORDER BY created_at DESC, id DESC LIMIT 500',
    $s !== '' ? [$like, $like, $like, $like, $like] : []
);

ob_start(); ?>
<form method="get"><input name="q" style="width:256px" placeholder="ค้นหาชื่อ / อีเมล / รหัส (กด Enter)" value="<?= h($s) ?>"></form>
<?php $search = ob_get_clean();

shell_start('จัดการผู้ใช้', 'users', true);
page_title('จัดการผู้ใช้', '', $search);
?>
<div class="card table-wrap">
  <table class="table">
    <thead><tr><th>รหัส</th><th>ชื่อ-นามสกุล</th><th>ชื่อผู้ใช้</th><th>อีเมล</th><th>โทรศัพท์</th><th>สมัครเมื่อ</th><th>ผู้ดูแล</th></tr></thead>
    <tbody>
    <?php if (!$rows): ?><tr><td colspan="7" class="empty">ไม่พบผู้ใช้</td></tr><?php endif; ?>
    <?php foreach ($rows as $p): $isAdm = $p['role'] === 'admin'; ?>
      <tr class="pressable">
        <td><?= h($p['member_code'] ?? '-') ?></td>
        <td><?= h($p['first_name'] . ' ' . $p['last_name']) ?></td>
        <td><?= h($p['username'] ?? '-') ?></td>
        <td><?= h($p['email']) ?></td>
        <td><?= h($p['phone'] ?? '-') ?></td>
        <td><?= h(thai_date($p['created_at'])) ?></td>
        <td>
          <?php if ((int)$p['id'] === (int)$me['id']): ?><span class="pill pill-ok">คุณ</span>
          <?php else: ?>
            <form method="post" class="inline" data-confirm="<?= $isAdm ? 'ยกเลิกสิทธิ์ผู้ดูแลของผู้ใช้นี้?' : 'ให้สิทธิ์ผู้ดูแลแก่ผู้ใช้นี้?' ?>">
              <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
              <input type="hidden" name="role" value="<?= $isAdm ? 'user' : 'admin' ?>">
              <input type="hidden" name="return" value="<?= h($_SERVER['REQUEST_URI']) ?>">
              <button class="btn<?= $isAdm ? '' : '-outline' ?>" style="padding:4px 14px;font-size:12px" type="submit"><?= $isAdm ? 'เป็นผู้ดูแล ✔' : 'ให้สิทธิ์' ?></button>
            </form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php shell_end();
