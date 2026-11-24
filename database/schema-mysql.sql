-- ============================================================================
-- SIM PUSKESMAS AKELAMO — Skema MySQL untuk import via phpMyAdmin (cPanel)
-- ============================================================================
-- Alternatif TANPA web installer:
--   1. Buat database kosong di cPanel (MySQL® Databases) + user, beri ALL PRIVILEGES
--   2. Import file ini lewat phpMyAdmin (tab Import)
--   3. Atur .env (DB_CONNECTION=mysql, host/port/nama/user/password, APP_KEY, dsb.)
--      — sesuai panduan README bagian "Instalasi cPanel"
--   4. Login: adminakelamo / AkelamoR4hasia#2026  <<== SEGERA GANTI PASSWORD!
--
-- Catatan: file ini membuat struktur tabel PERSIS hasil `php artisan migrate`
-- beserta isi tabel `migrations` agar Laravel mengenalinya.
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------- users & sessions ----------------
CREATE TABLE IF NOT EXISTS `users` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `full_name` VARCHAR(150) NOT NULL,
  `username` VARCHAR(80) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` VARCHAR(20) NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `remember_token` VARCHAR(100) DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_username_unique` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sessions` (
  `id` VARCHAR(255) NOT NULL,
  `user_id` BIGINT UNSIGNED DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` TEXT DEFAULT NULL,
  `payload` LONGTEXT NOT NULL,
  `last_activity` INT NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------- cache ----------------
CREATE TABLE IF NOT EXISTS `cache` (
  `key` VARCHAR(255) NOT NULL,
  `value` MEDIUMTEXT NOT NULL,
  `expiration` INT NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cache_locks` (
  `key` VARCHAR(255) NOT NULL,
  `owner` VARCHAR(255) NOT NULL,
  `expiration` INT NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------- jobs (standar Laravel) ----------------
CREATE TABLE IF NOT EXISTS `jobs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `queue` VARCHAR(255) NOT NULL,
  `payload` LONGTEXT NOT NULL,
  `attempts` TINYINT UNSIGNED NOT NULL,
  `reserved_at` INT UNSIGNED DEFAULT NULL,
  `available_at` INT UNSIGNED NOT NULL,
  `created_at` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `job_batches` (
  `id` VARCHAR(255) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `total_jobs` INT NOT NULL,
  `pending_jobs` INT NOT NULL,
  `failed_jobs` INT NOT NULL,
  `failed_job_ids` LONGTEXT NOT NULL,
  `options` MEDIUMTEXT DEFAULT NULL,
  `cancelled_at` INT DEFAULT NULL,
  `created_at` INT NOT NULL,
  `finished_at` INT DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `failed_jobs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid` VARCHAR(255) NOT NULL,
  `connection` TEXT NOT NULL,
  `queue` TEXT NOT NULL,
  `payload` LONGTEXT NOT NULL,
  `exception` LONGTEXT NOT NULL,
  `failed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------- domain aplikasi ----------------
-- Kolom phone/address/medical_notes berisi CIPHERTEXT (enkripsi AES-256-GCM aplikasi).
CREATE TABLE IF NOT EXISTS `patients` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `medical_record_no` VARCHAR(40) NOT NULL,
  `full_name` VARCHAR(160) NOT NULL,
  `nik` VARCHAR(16) DEFAULT NULL,
  `birth_date` DATE DEFAULT NULL,
  `gender` VARCHAR(12) DEFAULT NULL,
  `phone` TEXT DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `medical_notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `patients_medical_record_no_unique` (`medical_record_no`),
  UNIQUE KEY `patients_nik_unique` (`nik`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `medicines` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(50) NOT NULL,
  `name` VARCHAR(180) NOT NULL,
  `unit` VARCHAR(50) NOT NULL DEFAULT 'Pcs',
  `stock` INT UNSIGNED NOT NULL DEFAULT 0,
  `min_stock` INT UNSIGNED NOT NULL DEFAULT 10,
  `expiry_date` DATE DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `medicines_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `employees` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(160) NOT NULL,
  `nip` VARCHAR(32) DEFAULT NULL,
  `position` VARCHAR(160) NOT NULL,
  `unit` VARCHAR(100) DEFAULT NULL,
  `phone` VARCHAR(30) DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `employees_nip_unique` (`nip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `doctors` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(160) NOT NULL,
  `specialty` VARCHAR(100) NOT NULL,
  `sip_number` VARCHAR(100) DEFAULT NULL,
  `phone` VARCHAR(30) DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `schedules` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `doctor_id` BIGINT UNSIGNED NOT NULL,
  `day` VARCHAR(10) NOT NULL,
  `time_start` TIME NOT NULL,
  `time_end` TIME NOT NULL,
  `room` VARCHAR(80) DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `schedules_doctor_id_foreign` (`doctor_id`),
  CONSTRAINT `schedules_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `finances` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `transaction_date` DATE NOT NULL,
  `type` VARCHAR(16) NOT NULL,
  `category` VARCHAR(120) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `amount` DECIMAL(16,2) NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `surveys` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `service` VARCHAR(100) NOT NULL,
  `rating` TINYINT UNSIGNED NOT NULL,
  `suggestion` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `surveys_created_at_index` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `audit_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `actor_id` BIGINT UNSIGNED DEFAULT NULL,
  `action` VARCHAR(30) NOT NULL,
  `entity` VARCHAR(40) NOT NULL,
  `record_id` BIGINT UNSIGNED DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `audit_logs_created_at_index` (`created_at`),
  KEY `audit_logs_actor_id_foreign` (`actor_id`),
  CONSTRAINT `audit_logs_actor_id_foreign` FOREIGN KEY (`actor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------- registry migrasi Laravel ----------------
CREATE TABLE IF NOT EXISTS `migrations` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `migration` VARCHAR(255) NOT NULL,
  `batch` INT NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT * FROM (SELECT '0001_01_01_000000_create_users_table' AS m, 1 AS b
  UNION ALL SELECT '0001_01_01_000001_create_cache_table', 1
  UNION ALL SELECT '0001_01_01_000002_create_jobs_table', 1
  UNION ALL SELECT '2026_11_20_000100_create_puskesmas_tables', 1
  UNION ALL SELECT '2026_11_21_000000_create_integration_tables', 1) AS src
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_11_21_000000_create_integration_tables');

-- ---------------- TABEL INTEGRASI LAYANAN (2026-11-21) ----------------
-- users.email + notifikasi lonceng + pengaturan SMTP + resep obat
-- (jalur upgrade dari versi lama: gunakan /install.php atau Terminal → migrate)
ALTER TABLE `users` ADD COLUMN `email` VARCHAR(150) NULL UNIQUE AFTER `username`;

CREATE TABLE IF NOT EXISTS `app_notifications` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `type` VARCHAR(40) NOT NULL,
  `title` VARCHAR(170) NOT NULL,
  `body` TEXT NULL,
  `url` VARCHAR(255) NULL,
  `actor_id` BIGINT UNSIGNED NULL,
  `read_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `app_notifications_type_index` (`type`),
  INDEX `app_notifications_actor_id_index` (`actor_id`),
  INDEX `app_notifications_read_at_index` (`read_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `settings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `key` VARCHAR(100) NOT NULL,
  `value` TEXT NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `settings_key_unique` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `prescriptions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `patient_id` BIGINT UNSIGNED NOT NULL,
  `doctor_id` BIGINT UNSIGNED NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'aktif',
  `notes` TEXT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `prescriptions_patient_id_index` (`patient_id`),
  INDEX `prescriptions_doctor_id_index` (`doctor_id`),
  INDEX `prescriptions_user_id_index` (`user_id`),
  CONSTRAINT `fk_rx_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rx_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_rx_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `prescription_items` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `prescription_id` BIGINT UNSIGNED NOT NULL,
  `medicine_id` BIGINT UNSIGNED NOT NULL,
  `dose` VARCHAR(140) NOT NULL,
  `quantity` INT UNSIGNED NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `prescription_items_prescription_id_index` (`prescription_id`),
  INDEX `prescription_items_medicine_id_index` (`medicine_id`),
  CONSTRAINT `fk_rxi_rx` FOREIGN KEY (`prescription_id`) REFERENCES `prescriptions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rxi_med` FOREIGN KEY (`medicine_id`) REFERENCES `medicines` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------- AKUN ADMIN AWAL ----------------
-- Username : adminakelamo
-- Password : AkelamoR4hasia#2026   <<== WAJIB DIGANTI SETELAH LOGIN PERTAMA!
--           (Menu: Pengguna & Akses → Reset Sandi)
INSERT INTO `users` (`full_name`, `username`, `password`, `role`, `is_active`, `created_at`, `updated_at`)
SELECT 'Administrator Akelamo', 'adminakelamo',
       '$2y$12$T6qPTO.Dz/GAGpiNFW8.8ef/4rKj3yDIJgGZfbTmLHBhXyG.x7.d.', 'admin', 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `users` WHERE `username` = 'adminakelamo');

SET FOREIGN_KEY_CHECKS = 1;
