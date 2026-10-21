<?php
// app/helpers/functions.php
// Small helper functions used everywhere: safe HTML output, views, CSRF, redirects,
// flash messages, language texts, rate limits, logging and a few text helpers.
// Loaded by app/bootstrap.php, so every page and controller can call these.

// ---- Safe HTML text ----
// e() makes text safe before printing it in HTML (stops XSS attacks).
// Example: echo e($name);
function e($text)
{
    return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
}

// ---- Show a view ----
// view() loads a view file and gives it some data.
// Example: view('landing/home', ['title' => 'Home']);
function view($file, $data = [])
{
    extract($data);                          // turns ['title' => 'Home'] into $title
    require APP_PATH . '/views/' . $file . '.php';
}

// ---- Request helpers ----
// true when the browser sent a form (POST), false for a normal page open (GET)
function is_post()
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

// Headers for an AJAX answer in JSON that the browser must never cache
function json_headers()
{
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
}

// ---- CSRF protection ----
// csrf_token() / csrf_check() protect forms from fake requests sent by other sites.
// Every form prints csrf_token() in a hidden field; the controller checks it with csrf_check().
function csrf_token()
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_check($token)
{
    return !empty($_SESSION['csrf']) && is_string($token) && hash_equals($_SESSION['csrf'], $token);
}

// ---- Cache-busting links for css / js / images ----
// asset('assets/css/auth.css')  ->  'assets/css/auth.css?v=1760000000'
// The number changes whenever the file changes, so the browser never shows an OLD
// css / js / image from its cache after you update the project.
function asset($path)
{
    $file = dirname(__DIR__, 2) . '/public/' . $path;
    return $path . (is_file($file) ? '?v=' . filemtime($file) : '');
}

// ---- Redirect ----
// redirect('login.php') sends the browser to another page and stops this one.
function redirect($url)
{
    header('Location: ' . $url);
    exit;
}

// ---- Flash messages ----
// flash = a one-time message saved in the session, shown on the next page.
// flash_set('notice', 'Saved!');  then on the next page: flash_get('notice')
function flash_set($key, $value)
{
    $_SESSION['flash'][$key] = $value;
}

function flash_get($key, $default = '')
{
    $value = $_SESSION['flash'][$key] ?? $default;
    unset($_SESSION['flash'][$key]);
    return $value;
}

// ---- Language ----
// lang_texts() -> ['bn', [...texts...]]  (language from the "lang" cookie, Bangla by default)
function lang_texts()
{
    $lang = ($_COOKIE['lang'] ?? '') === 'en' ? 'en' : 'bn';
    return [$lang, require APP_PATH . '/lang/' . $lang . '.php'];     // only the chosen language file is loaded
}

// ---- Rate limit ----
// true = too many tries ($max in the last $seconds); false = allowed, and this try is counted.
// too_many('fp_hits', 10, 600)  ->  at most 10 tries in 10 minutes.
// Logged in = counted per account (many phones can share one mobile-network address),
// not logged in = per internet address. Kept in the database (table rate_hits), so throwing
// the cookie away does not reset it. If the database is off, it falls back to the browser session.
function too_many($key, $max, $seconds)
{
    try {
        $db   = Database::get();
        $auth = $_SESSION['auth'] ?? null;
        $who  = $auth
            ? $auth['kind'] . ':' . (int)$auth['id']
            : substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);

        $countSql = 'SELECT COUNT(*) FROM rate_hits WHERE k = ? AND ip = ? AND created_at > NOW() - INTERVAL ? SECOND';
        $count = $db->prepare($countSql);
        $count->execute([$key, $who, (int)$seconds]);
        if ((int)$count->fetchColumn() >= $max) {
            return true;
        }
        $db->prepare('INSERT INTO rate_hits (k, ip) VALUES (?, ?)')->execute([$key, $who]);

        // now and then, delete old rows to keep the table small
        if (random_int(1, 50) === 1) {
            $db->exec('DELETE FROM rate_hits WHERE created_at < NOW() - INTERVAL 1 DAY');
        }
        return false;
    } catch (Throwable $ex) {
        app_log('error', 'rate limit: ' . $ex->getMessage());
    }

    // Database is off: count the tries in the session instead
    $now  = time();
    $hits = array_filter($_SESSION[$key] ?? [], function ($ts) use ($now, $seconds) {
        return $ts > $now - $seconds;
    });
    if (count($hits) >= $max) {
        return true;
    }
    $hits[] = $now;
    $_SESSION[$key] = array_values($hits);
    return false;
}

// ---- Log file ----
// Writes one line to storage/logs/<file>.log (for errors you don't want to show visitors)
function app_log($file, $message)
{
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
    @file_put_contents(ROOT_PATH . '/storage/logs/' . $file . '.log', $line, FILE_APPEND);
}

// ---- Site info in the chosen language ----
// site_val($site, 'address', 'bn') -> $site['address_bn'] (or $site['address'])
function site_val(array $site, $key, $lang)
{
    return (string)($site[$key . '_' . $lang] ?? $site[$key . '_bn'] ?? $site[$key] ?? '');
}

// ---- Long admin text -> safe HTML ----
// An empty line = new paragraph, a "## " line = heading that starts a new <section>,
// other line breaks are kept as <br>. Used by the about / terms / privacy pages.
function rich_text($text)
{
    // Pass 1: each line becomes escaped text + <br>, a heading becomes <h2>, an empty line a "\n" gap
    $html = '';
    $sectionNo = 0;
    foreach (preg_split("/\\R/u", trim((string)$text)) as $line) {
        $line = trim($line);
        if ($line === '') {
            $html .= "\n";
            continue;
        }
        if (strpos($line, '## ') === 0) {
            $sectionNo++;
            $html .= "\n<h2 id=\"sec-" . $sectionNo . "\">" . e(substr($line, 3)) . "</h2>\n";
            continue;
        }
        $html .= e($line) . "<br>";
    }

    // Pass 2: every "## " heading starts a <section> (so the page can show the sections side by side),
    // every other block becomes a <p>
    $out = '';
    $open = false;
    foreach (preg_split("/\n+/u", $html) as $part) {
        if ($part === '') {
            continue;
        }
        if (strpos($part, '<h2') === 0) {
            $out .= ($open ? '</section>' : '') . '<section class="rt-sec">' . $part;
            $open = true;
        } else {
            $out .= '<p>' . preg_replace('/<br>$/', '', $part) . '</p>';
        }
    }
    return $out . ($open ? '</section>' : '');
}

// ---- Phone number for links ----
// "01700000000" -> "8801700000000" for tel: / WhatsApp links (the "+" is added by the view)
function phone_intl($mobile)
{
    $digits = preg_replace('/\D/', '', (string)$mobile);
    return strpos($digits, '880') === 0 ? $digits : '88' . $digits;
}
