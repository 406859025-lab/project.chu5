# ระบบจองสนามกีฬา มหาวิทยาลัย (PHP + MySQL สำหรับ XAMPP)

## วิธีติดตั้ง (5 ขั้นตอน)
1. ติดตั้ง XAMPP (PHP 8.0 ขึ้นไป) แล้วเปิด **XAMPP Control Panel** กด **Start** ที่ **Apache** และ **MySQL**
2. คัดลอกโฟลเดอร์ `unisport` ไปไว้ใน `C:\xampp\htdocs\`
3. สร้างฐานข้อมูล เลือกวิธีใดวิธีหนึ่ง
   - เปิด http://localhost/unisport/install.php แล้วกด **เริ่มติดตั้ง** *(ง่ายที่สุด)*
   - หรือเปิด http://localhost/phpmyadmin → แท็บ **Import** → เลือกไฟล์ `database.sql`
4. เปิด http://localhost/unisport/
5. (ใช้งานจริง) ลบ `install.php` และเปลี่ยนรหัสผ่านบัญชีตัวอย่างด้านล่าง

## บัญชีตัวอย่าง
| ประเภท | อีเมล | รหัสผ่าน |
|---|---|---|
| ผู้ดูแล | admin@unisport.local | admin1234 (หน้า `/admin-login.php` ต้องกรอก "รหัสผู้ดูแล" = `1234`) |
| สมาชิก | student@unisport.local | student1234 |

## การตั้งค่า — `includes/config.php`
- `DB_HOST / DB_PORT / DB_USER / DB_PASS / DB_NAME` — ค่าเริ่มต้นตรงกับ XAMPP (root ไม่มีรหัสผ่าน, พอร์ต 3306)
- `ADMIN_ACCESS_CODE` — รหัสผู้ดูแลชั้นที่ 2 (เปลี่ยนก่อนใช้งานจริง)
- `SHOW_RESET_LINK` — `true` = แสดงลิงก์รีเซ็ตรหัสผ่านบนหน้าจอ (XAMPP ไม่มีเมลเซิร์ฟเวอร์); ขึ้นเซิร์ฟเวอร์จริงตั้งเป็น `false`

## ปัญหาที่พบบ่อย
- **"เชื่อมต่อฐานข้อมูลไม่ได้"** — ยังไม่ได้ Start MySQL, หรือพอร์ตชน (แก้ `DB_PORT`), หรือยังไม่ได้ติดตั้งฐานข้อมูล
- **หน้าเว็บไม่มีรูปสนาม** — รูปตัวอย่างโหลดจากอินเทอร์เน็ต (Unsplash) ถ้าออฟไลน์จะแสดงพื้นหลังสำรองแทน; ผู้ดูแลอัปโหลดรูปเองได้ที่หน้า "จัดการสนาม"
- **อัปโหลดรูปไม่ได้** — ตรวจว่า `upload_max_filesize` ใน php.ini (XAMPP → Config → PHP) ไม่ต่ำกว่า 5M

## โครงสร้าง
```
index.php login.php register.php ...   หน้าสมาชิก/สาธารณะ
admin/                                 หน้าผู้ดูแล
includes/                              แกนระบบ (config, DB, auth, การจอง) — ห้ามเข้าผ่านเว็บ
assets/                                CSS / JS
uploads/                               รูปที่อัปโหลด (ห้ามรันสคริปต์)
database.sql                           โครงสร้างตาราง + ข้อมูลเริ่มต้น
```
