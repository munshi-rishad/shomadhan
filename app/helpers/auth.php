<?php
// app/helpers/auth.php
// Login helpers: who is logged in, log in, log out, and the "Remember me" cookie.
//
// Session:  $_SESSION['auth'] = ['role' => 'admin'|'employee'|'user', 'id' => 5] after a correct login.
// Remember me: cookie "shomadhan_remember" = role:id:secret, kept for 30 days.
//   The table keeps only sha256(secret) in remember_token. A new secret is made every time it is used.

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
    $key = $auth['role'] . ':' . $auth['id'];
    if (!array_key_exists($key, $cache)) {
        $cache[$key] = AccountModel::find($auth['role'], (int)$auth['id']);
    }
    $account = $cache[$key];
    if (!$account || $account['status'] !== 'active') {   // deleted or blocked while logged in
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
    $account = current_account();
    if (!$account) {
        redirect('login.php');
    }
    if ($account['role'] !== $role) {
        redirect(dashboard_url($account['role']));
    }
    return $account;
}

function login_account(array $account, $remember)
{
    session_regenerate_id(true);             // new session id after login
    $_SESSION['auth'] = ['role' => $account['role'], 'id' => (int)$account['id']];
    if ($remember) {
        remember_issue($account['role'], (int)$account['id']);
    }
}

function logout_account()
{
    if (!empty($_SESSION['auth'])) {
        AccountModel::clearRememberToken($_SESSION['auth']['role'], (int)$_SESSION['auth']['id']);
    }
    remember_clear_cookie();
    unset($_SESSION['auth']);
    session_regenerate_id(true);
}

function remember_issue($role, $id)
{
    $secret  = bin2hex(random_bytes(32));
    $expires = time() + REMEMBER_DAYS * 86400;
    AccountModel::setRememberToken($role, $id, hash('sha256', $secret), date('Y-m-d H:i:s', $expires));
    remember_set_cookie($role . ':' . $id . ':' . $secret, $expires);
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
    $parts = explode(':', (string)$_COOKIE[REMEMBER_COOKIE]);
    if (count($parts) !== 3 || !isset(AccountModel::TABLES[$parts[0]]) || !ctype_digit($parts[1]) || !ctype_xdigit($parts[2])) {
        remember_clear_cookie();
        return;
    }
    list($role, $id, $secret) = $parts;
    try {
        $account = AccountModel::find($role, (int)$id);
        $ok = $account
            && $account['status'] === 'active'
            && $account['remember_token'] !== null
            && strtotime($account['remember_expires']) > time()
            && hash_equals($account['remember_token'], hash('sha256', $secret));
        if (!$ok) {
            remember_clear_cookie();
            return;
        }
        login_account($account, true);       // also makes a new cookie secret
    } catch (PDOException $ex) {
        app_log('error', 'remember-me: ' . $ex->getMessage());
    }
}
