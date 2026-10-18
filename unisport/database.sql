-- ============================================================
--  ระบบจองสนามกีฬา มหาวิทยาลัย — ฐานข้อมูล MySQL / MariaDB (XAMPP)
--  นำเข้าผ่าน phpMyAdmin (แท็บ Import) หรือเปิด http://localhost/unisport/install.php
-- ============================================================

CREATE DATABASE IF NOT EXISTS `unisport`
  DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `unisport`;

SET NAMES utf8mb4;

-- ---------- ผู้ใช้ ----------
CREATE TABLE IF NOT EXISTS `users` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `member_code`   VARCHAR(30)  DEFAULT NULL,
  `first_name`    VARCHAR(60)  NOT NULL,
  `last_name`     VARCHAR(60)  NOT NULL DEFAULT '',
  `email`         VARCHAR(255) NOT NULL,
  `username`      VARCHAR(30)  DEFAULT NULL,
  `phone`         VARCHAR(20)  DEFAULT NULL,
  `avatar_url`    VARCHAR(500) DEFAULT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `role`          ENUM('user','admin') NOT NULL DEFAULT 'user',
  `created_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`),
  UNIQUE KEY `uq_users_username` (`username`),
  KEY `idx_users_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- โทเคนรีเซ็ตรหัสผ่าน ----------
CREATE TABLE IF NOT EXISTS `password_resets` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    INT UNSIGNED NOT NULL,
  `token_hash` CHAR(64) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `used_at`    DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_reset_token` (`token_hash`),
  CONSTRAINT `fk_reset_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- บันทึกการล็อกอินผิด (กันเดารหัสผ่าน) ----------
CREATE TABLE IF NOT EXISTS `login_attempts` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ip`         VARCHAR(45) NOT NULL,
  `identity`   VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_attempt_ip` (`ip`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- สนามกีฬา ----------
CREATE TABLE IF NOT EXISTS `fields` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code`        VARCHAR(10)  NOT NULL,
  `name`        VARCHAR(80)  NOT NULL,
  `sport_type`  VARCHAR(40)  NOT NULL,
  `location`    VARCHAR(120) DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `image_url`   VARCHAR(500) DEFAULT NULL,
  `facilities`  VARCHAR(255) NOT NULL DEFAULT '',   -- คั่นด้วยจุลภาค เช่น ห้องน้ำ,ที่จอดรถ
  `status`      ENUM('available','unavailable','maintenance') NOT NULL DEFAULT 'available',
  `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_fields_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- การจอง ----------
CREATE TABLE IF NOT EXISTS `bookings` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code`         VARCHAR(12) DEFAULT NULL,           -- BK0001 สร้างจาก id หลังบันทึก
  `user_id`      INT UNSIGNED NOT NULL,
  `field_id`     INT UNSIGNED NOT NULL,
  `booking_date` DATE NOT NULL,
  `start_time`   TIME NOT NULL,
  `end_time`     TIME NOT NULL,
  `participants` INT UNSIGNED NOT NULL DEFAULT 1,
  `purpose`      VARCHAR(100) DEFAULT NULL,
  `notes`        VARCHAR(500) DEFAULT NULL,
  `status`       ENUM('pending','approved','rejected','cancelled','completed') NOT NULL DEFAULT 'pending',
  `created_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_bookings_code` (`code`),
  KEY `idx_bookings_slot` (`field_id`, `booking_date`, `status`),
  KEY `idx_bookings_user` (`user_id`, `booking_date`),
  CONSTRAINT `fk_bookings_user`  FOREIGN KEY (`user_id`)  REFERENCES `users`(`id`)  ON DELETE CASCADE,
  CONSTRAINT `fk_bookings_field` FOREIGN KEY (`field_id`) REFERENCES `fields`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- รายงานปัญหา ----------
CREATE TABLE IF NOT EXISTS `reports` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`      INT UNSIGNED NOT NULL,
  `field_id`     INT UNSIGNED DEFAULT NULL,
  `problem_type` VARCHAR(60) NOT NULL,
  `details`      VARCHAR(1000) DEFAULT NULL,
  `found_date`   DATE DEFAULT NULL,
  `found_time`   TIME DEFAULT NULL,
  `photo_url`    VARCHAR(500) DEFAULT NULL,
  `status`       ENUM('open','in_progress','resolved') NOT NULL DEFAULT 'open',
  `created_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_reports_status` (`status`, `created_at`),
  CONSTRAINT `fk_reports_user`  FOREIGN KEY (`user_id`)  REFERENCES `users`(`id`)  ON DELETE CASCADE,
  CONSTRAINT `fk_reports_field` FOREIGN KEY (`field_id`) REFERENCES `fields`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  ข้อมูลเริ่มต้น
-- ============================================================

-- สนามตัวอย่าง 5 สนาม (ไม่ซ้ำถ้านำเข้าซ้ำ)
INSERT IGNORE INTO `fields` (`code`,`name`,`sport_type`,`location`,`description`,`facilities`,`status`,`image_url`) VALUES
('F001','สนามฟุตบอล 1','ฟุตบอล','อาคารกีฬากลาง','สนามหญ้าจริง ขนาดมาตรฐาน เหมาะสำหรับการแข่งขันและกิจกรรมต่างๆ','ห้องน้ำ,ที่จอดรถ,ไฟส่องสว่าง,อุปกรณ์กีฬา','available','https://images.unsplash.com/photo-1575361204480-aadea25e6e68?w=800'),
('F002','สนามบาสเกตบอล','บาสเกตบอล','โรงยิม 1','สนามในร่ม พื้นไม้มาตรฐาน','ห้องน้ำ,ไฟส่องสว่าง','available','https://images.unsplash.com/photo-1546519638-68e109498ffc?w=800'),
('F003','สนามวอลเลย์บอล','วอลเลย์บอล','โรงยิม 2','สนามในร่ม พร้อมตาข่ายมาตรฐาน','ห้องน้ำ,อุปกรณ์กีฬา','unavailable','https://images.unsplash.com/photo-1612872087720-bb876e2e67d1?w=800'),
('F004','สนามเทนนิส','เทนนิส','ลานกีฬากลางแจ้ง','สนามฮาร์ดคอร์ต 2 คอร์ต','ที่จอดรถ,ไฟส่องสว่าง','available','https://images.unsplash.com/photo-1595435934249-5df7ed86e1c0?w=800'),
('F005','สนามแบดมินตัน','แบดมินตัน','โรงยิม 3','สนามในร่ม 4 คอร์ต','ห้องน้ำ','maintenance','https://images.unsplash.com/photo-1626224583764-f87db24ac4ea?w=800');

-- บัญชีตัวอย่าง (ควรเปลี่ยนรหัสผ่านหรือลบทิ้งก่อนใช้งานจริง)
--   ผู้ดูแล : admin@unisport.local    รหัสผ่าน admin1234   (รหัสผู้ดูแลหน้า admin-login = ค่า ADMIN_ACCESS_CODE ใน includes/config.php)
--   สมาชิก : student@unisport.local   รหัสผ่าน student1234
INSERT IGNORE INTO `users` (`member_code`,`first_name`,`last_name`,`email`,`username`,`password_hash`,`role`) VALUES
('025','ผู้ดูแล','ระบบ','admin@unisport.local','admin','$2y$10$vtRMH5/jzEcQxqrGBdaENezpkTA2oSwo.2D5uWRXLb7O4uMN8HK/e','admin'),
('6500001','สมาชิก','ทดลอง','student@unisport.local','student','$2y$10$ndfekZnvveo8Fk9LOCcpfu9BgqiMOUv1E9qerxyclnR.i3I/jLe4G','user');
