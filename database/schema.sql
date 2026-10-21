-- =====================================================================
-- Shomadhan database
--   People   : staff     = admins + employees (people who work in the Shomadhan office, "role" says which)
--              users     = customers (they register themselves and book services)
--              providers = service providers (electrician, plumber ...) the office sends to a customer.
--                          They don't log in; the admin keeps their info.
--   Login    : remember_tokens ("Remember me", one row per device), login_attempts, rate_hits
--   Home page: banners, popups, services, sub_services (+ sub_service_providers), notifications (offers)
--   Bookings : bookings (user books, office sends a provider), coupons + coupon_uses, wallet_tx (wallet money in / out)
--   Messages : conversations + messages (user <-> office chat: support, and one chat per booking)
--   Favourites: favourites (user's hearted services)
--   Other    : areas, settings (welcome page picture, site info texts), account_changes (tells a person when an admin changed their account)
-- XAMPP: double-click setup.bat (or phpMyAdmin -> Import -> this file -> Go)
-- The first line deletes the OLD shomadhan database (if any) and makes it again, so old data is deleted.
-- Passwords and OTPs are saved as bcrypt hashes, never as they are.
-- People are never really deleted ("soft delete"): deleting only fills deleted_at + deleted_by,
-- so everything they added stays and their name still shows. A deleted account can't log in and can be restored.
-- One mobile / email = one person: unique inside each table (database) and across staff + users (website checks).
-- Foreign keys: every created_by / deleted_by / changed_by -> staff.id, users.area / providers.area -> areas.name,
--   providers.service_id + sub_services.service_id -> services.id, remember_tokens + account_changes -> staff / users.
--   ON DELETE RESTRICT: a row that others point to can't be removed by mistake (even from phpMyAdmin).
-- =====================================================================

DROP DATABASE IF EXISTS `shomadhan`;
CREATE DATABASE `shomadhan` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `shomadhan`;
SET NAMES utf8mb4;
SET time_zone = '+06:00';   -- Bangladesh time, same as the website

-- 0) AREAS: areas of Mymensingh (register page dropdown). A user's area must be one of these.
CREATE TABLE `areas` (
  `id`   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(60)  NOT NULL,
  `lat`  DECIMAL(9,6) NULL DEFAULT NULL,      -- middle point of the area (rough)
  `lng`  DECIMAL(9,6) NULL DEFAULT NULL,      --   used to show how far each provider is from a booking
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_areas_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 1) STAFF: admins and employees (office people who use the dashboard). role = admin / employee.
--   Making an employee an admin only changes "role": the id and everything linked to it stay.
CREATE TABLE `staff` (
  `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `role`             ENUM('admin','employee') NOT NULL,
  `name`             VARCHAR(50)  NOT NULL,
  `mobile`           CHAR(11)     NOT NULL,                             -- login = mobile + password
  `email`            VARCHAR(60)  NULL DEFAULT NULL,                    -- optional (same as the register page)
  `area`             VARCHAR(60)  NOT NULL,                             -- areas.name (same info as users / providers)
  `gender`           ENUM('male','female','other') NOT NULL,
  `password`         VARCHAR(255) NOT NULL,                             -- bcrypt hash, never the real password
  `must_change_password` TINYINT(1) NOT NULL DEFAULT 0,                -- 1 = made by an admin with a one-time password: must set a new one first
  `photo`            VARCHAR(255) NULL DEFAULT NULL,                    -- square picture, path inside public/
  `status`           ENUM('active','inactive') NOT NULL DEFAULT 'active',   -- inactive = can't log in or do anything
  `created_at`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by`       INT UNSIGNED NULL DEFAULT NULL,                   -- the admin who added this person (NULL = setup)
  `last_login_at`    DATETIME     NULL DEFAULT NULL,
  `otp`              VARCHAR(255) NULL DEFAULT NULL,                   -- hash of the forgot-password OTP
  `otp_expires`      DATETIME     NULL DEFAULT NULL,
  `otp_attempts`     TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `deleted_at`       DATETIME     NULL DEFAULT NULL,                    -- soft delete (NULL = not deleted)
  `deleted_by`       INT UNSIGNED NULL DEFAULT NULL,
  `live_mobile`      VARCHAR(11)  AS (IF(`deleted_at` IS NULL, RTRIM(`mobile`), NULL)) STORED,   -- auto: for UNIQUE (deleted ones don't count)
  `live_email`       VARCHAR(60)  AS (IF(`deleted_at` IS NULL, `email`, NULL)) STORED,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_staff_mobile` (`live_mobile`),
  UNIQUE KEY `uq_staff_email`  (`live_email`),
  KEY `idx_staff_role` (`role`, `deleted_at`),
  KEY `idx_staff_mobile` (`mobile`),
  KEY `idx_staff_area` (`area`),
  KEY `idx_staff_created_by` (`created_by`),
  KEY `idx_staff_deleted_by` (`deleted_by`),
  CONSTRAINT `fk_staff_created_by` FOREIGN KEY (`created_by`) REFERENCES `staff` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_staff_deleted_by` FOREIGN KEY (`deleted_by`) REFERENCES `staff` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_staff_area`       FOREIGN KEY (`area`)       REFERENCES `areas` (`name`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2) USERS: customers (register page). They book services, have a wallet and chat with the office.
CREATE TABLE `users` (
  `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`             VARCHAR(50)  NOT NULL,
  `mobile`           CHAR(11)     NOT NULL,
  `email`            VARCHAR(60)  NULL DEFAULT NULL,
  `password`         VARCHAR(255) NOT NULL,                             -- bcrypt hash
  `must_change_password` TINYINT(1) NOT NULL DEFAULT 0,                -- 1 = the office reset it to a one-time password: must set a new one first
  `photo`            VARCHAR(255) NULL DEFAULT NULL,                    -- square picture (the user adds it on the profile page)
  `area`             VARCHAR(60)  NOT NULL,
  `gender`           ENUM('male','female','other') NOT NULL,
  `birth_date`       DATE         NULL DEFAULT NULL,                    -- optional (profile page)
  `address`          VARCHAR(200) NULL DEFAULT NULL,                    -- house / road / block (profile page; booking form starts with it)
  `status`           ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_at`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login_at`    DATETIME     NULL DEFAULT NULL,
  `otp`              VARCHAR(255) NULL DEFAULT NULL,
  `otp_expires`      DATETIME     NULL DEFAULT NULL,
  `otp_attempts`     TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `notifications_seen_at` DATETIME NULL DEFAULT NULL,                  -- last time the user opened the bell (red count)
  `deleted_at`       DATETIME     NULL DEFAULT NULL,
  `deleted_by`       INT UNSIGNED NULL DEFAULT NULL,                   -- staff.id of the admin who deleted it
  `live_mobile`      VARCHAR(11)  AS (IF(`deleted_at` IS NULL, RTRIM(`mobile`), NULL)) STORED,
  `live_email`       VARCHAR(60)  AS (IF(`deleted_at` IS NULL, `email`, NULL)) STORED,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_mobile` (`live_mobile`),
  UNIQUE KEY `uq_users_email`  (`live_email`),
  KEY `idx_users_mobile` (`mobile`),
  KEY `idx_users_area` (`area`),
  KEY `idx_users_deleted_by` (`deleted_by`),
  CONSTRAINT `fk_users_area`       FOREIGN KEY (`area`)       REFERENCES `areas` (`name`) ON UPDATE CASCADE,
  CONSTRAINT `fk_users_deleted_by` FOREIGN KEY (`deleted_by`) REFERENCES `staff` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3) REMEMBER_TOKENS: "Remember me" (30 days), one row per browser / device. staff_id OR user_id is filled.
--   The cookie holds a secret; this table keeps only sha256(secret). Logout removes that device's row.
CREATE TABLE `remember_tokens` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `staff_id`   INT UNSIGNED NULL DEFAULT NULL,
  `user_id`    INT UNSIGNED NULL DEFAULT NULL,
  `token_hash` CHAR(64)     NOT NULL,
  `expires_at` DATETIME     NOT NULL,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_remember_hash` (`token_hash`),
  KEY `idx_remember_staff` (`staff_id`),
  KEY `idx_remember_user` (`user_id`),
  CONSTRAINT `fk_remember_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_remember_user`  FOREIGN KEY (`user_id`)  REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4) ACCOUNT_CHANGES: when an admin changes someone's account (info, status, role ...), that person sees it
--   in their notifications: who changed it, when, and what (changes = JSON list of [field, old, new]).
CREATE TABLE `account_changes` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `staff_id`   INT UNSIGNED NULL DEFAULT NULL,               -- the changed account (staff) ...
  `user_id`    INT UNSIGNED NULL DEFAULT NULL,               -- ... or (user)
  `changed_by` INT UNSIGNED NOT NULL,                        -- staff.id of the admin who changed it
  `changes`    TEXT         NOT NULL,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `seen_at`    DATETIME     NULL DEFAULT NULL,               -- NULL = not seen yet (red count)
  PRIMARY KEY (`id`),
  KEY `idx_changes_staff` (`staff_id`, `seen_at`),
  KEY `idx_changes_user` (`user_id`, `seen_at`),
  KEY `idx_changes_by` (`changed_by`),
  CONSTRAINT `fk_changes_staff` FOREIGN KEY (`staff_id`)   REFERENCES `staff` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_changes_user`  FOREIGN KEY (`user_id`)    REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_changes_by`    FOREIGN KEY (`changed_by`) REFERENCES `staff` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- USER HOME PAGE (admin adds / changes these from the admin dashboard)
-- =====================================================================

-- 5) BANNERS: pictures at the top of the user home page (slider). Picture sizes: see IMG_SIZES in app/helpers/upload.php.
CREATE TABLE `banners` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `image`      VARCHAR(255) NOT NULL,                 -- path inside public/, e.g. uploads/banners/abc.jpg
  `title`      VARCHAR(100) NULL DEFAULT NULL,        -- optional, for the admin's own note
  `sort_order` INT NOT NULL DEFAULT 0,                -- small number shows first
  `is_active`  TINYINT(1) NOT NULL DEFAULT 1,         -- 1 = show, 0 = hide
  `created_by` INT UNSIGNED NULL DEFAULT NULL,         -- staff.id of the admin who added it
  PRIMARY KEY (`id`),
  KEY `idx_banners_created_by` (`created_by`),
  CONSTRAINT `fk_banners_created_by` FOREIGN KEY (`created_by`) REFERENCES `staff` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- RATE_HITS: one row per try of a limited action, for limits like "10 forgot-password tries in 10 minutes"
--   (too_many() in helpers/functions.php), counted per internet address. Kept in the database,
--   so a script that throws its cookie away still hits the limit. k = which limit, ip = who tried.
CREATE TABLE `rate_hits` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `k`          VARCHAR(20)  NOT NULL,
  `ip`         VARCHAR(45)  NOT NULL,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ix_rate` (`k`, `ip`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- LOGIN_ATTEMPTS: failed logins (for "too many wrong tries, wait a few minutes", see AccountModel::loginLockedMinutes).
--   Rows older than one day are removed when a new failed login is saved.
CREATE TABLE `login_attempts` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `mobile`     CHAR(11)     NOT NULL,
  `ip`         VARCHAR(45)  NOT NULL,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ix_attempts_mobile` (`mobile`, `created_at`),
  KEY `ix_attempts_ip` (`ip`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6) SETTINGS: single values the admin can change (name -> value): the welcome page coupon picture,
--   and the site info texts (contact, about, terms, privacy) saved as site_<key>.
CREATE TABLE `settings` (
  `name`  VARCHAR(50)  NOT NULL,
  `value` TEXT NOT NULL,                         -- long texts too (site_terms_bn ...)
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7) POPUPS: pictures that pop up once when the user opens the home page.
--   Every popup that is turned on is shown, newest first, one after another.
CREATE TABLE `popups` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `image`      VARCHAR(255) NOT NULL,                 -- path inside public/
  `title`      VARCHAR(100) NULL DEFAULT NULL,        -- optional, for the admin's own note
  `link`       VARCHAR(255) NULL DEFAULT NULL,        -- optional: clicking the picture opens this website (promotion)
  `is_active`  TINYINT(1) NOT NULL DEFAULT 1,         -- 1 = show, 0 = hide
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` INT UNSIGNED NULL DEFAULT NULL,         -- staff.id of the admin who added it
  PRIMARY KEY (`id`),
  KEY `idx_popups_created_by` (`created_by`),
  CONSTRAINT `fk_popups_created_by` FOREIGN KEY (`created_by`) REFERENCES `staff` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8) SERVICES: the service categories (Electrical Work, Plumbing ...)
CREATE TABLE `services` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name_bn`    VARCHAR(60)  NOT NULL,
  `name_en`    VARCHAR(60)  NOT NULL,
  `icon`       VARCHAR(255) NOT NULL,                 -- logo (PNG) path inside public/
  `color`      ENUM('sand','sky','mint','rose','lilac','gray') NOT NULL DEFAULT 'mint',   -- tile background
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_active`  TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` INT UNSIGNED NULL DEFAULT NULL,         -- staff.id of the admin who added it
  PRIMARY KEY (`id`),
  KEY `idx_services_created_by` (`created_by`),
  CONSTRAINT `fk_services_created_by` FOREIGN KEY (`created_by`) REFERENCES `staff` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9) SUB_SERVICES: the services inside a category (AC Service -> "AC cleaning/servicing")
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
  `created_by` INT UNSIGNED NULL DEFAULT NULL,         -- staff.id of the admin who added it
  PRIMARY KEY (`id`),
  KEY `idx_sub_services_service` (`service_id`),
  CONSTRAINT `fk_sub_services_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE RESTRICT,
  KEY `idx_sub_services_created_by` (`created_by`),
  CONSTRAINT `fk_sub_services_created_by` FOREIGN KEY (`created_by`) REFERENCES `staff` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10) PROVIDERS: people who give a service (electrician, plumber ...). The office sends one of them to a customer.
--   They don't log in. Each one works in one category (service_id).
CREATE TABLE `providers` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `service_id` INT UNSIGNED NOT NULL,                       -- category (Electrical Work, Plumbing ...)
  `name`       VARCHAR(50)  NOT NULL,
  `mobile`     CHAR(11)     NOT NULL,
  `email`      VARCHAR(60)  NULL DEFAULT NULL,              -- optional
  `age`        TINYINT UNSIGNED NOT NULL,
  `gender`     ENUM('male','female','other') NOT NULL,
  `area`       VARCHAR(60)  NOT NULL,                       -- where they live / work (areas.name)
  `address`    VARCHAR(200) NOT NULL,
  `photo`      VARCHAR(255) NULL DEFAULT NULL,              -- square picture
  `note`       VARCHAR(255) NULL DEFAULT NULL,              -- anything else the admin wants to keep
  `status`     ENUM('active','inactive') NOT NULL DEFAULT 'active',   -- inactive = don't send to customers
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` INT UNSIGNED NULL DEFAULT NULL,
  `deleted_at` DATETIME     NULL DEFAULT NULL,              -- soft delete (future orders keep their provider)
  `deleted_by` INT UNSIGNED NULL DEFAULT NULL,
  `live_mobile` VARCHAR(11) AS (IF(`deleted_at` IS NULL, RTRIM(`mobile`), NULL)) STORED,
  `live_email`  VARCHAR(60) AS (IF(`deleted_at` IS NULL, `email`, NULL)) STORED,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_providers_mobile` (`live_mobile`),
  UNIQUE KEY `uq_providers_email`  (`live_email`),
  KEY `idx_providers_service` (`service_id`, `status`),
  KEY `idx_providers_area` (`area`),
  KEY `idx_providers_created_by` (`created_by`),
  KEY `idx_providers_deleted_by` (`deleted_by`),
  CONSTRAINT `fk_providers_service`    FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_providers_area`       FOREIGN KEY (`area`)       REFERENCES `areas` (`name`) ON UPDATE CASCADE,
  CONSTRAINT `fk_providers_created_by` FOREIGN KEY (`created_by`) REFERENCES `staff` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_providers_deleted_by` FOREIGN KEY (`deleted_by`) REFERENCES `staff` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11) SUB_SERVICE_PROVIDERS: which providers give which sub-service (one sub-service, many providers)
CREATE TABLE `sub_service_providers` (
  `sub_service_id` INT UNSIGNED NOT NULL,
  `provider_id`    INT UNSIGNED NOT NULL,
  PRIMARY KEY (`sub_service_id`, `provider_id`),
  KEY `idx_ssp_provider` (`provider_id`),
  CONSTRAINT `fk_ssp_sub`      FOREIGN KEY (`sub_service_id`) REFERENCES `sub_services` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ssp_provider` FOREIGN KEY (`provider_id`)    REFERENCES `providers` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12) NOTIFICATIONS: latest offers the admin sends to all users (bell icon)
CREATE TABLE `notifications` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title`      VARCHAR(100) NOT NULL,
  `message`    VARCHAR(500) NOT NULL,
  `image`      VARCHAR(255) NULL DEFAULT NULL,
  `is_active`  TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` INT UNSIGNED NULL DEFAULT NULL,         -- staff.id of the admin who added it
  PRIMARY KEY (`id`),
  KEY `idx_notifications_created_by` (`created_by`),
  CONSTRAINT `fk_notifications_created_by` FOREIGN KEY (`created_by`) REFERENCES `staff` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- BOOKINGS, WALLET, COUPONS
-- =====================================================================

-- 13) COUPONS: taka-off codes the admin makes. A user can use each coupon once (coupon_uses).
--   valid_days set   = valid for that many days after the user opened the account (welcome coupon)
--   expires_on set   = valid until that day for everyone
CREATE TABLE `coupons` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code`        VARCHAR(20)  NOT NULL,                 -- e.g. WELCOME50 (capital letters + numbers)
  `title_bn`    VARCHAR(80)  NOT NULL,
  `title_en`    VARCHAR(80)  NOT NULL,
  `amount`      INT UNSIGNED NOT NULL,                 -- taka off
  `min_order`   INT UNSIGNED NOT NULL DEFAULT 0,       -- service price must be at least this
  `valid_days`  SMALLINT UNSIGNED NULL DEFAULT NULL,
  `expires_on`  DATE NULL DEFAULT NULL,
  `is_active`   TINYINT(1) NOT NULL DEFAULT 1,
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by`  INT UNSIGNED NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_coupons_code` (`code`),
  KEY `idx_coupons_created_by` (`created_by`),
  CONSTRAINT `fk_coupons_created_by` FOREIGN KEY (`created_by`) REFERENCES `staff` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14) BOOKINGS: a user books a sub-service; the office sends a provider.
--   pending   = waiting for the office (user can cancel or change the time)
--   ongoing   = a provider is sent
--   completed = work done (user gets reward points)
--   cancelled = cancelled by the user or the office (wallet money comes back, coupon can be used again)
CREATE TABLE `bookings` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`        INT UNSIGNED NOT NULL,
  `sub_service_id` INT UNSIGNED NOT NULL,
  `provider_id`    INT UNSIGNED NULL DEFAULT NULL,
  `scheduled_at`   DATETIME     NOT NULL,                -- date + time the user wants the work
  `area`           VARCHAR(60)  NOT NULL,
  `address`        VARCHAR(200) NOT NULL,
  `lat`            DECIMAL(9,6) NULL DEFAULT NULL,       -- the user's phone location when booking (if allowed); empty = the area's point
  `lng`            DECIMAL(9,6) NULL DEFAULT NULL,
  `note`           VARCHAR(255) NULL DEFAULT NULL,
  `price`          INT UNSIGNED NOT NULL,                -- service price when booked
  `discount`       INT UNSIGNED NOT NULL DEFAULT 0,      -- coupon
  `total`          INT UNSIGNED NOT NULL,                -- price - discount
  `coupon_id`      INT UNSIGNED NULL DEFAULT NULL,
  `payment`        ENUM('cash','wallet') NOT NULL DEFAULT 'cash',   -- wallet = paid at booking time
  `status`         ENUM('pending','ongoing','completed','cancelled') NOT NULL DEFAULT 'pending',
  `points`         INT UNSIGNED NOT NULL DEFAULT 0,      -- reward points given when completed
  `cancelled_by`   ENUM('user','staff') NULL DEFAULT NULL,
  `cancel_reason`  VARCHAR(200) NULL DEFAULT NULL,
  `final_total`    INT UNSIGNED NULL DEFAULT NULL,       -- the real bill when the work is done (NULL = same as total)
  `final_note`     VARCHAR(200) NULL DEFAULT NULL,       -- why the bill changed (extra parts, bigger job ...)
  `commission`     INT UNSIGNED NOT NULL DEFAULT 0,      -- the office's share of this job (settings: commission %)
  `provider_cash`  INT UNSIGNED NOT NULL DEFAULT 0,      -- cash the provider took from the customer for this job
  `completed_at`   DATETIME     NULL DEFAULT NULL,
  `rating`         TINYINT UNSIGNED NULL DEFAULT NULL,   -- 1-5 stars from the user after the work
  `review`         VARCHAR(500) NULL DEFAULT NULL,
  `reviewed_at`    DATETIME     NULL DEFAULT NULL,
  `complaint`      VARCHAR(500) NULL DEFAULT NULL,       -- the user's problem with the work (a few days after it is done)
  `complaint_at`   DATETIME     NULL DEFAULT NULL,
  `complaint_status` ENUM('open','solved') NULL DEFAULT NULL,
  `complaint_answer` VARCHAR(300) NULL DEFAULT NULL,     -- what the office did about it (the user sees it)
  `staff_note`     VARCHAR(300) NULL DEFAULT NULL,       -- office-only note (calls made, who said no ...)
  `handled_by`     INT UNSIGNED NULL DEFAULT NULL,       -- staff who last changed it (sent provider, completed, cancelled)
  `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_bookings_user` (`user_id`, `status`),
  KEY `idx_bookings_status` (`status`, `scheduled_at`),
  KEY `idx_bookings_sub` (`sub_service_id`),
  KEY `idx_bookings_provider` (`provider_id`),
  KEY `idx_bookings_coupon` (`coupon_id`),
  KEY `idx_bookings_area` (`area`),
  KEY `idx_bookings_handled_by` (`handled_by`),
  KEY `idx_bookings_done` (`status`, `completed_at`),
  KEY `idx_bookings_complaint` (`complaint_status`),
  KEY `idx_bookings_created` (`created_at`),           -- reports: bookings made in a date range
  CONSTRAINT `fk_bookings_user`     FOREIGN KEY (`user_id`)        REFERENCES `users` (`id`)        ON DELETE RESTRICT,
  CONSTRAINT `fk_bookings_sub`      FOREIGN KEY (`sub_service_id`) REFERENCES `sub_services` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_bookings_provider` FOREIGN KEY (`provider_id`)    REFERENCES `providers` (`id`)    ON DELETE RESTRICT,
  CONSTRAINT `fk_bookings_coupon`   FOREIGN KEY (`coupon_id`)      REFERENCES `coupons` (`id`)      ON DELETE RESTRICT,
  CONSTRAINT `fk_bookings_area`     FOREIGN KEY (`area`)           REFERENCES `areas` (`name`)      ON UPDATE CASCADE,
  CONSTRAINT `fk_bookings_handled`  FOREIGN KEY (`handled_by`)     REFERENCES `staff` (`id`)        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15) COUPON_USES: which user used which coupon (once each). Removed again when that booking is cancelled.
CREATE TABLE `coupon_uses` (
  `coupon_id`  INT UNSIGNED NOT NULL,
  `user_id`    INT UNSIGNED NOT NULL,
  `booking_id` INT UNSIGNED NOT NULL,
  `used_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`coupon_id`, `user_id`),
  KEY `idx_cu_user` (`user_id`),
  KEY `idx_cu_booking` (`booking_id`),
  CONSTRAINT `fk_cu_coupon`  FOREIGN KEY (`coupon_id`)  REFERENCES `coupons` (`id`)  ON DELETE RESTRICT,
  CONSTRAINT `fk_cu_user`    FOREIGN KEY (`user_id`)    REFERENCES `users` (`id`)    ON DELETE RESTRICT,
  CONSTRAINT `fk_cu_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 16) WALLET_TX: every taka in or out of a user's wallet. Balance = SUM(amount) of 'successful' rows.
--   topup   = user sent money (bKash / Nagad / Rocket) and typed the transaction ID; the office checks it
--             (pending -> successful or rejected)
--   payment = booking paid from the wallet (minus)
--   refund  = cancelled booking, money back (plus)
CREATE TABLE `wallet_tx` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`     INT UNSIGNED NOT NULL,
  `kind`        ENUM('topup','payment','refund') NOT NULL,
  `amount`      INT NOT NULL,                          -- + in, - out
  `status`      ENUM('pending','successful','rejected') NOT NULL DEFAULT 'successful',
  `method`      ENUM('bkash','nagad','rocket') NULL DEFAULT NULL,    -- top-up only
  `trx_id`      VARCHAR(30)  NULL DEFAULT NULL,                      -- top-up only: the user's transaction ID
  `booking_id`  INT UNSIGNED NULL DEFAULT NULL,
  `checked_by`  INT UNSIGNED NULL DEFAULT NULL,        -- staff who approved / rejected a top-up
  `checked_at`  DATETIME NULL DEFAULT NULL,
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `live_trx`    VARCHAR(30) AS (IF(`status` <> 'rejected', `trx_id`, NULL)) STORED,   -- a rejected ID can be sent again
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_wallet_trx` (`method`, `live_trx`),   -- one transaction ID can't be used twice
  KEY `idx_wallet_user` (`user_id`, `status`),
  KEY `idx_wallet_booking` (`booking_id`),
  KEY `idx_wallet_checked_by` (`checked_by`),
  CONSTRAINT `fk_wallet_user`    FOREIGN KEY (`user_id`)    REFERENCES `users` (`id`)    ON DELETE RESTRICT,
  CONSTRAINT `fk_wallet_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_wallet_checked` FOREIGN KEY (`checked_by`) REFERENCES `staff` (`id`)    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 19) FAVOURITES: services a user marked with the heart (profile -> প্রিয় সেবা)
CREATE TABLE `favourites` (
  `user_id`        INT UNSIGNED NOT NULL,
  `sub_service_id` INT UNSIGNED NOT NULL,
  `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`, `sub_service_id`),
  KEY `idx_fav_sub` (`sub_service_id`),
  CONSTRAINT `fk_fav_user` FOREIGN KEY (`user_id`)        REFERENCES `users` (`id`)        ON DELETE CASCADE,
  CONSTRAINT `fk_fav_sub`  FOREIGN KEY (`sub_service_id`) REFERENCES `sub_services` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 17) CONVERSATIONS: chats between a user and the office (every admin + employee can answer).
--   booking_id NULL = general "Shomadhan Support" chat; otherwise the chat about that booking. One of each per user.
CREATE TABLE `conversations` (
  `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`         INT UNSIGNED NOT NULL,
  `booking_id`      INT UNSIGNED NULL DEFAULT NULL,
  `topic_key`       INT UNSIGNED AS (COALESCE(`booking_id`, 0)) STORED,   -- makes "one support chat per user" unique (NULLs are not)
  `last_message_at` DATETIME(3) NULL DEFAULT NULL,    -- (3) = milliseconds: a message sent in the same second as "seen" still counts as unread
  `user_seen_at`    DATETIME(3) NULL DEFAULT NULL,       -- user opened it (office messages after this = unread for the user)
  `staff_seen_at`   DATETIME(3) NULL DEFAULT NULL,       -- any admin / employee opened it
  `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_conv_topic` (`user_id`, `topic_key`),
  KEY `idx_conv_booking` (`booking_id`),
  KEY `idx_conv_last` (`last_message_at`),
  CONSTRAINT `fk_conv_user`    FOREIGN KEY (`user_id`)    REFERENCES `users` (`id`)    ON DELETE RESTRICT,
  CONSTRAINT `fk_conv_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 18) MESSAGES: one line in a chat. sender = user, or staff (staff_id says which admin / employee)
CREATE TABLE `messages` (
  `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `conversation_id` INT UNSIGNED NOT NULL,
  `sender`          ENUM('user','staff') NOT NULL,
  `staff_id`        INT UNSIGNED NULL DEFAULT NULL,
  `body`            VARCHAR(1000) NOT NULL,
  `created_at`      DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  PRIMARY KEY (`id`),
  KEY `idx_msg_conv` (`conversation_id`, `id`),
  KEY `idx_msg_staff` (`staff_id`),
  CONSTRAINT `fk_msg_conv`  FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_msg_staff` FOREIGN KEY (`staff_id`)        REFERENCES `staff` (`id`)         ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 19) PROVIDER PAYMENTS: money settled between the office and a provider (provider account page).
--   amount > 0 = the office paid the provider; amount < 0 = the provider paid the office (commission from cash jobs).
CREATE TABLE `provider_payments` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `provider_id` INT UNSIGNED NOT NULL,
  `amount`      INT NOT NULL,
  `method`      ENUM('cash','bkash','nagad','rocket','bank') NOT NULL DEFAULT 'cash',
  `note`        VARCHAR(200) NULL DEFAULT NULL,
  `created_by`  INT UNSIGNED NOT NULL,
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pp_provider` (`provider_id`, `created_at`),
  KEY `idx_pp_by` (`created_by`),
  CONSTRAINT `fk_pp_provider` FOREIGN KEY (`provider_id`) REFERENCES `providers` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_pp_by`       FOREIGN KEY (`created_by`)  REFERENCES `staff` (`id`)     ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 20) PROVIDER LEAVES: days a provider can't work (shown as "on leave" in the bookings page provider list)
CREATE TABLE `provider_leaves` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `provider_id` INT UNSIGNED NOT NULL,
  `from_date`   DATE NOT NULL,
  `to_date`     DATE NOT NULL,
  `note`        VARCHAR(100) NULL DEFAULT NULL,
  `created_by`  INT UNSIGNED NOT NULL,
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pl_provider` (`provider_id`, `to_date`),
  KEY `idx_pl_by` (`created_by`),
  CONSTRAINT `fk_pl_provider` FOREIGN KEY (`provider_id`) REFERENCES `providers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pl_by`       FOREIGN KEY (`created_by`)  REFERENCES `staff` (`id`)     ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 21) ACTIVITY LOG: every change an admin / employee saves on the dashboard (who, what, when). Admin "Work log" page.
CREATE TABLE `activity_log` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `staff_id`   INT UNSIGNED NOT NULL,
  `page`       VARCHAR(30)  NOT NULL,
  `action`     VARCHAR(30)  NOT NULL,
  `target_id`  INT UNSIGNED NULL DEFAULT NULL,
  `message`    VARCHAR(300) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_log_time` (`created_at`),
  KEY `idx_log_staff` (`staff_id`, `created_at`),
  CONSTRAINT `fk_log_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 22) MIGRATIONS: which files of database/migrations/ have run on this database (tools/update.php)
CREATE TABLE `migrations` (
  `name`   VARCHAR(100) NOT NULL,
  `run_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- lat / lng = rough middle of each area (only used to sort providers by distance on the bookings page)
INSERT INTO `areas` (`name`, `lat`, `lng`) VALUES
('Ahammadabad', 24.7505, 90.3985),
('Akua', 24.7395, 90.4005),
('Alia Madrasa Road', 24.7495, 90.4090),
('Amlapara', 24.7530, 90.4085),
('Baghmara', 24.7445, 90.4105),
('BAU Area', 24.7258, 90.4290),
('Biddamoye School Area', 24.7550, 90.4060),
('Boro Bazar', 24.7585, 90.3990),
('Boundary Road', 24.7440, 90.4040),
('Bramopolli', 24.7520, 90.4005),
('Bypass More', 24.7300, 90.4150),
('Cantonment', 24.7790, 90.3830),
('Chamra Godam', 24.7610, 90.4015),
('Choto Bazar', 24.7560, 90.4005),
('Dhopakhola', 24.7480, 90.4140),
('Durgabari', 24.7545, 90.4025),
('Fulpur', 24.9530, 90.3530),
('Ganginapar', 24.7525, 90.4045),
('Joynul Abedin Park', 24.7600, 90.4080),
('Jubilee Road', 24.7540, 90.4055),
('Kachari', 24.7570, 90.4050),
('Kachi Jhuli Mor', 24.7480, 90.3960),
('Kali Bari', 24.7550, 90.4035),
('Kestopur', 24.7420, 90.3990),
('Kewatkhali', 24.7620, 90.4160),
('Lichu Bagan', 24.7460, 90.3940),
('Maskanda', 24.7360, 90.4075),
('Mintu College Area', 24.7500, 90.4180),
('Nahar Road', 24.7440, 90.4070),
('Natok Gol Lane', 24.7535, 90.4070),
('Natun Bazar', 24.7490, 90.4110),
('Naumahal', 24.7505, 90.4160),
('Pat Godam', 24.7615, 90.3960),
('Pocha Pukur Par', 24.7470, 90.4030),
('Police Line', 24.7585, 90.4120),
('Pondit Para', 24.7510, 90.4020),
('Shehora', 24.7420, 90.4180),
('Shema Choron Rai Road', 24.7500, 90.4060),
('Shomvogonj', 24.7610, 90.4320),
('Stadium Area', 24.7598, 90.4031),
('Station Road', 24.7465, 90.4070),
('Taltola', 24.7460, 90.4005),
('Town Hall Mor', 24.7510, 90.4060),
('Trishal Bus Stand', 24.7345, 90.4060),
('Zilla More', 24.7575, 90.4090);

-- Default accounts (log in with mobile + password)
--   ADMIN    : 01700000001 / Admin@123     (admin.shomadhan@gmail.com)
--   EMPLOYEE : 01700000002 / Employee@123  (employee.shomadhan@gmail.com)
--   USER     : 01700000003 / User@1234     (user.shomadhan@gmail.com)
--   (the password column holds a bcrypt hash of these, not the real password. Change them after the first login.)
INSERT INTO `staff` (`id`, `role`, `name`, `mobile`, `email`, `password`, `status`, `created_at`, `created_by`, `last_login_at`, `area`, `gender`) VALUES
(1, 'admin', 'RASHEDUL KARIM', '01700000001', 'admin.shomadhan@gmail.com', '$2y$10$lNEKw93XRppMh1znbekvbuopuObNmyj1pZS97BsJAzlL5UaGZhcwm', 'active', '2026-01-05 10:00:00', NULL, '2026-10-19 09:15:00', 'Kachari', 'male'),
(2, 'admin', 'ARIFUL ISLAM', '01710000002', 'ariful.islam@gmail.com', '$2y$10$lNEKw93XRppMh1znbekvbuopuObNmyj1pZS97BsJAzlL5UaGZhcwm', 'active', '2026-02-11 11:20:00', 1, '2026-10-18 20:05:00', 'BAU Area', 'male'),
(3, 'admin', 'NUSRAT JAHAN', '01710000003', 'nusrat.jahan@gmail.com', '$2y$10$lNEKw93XRppMh1znbekvbuopuObNmyj1pZS97BsJAzlL5UaGZhcwm', 'active', '2026-03-02 09:45:00', 1, '2026-10-15 13:40:00', 'Ganginapar', 'female'),
(4, 'admin', 'TANVIR AHMED', '01710000004', 'tanvir.ahmed@gmail.com', '$2y$10$lNEKw93XRppMh1znbekvbuopuObNmyj1pZS97BsJAzlL5UaGZhcwm', 'inactive', '2026-03-21 16:10:00', 2, '2026-08-02 18:30:00', 'Boro Bazar', 'male'),
(5, 'admin', 'SADIA RAHMAN', '01710000005', 'sadia.rahman@gmail.com', '$2y$10$lNEKw93XRppMh1znbekvbuopuObNmyj1pZS97BsJAzlL5UaGZhcwm', 'active', '2026-04-08 12:00:00', 1, NULL, 'Akua', 'female'),
(6, 'admin', 'MAHMUDUL HASAN', '01710000006', 'mahmudul.hasan@gmail.com', '$2y$10$lNEKw93XRppMh1znbekvbuopuObNmyj1pZS97BsJAzlL5UaGZhcwm', 'active', '2026-05-14 15:35:00', 3, '2026-10-10 10:10:00', 'Natun Bazar', 'male'),
(7, 'admin', 'FARZANA AKTER', '01710000007', 'farzana.akter@gmail.com', '$2y$10$lNEKw93XRppMh1znbekvbuopuObNmyj1pZS97BsJAzlL5UaGZhcwm', 'inactive', '2026-06-01 08:50:00', 2, NULL, 'Choto Bazar', 'female'),
(8, 'admin', 'RAKIBUL HOQUE', '01710000008', 'rakibul.hoque@gmail.com', '$2y$10$lNEKw93XRppMh1znbekvbuopuObNmyj1pZS97BsJAzlL5UaGZhcwm', 'active', '2026-07-19 17:25:00', 1, '2026-10-19 22:00:00', 'Station Road', 'male'),
(9, 'admin', 'JANNATUL FERDOUS', '01710000009', 'jannatul.ferdous@gmail.com', '$2y$10$lNEKw93XRppMh1znbekvbuopuObNmyj1pZS97BsJAzlL5UaGZhcwm', 'active', '2026-08-27 10:05:00', 6, '2026-09-29 11:45:00', 'Cantonment', 'female'),
(10, 'admin', 'ZAHID HASAN', '01710000010', 'zahid.hasan@gmail.com', '$2y$10$lNEKw93XRppMh1znbekvbuopuObNmyj1pZS97BsJAzlL5UaGZhcwm', 'active', '2026-09-30 14:15:00', 3, NULL, 'Shehora', 'male'),
(11, 'employee', 'KARIM UDDIN', '01700000002', 'employee.shomadhan@gmail.com', '$2y$10$l3Gn/Z85955saalbstgqbushp4t6ErKy43T.FUgeMlNUlBGxeqXrK', 'active', '2026-01-20 10:30:00', 1, '2026-10-19 08:00:00', 'Kachari', 'male'),
(12, 'employee', 'ABDUL MALEK', '01720000002', 'abdul.malek@gmail.com', '$2y$10$l3Gn/Z85955saalbstgqbushp4t6ErKy43T.FUgeMlNUlBGxeqXrK', 'active', '2026-02-03 09:00:00', 1, '2026-10-17 17:20:00', 'BAU Area', 'male'),
(13, 'employee', 'RUMANA PARVIN', '01720000003', NULL, '$2y$10$l3Gn/Z85955saalbstgqbushp4t6ErKy43T.FUgeMlNUlBGxeqXrK', 'active', '2026-02-25 13:15:00', 2, '2026-10-12 09:05:00', 'Ganginapar', 'female'),
(14, 'employee', 'SHAHIN ALAM', '01720000004', 'shahin.alam@gmail.com', '$2y$10$l3Gn/Z85955saalbstgqbushp4t6ErKy43T.FUgeMlNUlBGxeqXrK', 'inactive', '2026-03-17 11:40:00', 3, '2026-06-11 14:00:00', 'Boro Bazar', 'male'),
(15, 'employee', 'MOU AKTER', '01720000005', NULL, '$2y$10$l3Gn/Z85955saalbstgqbushp4t6ErKy43T.FUgeMlNUlBGxeqXrK', 'active', '2026-04-29 16:00:00', 1, NULL, 'Akua', 'female'),
(16, 'employee', 'HABIBUR RAHMAN', '01720000006', 'habibur.rahman@gmail.com', '$2y$10$l3Gn/Z85955saalbstgqbushp4t6ErKy43T.FUgeMlNUlBGxeqXrK', 'active', '2026-05-30 10:20:00', 6, '2026-10-19 19:30:00', 'Natun Bazar', 'male'),
(17, 'employee', 'TASLIMA BEGUM', '01720000007', 'taslima.begum@gmail.com', '$2y$10$l3Gn/Z85955saalbstgqbushp4t6ErKy43T.FUgeMlNUlBGxeqXrK', 'active', '2026-06-22 12:45:00', 8, '2026-10-02 08:40:00', 'Choto Bazar', 'female'),
(18, 'employee', 'NAZMUL HUDA', '01720000008', NULL, '$2y$10$l3Gn/Z85955saalbstgqbushp4t6ErKy43T.FUgeMlNUlBGxeqXrK', 'inactive', '2026-07-08 09:10:00', 2, NULL, 'Station Road', 'male'),
(19, 'employee', 'SUMAIYA ISLAM', '01720000009', 'sumaiya.islam@gmail.com', '$2y$10$l3Gn/Z85955saalbstgqbushp4t6ErKy43T.FUgeMlNUlBGxeqXrK', 'active', '2026-08-16 15:00:00', 9, '2026-10-18 12:15:00', 'Cantonment', 'female'),
(20, 'employee', 'MOSHARROF HOSSAIN', '01720000010', 'mosharrof.hossain@gmail.com', '$2y$10$l3Gn/Z85955saalbstgqbushp4t6ErKy43T.FUgeMlNUlBGxeqXrK', 'active', '2026-10-01 11:30:00', 1, '2026-10-20 09:00:00', 'Shehora', 'male');

INSERT INTO `users` (`id`, `name`, `mobile`, `email`, `password`, `area`, `gender`, `status`, `created_at`, `last_login_at`) VALUES
(1, 'AYESHA SIDDIKA', '01700000003', 'user.shomadhan@gmail.com', '$2y$10$x0WAkmZXkyND5n3oa0m7a.FbYFfg6qTKAyBic0n7T/rUlQeZ.4GhG', 'BAU Area', 'female', 'active', '2026-01-10 10:00:00', '2026-10-19 21:10:00'),
(2, 'RAFIQUL ISLAM', '01730000002', 'rafiqul.islam@gmail.com', '$2y$10$x0WAkmZXkyND5n3oa0m7a.FbYFfg6qTKAyBic0n7T/rUlQeZ.4GhG', 'Kachari', 'male', 'active', '2026-02-14 18:30:00', '2026-10-18 10:00:00'),
(3, 'NASRIN SULTANA', '01730000003', NULL, '$2y$10$x0WAkmZXkyND5n3oa0m7a.FbYFfg6qTKAyBic0n7T/rUlQeZ.4GhG', 'Ganginapar', 'female', 'active', '2026-03-09 08:20:00', '2026-10-11 16:45:00'),
(4, 'MD. SHAKIL', '01730000004', 'md.shakil@gmail.com', '$2y$10$x0WAkmZXkyND5n3oa0m7a.FbYFfg6qTKAyBic0n7T/rUlQeZ.4GhG', 'Boro Bazar', 'male', 'inactive', '2026-03-28 21:00:00', NULL),
(5, 'RIYA DAS', '01730000005', 'riya.das@gmail.com', '$2y$10$x0WAkmZXkyND5n3oa0m7a.FbYFfg6qTKAyBic0n7T/rUlQeZ.4GhG', 'Natun Bazar', 'female', 'active', '2026-04-17 13:05:00', '2026-10-20 07:30:00'),
(6, 'ANIK ROY', '01730000006', NULL, '$2y$10$x0WAkmZXkyND5n3oa0m7a.FbYFfg6qTKAyBic0n7T/rUlQeZ.4GhG', 'Cantonment', 'other', 'active', '2026-05-05 19:45:00', '2026-09-21 12:00:00'),
(7, 'FATEMA KHATUN', '01730000007', 'fatema.khatun@gmail.com', '$2y$10$x0WAkmZXkyND5n3oa0m7a.FbYFfg6qTKAyBic0n7T/rUlQeZ.4GhG', 'Station Road', 'female', 'active', '2026-06-12 10:10:00', NULL),
(8, 'SAIFUL ISLAM', '01730000008', 'saiful.islam@gmail.com', '$2y$10$x0WAkmZXkyND5n3oa0m7a.FbYFfg6qTKAyBic0n7T/rUlQeZ.4GhG', 'Akua', 'male', 'inactive', '2026-07-23 15:50:00', '2026-08-01 09:00:00'),
(9, 'LIMA AKTER', '01730000009', NULL, '$2y$10$x0WAkmZXkyND5n3oa0m7a.FbYFfg6qTKAyBic0n7T/rUlQeZ.4GhG', 'Shehora', 'female', 'active', '2026-09-02 11:35:00', '2026-10-16 20:20:00'),
(10, 'IMRAN KABIR', '01730000010', 'imran.kabir@gmail.com', '$2y$10$x0WAkmZXkyND5n3oa0m7a.FbYFfg6qTKAyBic0n7T/rUlQeZ.4GhG', 'Durgabari', 'male', 'active', '2026-10-05 17:00:00', '2026-10-19 23:10:00');

-- Starting home page data (admin can change or delete all of this)
INSERT INTO `banners` (`image`, `title`, `sort_order`) VALUES
('assets/images/slider-coupon.jpg', '৳50 coupon for new users', 1),
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
(5, 'বাসা পরিষ্কার',  'Home Cleaning',    'assets/images/icons/cleaning.svg',   'mint',  5),
(6, 'বাসা / অফিস শিফটিং', 'Home Shifting', 'assets/images/icons/shifting.svg',   'lilac', 6),
(7, 'রংয়ের কাজ',     'Painting Service', 'assets/images/icons/painting.svg',   'sand',  7),
(8, 'পোকামাকড় দমন',  'Pest Control',     'assets/images/icons/pest.svg',       'rose',  8);

INSERT INTO `sub_services` (`service_id`, `name_bn`, `name_en`, `detail_bn`, `detail_en`, `price`, `rating`, `rating_count`, `popular_rank`) VALUES
(3, 'এসি সার্ভিস',        'AC Service',      '(পরিষ্কার/সার্ভিস)',      '(cleaning/servicing)',   500, 4.8, 120, 1),
(2, 'প্লাম্বিং কাজ',       'Plumbing Work',   '(ফিটিং/রিপেয়ার)',         '(fitting/repair)',       400, 4.7,  98, 2),
(5, 'বাসা পরিষ্কার',      'Home Cleaning',   '(ডিপ ক্লিনিং)',            '(deep cleaning)',        600, 4.6,  76, 3),
(1, 'ইলেকট্রিক কাজ',      'Electrical Work', '(ইনস্টলেশন/রিপেয়ার)',     '(installation/repair)',  500, 4.8,  64, 4),
(4, 'লন্ড্রি সার্ভিস',     'Laundry Service', '(ওয়াশ ও ড্রাই ক্লিন)',     '(wash & dry clean)',     300, 4.5,  42, NULL),
(6, 'বাসা শিফটিং',        'Home Shifting',   '(মালামাল প্যাকিং ও পরিবহন)', '(packing & moving)',    3000, 4.6,  31, NULL),
(7, 'ঘর রং করা',          'Room Painting',   '(ভেতর ও বাইরে)',           '(inside & outside)',    2500, 4.4,  18, NULL),
(8, 'পোকামাকড় দমন',       'Pest Control',    '(তেলাপোকা/উইপোকা)',        '(cockroach/termite)',    800, 4.5,  23, NULL);

INSERT INTO `notifications` (`title`, `message`) VALUES
('৳50 কুপন!', 'নতুন অ্যাকাউন্ট খুললেই প্রথম বুকিংয়ে ৳50 ছাড়।');

-- Sample service providers (admin can change or delete them)
INSERT INTO `providers` (`id`, `service_id`, `name`, `mobile`, `age`, `gender`, `area`, `address`, `status`, `created_by`, `created_at`, `note`) VALUES
(1, 1, 'RAHIM MIA', '01811111111', 34, 'male', 'Kachari', 'House 12, Kachari Road, Mymensingh', 'active', 1, '2026-02-01 10:00:00', NULL),
(2, 2, 'SOHEL RANA', '01822222222', 29, 'male', 'Boro Bazar', '45 Boro Bazar, Mymensingh', 'active', 1, '2026-02-15 11:00:00', '10 years experience'),
(3, 3, 'JAMAL HOSSAIN', '01833333333', 41, 'male', 'Ganginapar', 'Ganginapar Lane 3, Mymensingh', 'active', 2, '2026-03-05 09:30:00', NULL),
(4, 1, 'BABUL MIA', '01844444444', 46, 'male', 'Natun Bazar', 'Natun Bazar Road 7, Mymensingh', 'inactive', 3, '2026-03-30 14:00:00', 'On leave'),
(5, 5, 'SHIRIN AKTER', '01855555555', 31, 'female', 'Akua', 'Akua Bypass, Mymensingh', 'active', 1, '2026-04-20 10:45:00', 'Home cleaning team lead'),
(6, 4, 'KHOKON DAS', '01866666666', 38, 'male', 'Choto Bazar', 'Choto Bazar Lane 2, Mymensingh', 'active', 6, '2026-05-11 16:20:00', NULL),
(7, 2, 'MAMUN HOSSAIN', '01877777777', 25, 'male', 'Station Road', 'Station Road 18, Mymensingh', 'active', 2, '2026-06-03 12:10:00', NULL),
(8, 3, 'ROBIUL ALAM', '01888888888', 52, 'male', 'Cantonment', 'Cantonment Gate 1, Mymensingh', 'active', 8, '2026-07-14 09:00:00', 'AC specialist'),
(9, 5, 'HASINA BEGUM', '01899999999', 44, 'female', 'Shehora', 'Shehora Road 4, Mymensingh', 'active', 1, '2026-08-25 15:30:00', NULL),
(10, 1, 'TUHIN AHMED', '01810101010', 22, 'male', 'Durgabari', 'Durgabari Road 9, Mymensingh', 'active', 9, '2026-10-02 11:00:00', 'New, weekends only');

INSERT INTO `sub_service_providers` (`sub_service_id`, `provider_id`) VALUES
(4, 1), (4, 4), (4, 10), (2, 2), (2, 7), (1, 3), (1, 8), (3, 5), (3, 9), (5, 6);


-- Sample coupons, bookings and wallet for the test user AYESHA SIDDIKA (id 1). Dates are around "now", so pending ones are in the future.
INSERT INTO `coupons` (`id`, `code`, `title_bn`, `title_en`, `amount`, `min_order`, `valid_days`, `expires_on`, `created_by`) VALUES
(1, 'WELCOME50', 'নতুন অ্যাকাউন্টের কুপন', 'New account coupon', 50, 0, 15, NULL, 1),
(2, 'SAVE100', 'বড় কাজে ৳100 ছাড়', '৳100 off big jobs', 100, 500, NULL, DATE_ADD(CURDATE(), INTERVAL 30 DAY), 1),
(3, 'CLEAN30', 'পরিষ্কারে ৳30 ছাড়', '৳30 off cleaning', 30, 300, NULL, DATE_ADD(CURDATE(), INTERVAL 10 DAY), 1);

INSERT INTO `bookings` (`id`, `user_id`, `sub_service_id`, `provider_id`, `scheduled_at`, `area`, `address`, `price`, `discount`, `total`, `payment`, `status`, `points`, `handled_by`, `created_at`) VALUES
(1, 1, 1, NULL, DATE_ADD(DATE(NOW()), INTERVAL '2 10' DAY_HOUR), 'BAU Area', 'BAU Campus, Teachers Quarter 12', 500, 0, 500, 'wallet', 'pending', 0, NULL, DATE_SUB(NOW(), INTERVAL 3 HOUR)),
(2, 1, 3, NULL, DATE_ADD(DATE(NOW()), INTERVAL '4 14' DAY_HOUR), 'BAU Area', 'BAU Campus, Teachers Quarter 12', 600, 0, 600, 'cash', 'pending', 0, NULL, DATE_SUB(NOW(), INTERVAL 2 HOUR)),
(3, 1, 2, 2, DATE_ADD(DATE(NOW()), INTERVAL '1 11' DAY_HOUR), 'BAU Area', 'BAU Campus, Teachers Quarter 12', 400, 0, 400, 'cash', 'ongoing', 0, 11, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(4, 1, 4, 1, DATE_SUB(DATE(NOW()), INTERVAL 5 DAY) + INTERVAL 10 HOUR, 'BAU Area', 'BAU Campus, Teachers Quarter 12', 500, 0, 500, 'wallet', 'completed', 50, 1, DATE_SUB(NOW(), INTERVAL 6 DAY)),
(5, 1, 5, 6, DATE_SUB(DATE(NOW()), INTERVAL 9 DAY) + INTERVAL 16 HOUR, 'BAU Area', 'BAU Campus, Teachers Quarter 12', 300, 0, 300, 'cash', 'completed', 30, 11, DATE_SUB(NOW(), INTERVAL 10 DAY)),
(6, 1, 3, NULL, DATE_SUB(DATE(NOW()), INTERVAL 3 DAY) + INTERVAL 9 HOUR, 'BAU Area', 'BAU Campus, Teachers Quarter 12', 600, 0, 600, 'wallet', 'cancelled', 0, NULL, DATE_SUB(NOW(), INTERVAL 4 DAY));
UPDATE `bookings` SET `cancelled_by` = 'user' WHERE `id` = 6;

INSERT INTO `wallet_tx` (`user_id`, `kind`, `amount`, `status`, `method`, `trx_id`, `booking_id`, `checked_by`, `checked_at`, `created_at`) VALUES
(1, 'topup',   2000, 'successful', 'bkash', '9KX4T2LMQ8', NULL, 1, DATE_SUB(NOW(), INTERVAL 7 DAY), DATE_SUB(NOW(), INTERVAL 7 DAY)),
(1, 'payment', -500, 'successful', NULL, NULL, 4, NULL, NULL, DATE_SUB(NOW(), INTERVAL 6 DAY)),
(1, 'payment', -600, 'successful', NULL, NULL, 6, NULL, NULL, DATE_SUB(NOW(), INTERVAL 4 DAY)),
(1, 'refund',   600, 'successful', NULL, NULL, 6, NULL, NULL, DATE_SUB(NOW(), INTERVAL 3 DAY)),
(1, 'topup',    250, 'successful', 'nagad', '7HN2PQ5R1D', NULL, 11, DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY)),
(1, 'payment', -500, 'successful', NULL, NULL, 1, NULL, NULL, DATE_SUB(NOW(), INTERVAL 3 HOUR)),
(1, 'topup',    500, 'pending', 'bkash', '5TR8WQ3MZ1', NULL, NULL, NULL, DATE_SUB(NOW(), INTERVAL 1 HOUR)),
(2, 'topup',    300, 'pending', 'rocket', '1234567890', NULL, NULL, NULL, DATE_SUB(NOW(), INTERVAL 30 MINUTE));

UPDATE `users` SET `birth_date` = '2002-01-15', `address` = 'BAU Campus, Teachers Quarter 12' WHERE `id` = 1;
INSERT INTO `favourites` (`user_id`, `sub_service_id`) VALUES (1, 1), (1, 4);

-- Sample chats: support chat + the chat about the ongoing plumbing booking (#SHM00003)
INSERT INTO `conversations` (`id`, `user_id`, `booking_id`, `last_message_at`, `user_seen_at`, `staff_seen_at`, `created_at`) VALUES
(1, 1, NULL, DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY)),
(2, 1, 3, DATE_SUB(NOW(), INTERVAL 20 MINUTE), DATE_SUB(NOW(), INTERVAL 2 HOUR), DATE_SUB(NOW(), INTERVAL 20 MINUTE), DATE_SUB(NOW(), INTERVAL 1 DAY));
INSERT INTO `messages` (`conversation_id`, `sender`, `staff_id`, `body`, `created_at`) VALUES
(1, 'user', NULL, 'আমার ওয়ালেটে টাকা যোগ করেছি, কখন দেখাবে?', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(1, 'staff', 11, 'আপনার টাকা যোগ হয়েছে। আর কোনো সাহায্য লাগলে জানাবেন।', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(2, 'user', NULL, 'সেবাদাতা কখন আসবেন?', DATE_SUB(NOW(), INTERVAL 3 HOUR)),
(2, 'staff', 1, 'SOHEL RANA আগামীকাল সকাল ১১টায় আসবেন। তাঁর নম্বর বুকিংয়ে দেওয়া আছে।', DATE_SUB(NOW(), INTERVAL 20 MINUTE));


-- the 2 done seed bookings: when they were finished, the office's 15 % and the cash the provider took
UPDATE `bookings` SET `completed_at` = `scheduled_at` + INTERVAL 2 HOUR, `commission` = ROUND(`total` * 15 / 100),
                      `provider_cash` = IF(`payment` = 'cash', `total`, 0)
WHERE `status` = 'completed';
-- =====================================================================
-- DEMO DATA: some users, providers, bookings, top-ups and chats, to see how the site looks when it is busy.
-- Made by a small script with a fixed random seed. Everything is relative to NOW(), so it always looks current.
-- Demo users log in with password User@1234. To start empty, delete everything from here to the end.
-- =====================================================================
INSERT INTO `users` (`id`, `name`, `mobile`, `email`, `password`, `area`, `gender`, `status`, `created_at`, `last_login_at`, `address`) VALUES
(11, 'PAPIYA SARKAR', '01598698797', NULL, '$2y$10$x0WAkmZXkyND5n3oa0m7a.FbYFfg6qTKAyBic0n7T/rUlQeZ.4GhG', 'Durgabari', 'female', 'active', DATE_SUB(NOW(), INTERVAL 342198 MINUTE), DATE_SUB(NOW(), INTERVAL 14638 MINUTE), 'House 50, Road 11'),
(12, 'LAILA ARJUMAND', '01353645568', NULL, '$2y$10$x0WAkmZXkyND5n3oa0m7a.FbYFfg6qTKAyBic0n7T/rUlQeZ.4GhG', 'Kachi Jhuli Mor', 'female', 'inactive', DATE_SUB(NOW(), INTERVAL 238628 MINUTE), DATE_SUB(NOW(), INTERVAL 32136 MINUTE), 'House 71, Road 9'),
(13, 'RASHIDA BEGUM', '01540557616', 'rashida.begum@gmail.com', '$2y$10$x0WAkmZXkyND5n3oa0m7a.FbYFfg6qTKAyBic0n7T/rUlQeZ.4GhG', 'Pondit Para', 'female', 'active', DATE_SUB(NOW(), INTERVAL 242714 MINUTE), DATE_SUB(NOW(), INTERVAL 46955 MINUTE), 'House 40, Road 2'),
(14, 'MOSTAFIZUR RAHMAN', '01969788093', NULL, '$2y$10$x0WAkmZXkyND5n3oa0m7a.FbYFfg6qTKAyBic0n7T/rUlQeZ.4GhG', 'Amlapara', 'male', 'active', DATE_SUB(NOW(), INTERVAL 317539 MINUTE), DATE_SUB(NOW(), INTERVAL 34388 MINUTE), 'House 95, Road 19'),
(15, 'JAHID HASAN', '01417784864', NULL, '$2y$10$x0WAkmZXkyND5n3oa0m7a.FbYFfg6qTKAyBic0n7T/rUlQeZ.4GhG', 'Cantonment', 'male', 'active', DATE_SUB(NOW(), INTERVAL 228014 MINUTE), DATE_SUB(NOW(), INTERVAL 15007 MINUTE), 'House 85, Road 7'),
(16, 'ANWAR HOSSAIN', '01897875347', 'anwar.hossain@gmail.com', '$2y$10$x0WAkmZXkyND5n3oa0m7a.FbYFfg6qTKAyBic0n7T/rUlQeZ.4GhG', 'Akua', 'male', 'active', DATE_SUB(NOW(), INTERVAL 157216 MINUTE), NULL, 'House 21, Road 10'),
(17, 'ASHRAFUL ALAM', '01384726714', NULL, '$2y$10$x0WAkmZXkyND5n3oa0m7a.FbYFfg6qTKAyBic0n7T/rUlQeZ.4GhG', 'Kestopur', 'male', 'active', DATE_SUB(NOW(), INTERVAL 168899 MINUTE), DATE_SUB(NOW(), INTERVAL 28739 MINUTE), 'House 38, Road 18'),
(18, 'SUMAIYA ISLAM', '01779819331', NULL, '$2y$10$x0WAkmZXkyND5n3oa0m7a.FbYFfg6qTKAyBic0n7T/rUlQeZ.4GhG', 'Police Line', 'female', 'active', DATE_SUB(NOW(), INTERVAL 79614 MINUTE), NULL, 'House 37, Road 12'),
(19, 'TANVIR AHMED', '01628379298', 'tanvir.ahmed@gmail.com', '$2y$10$x0WAkmZXkyND5n3oa0m7a.FbYFfg6qTKAyBic0n7T/rUlQeZ.4GhG', 'Station Road', 'male', 'active', DATE_SUB(NOW(), INTERVAL 167214 MINUTE), DATE_SUB(NOW(), INTERVAL 56411 MINUTE), 'House 18, Road 13'),
(20, 'SHAMIM REZA', '01860161055', 'shamim.reza@gmail.com', '$2y$10$x0WAkmZXkyND5n3oa0m7a.FbYFfg6qTKAyBic0n7T/rUlQeZ.4GhG', 'Akua', 'male', 'active', DATE_SUB(NOW(), INTERVAL 218907 MINUTE), DATE_SUB(NOW(), INTERVAL 52886 MINUTE), 'House 25, Road 24'),
(21, 'NUSRAT JAHAN', '01699229836', 'nusrat.jahan@gmail.com', '$2y$10$x0WAkmZXkyND5n3oa0m7a.FbYFfg6qTKAyBic0n7T/rUlQeZ.4GhG', 'Natok Gol Lane', 'female', 'active', DATE_SUB(NOW(), INTERVAL 162091 MINUTE), DATE_SUB(NOW(), INTERVAL 32613 MINUTE), 'House 81, Road 1'),
(22, 'HABIBUR RAHMAN', '01380737028', 'habibur.rahman@gmail.com', '$2y$10$x0WAkmZXkyND5n3oa0m7a.FbYFfg6qTKAyBic0n7T/rUlQeZ.4GhG', 'Shehora', 'male', 'active', DATE_SUB(NOW(), INTERVAL 20785 MINUTE), DATE_SUB(NOW(), INTERVAL 1487 MINUTE), 'House 109, Road 20'),
(23, 'MIZANUR RAHMAN', '01925247764', 'mizanur.rahman@gmail.com', '$2y$10$x0WAkmZXkyND5n3oa0m7a.FbYFfg6qTKAyBic0n7T/rUlQeZ.4GhG', 'Natun Bazar', 'male', 'active', DATE_SUB(NOW(), INTERVAL 225888 MINUTE), DATE_SUB(NOW(), INTERVAL 25117 MINUTE), 'House 83, Road 16'),
(24, 'NAYEEM ISLAM', '01717568205', NULL, '$2y$10$x0WAkmZXkyND5n3oa0m7a.FbYFfg6qTKAyBic0n7T/rUlQeZ.4GhG', 'Kali Bari', 'male', 'active', DATE_SUB(NOW(), INTERVAL 146447 MINUTE), DATE_SUB(NOW(), INTERVAL 15165 MINUTE), 'House 55, Road 12'),
(25, 'OBAIDUL HAQUE', '01339494821', 'obaidul.haque@gmail.com', '$2y$10$x0WAkmZXkyND5n3oa0m7a.FbYFfg6qTKAyBic0n7T/rUlQeZ.4GhG', 'Biddamoye School Area', 'male', 'active', DATE_SUB(NOW(), INTERVAL 328349 MINUTE), DATE_SUB(NOW(), INTERVAL 37232 MINUTE), 'House 104, Road 25'),
(26, 'FAHIM AHMED', '01554914524', NULL, '$2y$10$x0WAkmZXkyND5n3oa0m7a.FbYFfg6qTKAyBic0n7T/rUlQeZ.4GhG', 'Kachari', 'male', 'active', DATE_SUB(NOW(), INTERVAL 381603 MINUTE), DATE_SUB(NOW(), INTERVAL 6972 MINUTE), 'House 116, Road 25'),
(27, 'DIPA RANI', '01730411895', NULL, '$2y$10$x0WAkmZXkyND5n3oa0m7a.FbYFfg6qTKAyBic0n7T/rUlQeZ.4GhG', 'Akua', 'female', 'active', DATE_SUB(NOW(), INTERVAL 137644 MINUTE), NULL, 'House 113, Road 18'),
(28, 'TOWHID ISLAM', '01615846450', 'towhid.islam@gmail.com', '$2y$10$x0WAkmZXkyND5n3oa0m7a.FbYFfg6qTKAyBic0n7T/rUlQeZ.4GhG', 'Mintu College Area', 'male', 'active', DATE_SUB(NOW(), INTERVAL 214792 MINUTE), DATE_SUB(NOW(), INTERVAL 30326 MINUTE), 'House 22, Road 24'),
(29, 'SUMON MIA', '01659085380', 'sumon.mia@gmail.com', '$2y$10$x0WAkmZXkyND5n3oa0m7a.FbYFfg6qTKAyBic0n7T/rUlQeZ.4GhG', 'Boro Bazar', 'male', 'active', DATE_SUB(NOW(), INTERVAL 406136 MINUTE), DATE_SUB(NOW(), INTERVAL 50329 MINUTE), 'House 67, Road 1'),
(30, 'ARIFA SULTANA', '01906925666', 'arifa.sultana@gmail.com', '$2y$10$x0WAkmZXkyND5n3oa0m7a.FbYFfg6qTKAyBic0n7T/rUlQeZ.4GhG', 'Station Road', 'female', 'active', DATE_SUB(NOW(), INTERVAL 68773 MINUTE), DATE_SUB(NOW(), INTERVAL 42983 MINUTE), 'House 23, Road 11'),
(31, 'MIZANUR RAHMAN', '01923004876', 'mizanur.rahman85@gmail.com', '$2y$10$x0WAkmZXkyND5n3oa0m7a.FbYFfg6qTKAyBic0n7T/rUlQeZ.4GhG', 'Natun Bazar', 'male', 'active', DATE_SUB(NOW(), INTERVAL 378934 MINUTE), DATE_SUB(NOW(), INTERVAL 26013 MINUTE), 'House 70, Road 5'),
(32, 'SADIA AFRIN', '01545850037', 'sadia.afrin@gmail.com', '$2y$10$x0WAkmZXkyND5n3oa0m7a.FbYFfg6qTKAyBic0n7T/rUlQeZ.4GhG', 'Boro Bazar', 'female', 'active', DATE_SUB(NOW(), INTERVAL 96886 MINUTE), DATE_SUB(NOW(), INTERVAL 53992 MINUTE), 'House 21, Road 1'),
(33, 'ANWAR HOSSAIN', '01953557787', NULL, '$2y$10$x0WAkmZXkyND5n3oa0m7a.FbYFfg6qTKAyBic0n7T/rUlQeZ.4GhG', 'Trishal Bus Stand', 'male', 'active', DATE_SUB(NOW(), INTERVAL 191587 MINUTE), DATE_SUB(NOW(), INTERVAL 54337 MINUTE), 'House 114, Road 4'),
(34, 'BELAL UDDIN', '01360214584', 'belal.uddin@gmail.com', '$2y$10$x0WAkmZXkyND5n3oa0m7a.FbYFfg6qTKAyBic0n7T/rUlQeZ.4GhG', 'Town Hall Mor', 'male', 'active', DATE_SUB(NOW(), INTERVAL 5664 MINUTE), DATE_SUB(NOW(), INTERVAL 4291 MINUTE), 'House 66, Road 22'),
(35, 'DIPA RANI', '01506688281', 'dipa.rani@gmail.com', '$2y$10$x0WAkmZXkyND5n3oa0m7a.FbYFfg6qTKAyBic0n7T/rUlQeZ.4GhG', 'Town Hall Mor', 'female', 'active', DATE_SUB(NOW(), INTERVAL 116714 MINUTE), DATE_SUB(NOW(), INTERVAL 20105 MINUTE), 'House 113, Road 23');

INSERT INTO `providers` (`id`, `service_id`, `name`, `mobile`, `age`, `gender`, `area`, `address`, `status`, `created_by`, `created_at`, `note`) VALUES
(11, 1, 'ABDUL RAHMAN', '01943829932', 26, 'male', 'Baghmara', 'House 42, Baghmara, Mymensingh', 'active', 12, DATE_SUB(NOW(), INTERVAL 52741 MINUTE), 'Team of 3'),
(12, 1, 'RASEL AHMED', '01757718835', 49, 'male', 'Taltola', 'House 75, Taltola, Mymensingh', 'active', 1, DATE_SUB(NOW(), INTERVAL 60467 MINUTE), 'Night work OK'),
(13, 2, 'SHAHIN ALAM', '01825688557', 45, 'male', 'Cantonment', 'House 41, Cantonment, Mymensingh', 'active', 3, DATE_SUB(NOW(), INTERVAL 129832 MINUTE), 'Has own tools'),
(14, 2, 'TANVIR AHMED', '01632792645', 27, 'male', 'Durgabari', 'House 41, Durgabari, Mymensingh', 'active', 3, DATE_SUB(NOW(), INTERVAL 152614 MINUTE), NULL),
(15, 3, 'OBAIDUL HAQUE', '01928144976', 45, 'male', 'Dhopakhola', 'House 3, Dhopakhola, Mymensingh', 'active', 2, DATE_SUB(NOW(), INTERVAL 367831 MINUTE), 'Team of 3'),
(16, 3, 'SUMON MIA', '01388365506', 31, 'male', 'Akua', 'House 2, Akua, Mymensingh', 'active', 12, DATE_SUB(NOW(), INTERVAL 146611 MINUTE), NULL),
(17, 4, 'SADIA AFRIN', '01650031450', 33, 'female', 'Natun Bazar', 'House 59, Natun Bazar, Mymensingh', 'active', 11, DATE_SUB(NOW(), INTERVAL 344790 MINUTE), 'Has own tools'),
(18, 5, 'SHIULY AKTER', '01725373924', 25, 'female', 'Kachari', 'House 73, Kachari, Mymensingh', 'active', 11, DATE_SUB(NOW(), INTERVAL 371513 MINUTE), NULL),
(19, 5, 'ARIFA SULTANA', '01728837555', 40, 'female', 'Station Road', 'House 78, Station Road, Mymensingh', 'active', 3, DATE_SUB(NOW(), INTERVAL 185859 MINUTE), 'Weekends only'),
(20, 6, 'ASHRAFUL ALAM', '01775473548', 45, 'male', 'Boundary Road', 'House 39, Boundary Road, Mymensingh', 'active', 1, DATE_SUB(NOW(), INTERVAL 229808 MINUTE), NULL),
(21, 7, 'MOSTAFIZUR RAHMAN', '01691889575', 41, 'male', 'Jubilee Road', 'House 47, Jubilee Road, Mymensingh', 'active', 11, DATE_SUB(NOW(), INTERVAL 105808 MINUTE), 'Weekends only'),
(22, 8, 'NAYEEM ISLAM', '01319307336', 52, 'male', 'Station Road', 'House 68, Station Road, Mymensingh', 'active', 1, DATE_SUB(NOW(), INTERVAL 156315 MINUTE), 'Night work OK'),
(23, 1, 'MAHBUB ALAM', '01968983298', 29, 'male', 'Natun Bazar', 'House 19, Natun Bazar, Mymensingh', 'active', 12, DATE_SUB(NOW(), INTERVAL 314786 MINUTE), NULL),
(24, 1, 'ASHRAFUL ALAM', '01824852722', 23, 'male', 'Choto Bazar', 'House 24, Choto Bazar, Mymensingh', 'active', 3, DATE_SUB(NOW(), INTERVAL 350586 MINUTE), NULL),
(25, 2, 'LITON DAS', '01938581762', 40, 'male', 'Akua', 'House 19, Akua, Mymensingh', 'active', 1, DATE_SUB(NOW(), INTERVAL 261254 MINUTE), NULL),
(26, 2, 'FAHIM AHMED', '01345028316', 47, 'male', 'Durgabari', 'House 26, Durgabari, Mymensingh', 'active', 1, DATE_SUB(NOW(), INTERVAL 140816 MINUTE), '5 years experience'),
(27, 3, 'LITON DAS', '01566753478', 58, 'male', 'BAU Area', 'House 62, BAU Area, Mymensingh', 'active', 1, DATE_SUB(NOW(), INTERVAL 116870 MINUTE), NULL),
(28, 3, 'HASAN MAHMUD', '01521236360', 55, 'male', 'Cantonment', 'House 19, Cantonment, Mymensingh', 'active', 12, DATE_SUB(NOW(), INTERVAL 155292 MINUTE), 'Weekends only'),
(29, 4, 'ANWAR HOSSAIN', '01645721283', 42, 'male', 'Shehora', 'House 59, Shehora, Mymensingh', 'active', 12, DATE_SUB(NOW(), INTERVAL 325322 MINUTE), NULL),
(30, 5, 'PARVEZ AHMED', '01317758881', 37, 'male', 'Choto Bazar', 'House 38, Choto Bazar, Mymensingh', 'inactive', 11, DATE_SUB(NOW(), INTERVAL 36353 MINUTE), NULL);
INSERT INTO `sub_service_providers` (`sub_service_id`, `provider_id`) VALUES
(4, 11), (4, 12), (2, 13), (2, 14), (1, 15), (1, 16), (5, 17), (3, 18), (3, 19), (6, 20), (7, 21), (8, 22), (4, 23), (4, 24), (2, 25), (2, 26), (1, 27), (1, 28), (5, 29), (3, 30);

INSERT INTO `bookings` (`id`, `user_id`, `sub_service_id`, `provider_id`, `scheduled_at`, `area`, `address`, `lat`, `lng`, `note`, `price`, `discount`, `total`, `coupon_id`, `payment`, `status`, `points`, `cancelled_by`, `cancel_reason`, `final_total`, `final_note`, `commission`, `provider_cash`, `completed_at`, `rating`, `review`, `reviewed_at`, `complaint`, `complaint_at`, `complaint_status`, `complaint_answer`, `handled_by`, `created_at`) VALUES
(7, 17, 4, 12, DATE_SUB(DATE(NOW()), INTERVAL 8 DAY) + INTERVAL 11 HOUR, 'Kestopur', 'House 110, Road 1', NULL, NULL, NULL, 500, 0, 500, NULL, 'wallet', 'completed', 50, NULL, NULL, NULL, NULL, 75, 0, DATE_SUB(DATE(NOW()), INTERVAL 8 DAY) + INTERVAL 11 HOUR + INTERVAL 2 HOUR, NULL, NULL, NULL, 'ফ্যান আবার শব্দ করছে।', DATE_SUB(DATE(NOW()), INTERVAL 8 DAY) + INTERVAL 11 HOUR + INTERVAL 1 DAY, 'open', NULL, 2, DATE_SUB(NOW(), INTERVAL 12424 MINUTE)),
(8, 30, 1, 27, DATE_SUB(DATE(NOW()), INTERVAL 71 DAY) + INTERVAL 11 HOUR, 'Station Road', 'House 15, Road 18', NULL, NULL, NULL, 500, 0, 500, NULL, 'wallet', 'completed', 50, NULL, NULL, NULL, NULL, 75, 0, DATE_SUB(DATE(NOW()), INTERVAL 71 DAY) + INTERVAL 11 HOUR + INTERVAL 2 HOUR, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 11, DATE_SUB(NOW(), INTERVAL 104092 MINUTE)),
(9, 34, 3, 9, DATE_SUB(DATE(NOW()), INTERVAL 119 DAY) + INTERVAL 10 HOUR, 'Town Hall Mor', 'House 33, Road 22', 24.748753, 90.405778, NULL, 600, 50, 550, 1, 'cash', 'completed', 75, NULL, NULL, 750, 'Extra parts', 112, 750, DATE_SUB(DATE(NOW()), INTERVAL 119 DAY) + INTERVAL 10 HOUR + INTERVAL 2 HOUR, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 11, DATE_SUB(NOW(), INTERVAL 174268 MINUTE)),
(10, 24, 4, 11, DATE_SUB(DATE(NOW()), INTERVAL 121 DAY) + INTERVAL 11 HOUR, 'Kali Bari', 'House 9, Road 21', NULL, NULL, 'Bring a ladder', 500, 50, 450, 1, 'wallet', 'completed', 45, NULL, NULL, NULL, NULL, 68, 0, DATE_SUB(DATE(NOW()), INTERVAL 121 DAY) + INTERVAL 11 HOUR + INTERVAL 2 HOUR, 4, 'খুব ভালো কাজ করেছেন, সময়মতো এসেছেন।', DATE_SUB(DATE(NOW()), INTERVAL 121 DAY) + INTERVAL 11 HOUR + INTERVAL 5 HOUR, NULL, NULL, NULL, NULL, 15, DATE_SUB(NOW(), INTERVAL 177274 MINUTE)),
(11, 16, 4, NULL, DATE_ADD(DATE(NOW()), INTERVAL 7 DAY) + INTERVAL 9 HOUR, 'Akua', 'House 45, Road 8', NULL, NULL, NULL, 500, 0, 500, NULL, 'cash', 'pending', 0, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, DATE_SUB(NOW(), INTERVAL 1096 MINUTE)),
(12, 27, 1, 28, DATE_SUB(DATE(NOW()), INTERVAL 79 DAY) + INTERVAL 12 HOUR, 'Akua', 'House 27, Road 10', 24.743078, 90.396781, NULL, 500, 0, 500, NULL, 'cash', 'completed', 50, NULL, NULL, NULL, NULL, 75, 500, DATE_SUB(DATE(NOW()), INTERVAL 79 DAY) + INTERVAL 12 HOUR + INTERVAL 2 HOUR, NULL, NULL, NULL, 'পানির লাইন থেকে আবার পানি পড়ছে।', DATE_SUB(DATE(NOW()), INTERVAL 79 DAY) + INTERVAL 12 HOUR + INTERVAL 1 DAY, 'open', NULL, 2, DATE_SUB(NOW(), INTERVAL 115063 MINUTE)),
(13, 20, 6, 20, DATE_SUB(DATE(NOW()), INTERVAL 19 DAY) + INTERVAL 14 HOUR, 'Akua', 'House 12, Road 7', 24.735816, 90.399364, 'Second floor, left side', 3000, 0, 3000, NULL, 'cash', 'cancelled', 0, 'staff', 'Wrong address', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, DATE_SUB(NOW(), INTERVAL 31342 MINUTE)),
(14, 18, 1, 15, DATE_SUB(DATE(NOW()), INTERVAL 8 DAY) + INTERVAL 11 HOUR, 'Police Line', 'House 33, Road 16', NULL, NULL, 'After 5 pm better', 500, 50, 450, 1, 'cash', 'completed', 45, NULL, NULL, NULL, NULL, 68, 450, DATE_SUB(DATE(NOW()), INTERVAL 8 DAY) + INTERVAL 11 HOUR + INTERVAL 2 HOUR, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, DATE_SUB(NOW(), INTERVAL 14161 MINUTE)),
(15, 25, 4, NULL, DATE_SUB(DATE(NOW()), INTERVAL 139 DAY) + INTERVAL 9 HOUR, 'Biddamoye School Area', 'House 120, Road 22', 24.758885, 90.409241, 'Gate code 1234', 500, 0, 500, NULL, 'cash', 'cancelled', 0, 'user', NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, DATE_SUB(NOW(), INTERVAL 202013 MINUTE)),
(16, 34, 6, NULL, DATE_ADD(DATE(NOW()), INTERVAL 1 DAY) + INTERVAL 14 HOUR, 'Town Hall Mor', 'House 26, Road 18', 24.747448, 90.405772, NULL, 3000, 0, 3000, NULL, 'cash', 'pending', 0, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, DATE_SUB(NOW(), INTERVAL 702 MINUTE)),
(17, 29, 5, 6, DATE_SUB(DATE(NOW()), INTERVAL 104 DAY) + INTERVAL 9 HOUR, 'Boro Bazar', 'House 77, Road 2', 24.755262, 90.397386, 'Gate code 1234', 300, 0, 300, NULL, 'cash', 'completed', 30, NULL, NULL, NULL, NULL, 45, 300, DATE_SUB(DATE(NOW()), INTERVAL 104 DAY) + INTERVAL 9 HOUR + INTERVAL 2 HOUR, 5, 'খুব ভালো কাজ করেছেন, সময়মতো এসেছেন।', DATE_SUB(DATE(NOW()), INTERVAL 104 DAY) + INTERVAL 9 HOUR + INTERVAL 5 HOUR, NULL, NULL, NULL, NULL, 12, DATE_SUB(NOW(), INTERVAL 150482 MINUTE)),
(18, 16, 8, NULL, DATE_ADD(DATE(NOW()), INTERVAL 4 DAY) + INTERVAL 14 HOUR, 'Akua', 'House 34, Road 4', 24.742248, 90.403822, NULL, 800, 0, 800, NULL, 'cash', 'pending', 0, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, DATE_SUB(NOW(), INTERVAL 248 MINUTE)),
(19, 25, 3, 5, DATE_SUB(DATE(NOW()), INTERVAL 54 DAY) + INTERVAL 11 HOUR, 'Biddamoye School Area', 'House 5, Road 24', NULL, NULL, 'After 5 pm better', 600, 0, 600, NULL, 'cash', 'completed', 80, NULL, NULL, 800, 'কাজ বেশি ছিল', 120, 800, DATE_SUB(DATE(NOW()), INTERVAL 54 DAY) + INTERVAL 11 HOUR + INTERVAL 2 HOUR, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, DATE_SUB(NOW(), INTERVAL 80099 MINUTE)),
(20, 27, 5, 17, DATE_SUB(DATE(NOW()), INTERVAL 45 DAY) + INTERVAL 10 HOUR, 'Akua', 'House 40, Road 25', NULL, NULL, 'Please call before coming', 300, 0, 300, NULL, 'cash', 'completed', 30, NULL, NULL, NULL, NULL, 45, 300, DATE_SUB(DATE(NOW()), INTERVAL 45 DAY) + INTERVAL 10 HOUR + INTERVAL 2 HOUR, 5, 'খুব ভালো কাজ করেছেন, সময়মতো এসেছেন।', DATE_SUB(DATE(NOW()), INTERVAL 45 DAY) + INTERVAL 10 HOUR + INTERVAL 5 HOUR, 'কাজ পুরো শেষ করেননি।', DATE_SUB(DATE(NOW()), INTERVAL 45 DAY) + INTERVAL 10 HOUR + INTERVAL 1 DAY, 'solved', 'সেবাদাতা আবার গিয়ে বিনা খরচে ঠিক করে দিয়েছেন।', 3, DATE_SUB(NOW(), INTERVAL 68085 MINUTE)),
(21, 21, 6, NULL, DATE_ADD(DATE(NOW()), INTERVAL 7 DAY) + INTERVAL 17 HOUR, 'Natok Gol Lane', 'House 12, Road 13', 24.749998, 90.406851, 'Please call before coming', 3000, 0, 3000, NULL, 'cash', 'pending', 0, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, DATE_SUB(NOW(), INTERVAL 895 MINUTE)),
(22, 23, 3, NULL, DATE_SUB(DATE(NOW()), INTERVAL 140 DAY) + INTERVAL 12 HOUR, 'Natun Bazar', 'House 110, Road 7', NULL, NULL, NULL, 600, 0, 600, NULL, 'cash', 'cancelled', 0, 'staff', 'Customer not reachable', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 13, DATE_SUB(NOW(), INTERVAL 205671 MINUTE)),
(23, 17, 1, 8, DATE_ADD(DATE(NOW()), INTERVAL 3 DAY) + INTERVAL 11 HOUR, 'Kestopur', 'House 80, Road 14', NULL, NULL, 'Gate code 1234', 500, 0, 500, NULL, 'cash', 'ongoing', 0, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 12, DATE_SUB(NOW(), INTERVAL 900 MINUTE)),
(24, 26, 1, 16, DATE_SUB(DATE(NOW()), INTERVAL 71 DAY) + INTERVAL 10 HOUR, 'Kachari', 'House 90, Road 3', 24.753066, 90.401705, 'Please call before coming', 500, 0, 500, NULL, 'wallet', 'completed', 50, NULL, NULL, NULL, NULL, 75, 0, DATE_SUB(DATE(NOW()), INTERVAL 71 DAY) + INTERVAL 10 HOUR + INTERVAL 2 HOUR, 2, 'ভালো, তবে একটু দেরি হয়েছে।', DATE_SUB(DATE(NOW()), INTERVAL 71 DAY) + INTERVAL 10 HOUR + INTERVAL 5 HOUR, NULL, NULL, NULL, NULL, 13, DATE_SUB(NOW(), INTERVAL 104677 MINUTE)),
(25, 28, 6, 20, DATE_SUB(DATE(NOW()), INTERVAL 81 DAY) + INTERVAL 10 HOUR, 'Mintu College Area', 'House 91, Road 2', NULL, NULL, 'After 5 pm better', 3000, 50, 2950, 1, 'cash', 'completed', 295, NULL, NULL, NULL, NULL, 442, 2950, DATE_SUB(DATE(NOW()), INTERVAL 81 DAY) + INTERVAL 10 HOUR + INTERVAL 2 HOUR, 2, 'ভালো, তবে একটু দেরি হয়েছে।', DATE_SUB(DATE(NOW()), INTERVAL 81 DAY) + INTERVAL 10 HOUR + INTERVAL 5 HOUR, NULL, NULL, NULL, NULL, 2, DATE_SUB(NOW(), INTERVAL 120131 MINUTE)),
(26, 16, 3, 5, DATE_ADD(DATE(NOW()), INTERVAL 2 DAY) + INTERVAL 14 HOUR, 'Akua', 'House 87, Road 10', 24.738622, 90.400307, NULL, 600, 0, 600, NULL, 'cash', 'ongoing', 0, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, DATE_SUB(NOW(), INTERVAL 2543 MINUTE)),
(27, 30, 4, 24, DATE_SUB(DATE(NOW()), INTERVAL 124 DAY) + INTERVAL 16 HOUR, 'Station Road', 'House 99, Road 18', NULL, NULL, NULL, 500, 0, 500, NULL, 'cash', 'completed', 50, NULL, NULL, NULL, NULL, 75, 500, DATE_SUB(DATE(NOW()), INTERVAL 124 DAY) + INTERVAL 16 HOUR + INTERVAL 2 HOUR, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, DATE_SUB(NOW(), INTERVAL 180900 MINUTE)),
(28, 20, 4, NULL, DATE_ADD(DATE(NOW()), INTERVAL 4 DAY) + INTERVAL 10 HOUR, 'Akua', 'House 12, Road 20', 24.737592, 90.402675, NULL, 500, 0, 500, NULL, 'cash', 'pending', 0, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, DATE_SUB(NOW(), INTERVAL 18 MINUTE)),
(29, 24, 1, 28, DATE_ADD(DATE(NOW()), INTERVAL 0 DAY) + INTERVAL 9 HOUR, 'Kali Bari', 'House 81, Road 11', 24.752314, 90.399701, 'Second floor, left side', 500, 0, 500, NULL, 'cash', 'ongoing', 0, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, DATE_SUB(NOW(), INTERVAL 464 MINUTE)),
(30, 16, 1, 16, DATE_SUB(DATE(NOW()), INTERVAL 6 DAY) + INTERVAL 9 HOUR, 'Akua', 'House 99, Road 2', NULL, NULL, NULL, 500, 0, 500, NULL, 'cash', 'completed', 50, NULL, NULL, NULL, NULL, 75, 500, DATE_SUB(DATE(NOW()), INTERVAL 6 DAY) + INTERVAL 9 HOUR + INTERVAL 2 HOUR, 4, NULL, DATE_SUB(DATE(NOW()), INTERVAL 6 DAY) + INTERVAL 9 HOUR + INTERVAL 5 HOUR, NULL, NULL, NULL, NULL, 15, DATE_SUB(NOW(), INTERVAL 12710 MINUTE)),
(31, 31, 2, NULL, DATE_SUB(DATE(NOW()), INTERVAL 127 DAY) + INTERVAL 11 HOUR, 'Natun Bazar', 'House 58, Road 23', 24.749012, 90.410033, 'After 5 pm better', 400, 0, 400, NULL, 'cash', 'cancelled', 0, 'staff', 'Wrong address', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 12, DATE_SUB(NOW(), INTERVAL 184585 MINUTE)),
(32, 34, 2, NULL, DATE_SUB(DATE(NOW()), INTERVAL 108 DAY) + INTERVAL 10 HOUR, 'Town Hall Mor', 'House 1, Road 6', NULL, NULL, NULL, 400, 0, 400, NULL, 'cash', 'cancelled', 0, 'staff', 'Customer not reachable', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, DATE_SUB(NOW(), INTERVAL 158855 MINUTE)),
(33, 19, 3, 19, DATE_SUB(DATE(NOW()), INTERVAL 10 DAY) + INTERVAL 12 HOUR, 'Station Road', 'House 22, Road 13', NULL, NULL, NULL, 600, 50, 550, 1, 'cash', 'completed', 75, NULL, NULL, 750, 'কাজ বেশি ছিল', 112, 750, DATE_SUB(DATE(NOW()), INTERVAL 10 DAY) + INTERVAL 12 HOUR + INTERVAL 2 HOUR, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 12, DATE_SUB(NOW(), INTERVAL 14867 MINUTE)),
(34, 26, 1, 27, DATE_SUB(DATE(NOW()), INTERVAL 111 DAY) + INTERVAL 10 HOUR, 'Kachari', 'House 118, Road 2', 24.756916, 90.403142, NULL, 500, 0, 500, NULL, 'wallet', 'completed', 50, NULL, NULL, NULL, NULL, 75, 0, DATE_SUB(DATE(NOW()), INTERVAL 111 DAY) + INTERVAL 10 HOUR + INTERVAL 2 HOUR, 4, 'Very professional, thanks!', DATE_SUB(DATE(NOW()), INTERVAL 111 DAY) + INTERVAL 10 HOUR + INTERVAL 5 HOUR, NULL, NULL, NULL, NULL, 11, DATE_SUB(NOW(), INTERVAL 163934 MINUTE)),
(35, 16, 6, 20, DATE_SUB(DATE(NOW()), INTERVAL 113 DAY) + INTERVAL 17 HOUR, 'Akua', 'House 111, Road 15', NULL, NULL, 'Gate code 1234', 3000, 0, 3000, NULL, 'cash', 'completed', 300, NULL, NULL, NULL, NULL, 450, 3000, DATE_SUB(DATE(NOW()), INTERVAL 113 DAY) + INTERVAL 17 HOUR + INTERVAL 2 HOUR, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 11, DATE_SUB(NOW(), INTERVAL 163126 MINUTE)),
(36, 11, 4, 10, DATE_ADD(DATE(NOW()), INTERVAL 3 DAY) + INTERVAL 10 HOUR, 'Durgabari', 'House 26, Road 16', 24.755474, 90.40086, NULL, 500, 0, 500, NULL, 'cash', 'ongoing', 0, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 11, DATE_SUB(NOW(), INTERVAL 2652 MINUTE)),
(37, 16, 8, NULL, DATE_ADD(DATE(NOW()), INTERVAL 6 DAY) + INTERVAL 17 HOUR, 'Akua', 'House 48, Road 18', 24.73913, 90.398013, NULL, 800, 0, 800, NULL, 'cash', 'pending', 0, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, DATE_SUB(NOW(), INTERVAL 208 MINUTE)),
(38, 16, 2, 25, DATE_SUB(DATE(NOW()), INTERVAL 75 DAY) + INTERVAL 9 HOUR, 'Akua', 'House 34, Road 18', 24.736066, 90.39665, NULL, 400, 0, 400, NULL, 'cash', 'completed', 40, NULL, NULL, NULL, NULL, 60, 400, DATE_SUB(DATE(NOW()), INTERVAL 75 DAY) + INTERVAL 9 HOUR + INTERVAL 2 HOUR, 4, NULL, DATE_SUB(DATE(NOW()), INTERVAL 75 DAY) + INTERVAL 9 HOUR + INTERVAL 5 HOUR, NULL, NULL, NULL, NULL, 13, DATE_SUB(NOW(), INTERVAL 112065 MINUTE)),
(39, 13, 6, 20, DATE_ADD(DATE(NOW()), INTERVAL 3 DAY) + INTERVAL 14 HOUR, 'Pondit Para', 'House 101, Road 17', 24.74772, 90.405026, NULL, 3000, 50, 2950, 1, 'cash', 'ongoing', 0, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 14, DATE_SUB(NOW(), INTERVAL 1121 MINUTE)),
(40, 22, 7, 21, DATE_SUB(DATE(NOW()), INTERVAL 51 DAY) + INTERVAL 17 HOUR, 'Shehora', 'House 16, Road 15', 24.74343, 90.420232, 'Second floor, left side', 2500, 0, 2500, NULL, 'cash', 'completed', 250, NULL, NULL, NULL, NULL, 375, 2500, DATE_SUB(DATE(NOW()), INTERVAL 51 DAY) + INTERVAL 17 HOUR + INTERVAL 2 HOUR, 5, NULL, DATE_SUB(DATE(NOW()), INTERVAL 51 DAY) + INTERVAL 17 HOUR + INTERVAL 5 HOUR, NULL, NULL, NULL, NULL, 12, DATE_SUB(NOW(), INTERVAL 75587 MINUTE)),
(41, 33, 2, 14, DATE_SUB(DATE(NOW()), INTERVAL 97 DAY) + INTERVAL 11 HOUR, 'Trishal Bus Stand', 'House 104, Road 5', 24.738111, 90.405074, 'After 5 pm better', 400, 0, 400, NULL, 'cash', 'completed', 60, NULL, NULL, 600, 'Extra parts', 90, 600, DATE_SUB(DATE(NOW()), INTERVAL 97 DAY) + INTERVAL 11 HOUR + INTERVAL 2 HOUR, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 14, DATE_SUB(NOW(), INTERVAL 141915 MINUTE)),
(42, 17, 1, 28, DATE_ADD(DATE(NOW()), INTERVAL 1 DAY) + INTERVAL 17 HOUR, 'Kestopur', 'House 91, Road 24', 24.741907, 90.400562, NULL, 500, 0, 500, NULL, 'cash', 'ongoing', 0, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3, DATE_SUB(NOW(), INTERVAL 240 MINUTE)),
(43, 16, 2, 25, DATE_ADD(DATE(NOW()), INTERVAL 0 DAY) + INTERVAL 10 HOUR, 'Akua', 'House 101, Road 22', NULL, NULL, 'Bring a ladder', 400, 0, 400, NULL, 'cash', 'ongoing', 0, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, DATE_SUB(NOW(), INTERVAL 1384 MINUTE)),
(44, 32, 5, 29, DATE_SUB(DATE(NOW()), INTERVAL 12 DAY) + INTERVAL 9 HOUR, 'Boro Bazar', 'House 70, Road 17', 24.761126, 90.398425, 'Bring a ladder', 300, 0, 300, NULL, 'cash', 'completed', 30, NULL, NULL, NULL, NULL, 45, 300, DATE_SUB(DATE(NOW()), INTERVAL 12 DAY) + INTERVAL 9 HOUR + INTERVAL 2 HOUR, 3, 'দাম একটু বেশি মনে হয়েছে।', DATE_SUB(DATE(NOW()), INTERVAL 12 DAY) + INTERVAL 9 HOUR + INTERVAL 5 HOUR, NULL, NULL, NULL, NULL, 2, DATE_SUB(NOW(), INTERVAL 20470 MINUTE)),
(45, 33, 7, 21, DATE_SUB(DATE(NOW()), INTERVAL 3 DAY) + INTERVAL 10 HOUR, 'Trishal Bus Stand', 'House 30, Road 8', 24.737414, 90.408976, 'Second floor, left side', 2500, 0, 2500, NULL, 'wallet', 'completed', 250, NULL, NULL, NULL, NULL, 375, 0, DATE_SUB(DATE(NOW()), INTERVAL 3 DAY) + INTERVAL 10 HOUR + INTERVAL 2 HOUR, 2, 'Very professional, thanks!', DATE_SUB(DATE(NOW()), INTERVAL 3 DAY) + INTERVAL 10 HOUR + INTERVAL 5 HOUR, NULL, NULL, NULL, NULL, 2, DATE_SUB(NOW(), INTERVAL 6826 MINUTE)),
(46, 16, 4, 24, DATE_SUB(DATE(NOW()), INTERVAL 46 DAY) + INTERVAL 18 HOUR, 'Akua', 'House 94, Road 7', NULL, NULL, 'Second floor, left side', 500, 0, 500, NULL, 'cash', 'completed', 50, NULL, NULL, NULL, NULL, 75, 500, DATE_SUB(DATE(NOW()), INTERVAL 46 DAY) + INTERVAL 18 HOUR + INTERVAL 2 HOUR, 3, 'ভালো, তবে একটু দেরি হয়েছে।', DATE_SUB(DATE(NOW()), INTERVAL 46 DAY) + INTERVAL 18 HOUR + INTERVAL 5 HOUR, NULL, NULL, NULL, NULL, 14, DATE_SUB(NOW(), INTERVAL 69446 MINUTE)),
(47, 27, 2, 25, DATE_SUB(DATE(NOW()), INTERVAL 120 DAY) + INTERVAL 9 HOUR, 'Akua', 'House 14, Road 6', 24.742811, 90.402707, NULL, 400, 0, 400, NULL, 'cash', 'completed', 40, NULL, NULL, NULL, NULL, 60, 400, DATE_SUB(DATE(NOW()), INTERVAL 120 DAY) + INTERVAL 9 HOUR + INTERVAL 2 HOUR, 4, 'খুব ভালো কাজ করেছেন, সময়মতো এসেছেন।', DATE_SUB(DATE(NOW()), INTERVAL 120 DAY) + INTERVAL 9 HOUR + INTERVAL 5 HOUR, NULL, NULL, NULL, NULL, 12, DATE_SUB(NOW(), INTERVAL 176103 MINUTE)),
(48, 18, 5, 6, DATE_SUB(DATE(NOW()), INTERVAL 103 DAY) + INTERVAL 16 HOUR, 'Police Line', 'House 98, Road 23', NULL, NULL, 'Bring a ladder', 300, 0, 300, NULL, 'cash', 'cancelled', 0, 'staff', 'Wrong address', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3, DATE_SUB(NOW(), INTERVAL 148878 MINUTE)),
(49, 24, 4, 11, DATE_SUB(DATE(NOW()), INTERVAL 53 DAY) + INTERVAL 11 HOUR, 'Kali Bari', 'House 112, Road 11', NULL, NULL, NULL, 500, 0, 500, NULL, 'wallet', 'completed', 50, NULL, NULL, NULL, NULL, 75, 0, DATE_SUB(DATE(NOW()), INTERVAL 53 DAY) + INTERVAL 11 HOUR + INTERVAL 2 HOUR, 2, 'দাম একটু বেশি মনে হয়েছে।', DATE_SUB(DATE(NOW()), INTERVAL 53 DAY) + INTERVAL 11 HOUR + INTERVAL 5 HOUR, NULL, NULL, NULL, NULL, 14, DATE_SUB(NOW(), INTERVAL 78511 MINUTE)),
(50, 13, 1, 3, DATE_SUB(DATE(NOW()), INTERVAL 52 DAY) + INTERVAL 10 HOUR, 'Pondit Para', 'House 56, Road 7', NULL, NULL, 'Gate code 1234', 500, 0, 500, NULL, 'cash', 'completed', 50, NULL, NULL, NULL, NULL, 75, 500, DATE_SUB(DATE(NOW()), INTERVAL 52 DAY) + INTERVAL 10 HOUR + INTERVAL 2 HOUR, 4, 'দাম একটু বেশি মনে হয়েছে।', DATE_SUB(DATE(NOW()), INTERVAL 52 DAY) + INTERVAL 10 HOUR + INTERVAL 5 HOUR, NULL, NULL, NULL, NULL, 1, DATE_SUB(NOW(), INTERVAL 77904 MINUTE)),
(51, 14, 8, 22, DATE_SUB(DATE(NOW()), INTERVAL 19 DAY) + INTERVAL 10 HOUR, 'Amlapara', 'House 31, Road 21', 24.754994, 90.410749, NULL, 800, 0, 800, NULL, 'cash', 'completed', 80, NULL, NULL, NULL, NULL, 120, 800, DATE_SUB(DATE(NOW()), INTERVAL 19 DAY) + INTERVAL 10 HOUR + INTERVAL 2 HOUR, 4, 'ভালো, তবে একটু দেরি হয়েছে।', DATE_SUB(DATE(NOW()), INTERVAL 19 DAY) + INTERVAL 10 HOUR + INTERVAL 5 HOUR, NULL, NULL, NULL, NULL, 11, DATE_SUB(NOW(), INTERVAL 28435 MINUTE)),
(52, 14, 6, 20, DATE_SUB(DATE(NOW()), INTERVAL 87 DAY) + INTERVAL 9 HOUR, 'Amlapara', 'House 3, Road 25', 24.753035, 90.407703, NULL, 3000, 0, 3000, NULL, 'cash', 'completed', 300, NULL, NULL, NULL, NULL, 450, 3000, DATE_SUB(DATE(NOW()), INTERVAL 87 DAY) + INTERVAL 9 HOUR + INTERVAL 2 HOUR, 5, NULL, DATE_SUB(DATE(NOW()), INTERVAL 87 DAY) + INTERVAL 9 HOUR + INTERVAL 5 HOUR, NULL, NULL, NULL, NULL, 11, DATE_SUB(NOW(), INTERVAL 128347 MINUTE)),
(53, 22, 4, 10, DATE_ADD(DATE(NOW()), INTERVAL 1 DAY) + INTERVAL 15 HOUR, 'Shehora', 'House 120, Road 1', NULL, NULL, NULL, 500, 50, 450, 1, 'cash', 'ongoing', 0, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 12, DATE_SUB(NOW(), INTERVAL 623 MINUTE)),
(54, 17, 5, NULL, DATE_ADD(DATE(NOW()), INTERVAL 1 DAY) + INTERVAL 10 HOUR, 'Kestopur', 'House 21, Road 1', 24.743972, 90.399495, NULL, 300, 0, 300, NULL, 'cash', 'pending', 0, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, DATE_SUB(NOW(), INTERVAL 322 MINUTE)),
(55, 34, 2, 25, DATE_SUB(DATE(NOW()), INTERVAL 22 DAY) + INTERVAL 17 HOUR, 'Town Hall Mor', 'House 33, Road 4', NULL, NULL, 'After 5 pm better', 400, 0, 400, NULL, 'cash', 'completed', 40, NULL, NULL, NULL, NULL, 60, 400, DATE_SUB(DATE(NOW()), INTERVAL 22 DAY) + INTERVAL 17 HOUR + INTERVAL 2 HOUR, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 12, DATE_SUB(NOW(), INTERVAL 33971 MINUTE)),
(56, 18, 4, 10, DATE_SUB(DATE(NOW()), INTERVAL 30 DAY) + INTERVAL 17 HOUR, 'Police Line', 'House 66, Road 13', 24.755131, 90.413401, 'Second floor, left side', 500, 0, 500, NULL, 'cash', 'completed', 50, NULL, NULL, NULL, NULL, 75, 500, DATE_SUB(DATE(NOW()), INTERVAL 30 DAY) + INTERVAL 17 HOUR + INTERVAL 2 HOUR, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 11, DATE_SUB(NOW(), INTERVAL 43404 MINUTE)),
(57, 14, 3, NULL, DATE_ADD(DATE(NOW()), INTERVAL 3 DAY) + INTERVAL 10 HOUR, 'Amlapara', 'House 17, Road 6', 24.751876, 90.410752, NULL, 600, 0, 600, NULL, 'cash', 'pending', 0, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, DATE_SUB(NOW(), INTERVAL 272 MINUTE)),
(58, 18, 5, 17, DATE_SUB(DATE(NOW()), INTERVAL 53 DAY) + INTERVAL 17 HOUR, 'Police Line', 'House 51, Road 2', 24.761788, 90.412711, NULL, 300, 0, 300, NULL, 'cash', 'completed', 30, NULL, NULL, NULL, NULL, 45, 300, DATE_SUB(DATE(NOW()), INTERVAL 53 DAY) + INTERVAL 17 HOUR + INTERVAL 2 HOUR, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 15, DATE_SUB(NOW(), INTERVAL 80385 MINUTE));
INSERT INTO `coupon_uses` (`coupon_id`, `user_id`, `booking_id`, `used_at`) VALUES
(1, 34, 9, DATE_SUB(NOW(), INTERVAL 174268 MINUTE)), (1, 24, 10, DATE_SUB(NOW(), INTERVAL 177274 MINUTE)), (1, 18, 14, DATE_SUB(NOW(), INTERVAL 14161 MINUTE)), (1, 28, 25, DATE_SUB(NOW(), INTERVAL 120131 MINUTE)), (1, 19, 33, DATE_SUB(NOW(), INTERVAL 14867 MINUTE)), (1, 13, 39, DATE_SUB(NOW(), INTERVAL 1121 MINUTE)), (1, 22, 53, DATE_SUB(NOW(), INTERVAL 623 MINUTE));

INSERT INTO `wallet_tx` (`user_id`, `kind`, `amount`, `status`, `method`, `trx_id`, `booking_id`, `checked_by`, `checked_at`, `created_at`) VALUES
(28, 'topup', 300, 'successful', 'bkash', 'LSBCEHXKV8', NULL, 11, DATE_SUB(NOW(), INTERVAL 106868 MINUTE), DATE_SUB(NOW(), INTERVAL 107037 MINUTE)),
(26, 'topup', 2000, 'successful', 'rocket', 'RW4C9RM6NH', NULL, 11, DATE_SUB(NOW(), INTERVAL 67650 MINUTE), DATE_SUB(NOW(), INTERVAL 67898 MINUTE)),
(26, 'topup', 1500, 'successful', 'bkash', '9P2C7JURWT', NULL, 14, DATE_SUB(NOW(), INTERVAL 130874 MINUTE), DATE_SUB(NOW(), INTERVAL 130892 MINUTE)),
(26, 'topup', 1000, 'successful', 'bkash', 'HZPEL73HG4', NULL, 14, DATE_SUB(NOW(), INTERVAL 162180 MINUTE), DATE_SUB(NOW(), INTERVAL 162187 MINUTE)),
(30, 'topup', 500, 'successful', 'nagad', 'LTZFSLCPH6', NULL, 11, DATE_SUB(NOW(), INTERVAL 17695 MINUTE), DATE_SUB(NOW(), INTERVAL 17718 MINUTE)),
(15, 'topup', 2000, 'successful', 'bkash', 'N8DUFLETPA', NULL, 12, DATE_SUB(NOW(), INTERVAL 139425 MINUTE), DATE_SUB(NOW(), INTERVAL 139452 MINUTE)),
(15, 'topup', 2000, 'successful', 'bkash', 'LR9DYQ37FR', NULL, 1, DATE_SUB(NOW(), INTERVAL 72292 MINUTE), DATE_SUB(NOW(), INTERVAL 72304 MINUTE)),
(24, 'topup', 1500, 'successful', 'bkash', '6MLRLNX3TL', NULL, 15, DATE_SUB(NOW(), INTERVAL 149989 MINUTE), DATE_SUB(NOW(), INTERVAL 150041 MINUTE)),
(24, 'topup', 200, 'successful', 'bkash', 'JR8KWM9HJA', NULL, 14, DATE_SUB(NOW(), INTERVAL 94534 MINUTE), DATE_SUB(NOW(), INTERVAL 94584 MINUTE)),
(33, 'topup', 2000, 'successful', 'rocket', 'XYFBSRH2RV', NULL, 11, DATE_SUB(NOW(), INTERVAL 168978 MINUTE), DATE_SUB(NOW(), INTERVAL 169278 MINUTE)),
(33, 'topup', 500, 'successful', 'bkash', 'BXM7GR8L8H', NULL, 2, DATE_SUB(NOW(), INTERVAL 84695 MINUTE), DATE_SUB(NOW(), INTERVAL 84765 MINUTE)),
(33, 'topup', 200, 'successful', 'nagad', 'K4SB6UK2A6', NULL, 11, DATE_SUB(NOW(), INTERVAL 164946 MINUTE), DATE_SUB(NOW(), INTERVAL 165013 MINUTE)),
(34, 'topup', 500, 'successful', 'rocket', 'E2T66VS648', NULL, 12, DATE_SUB(NOW(), INTERVAL 89289 MINUTE), DATE_SUB(NOW(), INTERVAL 89454 MINUTE)),
(27, 'topup', 300, 'successful', 'bkash', 'PPU3QWLGQ2', NULL, 3, DATE_SUB(NOW(), INTERVAL 164683 MINUTE), DATE_SUB(NOW(), INTERVAL 164859 MINUTE)),
(11, 'topup', 3000, 'successful', 'bkash', 'V2SY6RMVCN', NULL, 12, DATE_SUB(NOW(), INTERVAL 107672 MINUTE), DATE_SUB(NOW(), INTERVAL 107763 MINUTE)),
(11, 'topup', 1500, 'successful', 'bkash', '629DVCXV4Q', NULL, 12, DATE_SUB(NOW(), INTERVAL 156207 MINUTE), DATE_SUB(NOW(), INTERVAL 156485 MINUTE)),
(11, 'topup', 3000, 'successful', 'nagad', 'Y2QA452ZR2', NULL, 13, DATE_SUB(NOW(), INTERVAL 79161 MINUTE), DATE_SUB(NOW(), INTERVAL 79180 MINUTE)),
(14, 'topup', 500, 'successful', 'bkash', '938L5NZBFG', NULL, 2, DATE_SUB(NOW(), INTERVAL 60645 MINUTE), DATE_SUB(NOW(), INTERVAL 60761 MINUTE)),
(17, 'topup', 300, 'successful', 'nagad', 'G4RWVAGEXK', NULL, 15, DATE_SUB(NOW(), INTERVAL 123999 MINUTE), DATE_SUB(NOW(), INTERVAL 124215 MINUTE)),
(17, 'topup', 300, 'successful', 'bkash', '9XMHFYRK67', NULL, 2, DATE_SUB(NOW(), INTERVAL 31684 MINUTE), DATE_SUB(NOW(), INTERVAL 31970 MINUTE)),
(29, 'topup', 500, 'successful', 'bkash', '75E69D68YG', NULL, 1, DATE_SUB(NOW(), INTERVAL 28005 MINUTE), DATE_SUB(NOW(), INTERVAL 28218 MINUTE)),
(29, 'topup', 500, 'successful', 'bkash', 'SR5GYLAKVS', NULL, 14, DATE_SUB(NOW(), INTERVAL 116499 MINUTE), DATE_SUB(NOW(), INTERVAL 116556 MINUTE)),
(34, 'topup', 1000, 'pending', 'nagad', '5VQM4SCS7U', NULL, NULL, NULL, DATE_SUB(NOW(), INTERVAL 2533 MINUTE)),
(19, 'topup', 500, 'pending', 'rocket', '6LYVSVHRAN', NULL, NULL, NULL, DATE_SUB(NOW(), INTERVAL 1070 MINUTE)),
(23, 'topup', 1000, 'pending', 'rocket', 'HX3572GZ56', NULL, NULL, NULL, DATE_SUB(NOW(), INTERVAL 1385 MINUTE)),
(14, 'topup', 1500, 'pending', 'nagad', 'AY2YS8AZ47', NULL, NULL, NULL, DATE_SUB(NOW(), INTERVAL 2335 MINUTE)),
(25, 'topup', 1000, 'pending', 'rocket', 'DHX5S2VSAP', NULL, NULL, NULL, DATE_SUB(NOW(), INTERVAL 97 MINUTE)),
(32, 'topup', 500, 'pending', 'bkash', 'DZ7GDUJG4Q', NULL, NULL, NULL, DATE_SUB(NOW(), INTERVAL 322 MINUTE)),
(17, 'payment', -500, 'successful', NULL, NULL, 7, NULL, NULL, DATE_SUB(NOW(), INTERVAL 12424 MINUTE)),
(30, 'payment', -500, 'successful', NULL, NULL, 8, NULL, NULL, DATE_SUB(NOW(), INTERVAL 104092 MINUTE)),
(24, 'payment', -450, 'successful', NULL, NULL, 10, NULL, NULL, DATE_SUB(NOW(), INTERVAL 177274 MINUTE)),
(26, 'payment', -500, 'successful', NULL, NULL, 24, NULL, NULL, DATE_SUB(NOW(), INTERVAL 104677 MINUTE)),
(26, 'payment', -500, 'successful', NULL, NULL, 34, NULL, NULL, DATE_SUB(NOW(), INTERVAL 163934 MINUTE)),
(33, 'payment', -2500, 'successful', NULL, NULL, 45, NULL, NULL, DATE_SUB(NOW(), INTERVAL 6826 MINUTE)),
(24, 'payment', -500, 'successful', NULL, NULL, 49, NULL, NULL, DATE_SUB(NOW(), INTERVAL 78511 MINUTE));

INSERT INTO `provider_payments` (`provider_id`, `amount`, `method`, `note`, `created_by`, `created_at`) VALUES
(3, -75, 'bkash', 'কমিশন জমা', 1, DATE_SUB(NOW(), INTERVAL 8006 MINUTE)),
(15, -68, 'cash', 'কমিশন জমা', 12, DATE_SUB(NOW(), INTERVAL 13184 MINUTE)),
(16, -75, 'bkash', 'কমিশন জমা', 11, DATE_SUB(NOW(), INTERVAL 19059 MINUTE)),
(17, -90, 'cash', 'কমিশন জমা', 12, DATE_SUB(NOW(), INTERVAL 13325 MINUTE)),
(22, -120, 'cash', 'কমিশন জমা', 1, DATE_SUB(NOW(), INTERVAL 28596 MINUTE)),
(25, -180, 'bkash', 'কমিশন জমা', 11, DATE_SUB(NOW(), INTERVAL 3162 MINUTE));
INSERT INTO `provider_leaves` (`provider_id`, `from_date`, `to_date`, `note`, `created_by`) VALUES
(1, DATE_ADD(CURDATE(), INTERVAL 1 DAY), DATE_ADD(CURDATE(), INTERVAL 3 DAY), 'বাড়ি যাবেন', 1),
(12, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 DAY), 'অসুস্থ', 11);

INSERT INTO `activity_log` (`staff_id`, `page`, `action`, `target_id`, `message`, `created_at`) VALUES
(11, 'bookings', 'complete', 9, '#SHM00009', DATE_SUB(NOW(), INTERVAL 14258 MINUTE)),
(1, 'bookings', 'complete', 7, '#SHM00007', DATE_SUB(NOW(), INTERVAL 13798 MINUTE)),
(1, 'bookings', 'assign', 10, '#SHM00010', DATE_SUB(NOW(), INTERVAL 12536 MINUTE)),
(1, 'bookings', 'assign', 9, '#SHM00009', DATE_SUB(NOW(), INTERVAL 12242 MINUTE)),
(11, 'bookings', 'assign', 7, '#SHM00007', DATE_SUB(NOW(), INTERVAL 11808 MINUTE)),
(1, 'bookings', 'complete', 10, '#SHM00010', DATE_SUB(NOW(), INTERVAL 9433 MINUTE)),
(1, 'services', 'save', 2, 'এসি সার্ভিস', DATE_SUB(NOW(), INTERVAL 8640 MINUTE)),
(11, 'bookings', 'assign', 8, '#SHM00008', DATE_SUB(NOW(), INTERVAL 8220 MINUTE)),
(11, 'bookings', 'complete', 25, '#SHM00025', DATE_SUB(NOW(), INTERVAL 6827 MINUTE)),
(1, 'bookings', 'complete', 12, '#SHM00012', DATE_SUB(NOW(), INTERVAL 6717 MINUTE)),
(11, 'bookings', 'assign', 23, '#SHM00023', DATE_SUB(NOW(), INTERVAL 6577 MINUTE)),
(1, 'bookings', 'complete', 24, '#SHM00024', DATE_SUB(NOW(), INTERVAL 6308 MINUTE)),
(11, 'bookings', 'assign', 14, '#SHM00014', DATE_SUB(NOW(), INTERVAL 6026 MINUTE)),
(1, 'bookings', 'assign', 24, '#SHM00024', DATE_SUB(NOW(), INTERVAL 6012 MINUTE)),
(11, 'banners', 'add', NULL, '', DATE_SUB(NOW(), INTERVAL 5760 MINUTE)),
(1, 'bookings', 'assign', 20, '#SHM00020', DATE_SUB(NOW(), INTERVAL 5494 MINUTE)),
(1, 'bookings', 'complete', 20, '#SHM00020', DATE_SUB(NOW(), INTERVAL 4769 MINUTE)),
(1, 'bookings', 'assign', 17, '#SHM00017', DATE_SUB(NOW(), INTERVAL 4607 MINUTE)),
(1, 'site', 'save', NULL, '', DATE_SUB(NOW(), INTERVAL 4320 MINUTE)),
(1, 'bookings', 'complete', 19, '#SHM00019', DATE_SUB(NOW(), INTERVAL 3461 MINUTE)),
(1, 'payouts', 'pay', 3, 'JAMAL HOSSAIN', DATE_SUB(NOW(), INTERVAL 2880 MINUTE)),
(11, 'bookings', 'complete', 14, '#SHM00014', DATE_SUB(NOW(), INTERVAL 2644 MINUTE)),
(1, 'bookings', 'complete', 8, '#SHM00008', DATE_SUB(NOW(), INTERVAL 2599 MINUTE)),
(11, 'bookings', 'complete', 17, '#SHM00017', DATE_SUB(NOW(), INTERVAL 2105 MINUTE)),
(11, 'bookings', 'assign', 25, '#SHM00025', DATE_SUB(NOW(), INTERVAL 766 MINUTE)),
(1, 'bookings', 'assign', 12, '#SHM00012', DATE_SUB(NOW(), INTERVAL 489 MINUTE)),
(11, 'providers', 'leave_add', 12, 'RASEL AHMED', DATE_SUB(NOW(), INTERVAL 300 MINUTE)),
(1, 'bookings', 'assign', 19, '#SHM00019', DATE_SUB(NOW(), INTERVAL 118 MINUTE));

INSERT INTO `conversations` (`id`, `user_id`, `booking_id`, `last_message_at`, `user_seen_at`, `staff_seen_at`, `created_at`) VALUES
(3, 34, NULL, DATE_SUB(NOW(3), INTERVAL 718680000 * 1000 MICROSECOND), DATE_SUB(NOW(3), INTERVAL 718980000 * 1000 MICROSECOND), DATE_SUB(NOW(3), INTERVAL 718620000 * 1000 MICROSECOND), DATE_SUB(NOW(), INTERVAL 12431 MINUTE)),
(4, 11, NULL, DATE_SUB(NOW(3), INTERVAL 397860000 * 1000 MICROSECOND), DATE_SUB(NOW(3), INTERVAL 397800000 * 1000 MICROSECOND), DATE_SUB(NOW(3), INTERVAL 397800000 * 1000 MICROSECOND), DATE_SUB(NOW(), INTERVAL 8071 MINUTE)),
(5, 23, NULL, DATE_SUB(NOW(3), INTERVAL 508740000 * 1000 MICROSECOND), DATE_SUB(NOW(3), INTERVAL 508680000 * 1000 MICROSECOND), DATE_SUB(NOW(3), INTERVAL 509040000 * 1000 MICROSECOND), DATE_SUB(NOW(), INTERVAL 9452 MINUTE)),
(6, 21, NULL, DATE_SUB(NOW(3), INTERVAL 661380000 * 1000 MICROSECOND), DATE_SUB(NOW(3), INTERVAL 661320000 * 1000 MICROSECOND), DATE_SUB(NOW(3), INTERVAL 661320000 * 1000 MICROSECOND), DATE_SUB(NOW(), INTERVAL 11442 MINUTE)),
(7, 17, NULL, DATE_SUB(NOW(3), INTERVAL 47760000 * 1000 MICROSECOND), DATE_SUB(NOW(3), INTERVAL 47700000 * 1000 MICROSECOND), DATE_SUB(NOW(3), INTERVAL 47700000 * 1000 MICROSECOND), DATE_SUB(NOW(), INTERVAL 1830 MINUTE)),
(8, 32, NULL, DATE_SUB(NOW(3), INTERVAL 372600000 * 1000 MICROSECOND), DATE_SUB(NOW(3), INTERVAL 372540000 * 1000 MICROSECOND), DATE_SUB(NOW(3), INTERVAL 372900000 * 1000 MICROSECOND), DATE_SUB(NOW(), INTERVAL 6874 MINUTE)),
(9, 24, NULL, DATE_SUB(NOW(3), INTERVAL 537180000 * 1000 MICROSECOND), DATE_SUB(NOW(3), INTERVAL 537120000 * 1000 MICROSECOND), DATE_SUB(NOW(3), INTERVAL 537480000 * 1000 MICROSECOND), DATE_SUB(NOW(), INTERVAL 9750 MINUTE)),
(10, 28, NULL, DATE_SUB(NOW(3), INTERVAL 2220000 * 1000 MICROSECOND), DATE_SUB(NOW(3), INTERVAL 2160000 * 1000 MICROSECOND), DATE_SUB(NOW(3), INTERVAL 2160000 * 1000 MICROSECOND), DATE_SUB(NOW(), INTERVAL 595 MINUTE)),
(11, 13, 39, DATE_SUB(NOW(3), INTERVAL 628740000 * 1000 MICROSECOND), DATE_SUB(NOW(3), INTERVAL 629040000 * 1000 MICROSECOND), DATE_SUB(NOW(3), INTERVAL 628680000 * 1000 MICROSECOND), DATE_SUB(NOW(), INTERVAL 11803 MINUTE)),
(12, 17, 23, DATE_SUB(NOW(3), INTERVAL 87780000 * 1000 MICROSECOND), DATE_SUB(NOW(3), INTERVAL 87720000 * 1000 MICROSECOND), DATE_SUB(NOW(3), INTERVAL 87720000 * 1000 MICROSECOND), DATE_SUB(NOW(), INTERVAL 1473 MINUTE)),
(13, 14, 57, DATE_SUB(NOW(3), INTERVAL 220560000 * 1000 MICROSECOND), DATE_SUB(NOW(3), INTERVAL 220500000 * 1000 MICROSECOND), DATE_SUB(NOW(3), INTERVAL 220500000 * 1000 MICROSECOND), DATE_SUB(NOW(), INTERVAL 4318 MINUTE)),
(14, 16, 18, DATE_SUB(NOW(3), INTERVAL 389880000 * 1000 MICROSECOND), DATE_SUB(NOW(3), INTERVAL 389820000 * 1000 MICROSECOND), DATE_SUB(NOW(3), INTERVAL 389820000 * 1000 MICROSECOND), DATE_SUB(NOW(), INTERVAL 7003 MINUTE)),
(15, 17, 54, DATE_SUB(NOW(3), INTERVAL 412740000 * 1000 MICROSECOND), DATE_SUB(NOW(3), INTERVAL 413040000 * 1000 MICROSECOND), DATE_SUB(NOW(3), INTERVAL 412680000 * 1000 MICROSECOND), DATE_SUB(NOW(), INTERVAL 7374 MINUTE));
INSERT INTO `messages` (`conversation_id`, `sender`, `staff_id`, `body`, `created_at`) VALUES
(3, 'user', NULL, 'দাম কি কম-বেশি হতে পারে?', DATE_SUB(NOW(3), INTERVAL 745260000 * 1000 MICROSECOND)),
(3, 'staff', 12, 'সেবাদাতা আগামীকাল সকাল ১০টায় আসবেন।', DATE_SUB(NOW(3), INTERVAL 718680000 * 1000 MICROSECOND)),
(4, 'user', NULL, 'কাজটা খুব ভালো হয়েছে, ধন্যবাদ!', DATE_SUB(NOW(3), INTERVAL 483660000 * 1000 MICROSECOND)),
(4, 'staff', 13, 'কুপনটা প্রথম বুকিংয়ে কাজ করে।', DATE_SUB(NOW(3), INTERVAL 467820000 * 1000 MICROSECOND)),
(4, 'user', NULL, 'আমার বুকিংয়ের সময় একটু বদলানো যাবে?', DATE_SUB(NOW(3), INTERVAL 443040000 * 1000 MICROSECOND)),
(4, 'staff', 13, 'সেবাদাতা আগামীকাল সকাল ১০টায় আসবেন।', DATE_SUB(NOW(3), INTERVAL 425940000 * 1000 MICROSECOND)),
(4, 'user', NULL, 'আজকে কি কেউ আসবেন?', DATE_SUB(NOW(3), INTERVAL 412020000 * 1000 MICROSECOND)),
(4, 'staff', 13, 'জি, আমরা দেখছি। একটু অপেক্ষা করুন।', DATE_SUB(NOW(3), INTERVAL 397860000 * 1000 MICROSECOND)),
(5, 'user', NULL, 'ঠিকানা: গলির শেষ মাথায় নীল গেট।', DATE_SUB(NOW(3), INTERVAL 566520000 * 1000 MICROSECOND)),
(5, 'staff', 13, 'জি, আমরা দেখছি। একটু অপেক্ষা করুন।', DATE_SUB(NOW(3), INTERVAL 543960000 * 1000 MICROSECOND)),
(5, 'user', NULL, 'ঠিকানা: গলির শেষ মাথায় নীল গেট।', DATE_SUB(NOW(3), INTERVAL 508740000 * 1000 MICROSECOND)),
(6, 'user', NULL, 'কুপন কাজ করছে না কেন?', DATE_SUB(NOW(3), INTERVAL 685920000 * 1000 MICROSECOND)),
(6, 'staff', 11, 'আপনার সাথে যোগাযোগ করা হবে।', DATE_SUB(NOW(3), INTERVAL 671760000 * 1000 MICROSECOND)),
(6, 'user', NULL, 'ঠিকানা: গলির শেষ মাথায় নীল গেট।', DATE_SUB(NOW(3), INTERVAL 661380000 * 1000 MICROSECOND)),
(7, 'user', NULL, 'আমার বুকিংয়ের সময় একটু বদলানো যাবে?', DATE_SUB(NOW(3), INTERVAL 109200000 * 1000 MICROSECOND)),
(7, 'staff', 12, 'ধন্যবাদ! আবার দরকার হলে জানাবেন।', DATE_SUB(NOW(3), INTERVAL 84420000 * 1000 MICROSECOND)),
(7, 'user', NULL, 'ঠিকানা: গলির শেষ মাথায় নীল গেট।', DATE_SUB(NOW(3), INTERVAL 66660000 * 1000 MICROSECOND)),
(7, 'staff', 1, 'সময় বদলে দেওয়া হয়েছে।', DATE_SUB(NOW(3), INTERVAL 47760000 * 1000 MICROSECOND)),
(8, 'user', NULL, 'সেবাদাতার নম্বরটা দিন প্লিজ।', DATE_SUB(NOW(3), INTERVAL 411840000 * 1000 MICROSECOND)),
(8, 'staff', 11, 'কুপনটা প্রথম বুকিংয়ে কাজ করে।', DATE_SUB(NOW(3), INTERVAL 396660000 * 1000 MICROSECOND)),
(8, 'user', NULL, 'আমার বুকিংয়ের সময় একটু বদলানো যাবে?', DATE_SUB(NOW(3), INTERVAL 372600000 * 1000 MICROSECOND)),
(9, 'user', NULL, 'ঠিকানা: গলির শেষ মাথায় নীল গেট।', DATE_SUB(NOW(3), INTERVAL 584400000 * 1000 MICROSECOND)),
(9, 'staff', 1, 'কুপনটা প্রথম বুকিংয়ে কাজ করে।', DATE_SUB(NOW(3), INTERVAL 562500000 * 1000 MICROSECOND)),
(9, 'user', NULL, 'সেবাদাতার নম্বরটা দিন প্লিজ।', DATE_SUB(NOW(3), INTERVAL 543780000 * 1000 MICROSECOND)),
(9, 'staff', 13, 'আপনার টাকা যোগ হয়েছে।', DATE_SUB(NOW(3), INTERVAL 540060000 * 1000 MICROSECOND)),
(9, 'user', NULL, 'কুপন কাজ করছে না কেন?', DATE_SUB(NOW(3), INTERVAL 537180000 * 1000 MICROSECOND)),
(10, 'user', NULL, 'আজকে কি কেউ আসবেন?', DATE_SUB(NOW(3), INTERVAL 35100000 * 1000 MICROSECOND)),
(10, 'staff', 13, 'কুপনটা প্রথম বুকিংয়ে কাজ করে।', DATE_SUB(NOW(3), INTERVAL 2220000 * 1000 MICROSECOND)),
(11, 'user', NULL, 'আজকে কি কেউ আসবেন?', DATE_SUB(NOW(3), INTERVAL 707580000 * 1000 MICROSECOND)),
(11, 'staff', 1, 'সেবাদাতা আগামীকাল সকাল ১০টায় আসবেন।', DATE_SUB(NOW(3), INTERVAL 676860000 * 1000 MICROSECOND)),
(11, 'user', NULL, 'আমার বুকিংয়ের সময় একটু বদলানো যাবে?', DATE_SUB(NOW(3), INTERVAL 676320000 * 1000 MICROSECOND)),
(11, 'staff', 13, 'কুপনটা প্রথম বুকিংয়ে কাজ করে।', DATE_SUB(NOW(3), INTERVAL 641220000 * 1000 MICROSECOND)),
(11, 'user', NULL, 'আমার বুকিংয়ের সময় একটু বদলানো যাবে?', DATE_SUB(NOW(3), INTERVAL 639720000 * 1000 MICROSECOND)),
(11, 'staff', 11, 'আপনার টাকা যোগ হয়েছে।', DATE_SUB(NOW(3), INTERVAL 628740000 * 1000 MICROSECOND)),
(12, 'user', NULL, 'ঠিকানা: গলির শেষ মাথায় নীল গেট।', DATE_SUB(NOW(3), INTERVAL 87780000 * 1000 MICROSECOND)),
(13, 'user', NULL, 'কাজটা খুব ভালো হয়েছে, ধন্যবাদ!', DATE_SUB(NOW(3), INTERVAL 258480000 * 1000 MICROSECOND)),
(13, 'staff', 13, 'আপনার টাকা যোগ হয়েছে।', DATE_SUB(NOW(3), INTERVAL 246900000 * 1000 MICROSECOND)),
(13, 'user', NULL, 'ঠিকানা: গলির শেষ মাথায় নীল গেট।', DATE_SUB(NOW(3), INTERVAL 243600000 * 1000 MICROSECOND)),
(13, 'staff', 12, 'ধন্যবাদ! আবার দরকার হলে জানাবেন।', DATE_SUB(NOW(3), INTERVAL 234060000 * 1000 MICROSECOND)),
(13, 'user', NULL, 'সেবাদাতা কখন আসবেন?', DATE_SUB(NOW(3), INTERVAL 220560000 * 1000 MICROSECOND)),
(14, 'user', NULL, 'ঠিকানা: গলির শেষ মাথায় নীল গেট।', DATE_SUB(NOW(3), INTERVAL 419580000 * 1000 MICROSECOND)),
(14, 'staff', 11, 'আপনার টাকা যোগ হয়েছে।', DATE_SUB(NOW(3), INTERVAL 402780000 * 1000 MICROSECOND)),
(14, 'user', NULL, 'সেবাদাতা কখন আসবেন?', DATE_SUB(NOW(3), INTERVAL 389880000 * 1000 MICROSECOND)),
(15, 'user', NULL, 'আজকে কি কেউ আসবেন?', DATE_SUB(NOW(3), INTERVAL 441840000 * 1000 MICROSECOND)),
(15, 'staff', 1, 'ধন্যবাদ! আবার দরকার হলে জানাবেন।', DATE_SUB(NOW(3), INTERVAL 431940000 * 1000 MICROSECOND)),
(15, 'user', NULL, 'বিকেল ৫টার পরে আসতে পারবেন?', DATE_SUB(NOW(3), INTERVAL 426180000 * 1000 MICROSECOND)),
(15, 'staff', 12, 'ধন্যবাদ! আবার দরকার হলে জানাবেন।', DATE_SUB(NOW(3), INTERVAL 412740000 * 1000 MICROSECOND));

INSERT INTO `favourites` (`user_id`, `sub_service_id`) VALUES (13, 1), (13, 2), (13, 6), (14, 1), (14, 3), (14, 8), (15, 1), (16, 4), (17, 5), (23, 1), (23, 4), (23, 7), (24, 4), (24, 5), (24, 7), (25, 2), (25, 5), (26, 2), (26, 3), (26, 4), (31, 2), (33, 7), (34, 2), (34, 5), (34, 6);
INSERT INTO `account_changes` (`user_id`, `changed_by`, `changes`, `created_at`, `seen_at`) VALUES
(28, 11, '[["wl_event", "successful", 300]]', DATE_SUB(NOW(), INTERVAL 106737 MINUTE), DATE_SUB(NOW(), INTERVAL 106707 MINUTE)),
(26, 11, '[["wl_event", "successful", 2000]]', DATE_SUB(NOW(), INTERVAL 67598 MINUTE), DATE_SUB(NOW(), INTERVAL 67568 MINUTE)),
(26, 14, '[["wl_event", "successful", 1500]]', DATE_SUB(NOW(), INTERVAL 130592 MINUTE), DATE_SUB(NOW(), INTERVAL 130562 MINUTE)),
(26, 14, '[["wl_event", "successful", 1000]]', DATE_SUB(NOW(), INTERVAL 161887 MINUTE), DATE_SUB(NOW(), INTERVAL 161857 MINUTE)),
(30, 11, '[["wl_event", "successful", 500]]', DATE_SUB(NOW(), INTERVAL 17418 MINUTE), DATE_SUB(NOW(), INTERVAL 17388 MINUTE)),
(15, 12, '[["wl_event", "successful", 2000]]', DATE_SUB(NOW(), INTERVAL 139152 MINUTE), DATE_SUB(NOW(), INTERVAL 139122 MINUTE)),
(15, 1, '[["wl_event", "successful", 2000]]', DATE_SUB(NOW(), INTERVAL 72004 MINUTE), DATE_SUB(NOW(), INTERVAL 71974 MINUTE)),
(24, 15, '[["wl_event", "successful", 1500]]', DATE_SUB(NOW(), INTERVAL 149741 MINUTE), DATE_SUB(NOW(), INTERVAL 149711 MINUTE)),
(24, 14, '[["wl_event", "successful", 200]]', DATE_SUB(NOW(), INTERVAL 94284 MINUTE), DATE_SUB(NOW(), INTERVAL 94254 MINUTE)),
(33, 11, '[["wl_event", "successful", 2000]]', DATE_SUB(NOW(), INTERVAL 168978 MINUTE), DATE_SUB(NOW(), INTERVAL 168948 MINUTE)),
(33, 2, '[["wl_event", "successful", 500]]', DATE_SUB(NOW(), INTERVAL 84465 MINUTE), DATE_SUB(NOW(), INTERVAL 84435 MINUTE)),
(33, 11, '[["wl_event", "successful", 200]]', DATE_SUB(NOW(), INTERVAL 164713 MINUTE), DATE_SUB(NOW(), INTERVAL 164683 MINUTE)),
(34, 12, '[["wl_event", "successful", 500]]', DATE_SUB(NOW(), INTERVAL 89154 MINUTE), DATE_SUB(NOW(), INTERVAL 89124 MINUTE)),
(27, 3, '[["wl_event", "successful", 300]]', DATE_SUB(NOW(), INTERVAL 164559 MINUTE), DATE_SUB(NOW(), INTERVAL 164529 MINUTE)),
(11, 12, '[["wl_event", "successful", 3000]]', DATE_SUB(NOW(), INTERVAL 107463 MINUTE), DATE_SUB(NOW(), INTERVAL 107433 MINUTE)),
(11, 12, '[["wl_event", "successful", 1500]]', DATE_SUB(NOW(), INTERVAL 156185 MINUTE), DATE_SUB(NOW(), INTERVAL 156155 MINUTE)),
(11, 13, '[["wl_event", "successful", 3000]]', DATE_SUB(NOW(), INTERVAL 78880 MINUTE), DATE_SUB(NOW(), INTERVAL 78850 MINUTE)),
(14, 2, '[["wl_event", "successful", 500]]', DATE_SUB(NOW(), INTERVAL 60461 MINUTE), DATE_SUB(NOW(), INTERVAL 60431 MINUTE)),
(17, 15, '[["wl_event", "successful", 300]]', DATE_SUB(NOW(), INTERVAL 123915 MINUTE), DATE_SUB(NOW(), INTERVAL 123885 MINUTE)),
(17, 2, '[["wl_event", "successful", 300]]', DATE_SUB(NOW(), INTERVAL 31670 MINUTE), DATE_SUB(NOW(), INTERVAL 31640 MINUTE)),
(29, 1, '[["wl_event", "successful", 500]]', DATE_SUB(NOW(), INTERVAL 27918 MINUTE), DATE_SUB(NOW(), INTERVAL 27888 MINUTE)),
(29, 14, '[["wl_event", "successful", 500]]', DATE_SUB(NOW(), INTERVAL 116256 MINUTE), DATE_SUB(NOW(), INTERVAL 116226 MINUTE)),
(17, 2, '[["bk_event", "ongoing", "SHM00007"]]', DATE_SUB(NOW(), INTERVAL 12364 MINUTE), DATE_SUB(NOW(), INTERVAL 12334 MINUTE)),
(17, 2, '[["bk_event", "completed", "SHM00007"]]', DATE_SUB(NOW(), INTERVAL 11824 MINUTE), DATE_SUB(NOW(), INTERVAL 11794 MINUTE)),
(30, 11, '[["bk_event", "ongoing", "SHM00008"]]', DATE_SUB(NOW(), INTERVAL 104032 MINUTE), DATE_SUB(NOW(), INTERVAL 104002 MINUTE)),
(30, 11, '[["bk_event", "completed", "SHM00008"]]', DATE_SUB(NOW(), INTERVAL 103492 MINUTE), DATE_SUB(NOW(), INTERVAL 103462 MINUTE)),
(34, 11, '[["bk_event", "ongoing", "SHM00009"]]', DATE_SUB(NOW(), INTERVAL 174208 MINUTE), DATE_SUB(NOW(), INTERVAL 174178 MINUTE)),
(34, 11, '[["bk_event", "completed", "SHM00009"]]', DATE_SUB(NOW(), INTERVAL 173668 MINUTE), DATE_SUB(NOW(), INTERVAL 173638 MINUTE)),
(24, 15, '[["bk_event", "ongoing", "SHM00010"]]', DATE_SUB(NOW(), INTERVAL 177214 MINUTE), DATE_SUB(NOW(), INTERVAL 177184 MINUTE)),
(24, 15, '[["bk_event", "completed", "SHM00010"]]', DATE_SUB(NOW(), INTERVAL 176674 MINUTE), DATE_SUB(NOW(), INTERVAL 176644 MINUTE)),
(27, 2, '[["bk_event", "ongoing", "SHM00012"]]', DATE_SUB(NOW(), INTERVAL 115003 MINUTE), DATE_SUB(NOW(), INTERVAL 114973 MINUTE)),
(27, 2, '[["bk_event", "completed", "SHM00012"]]', DATE_SUB(NOW(), INTERVAL 114463 MINUTE), DATE_SUB(NOW(), INTERVAL 114433 MINUTE)),
(20, 2, '[["bk_event", "ongoing", "SHM00013"]]', DATE_SUB(NOW(), INTERVAL 31282 MINUTE), DATE_SUB(NOW(), INTERVAL 31252 MINUTE)),
(20, 2, '[["bk_event", "cancelled", "SHM00013"]]', DATE_SUB(NOW(), INTERVAL 30742 MINUTE), DATE_SUB(NOW(), INTERVAL 30712 MINUTE)),
(18, 2, '[["bk_event", "ongoing", "SHM00014"]]', DATE_SUB(NOW(), INTERVAL 14101 MINUTE), DATE_SUB(NOW(), INTERVAL 14071 MINUTE)),
(18, 2, '[["bk_event", "completed", "SHM00014"]]', DATE_SUB(NOW(), INTERVAL 13561 MINUTE), DATE_SUB(NOW(), INTERVAL 13531 MINUTE)),
(29, 12, '[["bk_event", "ongoing", "SHM00017"]]', DATE_SUB(NOW(), INTERVAL 150422 MINUTE), DATE_SUB(NOW(), INTERVAL 150392 MINUTE)),
(29, 12, '[["bk_event", "completed", "SHM00017"]]', DATE_SUB(NOW(), INTERVAL 149882 MINUTE), DATE_SUB(NOW(), INTERVAL 149852 MINUTE)),
(25, 2, '[["bk_event", "ongoing", "SHM00019"]]', DATE_SUB(NOW(), INTERVAL 80039 MINUTE), DATE_SUB(NOW(), INTERVAL 80009 MINUTE)),
(25, 2, '[["bk_event", "completed", "SHM00019"]]', DATE_SUB(NOW(), INTERVAL 79499 MINUTE), DATE_SUB(NOW(), INTERVAL 79469 MINUTE)),
(27, 3, '[["bk_event", "ongoing", "SHM00020"]]', DATE_SUB(NOW(), INTERVAL 68025 MINUTE), DATE_SUB(NOW(), INTERVAL 67995 MINUTE)),
(27, 3, '[["bk_event", "completed", "SHM00020"]]', DATE_SUB(NOW(), INTERVAL 67485 MINUTE), DATE_SUB(NOW(), INTERVAL 67455 MINUTE)),
(23, 13, '[["bk_event", "ongoing", "SHM00022"]]', DATE_SUB(NOW(), INTERVAL 205611 MINUTE), DATE_SUB(NOW(), INTERVAL 205581 MINUTE)),
(23, 13, '[["bk_event", "cancelled", "SHM00022"]]', DATE_SUB(NOW(), INTERVAL 205071 MINUTE), DATE_SUB(NOW(), INTERVAL 205041 MINUTE)),
(17, 12, '[["bk_event", "ongoing", "SHM00023"]]', DATE_SUB(NOW(), INTERVAL 840 MINUTE), NULL),
(26, 13, '[["bk_event", "ongoing", "SHM00024"]]', DATE_SUB(NOW(), INTERVAL 104617 MINUTE), DATE_SUB(NOW(), INTERVAL 104587 MINUTE)),
(26, 13, '[["bk_event", "completed", "SHM00024"]]', DATE_SUB(NOW(), INTERVAL 104077 MINUTE), DATE_SUB(NOW(), INTERVAL 104047 MINUTE)),
(28, 2, '[["bk_event", "ongoing", "SHM00025"]]', DATE_SUB(NOW(), INTERVAL 120071 MINUTE), DATE_SUB(NOW(), INTERVAL 120041 MINUTE)),
(28, 2, '[["bk_event", "completed", "SHM00025"]]', DATE_SUB(NOW(), INTERVAL 119531 MINUTE), DATE_SUB(NOW(), INTERVAL 119501 MINUTE)),
(16, 1, '[["bk_event", "ongoing", "SHM00026"]]', DATE_SUB(NOW(), INTERVAL 2483 MINUTE), DATE_SUB(NOW(), INTERVAL 2453 MINUTE)),
(30, 2, '[["bk_event", "ongoing", "SHM00027"]]', DATE_SUB(NOW(), INTERVAL 180840 MINUTE), DATE_SUB(NOW(), INTERVAL 180810 MINUTE)),
(30, 2, '[["bk_event", "completed", "SHM00027"]]', DATE_SUB(NOW(), INTERVAL 180300 MINUTE), DATE_SUB(NOW(), INTERVAL 180270 MINUTE)),
(24, 1, '[["bk_event", "ongoing", "SHM00029"]]', DATE_SUB(NOW(), INTERVAL 404 MINUTE), NULL),
(16, 15, '[["bk_event", "ongoing", "SHM00030"]]', DATE_SUB(NOW(), INTERVAL 12650 MINUTE), DATE_SUB(NOW(), INTERVAL 12620 MINUTE)),
(16, 15, '[["bk_event", "completed", "SHM00030"]]', DATE_SUB(NOW(), INTERVAL 12110 MINUTE), DATE_SUB(NOW(), INTERVAL 12080 MINUTE)),
(31, 12, '[["bk_event", "ongoing", "SHM00031"]]', DATE_SUB(NOW(), INTERVAL 184525 MINUTE), DATE_SUB(NOW(), INTERVAL 184495 MINUTE)),
(31, 12, '[["bk_event", "cancelled", "SHM00031"]]', DATE_SUB(NOW(), INTERVAL 183985 MINUTE), DATE_SUB(NOW(), INTERVAL 183955 MINUTE)),
(34, 2, '[["bk_event", "ongoing", "SHM00032"]]', DATE_SUB(NOW(), INTERVAL 158795 MINUTE), DATE_SUB(NOW(), INTERVAL 158765 MINUTE)),
(34, 2, '[["bk_event", "cancelled", "SHM00032"]]', DATE_SUB(NOW(), INTERVAL 158255 MINUTE), DATE_SUB(NOW(), INTERVAL 158225 MINUTE)),
(19, 12, '[["bk_event", "ongoing", "SHM00033"]]', DATE_SUB(NOW(), INTERVAL 14807 MINUTE), DATE_SUB(NOW(), INTERVAL 14777 MINUTE)),
(19, 12, '[["bk_event", "completed", "SHM00033"]]', DATE_SUB(NOW(), INTERVAL 14267 MINUTE), DATE_SUB(NOW(), INTERVAL 14237 MINUTE)),
(26, 11, '[["bk_event", "ongoing", "SHM00034"]]', DATE_SUB(NOW(), INTERVAL 163874 MINUTE), DATE_SUB(NOW(), INTERVAL 163844 MINUTE)),
(26, 11, '[["bk_event", "completed", "SHM00034"]]', DATE_SUB(NOW(), INTERVAL 163334 MINUTE), DATE_SUB(NOW(), INTERVAL 163304 MINUTE)),
(16, 11, '[["bk_event", "ongoing", "SHM00035"]]', DATE_SUB(NOW(), INTERVAL 163066 MINUTE), DATE_SUB(NOW(), INTERVAL 163036 MINUTE)),
(16, 11, '[["bk_event", "completed", "SHM00035"]]', DATE_SUB(NOW(), INTERVAL 162526 MINUTE), DATE_SUB(NOW(), INTERVAL 162496 MINUTE)),
(11, 11, '[["bk_event", "ongoing", "SHM00036"]]', DATE_SUB(NOW(), INTERVAL 2592 MINUTE), DATE_SUB(NOW(), INTERVAL 2562 MINUTE)),
(16, 13, '[["bk_event", "ongoing", "SHM00038"]]', DATE_SUB(NOW(), INTERVAL 112005 MINUTE), DATE_SUB(NOW(), INTERVAL 111975 MINUTE)),
(16, 13, '[["bk_event", "completed", "SHM00038"]]', DATE_SUB(NOW(), INTERVAL 111465 MINUTE), DATE_SUB(NOW(), INTERVAL 111435 MINUTE)),
(13, 14, '[["bk_event", "ongoing", "SHM00039"]]', DATE_SUB(NOW(), INTERVAL 1061 MINUTE), NULL),
(22, 12, '[["bk_event", "ongoing", "SHM00040"]]', DATE_SUB(NOW(), INTERVAL 75527 MINUTE), DATE_SUB(NOW(), INTERVAL 75497 MINUTE)),
(22, 12, '[["bk_event", "completed", "SHM00040"]]', DATE_SUB(NOW(), INTERVAL 74987 MINUTE), DATE_SUB(NOW(), INTERVAL 74957 MINUTE)),
(33, 14, '[["bk_event", "ongoing", "SHM00041"]]', DATE_SUB(NOW(), INTERVAL 141855 MINUTE), DATE_SUB(NOW(), INTERVAL 141825 MINUTE)),
(33, 14, '[["bk_event", "completed", "SHM00041"]]', DATE_SUB(NOW(), INTERVAL 141315 MINUTE), DATE_SUB(NOW(), INTERVAL 141285 MINUTE)),
(17, 3, '[["bk_event", "ongoing", "SHM00042"]]', DATE_SUB(NOW(), INTERVAL 180 MINUTE), NULL),
(16, 1, '[["bk_event", "ongoing", "SHM00043"]]', DATE_SUB(NOW(), INTERVAL 1324 MINUTE), NULL),
(32, 2, '[["bk_event", "ongoing", "SHM00044"]]', DATE_SUB(NOW(), INTERVAL 20410 MINUTE), DATE_SUB(NOW(), INTERVAL 20380 MINUTE)),
(32, 2, '[["bk_event", "completed", "SHM00044"]]', DATE_SUB(NOW(), INTERVAL 19870 MINUTE), DATE_SUB(NOW(), INTERVAL 19840 MINUTE)),
(33, 2, '[["bk_event", "ongoing", "SHM00045"]]', DATE_SUB(NOW(), INTERVAL 6766 MINUTE), DATE_SUB(NOW(), INTERVAL 6736 MINUTE)),
(33, 2, '[["bk_event", "completed", "SHM00045"]]', DATE_SUB(NOW(), INTERVAL 6226 MINUTE), DATE_SUB(NOW(), INTERVAL 6196 MINUTE)),
(16, 14, '[["bk_event", "ongoing", "SHM00046"]]', DATE_SUB(NOW(), INTERVAL 69386 MINUTE), DATE_SUB(NOW(), INTERVAL 69356 MINUTE)),
(16, 14, '[["bk_event", "completed", "SHM00046"]]', DATE_SUB(NOW(), INTERVAL 68846 MINUTE), DATE_SUB(NOW(), INTERVAL 68816 MINUTE)),
(27, 12, '[["bk_event", "ongoing", "SHM00047"]]', DATE_SUB(NOW(), INTERVAL 176043 MINUTE), DATE_SUB(NOW(), INTERVAL 176013 MINUTE)),
(27, 12, '[["bk_event", "completed", "SHM00047"]]', DATE_SUB(NOW(), INTERVAL 175503 MINUTE), DATE_SUB(NOW(), INTERVAL 175473 MINUTE)),
(18, 3, '[["bk_event", "ongoing", "SHM00048"]]', DATE_SUB(NOW(), INTERVAL 148818 MINUTE), DATE_SUB(NOW(), INTERVAL 148788 MINUTE)),
(18, 3, '[["bk_event", "cancelled", "SHM00048"]]', DATE_SUB(NOW(), INTERVAL 148278 MINUTE), DATE_SUB(NOW(), INTERVAL 148248 MINUTE)),
(24, 14, '[["bk_event", "ongoing", "SHM00049"]]', DATE_SUB(NOW(), INTERVAL 78451 MINUTE), DATE_SUB(NOW(), INTERVAL 78421 MINUTE)),
(24, 14, '[["bk_event", "completed", "SHM00049"]]', DATE_SUB(NOW(), INTERVAL 77911 MINUTE), DATE_SUB(NOW(), INTERVAL 77881 MINUTE)),
(13, 1, '[["bk_event", "ongoing", "SHM00050"]]', DATE_SUB(NOW(), INTERVAL 77844 MINUTE), DATE_SUB(NOW(), INTERVAL 77814 MINUTE)),
(13, 1, '[["bk_event", "completed", "SHM00050"]]', DATE_SUB(NOW(), INTERVAL 77304 MINUTE), DATE_SUB(NOW(), INTERVAL 77274 MINUTE)),
(14, 11, '[["bk_event", "ongoing", "SHM00051"]]', DATE_SUB(NOW(), INTERVAL 28375 MINUTE), DATE_SUB(NOW(), INTERVAL 28345 MINUTE)),
(14, 11, '[["bk_event", "completed", "SHM00051"]]', DATE_SUB(NOW(), INTERVAL 27835 MINUTE), DATE_SUB(NOW(), INTERVAL 27805 MINUTE)),
(14, 11, '[["bk_event", "ongoing", "SHM00052"]]', DATE_SUB(NOW(), INTERVAL 128287 MINUTE), DATE_SUB(NOW(), INTERVAL 128257 MINUTE)),
(14, 11, '[["bk_event", "completed", "SHM00052"]]', DATE_SUB(NOW(), INTERVAL 127747 MINUTE), DATE_SUB(NOW(), INTERVAL 127717 MINUTE)),
(22, 12, '[["bk_event", "ongoing", "SHM00053"]]', DATE_SUB(NOW(), INTERVAL 563 MINUTE), NULL),
(34, 12, '[["bk_event", "ongoing", "SHM00055"]]', DATE_SUB(NOW(), INTERVAL 33911 MINUTE), DATE_SUB(NOW(), INTERVAL 33881 MINUTE)),
(34, 12, '[["bk_event", "completed", "SHM00055"]]', DATE_SUB(NOW(), INTERVAL 33371 MINUTE), DATE_SUB(NOW(), INTERVAL 33341 MINUTE)),
(18, 11, '[["bk_event", "ongoing", "SHM00056"]]', DATE_SUB(NOW(), INTERVAL 43344 MINUTE), DATE_SUB(NOW(), INTERVAL 43314 MINUTE)),
(18, 11, '[["bk_event", "completed", "SHM00056"]]', DATE_SUB(NOW(), INTERVAL 42804 MINUTE), DATE_SUB(NOW(), INTERVAL 42774 MINUTE)),
(18, 15, '[["bk_event", "ongoing", "SHM00058"]]', DATE_SUB(NOW(), INTERVAL 80325 MINUTE), DATE_SUB(NOW(), INTERVAL 80295 MINUTE)),
(18, 15, '[["bk_event", "completed", "SHM00058"]]', DATE_SUB(NOW(), INTERVAL 79785 MINUTE), DATE_SUB(NOW(), INTERVAL 79755 MINUTE));
