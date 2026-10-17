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

## Done: welcome page (phone fits one screen + PC), Bangla/English switch
## Empty files = next steps: login, register, validation (client + server)
