<?php
declare(strict_types=1);

/* ================================================================== */
/*  กติกาและการตรวจสอบการจอง (ทำฝั่งเซิร์ฟเวอร์ทั้งหมด)                     */
/* ================================================================== */

/** ชั่วโมงที่เลือกได้ในแบบฟอร์ม เช่น 08:00 … 22:00 */
function booking_hours(): array
{
    $o = [];
    for ($h = OPEN_HOUR; $h <= CLOSE_HOUR; $h++) {
        $o[] = sprintf('%02d:00', $h);
    }
    return $o;
}

/**
 * ตรวจและทำความสะอาดข้อมูลการจอง
 * @return array{0:?array,1:?string} [data, error]
 */
function parse_booking_input(array $in, bool $isAdmin, bool $requireListedPurpose): array
{
    $date  = trim((string)($in['date'] ?? ''));
    $start = trim((string)($in['start'] ?? ''));
    $end   = trim((string)($in['end'] ?? ''));
    $purpose = trim((string)($in['purpose'] ?? ''));
    $notes   = trim((string)($in['notes'] ?? ''));
    $count   = trim((string)($in['participants'] ?? ''));

    if ($date === '' || $start === '' || $end === '' || $purpose === '') {
        return [null, 'กรุณากรอกข้อมูลให้ครบ'];
    }
    $dt = DateTime::createFromFormat('!Y-m-d', $date);
    if (!$dt || $dt->format('Y-m-d') !== $date) {
        return [null, 'วันที่ไม่ถูกต้อง'];
    }
    $timeRe = '/^([01]\d|2[0-3]):[0-5]\d$/';
    if (!preg_match($timeRe, $start) || !preg_match($timeRe, $end)) {
        return [null, 'เวลาไม่ถูกต้อง'];
    }
    $open  = sprintf('%02d:00', OPEN_HOUR);
    $close = sprintf('%02d:00', CLOSE_HOUR);
    if ($start < $open || $end > $close) {
        return [null, "สนามเปิดให้บริการ {$open} - {$close} น."];
    }
    if ($end <= $start) {
        return [null, 'เวลาสิ้นสุดต้องหลังเวลาเริ่ม'];
    }
    if (!ctype_digit($count) || (int)$count < 1 || (int)$count > 200) {
        return [null, 'จำนวนผู้ใช้งานต้องอยู่ระหว่าง 1 - 200 คน'];
    }
    if ($requireListedPurpose && !in_array($purpose, PURPOSES, true)) {
        return [null, 'กรุณาเลือกวัตถุประสงค์จากรายการ'];
    }
    if (mb_strlen($purpose) > 100) {
        return [null, 'วัตถุประสงค์ยาวเกินไป'];
    }
    if (mb_strlen($notes) > 500) {
        return [null, 'รายละเอียดเพิ่มเติมยาวเกิน 500 ตัวอักษร'];
    }
    if (!$isAdmin) {
        if ($date < today()) {
            return [null, 'ไม่สามารถจองวันที่ผ่านมาแล้ว'];
        }
        if ($date === today() && $start <= date('H:i')) {
            return [null, 'เวลาเริ่มของวันนี้ผ่านไปแล้ว กรุณาเลือกเวลาถัดไป'];
        }
    }
    return [[
        'date' => $date, 'start' => $start, 'end' => $end,
        'participants' => (int)$count, 'purpose' => $purpose,
        'notes' => $notes !== '' ? $notes : null,
    ], null];
}

/** มีการจองทับช่วงเวลานี้หรือไม่ (นับเฉพาะ pending/approved ตามค่าเริ่มต้น) */
function booking_clash(int $fieldId, string $date, string $start, string $end, int $excludeId = 0, array $statuses = ['pending', 'approved']): ?array
{
    $in = implode(',', array_fill(0, count($statuses), '?'));
    return fetch_one(
        "SELECT id, code FROM bookings
          WHERE field_id = ? AND booking_date = ? AND status IN ($in)
            AND start_time < ? AND end_time > ? AND id <> ? LIMIT 1",
        array_merge([$fieldId, $date], $statuses, [$end, $start, $excludeId])
    );
}

/** สร้างการจอง — ล็อกแถวสนามใน transaction กันสองคนจองชนกันพร้อมกัน */
function create_booking(int $userId, int $fieldId, array $d): array
{
    $pdo = db();
    try {
        $pdo->beginTransaction();
        $f = fetch_one('SELECT id, status FROM fields WHERE id = ? FOR UPDATE', [$fieldId]);
        if (!$f) {
            $pdo->rollBack();
            return [false, 'ไม่พบสนามที่เลือก'];
        }
        if ($f['status'] !== 'available') {
            $pdo->rollBack();
            return [false, 'สนามนี้ยังไม่เปิดให้จองในขณะนี้'];
        }
        if (booking_clash($fieldId, $d['date'], $d['start'], $d['end'])) {
            $pdo->rollBack();
            return [false, 'ช่วงเวลานี้มีผู้จองแล้ว กรุณาเลือกเวลาอื่น'];
        }
        q('INSERT INTO bookings (user_id, field_id, booking_date, start_time, end_time, participants, purpose, notes)
           VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
          [$userId, $fieldId, $d['date'], $d['start'], $d['end'], $d['participants'], $d['purpose'], $d['notes']]);
        $id = (int)$pdo->lastInsertId();
        q('UPDATE bookings SET code = ? WHERE id = ?', [sprintf('BK%04d', $id), $id]);
        $pdo->commit();
        return [true, $id];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('create_booking: ' . $e->getMessage());
        return [false, 'เกิดข้อผิดพลาดในการบันทึก กรุณาลองใหม่'];
    }
}

/** แก้ไขการจอง (ตรวจเวลาทับซ้อนทุกครั้ง) */
function update_booking(array $b, array $d): array
{
    $pdo = db();
    try {
        $pdo->beginTransaction();
        fetch_one('SELECT id FROM fields WHERE id = ? FOR UPDATE', [(int)$b['field_id']]);
        if (in_array($b['status'], ['pending', 'approved'], true)
            && booking_clash((int)$b['field_id'], $d['date'], $d['start'], $d['end'], (int)$b['id'])) {
            $pdo->rollBack();
            return [false, 'ช่วงเวลานี้มีผู้จองแล้ว กรุณาเลือกเวลาอื่น'];
        }
        q('UPDATE bookings SET booking_date = ?, start_time = ?, end_time = ?, participants = ?, purpose = ?, notes = ? WHERE id = ?',
          [$d['date'], $d['start'], $d['end'], $d['participants'], $d['purpose'], $d['notes'], (int)$b['id']]);
        $pdo->commit();
        return [true, null];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('update_booking: ' . $e->getMessage());
        return [false, 'เกิดข้อผิดพลาดในการบันทึก กรุณาลองใหม่'];
    }
}

/** เปลี่ยนสถานะ — ตรวจสิทธิ์และลำดับสถานะที่อนุญาต */
function change_booking_status(array $b, string $to, bool $admin): array
{
    $allowed = $admin
        ? ['pending' => ['approved', 'rejected', 'cancelled'], 'approved' => ['completed', 'cancelled']]
        : ['pending' => ['cancelled'], 'approved' => ['cancelled']];
    if (!in_array($to, $allowed[$b['status']] ?? [], true)) {
        return [false, 'ไม่สามารถเปลี่ยนสถานะนี้ได้'];
    }
    $pdo = db();
    try {
        $pdo->beginTransaction();
        fetch_one('SELECT id FROM fields WHERE id = ? FOR UPDATE', [(int)$b['field_id']]);
        if ($to === 'approved') {
            $f = fetch_one('SELECT status FROM fields WHERE id = ?', [(int)$b['field_id']]);
            if (!$f || $f['status'] !== 'available') {
                $pdo->rollBack();
                return [false, 'สนามนี้ปิดให้บริการอยู่ จึงอนุมัติไม่ได้'];
            }
            if (booking_clash((int)$b['field_id'], $b['booking_date'], $b['start_time'], $b['end_time'], (int)$b['id'], ['approved'])) {
                $pdo->rollBack();
                return [false, 'มีการจองที่อนุมัติแล้วทับช่วงเวลานี้ จึงอนุมัติไม่ได้'];
            }
        }
        q('UPDATE bookings SET status = ? WHERE id = ?', [$to, (int)$b['id']]);
        $pdo->commit();
        return [true, null];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('change_booking_status: ' . $e->getMessage());
        return [false, 'เกิดข้อผิดพลาด กรุณาลองใหม่'];
    }
}

/* ================================================================== */
/*  รับคำสั่ง POST ของตารางการจอง (ใช้ร่วมกันทั้งหน้าสมาชิกและผู้ดูแล)          */
/* ================================================================== */
function handle_booking_post(bool $admin): void
{
    if (!is_post()) {
        return;
    }
    csrf_guard();
    $u    = $admin ? require_admin() : require_login();
    $back = safe_path(post('return'), $admin ? 'admin/bookings.php' : 'bookings.php');
    $id   = (int)post('id');
    $b    = fetch_one('SELECT * FROM bookings WHERE id = ?', [$id]);

    if (!$b || (!$admin && (int)$b['user_id'] !== (int)$u['id'])) {
        flash('error', 'ไม่พบรายการจองนี้');
        redirect($back);
    }

    switch (post('action')) {
        case 'status':
            [$ok, $err] = change_booking_status($b, post('to'), $admin);
            $ok ? flash('success', 'อัปเดตสถานะแล้ว') : flash('error', (string)$err);
            break;

        case 'delete':
            if (!$admin) {
                flash('error', 'ไม่มีสิทธิ์ลบรายการจอง');
                break;
            }
            q('DELETE FROM bookings WHERE id = ?', [$id]);
            flash('success', 'ลบรายการจองแล้ว');
            break;

        case 'edit':
            if (!$admin && $b['status'] !== 'pending') {
                flash('error', 'แก้ไขได้เฉพาะการจองที่ยังรออนุมัติ');
                break;
            }
            [$d, $err] = parse_booking_input($_POST, $admin, false);
            if (!$d) {
                flash('error', (string)$err);
                break;
            }
            [$ok, $err] = update_booking($b, $d);
            $ok ? flash('success', 'บันทึกการแก้ไขแล้ว') : flash('error', (string)$err);
            break;

        default:
            flash('error', 'คำสั่งไม่ถูกต้อง');
    }
    redirect($back);
}

/* ================================================================== */
/*  ตารางการจอง + ตัวกรอง                                                */
/*  $mode: 'pending' (การรออนุมัติ) | 'history' (ประวัติ) | 'admin'        */
/* ================================================================== */
function booking_action_form(array $b, string $action, string $label, string $icon, string $tone, string $to = '', string $confirm = ''): string
{
    return '<form method="post" class="inline" ' . ($confirm !== '' ? 'data-confirm="' . h($confirm) . '"' : '') . '>'
        . csrf_field()
        . '<input type="hidden" name="return" value="' . h($_SERVER['REQUEST_URI'] ?? '') . '">'
        . '<input type="hidden" name="id" value="' . (int)$b['id'] . '">'
        . '<input type="hidden" name="action" value="' . h($action) . '">'
        . ($to !== '' ? '<input type="hidden" name="to" value="' . h($to) . '">' : '')
        . '<button class="icon-btn icon-' . h($tone) . '" type="submit" title="' . h($label) . '" aria-label="' . h($label) . '">' . $icon . '</button></form>';
}

function render_bookings(string $mode): void
{
    $admin = $mode === 'admin';
    $u     = current_user();

    $defaultStatus = $mode === 'pending' ? 'pending' : 'all';
    $status = get('status', $defaultStatus);
    if ($status !== 'all' && !isset(BOOKING_STATUS[$status])) {
        $status = $defaultStatus;
    }
    $from = preg_match('/^\d{4}-\d{2}-\d{2}$/', get('from')) ? get('from') : '';
    $to   = preg_match('/^\d{4}-\d{2}-\d{2}$/', get('to')) ? get('to') : '';

    $where = [];
    $args  = [];
    if (!$admin) {
        $where[] = 'b.user_id = ?';
        $args[]  = (int)$u['id'];
    }
    if ($status !== 'all') {
        $where[] = 'b.status = ?';
        $args[]  = $status;
    }
    if ($from !== '') {
        $where[] = 'b.booking_date >= ?';
        $args[]  = $from;
    }
    if ($to !== '') {
        $where[] = 'b.booking_date <= ?';
        $args[]  = $to;
    }
    $rows = fetch_all(
        'SELECT b.*, f.name AS field_name, CONCAT(u.first_name, " ", u.last_name) AS who
           FROM bookings b
           JOIN fields f ON f.id = b.field_id
           JOIN users  u ON u.id = b.user_id'
        . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
        . ' ORDER BY b.booking_date DESC, b.start_time ASC, b.id DESC LIMIT 500',
        $args
    );
    $cols = $admin ? 9 : 8;
    ?>
<div class="card pad">
  <form method="get" class="filters">
    <div><label>ตั้งแต่วันที่</label><input type="date" name="from" value="<?= h($from) ?>"></div>
    <div><label>ถึงวันที่</label><input type="date" name="to" value="<?= h($to) ?>"></div>
    <div><label>สถานะ</label>
      <select name="status">
        <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>สถานะทั้งหมด</option>
        <?php foreach (BOOKING_STATUS as $k => [$label]): ?>
          <option value="<?= h($k) ?>" <?= $status === $k ? 'selected' : '' ?>><?= h($label) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <button class="btn" type="submit">ค้นหา</button>
  </form>

  <div class="table-wrap">
    <table class="table">
      <thead><tr>
        <th>เลขที่การจอง</th><?php if ($admin): ?><th>ผู้จอง</th><?php endif; ?>
        <th>สนาม</th><th>วันที่</th><th>เวลา</th><th>จำนวน</th><th>วัตถุประสงค์</th><th>สถานะ</th><th>จัดการ</th>
      </tr></thead>
      <tbody>
      <?php if (!$rows): ?>
        <tr><td colspan="<?= $cols ?>" class="empty">ไม่พบรายการ</td></tr>
      <?php endif; ?>
      <?php foreach ($rows as $b): ?>
        <tr class="pressable">
          <td><?= h($b['code']) ?></td>
          <?php if ($admin): ?><td><?= h($b['who']) ?></td><?php endif; ?>
          <td><?= h($b['field_name']) ?></td>
          <td><?= h(thai_date($b['booking_date'])) ?></td>
          <td><?= h(hm($b['start_time'])) ?>-<?= h(hm($b['end_time'])) ?></td>
          <td><?= (int)$b['participants'] ?></td>
          <td><?= h($b['purpose'] ?: '-') ?></td>
          <td><?= pill(BOOKING_STATUS, $b['status']) ?></td>
          <td>
            <div class="actions">
            <?php
            $editBtn = '<button type="button" class="icon-btn icon-primary" title="แก้ไข" aria-label="แก้ไข" data-edit-booking'
                . ' data-id="' . (int)$b['id'] . '" data-code="' . h($b['code']) . '" data-date="' . h($b['booking_date']) . '"'
                . ' data-start="' . h(hm($b['start_time'])) . '" data-end="' . h(hm($b['end_time'])) . '"'
                . ' data-participants="' . (int)$b['participants'] . '" data-purpose="' . h($b['purpose']) . '"'
                . ' data-notes="' . h($b['notes']) . '">' . nav_icon('edit') . '</button>';
            if ($admin) {
                if ($b['status'] === 'pending') {
                    echo booking_action_form($b, 'status', 'อนุมัติ', '✔', 'ok', 'approved');
                    echo booking_action_form($b, 'status', 'ไม่อนุมัติ', '✖', 'bad', 'rejected');
                }
                if ($b['status'] === 'approved') {
                    echo booking_action_form($b, 'status', 'ทำเครื่องหมายสำเร็จ', '✔', 'ok', 'completed');
                }
                echo $editBtn;
                echo booking_action_form($b, 'delete', 'ลบ', '🗑', 'bad', '', 'ลบรายการจองนี้?');
            } elseif ($b['status'] === 'pending') {
                echo $editBtn;
                echo booking_action_form($b, 'status', 'ยกเลิก', nav_icon('cancel'), 'bad', 'cancelled', 'ยกเลิกการจองนี้?');
            } elseif ($b['status'] === 'approved') {
                echo booking_action_form($b, 'status', 'ยกเลิก', nav_icon('cancel'), 'bad', 'cancelled', 'ยกเลิกการจองนี้?');
            } else {
                echo '<span class="muted">-</span>';
            }
            ?>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if (count($rows) >= 500): ?><p class="muted small">แสดง 500 รายการล่าสุด — ใช้ตัวกรองเพื่อจำกัดผลลัพธ์</p><?php endif; ?>
</div>

<dialog id="editBooking" class="dlg">
  <form method="post" class="dlg-body">
    <?= csrf_field() ?>
    <input type="hidden" name="return" value="<?= h($_SERVER['REQUEST_URI'] ?? '') ?>">
    <input type="hidden" name="action" value="edit">
    <input type="hidden" name="id" value="">
    <h3>แก้ไขการจอง <span data-fill="code"></span></h3>
    <label>วันที่</label>
    <input type="date" name="date" required <?= $admin ? '' : 'min="' . h(today()) . '"' ?>>
    <div class="two">
      <div><label>เวลาเริ่ม</label><input type="time" name="start" step="1800" min="<?= sprintf('%02d:00', OPEN_HOUR) ?>" max="<?= sprintf('%02d:00', CLOSE_HOUR) ?>" required></div>
      <div><label>เวลาสิ้นสุด</label><input type="time" name="end" step="1800" min="<?= sprintf('%02d:00', OPEN_HOUR) ?>" max="<?= sprintf('%02d:00', CLOSE_HOUR) ?>" required></div>
    </div>
    <label>จำนวนผู้ใช้งาน</label>
    <input type="number" name="participants" min="1" max="200" required>
    <label>วัตถุประสงค์</label>
    <input name="purpose" maxlength="100" required placeholder="วัตถุประสงค์">
    <label>รายละเอียดเพิ่มเติม</label>
    <textarea name="notes" rows="2" maxlength="500"></textarea>
    <div class="dlg-actions">
      <button type="button" class="btn-text" data-close-dialog>ยกเลิก</button>
      <button class="btn" type="submit">บันทึก</button>
    </div>
  </form>
</dialog>
<?php
}
