<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/layout.php';
require_admin();

if (is_post()) {
    csrf_guard();
    $id = (int)post('id');
    $old = $id ? fetch_one('SELECT * FROM fields WHERE id = ?', [$id]) : null;

    if (post('action') === 'delete') {
        if ($old) {
            q('DELETE FROM fields WHERE id = ?', [$id]);
            delete_upload($old['image_url']);
            flash('success', 'ลบสนามแล้ว');
        }
        redirect('admin/fields.php');
    }

    $code = post('code'); $name = post('name'); $sport = post('sport_type');
    $loc = post('location'); $desc = post('description'); $status = post('status');
    $fac = array_values(array_intersect(FACILITIES, (array)($_POST['facilities'] ?? [])));
    $link = post('image_url');
    $err = null;

    if ($code === '' || $name === '') {
        $err = 'กรุณากรอกรหัสและชื่อสนาม';
    } elseif (mb_strlen($code) > 10 || mb_strlen($name) > 80 || mb_strlen($loc) > 120 || mb_strlen($desc) > 500) {
        $err = 'ข้อมูลยาวเกินกำหนด';
    } elseif (!in_array($sport, SPORT_TYPES, true) || !isset(FIELD_STATUS[$status])) {
        $err = 'ประเภทหรือสถานะไม่ถูกต้อง';
    }

    $image = $old['image_url'] ?? null;
    if (!$err) {
        [$up, $uerr] = save_upload('image_file', 'fields');
        if ($uerr) {
            $err = $uerr;
        } elseif ($up) {
            $image = $up;
        } elseif ($link === '') {
            $image = null;
        } elseif ($link !== $image) {
            if (!valid_image_link($link)) {
                $err = 'ลิงก์รูปภาพต้องขึ้นต้นด้วย http:// หรือ https://';
            } else {
                $image = $link;
            }
        }
    }

    if (!$err) {
        try {
            if ($old) {
                q('UPDATE fields SET code=?, name=?, sport_type=?, location=?, description=?, image_url=?, facilities=?, status=? WHERE id=?',
                  [$code, $name, $sport, $loc ?: null, $desc ?: null, $image, implode(',', $fac), $status, $id]);
                if ($old['image_url'] && $old['image_url'] !== $image) {
                    delete_upload($old['image_url']);
                }
                flash('success', 'แก้ไขสนามแล้ว');
            } else {
                q('INSERT INTO fields (code, name, sport_type, location, description, image_url, facilities, status) VALUES (?,?,?,?,?,?,?,?)',
                  [$code, $name, $sport, $loc ?: null, $desc ?: null, $image, implode(',', $fac), $status]);
                flash('success', 'เพิ่มสนามแล้ว');
            }
            redirect('admin/fields.php');
        } catch (PDOException $e) {
            if (($e->errorInfo[1] ?? 0) === 1062) {
                $err = 'รหัสสนามนี้มีอยู่แล้ว';
            } else {
                error_log('fields save: ' . $e->getMessage());
                $err = 'บันทึกไม่สำเร็จ กรุณาลองใหม่';
            }
            if ($image !== ($old['image_url'] ?? null)) {
                delete_upload($image);
            }
        }
    }
    flash('error', $err);
    redirect('admin/fields.php');
}

$fields = fetch_all('SELECT * FROM fields ORDER BY code');
$next = 'F' . str_pad((string)((int)fetch_val('SELECT COUNT(*) FROM fields') + 1), 3, '0', STR_PAD_LEFT);
while (fetch_val('SELECT COUNT(*) FROM fields WHERE code = ?', [$next])) {
    $next = 'F' . str_pad((string)((int)substr($next, 1) + 1), 3, '0', STR_PAD_LEFT);
}

shell_start('จัดการสนาม', 'fields', true);
?>
<div class="card" style="overflow:hidden">
  <div class="pad">
    <?php page_title('จัดการสนาม', '', '<button class="btn" type="button" data-field-add data-code="' . h($next) . '">+ เพิ่มสนาม</button>'); ?>
  </div>
  <div class="table-wrap">
    <table class="table">
      <thead><tr><th>รหัสสนาม</th><th>ชื่อสนาม</th><th>ประเภท</th><th>สถานที่</th><th>สถานะ</th><th>จัดการ</th></tr></thead>
      <tbody>
      <?php if (!$fields): ?><tr><td colspan="6" class="empty">ยังไม่มีสนาม</td></tr><?php endif; ?>
      <?php foreach ($fields as $f): ?>
        <tr class="pressable">
          <td><?= h($f['code']) ?></td><td><?= h($f['name']) ?></td><td><?= h($f['sport_type']) ?></td><td><?= h($f['location'] ?: '-') ?></td>
          <td><?= pill(FIELD_STATUS, $f['status']) ?></td>
          <td><div class="actions">
            <button type="button" class="icon-btn icon-primary" title="แก้ไข" data-field-edit
              data-id="<?= (int)$f['id'] ?>" data-code="<?= h($f['code']) ?>" data-name="<?= h($f['name']) ?>" data-sport="<?= h($f['sport_type']) ?>"
              data-location="<?= h($f['location']) ?>" data-description="<?= h($f['description']) ?>" data-status="<?= h($f['status']) ?>"
              data-image="<?= h($f['image_url']) ?>" data-image-preview="<?= h(img_url($f['image_url'])) ?>" data-facilities="<?= h($f['facilities']) ?>"><?= nav_icon('edit') ?></button>
            <form method="post" class="inline" data-confirm="ลบ &quot;<?= h($f['name']) ?>&quot;? การจองของสนามนี้จะถูกลบด้วย">
              <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$f['id'] ?>">
              <button class="icon-btn icon-bad" title="ลบ" type="submit">🗑</button>
            </form>
          </div></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<dialog id="fieldDialog" class="dlg">
  <form method="post" enctype="multipart/form-data" class="dlg-body">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="save"><input type="hidden" name="id" value="">
    <h3 data-field-title>เพิ่มสนาม</h3>
    <div class="two">
      <div><label>รหัสสนาม</label><input name="code" maxlength="10" required></div>
      <div><label>ประเภท</label><select name="sport_type"><?php foreach (SPORT_TYPES as $t): ?><option><?= h($t) ?></option><?php endforeach; ?></select></div>
    </div>
    <label>ชื่อสนาม</label><input name="name" maxlength="80" required>
    <label>สถานที่</label><input name="location" maxlength="120">
    <label>รายละเอียด</label><textarea name="description" rows="2" maxlength="500"></textarea>
    <label>สถานะ</label><select name="status"><?php foreach (FIELD_STATUS as $k => [$l]): ?><option value="<?= h($k) ?>"><?= h($l) ?></option><?php endforeach; ?></select>
    <label>สิ่งอำนวยความสะดวก</label>
    <div class="checks"><?php foreach (FACILITIES as $x): ?><label class="check"><input type="checkbox" name="facilities[]" value="<?= h($x) ?>"> <?= h($x) ?></label><?php endforeach; ?></div>
    <label>รูปภาพ (ลิงก์ หรืออัปโหลด)</label>
    <input name="image_url" placeholder="https://...">
    <div class="file-picker" style="margin-top:8px">
      <input class="file-input" id="fieldImageFile" type="file" name="image_file" accept="image/*" data-file-picker>
      <label class="file-label" for="fieldImageFile">Choose file</label>
      <button class="file-clear" type="button" data-file-clear aria-label="ล้างรูปภาพ" title="ล้างรูปภาพ" disabled>&times;</button>
    </div>
    <div data-field-preview></div>
    <div class="dlg-actions"><button type="button" class="btn-text" data-close-dialog>ยกเลิก</button><button class="btn" type="submit">บันทึก</button></div>
  </form>
</dialog>
<?php shell_end();
