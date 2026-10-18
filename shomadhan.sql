-- =====================================================================
-- Shomadhan database: 3 tables (admins, employees, users)
-- XAMPP: http://localhost/phpmyadmin -> Import -> choose this file -> Go
-- The first line deletes the OLD shomadhan database (if any) and makes it again,
-- so you can import this file again any time. (Old data will be deleted.)
-- Passwords are plain text (no hashing), as asked.
-- =====================================================================

DROP DATABASE IF EXISTS `shomadhan`;
CREATE DATABASE `shomadhan` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `shomadhan`;

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
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_mobile` (`mobile`),
  UNIQUE KEY `uq_users_email`  (`email`)
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
