# Shomadhan (MVC, PHP)

Run: copy this folder to `C:/xampp/htdocs/`, start Apache, open
`http://localhost/shomadhan/public/`

## Folders (simple words)
- `public/`      what the browser can open: index.php, css, js, images
- `app/controllers/`  the manager: takes the request, asks Model, shows View
- `app/models/`       the data (services list now, database later)
- `app/views/`        the HTML pages
- `app/validators/`   server-side validation (next step)
- `app/lang/`         page texts: bn.php (Bangla, default) and en.php (English)
- `app/helpers/`      small helper functions (e(), view())
- `config/`           settings (config.php, database.php)
- `database/`         schema.sql
- `storage/logs/`     log files

## Flow (landing page)
index.php -> HomeController::index() -> ServiceModel::getAll() -> views/landing/home.php

## Setup (database)
1. Start Apache and MySQL in XAMPP.
2. Open http://localhost/phpmyadmin -> Import -> choose `database/schema.sql` -> Go.
   It makes the database `shomadhan` with 3 tables: `admins`, `employees`, `users`.
3. `config/database.php` already uses XAMPP's default (root, no password).

## Default logins (mobile + password)
- Admin:    01700000001 / Admin@123     -> admin-dashboard.php
- Employee: 01700000002 / Employee@123  -> employee-dashboard.php
- User:     01700000003 / User@1234     -> user-dashboard.php
Passwords are saved as plain text for now (no hashing).

## Forgot password email (OTP)
Put your Gmail and a Google "App password" in `config/mail.php`.
Until then (APP_DEV = true in config/config.php) the OTP is shown on the screen.

## Done
- Welcome page (phone + PC), Bangla/English switch
- Register: saves to `users`, live check (AJAX) if mobile/email is already used, then goes to login
- Login: checks admins, employees, users; correct -> that role's dashboard, wrong -> error message
- Remember me (30-day cookie) + session, logout
- Forgot password: email -> OTP -> shows the current password
- 3 blank dashboards: admin, employee, user
