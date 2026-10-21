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

INSERT INTO `areas` (`name`) VALUES
('Ahammadabad'),
('Akua'),
('Alia Madrasa Road'),
('Amlapara'),
('Baghmara'),
('BAU Area'),
('Biddamoye School Area'),
('Boro Bazar'),
('Boundary Road'),
('Bramopolli'),
('Bypass More'),
('Cantonment'),
('Chamra Godam'),
('Choto Bazar'),
('Dhopakhola'),
('Durgabari'),
('Fulpur'),
('Ganginapar'),
('Joynul Abedin Park'),
('Jubilee Road'),
('Kachari'),
('Kachi Jhuli Mor'),
('Kali Bari'),
('Kestopur'),
('Kewatkhali'),
('Lichu Bagan'),
('Maskanda'),
('Mintu College Area'),
('Nahar Road'),
('Natok Gol Lane'),
('Natun Bazar'),
('Naumahal'),
('Pat Godam'),
('Pocha Pukur Par'),
('Police Line'),
('Pondit Para'),
('Shehora'),
('Shema Choron Rai Road'),
('Shomvogonj'),
('Stadium Area'),
('Station Road'),
('Taltola'),
('Town Hall Mor'),
('Trishal Bus Stand'),
('Zilla More');

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
