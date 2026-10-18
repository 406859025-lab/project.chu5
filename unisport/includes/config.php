<?php
/**
 * ตั้งค่าระบบ — ค่าเริ่มต้นตรงกับ XAMPP (MySQL user: root, ไม่มีรหัสผ่าน)
 * ถ้าแก้รหัสผ่าน MySQL ใน XAMPP ให้มาแก้ DB_PASS ตรงนี้
 */
define('DB_HOST', '127.0.0.1');
define('DB_PORT', 3306);          // XAMPP ปกติใช้ 3306 (ถ้าพอร์ตชน เช่น ใช้ 3307 ให้แก้ตรงนี้)
define('DB_NAME', 'unisport');
define('DB_USER', 'root');
define('DB_PASS', '');

// โฟลเดอร์ของเว็บ เช่น '/unisport'  (null = ตรวจจับอัตโนมัติ)
define('BASE_URL_OVERRIDE', null);

define('APP_TIMEZONE', 'Asia/Bangkok');
define('APP_NAME', 'ระบบจองสนามกีฬา');

// รหัสผ่านชั้นที่ 2 สำหรับหน้า "เข้าสู่ระบบผู้ดูแล" (ตรวจฝั่งเซิร์ฟเวอร์) — ควรเปลี่ยนก่อนใช้งานจริง
define('ADMIN_ACCESS_CODE', '1234');

// XAMPP ไม่ได้ตั้งค่าส่งอีเมล จึงแสดงลิงก์รีเซ็ตรหัสผ่านบนหน้าจอแทน
// ตอนขึ้นเซิร์ฟเวอร์จริงให้ตั้งเป็น false (ระบบจะส่งอีเมลด้วย mail())
define('SHOW_RESET_LINK', true);

// เปิดแสดงข้อผิดพลาดละเอียดตอนพัฒนา (ตอนใช้งานจริงให้เป็น false)
define('APP_DEBUG', false);

// การจอง: เวลาเปิด-ปิดสนาม (ชั่วโมง) และขนาดไฟล์รูปสูงสุด
define('OPEN_HOUR', 8);
define('CLOSE_HOUR', 22);
define('MAX_UPLOAD_MB', 5);
