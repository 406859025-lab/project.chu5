<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

// ออกจากระบบด้วย POST + CSRF เท่านั้น (กันถูกลิงก์ภายนอกบังคับ logout)
if (is_post()) {
    csrf_guard();
    $to = post('to') === 'admin-login.php' ? 'admin-login.php' : 'login.php';
    logout_user();
    session_start();
    session_regenerate_id(true);
    flash('success', 'ออกจากระบบแล้ว');
    redirect($to);
}
redirect('index.php');
