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
