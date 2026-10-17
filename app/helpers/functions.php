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
