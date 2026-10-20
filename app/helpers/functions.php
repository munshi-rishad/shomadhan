<?php
// app/helpers/functions.php
// Small helper functions used by many files.

// e() makes text safe before printing it in HTML (stops XSS attacks).
// Example: echo e($name);
function e($text)
{
    return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
}

// view() loads a view file and gives it some data.
// Example: view('landing/home', ['title' => 'Home']);
function view($file, $data = [])
{
    extract($data);                          // turns ['title'=>'Home'] into $title
    require APP_PATH . '/views/' . $file . '.php';
}

// csrf_token() / csrf_check() protect forms from fake requests from other sites.
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

// asset('assets/css/auth.css')  ->  'assets/css/auth.css?v=1760000000'
// The number changes whenever the file changes, so the browser never shows an OLD
// css / js / image from its cache after you update the project.
function asset($path)
{
    $file = dirname(__DIR__, 2) . '/public/' . $path;
    return $path . (is_file($file) ? '?v=' . filemtime($file) : '');
}

// redirect('login.php') sends the browser to another page and stops this one.
function redirect($url)
{
    header('Location: ' . $url);
    exit;
}

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

// lang_texts() -> ['bn', [...texts...]]  (language from the "lang" cookie, Bangla by default)
function lang_texts()
{
    $lang = ($_COOKIE['lang'] ?? '') === 'en' ? 'en' : 'bn';
    return [$lang, require APP_PATH . '/lang/' . $lang . '.php'];     // only the chosen language file is loaded
}

// Simple limit per browser: true = too many tries ($max in the last $seconds). Otherwise counts this try.
// too_many('fp_hits', 10, 600)  ->  at most 10 tries in 10 minutes
function too_many($key, $max, $seconds)
{
    $now  = time();
    $hits = array_filter($_SESSION[$key] ?? [], function ($ts) use ($now, $seconds) { return $ts > $now - $seconds; });
    if (count($hits) >= $max) {
        return true;
    }
    $hits[] = $now;
    $_SESSION[$key] = array_values($hits);
    return false;
}

// Small log file in storage/logs/ (for errors you don't want to show visitors)
function app_log($file, $message)
{
    @file_put_contents(ROOT_PATH . '/storage/logs/' . $file . '.log',
        '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL, FILE_APPEND);
}
