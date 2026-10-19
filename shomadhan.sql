-- =====================================================================
-- Shomadhan database
--   Accounts : admins, employees, users
--   Home page: banners, popups, services, sub_services, notifications (admin sets these, users see them)
--   Welcome page: settings (the coupon picture)
-- XAMPP: http://localhost/phpmyadmin -> Import -> choose this file -> Go
-- The first line deletes the OLD shomadhan database (if any) and makes it again,
-- so you can import this file again any time. (Old data will be deleted.)
-- Passwords are plain text (no hashing), as asked.
-- =====================================================================

DROP DATABASE IF EXISTS `shomadhan`;
CREATE DATABASE `shomadhan` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `shomadhan`;
SET NAMES utf8mb4;

-- Columns (same in all 3 tables):
--   id, name, mobile, email, password  -> account and login (mobile + password)
--   status                             -> active = can log in, inactive / blocked = can't
--   remember_token, remember_expires   -> "Remember me" (30 days)
--   otp, otp_expires, otp_attempts     -> forgot password OTP (10 minutes, max 5 wrong tries)
-- users also has area and gender (from the register form).

-- 1) ADMINS
CREATE TABLE `admins` (
  `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`             VARCHAR(50)  NOT NULL,
  `mobile`           CHAR(11)     NOT NULL,
  `email`            VARCHAR(60)  NOT NULL,
  `password`         VARCHAR(128) NOT NULL,
  `status`           ENUM('active','inactive','blocked') NOT NULL DEFAULT 'active',
  `remember_token`   CHAR(64)     NULL DEFAULT NULL,
  `remember_expires` DATETIME     NULL DEFAULT NULL,
  `otp`              CHAR(6)      NULL DEFAULT NULL,
  `otp_expires`      DATETIME     NULL DEFAULT NULL,
  `otp_attempts`     TINYINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_admins_mobile` (`mobile`),
  UNIQUE KEY `uq_admins_email`  (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2) EMPLOYEES
CREATE TABLE `employees` (
  `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`             VARCHAR(50)  NOT NULL,
  `mobile`           CHAR(11)     NOT NULL,
  `email`            VARCHAR(60)  NULL DEFAULT NULL,
  `password`         VARCHAR(128) NOT NULL,
  `status`           ENUM('active','inactive','blocked') NOT NULL DEFAULT 'active',
  `remember_token`   CHAR(64)     NULL DEFAULT NULL,
  `remember_expires` DATETIME     NULL DEFAULT NULL,
  `otp`              CHAR(6)      NULL DEFAULT NULL,
  `otp_expires`      DATETIME     NULL DEFAULT NULL,
  `otp_attempts`     TINYINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_employees_mobile` (`mobile`),
  UNIQUE KEY `uq_employees_email`  (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3) USERS
CREATE TABLE `users` (
  `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`             VARCHAR(50)  NOT NULL,
  `mobile`           CHAR(11)     NOT NULL,
  `email`            VARCHAR(60)  NULL DEFAULT NULL,
  `password`         VARCHAR(128) NOT NULL,
  `area`             VARCHAR(60)  NOT NULL,
  `gender`           ENUM('male','female','other') NOT NULL,
  `status`           ENUM('active','inactive','blocked') NOT NULL DEFAULT 'active',
  `remember_token`   CHAR(64)     NULL DEFAULT NULL,
  `remember_expires` DATETIME     NULL DEFAULT NULL,
  `otp`              CHAR(6)      NULL DEFAULT NULL,
  `otp_expires`      DATETIME     NULL DEFAULT NULL,
  `otp_attempts`     TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `notifications_seen_at` DATETIME NULL DEFAULT NULL,       -- last time the user opened notifications (for the red count)
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_mobile` (`mobile`),
  UNIQUE KEY `uq_users_email`  (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- USER HOME PAGE (admin adds / changes these from the admin dashboard)
-- =====================================================================

-- 4) BANNERS: pictures at the top of the user home page (slider)
CREATE TABLE `banners` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `image`      VARCHAR(255) NOT NULL,                 -- path inside public/, e.g. uploads/banners/abc.jpg
  `title`      VARCHAR(100) NULL DEFAULT NULL,        -- optional, for the admin's own note
  `sort_order` INT NOT NULL DEFAULT 0,                -- small number shows first
  `is_active`  TINYINT(1) NOT NULL DEFAULT 1,         -- 1 = show, 0 = hide
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4a) SETTINGS: single values the admin can change (welcome page coupon picture)
CREATE TABLE `settings` (
  `name`  VARCHAR(50)  NOT NULL,
  `value` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4b) POPUPS: a picture that pops up once when the user opens the home page (newest shown one)
CREATE TABLE `popups` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `image`      VARCHAR(255) NOT NULL,                 -- path inside public/
  `title`      VARCHAR(100) NULL DEFAULT NULL,        -- optional, for the admin's own note
  `is_active`  TINYINT(1) NOT NULL DEFAULT 1,         -- 1 = show, 0 = hide
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5) SERVICES: the service categories (Electrical Work, Plumbing ...)
CREATE TABLE `services` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name_bn`    VARCHAR(60)  NOT NULL,
  `name_en`    VARCHAR(60)  NOT NULL,
  `icon`       VARCHAR(255) NOT NULL,                 -- logo (PNG) path inside public/
  `color`      ENUM('sand','sky','mint','rose','lilac','gray') NOT NULL DEFAULT 'mint',   -- tile background
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_active`  TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6) SUB_SERVICES: the services inside a category (AC Service -> "AC cleaning/servicing")
CREATE TABLE `sub_services` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `service_id`   INT UNSIGNED NOT NULL,
  `name_bn`      VARCHAR(80)  NOT NULL,
  `name_en`      VARCHAR(80)  NOT NULL,
  `detail_bn`    VARCHAR(120) NULL DEFAULT NULL,      -- short line, e.g. (পরিষ্কার/সার্ভিস)
  `detail_en`    VARCHAR(120) NULL DEFAULT NULL,
  `image`        VARCHAR(255) NULL DEFAULT NULL,      -- photo path inside public/
  `price`        INT UNSIGNED NOT NULL DEFAULT 0,     -- "৳ 500 থেকে" (starting price)
  `rating`       DECIMAL(2,1) NOT NULL DEFAULT 0.0,   -- 4.8
  `rating_count` INT UNSIGNED NOT NULL DEFAULT 0,     -- (120)
  `popular_rank` INT UNSIGNED NULL DEFAULT NULL,      -- 1, 2, 3 ... = shown in "জনপ্রিয় সেবা" in this order. NULL = not popular
  `is_active`    TINYINT(1) NOT NULL DEFAULT 1,
  `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,   -- newest ones show in "নতুন যুক্ত হওয়া সেবা"
  PRIMARY KEY (`id`),
  KEY `idx_sub_services_service` (`service_id`),
  CONSTRAINT `fk_sub_services_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7) NOTIFICATIONS: latest offers the admin sends to all users (bell icon)
CREATE TABLE `notifications` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title`      VARCHAR(100) NOT NULL,
  `message`    VARCHAR(500) NOT NULL,
  `image`      VARCHAR(255) NULL DEFAULT NULL,
  `is_active`  TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default accounts (log in with mobile + password)
--   ADMIN    : 01700000001 / Admin@123     (admin.shomadhan@gmail.com)
--   EMPLOYEE : 01700000002 / Employee@123  (employee.shomadhan@gmail.com)
--   USER     : 01700000003 / User@1234     (user.shomadhan@gmail.com)
INSERT INTO `admins` (`name`, `mobile`, `email`, `password`) VALUES
('SHOMADHAN ADMIN', '01700000001', 'admin.shomadhan@gmail.com', 'Admin@123');

INSERT INTO `employees` (`name`, `mobile`, `email`, `password`) VALUES
('KARIM ELECTRIC', '01700000002', 'employee.shomadhan@gmail.com', 'Employee@123');

INSERT INTO `users` (`name`, `mobile`, `email`, `password`, `area`, `gender`) VALUES
('DEMO USER', '01700000003', 'user.shomadhan@gmail.com', 'User@1234', 'BAU Area', 'male');

-- Starting home page data (admin can change or delete all of this)
INSERT INTO `banners` (`image`, `title`, `sort_order`) VALUES
('assets/images/coupon-banner.jpg', '৳50 coupon for new users', 1),
('assets/images/slider-all-services.jpg', 'All services in one app', 2);

INSERT INTO `settings` (`name`, `value`) VALUES
('welcome_banner', 'assets/images/coupon-banner.jpg');

INSERT INTO `popups` (`image`, `title`) VALUES
('assets/images/popup-coupon.jpg', '৳50 coupon popup');

INSERT INTO `services` (`id`, `name_bn`, `name_en`, `icon`, `color`, `sort_order`) VALUES
(1, 'ইলেকট্রিক কাজ', 'Electrical Work',  'assets/images/icons/electrical.svg', 'sand',  1),
(2, 'প্লাম্বিং',      'Plumbing',         'assets/images/icons/plumbing.svg',   'sky',   2),
(3, 'এসি সার্ভিস',    'AC Service',       'assets/images/icons/ac.svg',         'mint',  3),
(4, 'লন্ড্রি সার্ভিস', 'Laundry Service',  'assets/images/icons/laundry.svg',    'rose',  4),
(5, 'বাসা পরিষ্কার',  'Home Cleaning',    'assets/images/icons/cleaning.svg',   'mint',  5);

INSERT INTO `sub_services` (`service_id`, `name_bn`, `name_en`, `detail_bn`, `detail_en`, `price`, `rating`, `rating_count`, `popular_rank`) VALUES
(3, 'এসি সার্ভিস',        'AC Service',      '(পরিষ্কার/সার্ভিস)',      '(cleaning/servicing)',   500, 4.8, 120, 1),
(2, 'প্লাম্বিং কাজ',       'Plumbing Work',   '(ফিটিং/রিপেয়ার)',         '(fitting/repair)',       400, 4.7,  98, 2),
(5, 'বাসা পরিষ্কার',      'Home Cleaning',   '(ডিপ ক্লিনিং)',            '(deep cleaning)',        600, 4.6,  76, 3),
(1, 'ইলেকট্রিক কাজ',      'Electrical Work', '(ইনস্টলেশন/রিপেয়ার)',     '(installation/repair)',  500, 4.8,  64, 4),
(4, 'লন্ড্রি সার্ভিস',     'Laundry Service', '(ওয়াশ ও ড্রাই ক্লিন)',     '(wash & dry clean)',     300, 4.5,  42, NULL);

INSERT INTO `notifications` (`title`, `message`) VALUES
('৳50 কুপন!', 'নতুন অ্যাকাউন্ট খুললেই প্রথম বুকিংয়ে ৳50 ছাড়।');

