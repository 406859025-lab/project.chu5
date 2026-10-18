<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/layout.php';
require __DIR__ . '/../includes/bookings.php';
require_admin();
handle_booking_post(true);

shell_start('รายการจองทั้งหมด', 'bookings', true);
page_title('รายการจอง', 'อนุมัติ / ไม่อนุมัติ / แก้ไขการจองของสมาชิก');
render_bookings('admin');
shell_end();
