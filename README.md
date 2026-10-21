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
   It makes the database `shomadhan` with tables: `admins`, `employees`, `users`,
   `settings`, `banners`, `popups`, `services`, `sub_services`, `notifications`.
   Note: the file starts with DROP DATABASE, so importing again deletes old data.
3. `config/database.php` already uses XAMPP's default (root, no password).

## Quick tools (Windows, double-click)
- `setup.bat`: unzip anywhere and double-click it, no questions. First it starts XAMPP if it is off: it starts MySQL + Apache hidden, repairs MySQL by itself if it shows "shutdown unexpectedly", and opens the XAMPP Control Panel minimized. No questions or Yes prompts. Then it cut-pastes the folder to `C:\xampp\htdocs\shomadhan` (the unzipped folder is deleted at the end, the zip stays; never if it has `.git`)
  (replaces the old copy, keeps its `.git` and config secrets), starts MySQL + Apache, drops the `shomadhan` database and imports
  `database/schema.sql` again (all old data is deleted), makes MySQL + Apache start by themselves at Windows login,
  adds 2 Chrome bookmarks (Welcome, Database; Chrome may close once for this) and opens both in Chrome.
- `update.bat`: updates the database WITHOUT deleting data (use this once the site has real data, never setup.bat).
  It starts MySQL if needed, saves a backup to `backups\shomadhan-<date>.sql`, then runs only the new files in
  `database/migrations/` (how to write one: `database/migrations/README.md`). Same thing anywhere: `php tools/update.php`.
- `fix-mysql.bat`: fixes XAMPP's "Error: MySQL shutdown unexpectedly". Stop MySQL first.
  It keeps the old data folder as `mysql\data_old_<date>` and brings your databases back.
Both expect XAMPP in `C:\xampp` (change the `set XAMPP=` line if not).

## Default logins (mobile + password)
- Admin:    01700000001 / Admin@123     -> admin-dashboard.php
- Employee: 01700000002 / Employee@123  -> employee-dashboard.php
- User:     01700000003 / User@1234     -> user-dashboard.php
Passwords are saved as a bcrypt hash (password_hash), so nobody can read them, not even the admin.
Change these default passwords after the first login.

## Forgot password email (OTP)
Put your Gmail and a Google "App password" in `config/mail.php`.
Until then the OTP email fails and an error is shown (the OTP is never shown on screen).

## Done
- Welcome page (phone + PC), Bangla/English switch
- Register: saves to `users`, live check (AJAX) if mobile/email is already used (staff + users), then goes to login
- Login: mobile + password is looked up in `staff` (admins + employees) then `users`; correct -> that role's dashboard, wrong -> error message
- Remember me (30-day cookie) + session, logout
- Forgot password: email -> OTP -> set a new password
- Admin dashboard (admin-dashboard.php): welcome page coupon picture, banners (slider pictures),
  popup (picture shown once after login), services (name + PNG logo),
  sub-services (name, photo, details, price, rating), popular ranking, offers/notifications
- User dashboard = home page (user-dashboard.php): banner slider, service categories,
  popular services (admin's ranking), newly added, search, notification bell
- Employee dashboard: same panel as the admin (same look), only Service providers, All services, Offers, Users, Notifications and Profile, view only (server blocks any change). Admin keeps full control.
- Upload sizes (exact, = the place where each picture shows on a 1920x1080 screen, see IMG_SIZES in app/helpers/upload.php): banner 1600x200, welcome coupon 600x225, popup 500x500 (square), service logo 128x128 PNG, service photo 480x270, offer picture 200x200; max 3 MB; popup can have a link
- User home page has a left menu (PC: always open, phone: ☰ button)
- Uploaded pictures go to public/uploads/ (welcome, banners, popups, services, sub-services, offers)
- Profile for admin (admin-dashboard.php?page=profile), employee (employee-dashboard.php?page=profile) and user (profile.php): their info, change password under it
- Admin: users -> edit/delete; employees and admins -> add/edit/delete; employee -> admin and admin -> employee (only the role changes, same id)
- Admin lists: users, employees, admins and service providers (employee also: all services) with search, filters (status, gender, area, category), sort (click a column title; phone: sort menu) and 25 per page. On phone each row is a card. Accounts have created_at (join date)
- Profile: everyone can edit their own name and email (user also area and gender); mobile only the admin changes
- Last login time is saved (last_login_at) and shown in the admin lists and on the profile
- Employees / admins list shows "Added by" (which admin made the account)
- Delete = soft delete: the account stays in the table (deleted_at, deleted_by), can't log in, and everything it added keeps its name. Status filter "Deleted" shows them with a Restore button
- Foreign keys: created_by / deleted_by / changed_by -> staff.id (ON DELETE RESTRICT, so nothing that others point to can be removed by mistake), users.area + providers.area -> areas.name, remember_tokens + account_changes -> staff / users, sub_services.service_id + providers.service_id -> services.id (a category with services in it can't be deleted), sub_service_providers -> sub_services + providers
- Remember me works on several devices at once (remember_tokens, one row per device). Logout logs out only that device; a new password, inactive or delete logs out every device
- Forgot-password OTP is saved as a hash, like passwords
- Welcome page service icons come from the admin's Services (first 5 shown)
- 3 people tables: `staff` (admins + employees = office people who use the dashboard), `users` (customers), `providers` (electrician, plumber ... who go and do the work; they don't log in)
- Status is only Active / Inactive (set in the edit form). Inactive = can't log in or do anything
- New admin / employee: the admin gives a square photo and a one-time password. On the first login they must set their own password (set-password.php) before they can open anything; the one-time password then stops working
- Nobody can see or change another person's password: the admin edit form has no password field. Everyone changes their own from Profile
- Every account change by an admin (add, edit, role change, delete, restore) opens our own "Are you sure?" box (confirm.js, no browser alert) that lists what changes (old -> new) and asks for the admin's OWN password. Wrong password = nothing is saved (5 wrong in 10 minutes = wait)
- The changed person is told who changed what and when (account_changes): staff see it under Notifications (bell, top right), users in their bell
- Service providers page (admin: add / edit / delete / restore, employee: view): category, name, mobile, age, gender, area, address, photo, note, status. The sub-service form shows the providers of its category as tick boxes (sub_service_providers). Next step: orders, where the admin picks one of these providers
- Profile photos: square, at least 150 px, max 2 MB (uploads/photos). Required for a new admin / employee; everyone can change their own from Profile
- Every inner page has its own back button (no need for the browser's back)
- Same left menu for user, employee and admin (groups with small titles, profile under "My account", logout at the bottom). Phone: ☰ button opens it

## Security
- Passwords: bcrypt hash; old plain-text rows are turned into a hash on their next login
- Login: 5 wrong passwords for one mobile (or 20 from one IP) in 15 minutes -> must wait 15 minutes
- OTP: one per minute, 5 tries; email/availability checks are rate limited
- Every form: CSRF token, server-side checks (app/validators, controllers) + browser checks (validation.js, form-check.js)
- SQL: prepared statements only; sort/filter columns come from a fixed list
- Output: everything printed through e() (no XSS); uploads checked by real file type and renamed randomly
- Session cookie HttpOnly + SameSite, new session id on login, logged out after 2 hours idle
- Safety headers (no framing, nosniff, CSP), PHP errors go to storage/logs/php-errors.log, never on screen
- `.htaccess` blocks everything outside public/ (config, .sql, logs, app code)

## Shared code (reuse)
- `public/assets/css/base.css` - loaded on every page: colors, buttons, message boxes, language buttons, profile info card. Change a color here and it changes everywhere.
- `app/views/partials/head.php` - the same `<head>` (fonts + base.css + the page's CSS)
- `app/views/partials/lang-switch.php` + `assets/js/lang.js` - বাংলা / English buttons
- `app/views/partials/logout-form.php` - logout button
- `assets/js/side-menu.js` + `.side-menu` / `.side-nav` / `.nav-grp` in base.css - the left menu for every role
- `app/views/partials/profile-info.php` - profile info card (photo + change photo)
- `app/views/partials/confirm-modal.php` + `assets/js/confirm.js` - our own "Are you sure?" box (with change list and password) for every page
- `app/views/partials/avatar.php` - round photo, or the first letter when there is no photo
- `app/views/partials/account-changes.php` - "who changed what in my account" list (staff page + user bell)
- `app/validators/AccountValidator.php` - name/email/area/gender check for the admin's account form and "my profile"
- `too_many()` in `app/helpers/functions.php` - limit tries per browser
- `AdminController` runs both the admin and the employee dashboard (`ACCESS` says which pages each role can open)
