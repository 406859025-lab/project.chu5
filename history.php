<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';
require __DIR__ . '/includes/bookings.php';
require_login();
handle_booking_post(false);

shell_start('ประวัติการจอง', 'history');
page_title('ประวัติการจอง');
render_bookings('history');
shell_end();
