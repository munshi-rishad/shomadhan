<?php
// app/helpers/auth.php
// Login helpers: who is logged in, log in, log out, and the "Remember me" cookie.
//
// Session:  $_SESSION['auth'] = ['kind' => 'staff'|'user', 'id' => 5] after a correct login
//           (staff = admin / employee table, user = users table). The role is read fresh from the table on every page,
//           so if an admin changes someone's role, their next page already follows it.
// Remember me: cookie "shomadhan_remember" = a random secret, kept for 30 days.
//   The remember_tokens table keeps only sha256(secret), one row per device. A new secret is made every time it is used.

const REMEMBER_COOKIE = 'shomadhan_remember';
const REMEMBER_DAYS   = 30;

// Where each role goes after login
function dashboard_url($role)
{
    $pages = ['admin' => 'admin-dashboard.php', 'employee' => 'employee-dashboard.php', 'user' => 'user-dashboard.php'];
    return $pages[$role] ?? 'login.php';
}

function current_account()
{
    static $cache = [];
    $auth = $_SESSION['auth'] ?? null;
    if (!$auth) {
        return null;
    }
    $key = ($auth['kind'] ?? '') . ':' . $auth['id'];
    if (!array_key_exists($key, $cache)) {
        $cache[$key] = AccountModel::findByKind($auth['kind'] ?? '', (int)$auth['id']);
    }
    $account = $cache[$key];
    if (!$account || $account['status'] !== 'active') {   // deleted or made inactive while logged in
        logout_account();
        return null;
    }
    return $account;
}

// Logged-in people can't open login / register / forgot: send them to their dashboard
function redirect_if_logged_in()
{
    if ($account = current_account()) {
        redirect(dashboard_url($account['role']));
    }
}

// Each dashboard calls this with its role
function require_role($role)
{
    $account = require_first_password_done();
    if ($account['role'] !== $role) {
        redirect(dashboard_url($account['role']));
    }
    return $account;
}

// Logged in AND has set their own password. Someone the admin made with a one-time password
// must set a new password first (set-password.php); until then no other page opens.
function require_first_password_done()
{
    $account = current_account();
    if (!$account) {
        redirect('login.php');
    }
    if (!empty($account['must_change_password'])) {
        redirect('set-password.php');
    }
    return $account;
}

function login_account(array $account, $remember)
{
    session_regenerate_id(true);             // new session id after login
    $_SESSION['auth'] = ['kind' => $account['kind'], 'id' => (int)$account['id']];
    unset($_SESSION['popup_seen']);          // show the home page popup again after each login
    try {
        AccountModel::touchLogin($account['role'], (int)$account['id']);   // "last login" shown to admin and on the profile
    } catch (PDOException $ex) {
        app_log('error', 'last login: ' . $ex->getMessage());
    }
    if ($remember) {
        remember_issue($account['role'], (int)$account['id']);
    }
}

// Logs out this browser only (other devices with "remember me" stay logged in)
function logout_account()
{
    $secret = (string)($_COOKIE[REMEMBER_COOKIE] ?? '');
    if ($secret !== '') {
        try {
            AccountModel::forgetDevice(hash('sha256', $secret));
        } catch (PDOException $ex) {
            app_log('error', 'logout: ' . $ex->getMessage());
        }
    }
    remember_clear_cookie();
    unset($_SESSION['auth']);
    session_regenerate_id(true);
}

function remember_issue($role, $id)
{
    $old = (string)($_COOKIE[REMEMBER_COOKIE] ?? '');
    if ($old !== '') {
        AccountModel::forgetDevice(hash('sha256', $old));   // this browser had an older token: replace it, don't keep both
    }
    $secret  = bin2hex(random_bytes(32));
    $expires = time() + REMEMBER_DAYS * 86400;
    AccountModel::addDevice($role, $id, hash('sha256', $secret), date('Y-m-d H:i:s', $expires));
    remember_set_cookie($secret, $expires);
}

function remember_set_cookie($value, $expires)
{
    setcookie(REMEMBER_COOKIE, $value, [
        'expires'  => $expires,
        'path'     => '/',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,                  // JavaScript can't read it
        'samesite' => 'Lax',
    ]);
}

function remember_clear_cookie()
{
    if (isset($_COOKIE[REMEMBER_COOKIE])) {
        remember_set_cookie('', time() - 3600);
        unset($_COOKIE[REMEMBER_COOKIE]);
    }
}

// Called on every page (bootstrap.php). No session but a valid remember cookie -> log in again.
function auth_from_remember_cookie()
{
    if (!empty($_SESSION['auth']) || empty($_COOKIE[REMEMBER_COOKIE])) {
        return;
    }
    $secret = (string)$_COOKIE[REMEMBER_COOKIE];
    if (strlen($secret) !== 64 || !ctype_xdigit($secret)) {
        remember_clear_cookie();
        return;
    }
    try {
        $hash = hash('sha256', $secret);
        $account = AccountModel::findByDevice($hash);
        if (!$account || $account['status'] !== 'active') {
            remember_clear_cookie();
            return;
        }
        AccountModel::forgetDevice($hash);   // used once: login_account() below gives this device a new secret
        login_account($account, true);       // also makes a new cookie secret
    } catch (PDOException $ex) {
        app_log('error', 'remember-me: ' . $ex->getMessage());
    }
}
