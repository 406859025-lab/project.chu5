<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';
require __DIR__ . '/includes/bookings.php';
require_login();
handle_booking_post(false);

shell_start('การจองของฉัน', 'bookings');
page_title('จัดการการจอง', 'การจองของฉัน (การรออนุมัติ)');
render_bookings('pending');
shell_end();
