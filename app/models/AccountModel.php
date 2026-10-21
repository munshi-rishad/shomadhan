<?php
// app/models/AccountModel.php
// MODEL for everyone who can log in:
//   staff table = admins + employees (office people, "role" column says which)
//   users table = customers
// Every function gets the person's role ('admin', 'employee' or 'user') and picks the table from it.
// Every row this model returns has 'role' and 'kind' ('staff' or 'user') added.
// One mobile / email = one person: the database keeps it unique inside each table, takenBy() checks both tables.
// Passwords and OTPs are saved as a bcrypt hash. Nobody, not even the admin, can read or set someone else's password.
// Deleted people (soft delete, deleted_at filled) are skipped everywhere: they can't log in, reset password or be edited.
// Used by login / register / forgot password, the profile pages and the admin panel. Loaded by app/bootstrap.php.

class AccountModel
{
    // Forgot password OTP
    const OTP_MINUTES      = 10;   // an OTP works for 10 minutes
    const MAX_OTP_TRIES    = 5;    // 5 wrong OTPs -> must ask for a new one
    const OTP_WAIT_SECONDS = 60;   // a new OTP can be sent only once a minute (stops email spam)

    // Too many wrong logins inside LOGIN_LOCK_MIN minutes = wait
    const LOGIN_MAX_MOBILE     = 5;    // for one mobile from one internet address (so a stranger can't lock someone else out)
    const LOGIN_MAX_MOBILE_ALL = 50;   // for one mobile from all addresses together
    const LOGIN_MAX_IP         = 20;   // from one internet address, any mobile
    const LOGIN_LOCK_MIN       = 15;

    // ---- Small database helpers

    private static function db()
    {
        return Database::get();
    }

    private static function one($sql, array $args)
    {
        $st = self::db()->prepare($sql);
        $st->execute($args);
        return $st->fetch() ?: null;
    }

    private static function run($sql, array $args)
    {
        self::db()->prepare($sql)->execute($args);
    }

    // "?, ?, ?" for $count values
    private static function placeholders($count)
    {
        return rtrim(str_repeat('?, ', $count), ', ');
    }

    // 'user' / 'users' -> users table, anything else (admin, employee, staff) -> staff table
    private static function table($role)
    {
        return $role === 'user' || $role === 'users' ? 'users' : 'staff';
    }

    // Column that links a row of remember_tokens to its owner
    private static function ownerColumn($role)
    {
        return self::table($role) === 'users' ? 'user_id' : 'staff_id';
    }

    // Adds 'role' + 'kind' so the rest of the website can treat staff and users the same way
    private static function shape($row, $table)
    {
        if (!$row) {
            return null;
        }
        return $table === 'users' ? $row + ['role' => 'user', 'kind' => 'user'] : $row + ['kind' => 'staff'];
    }

    // ---- Find

    // By kind ('staff' / 'user') + id, any role. Used for the logged-in person (an admin may have changed their role).
    public static function findByKind($kind, $id)
    {
        $table = self::table($kind);
        return self::shape(self::one('SELECT * FROM ' . $table . ' WHERE id = ? AND deleted_at IS NULL', [$id]), $table);
    }

    // One account of this role, live or deleted ($deleted). Staff rows must also have this role.
    private static function findWithRole($role, $id, $deleted)
    {
        $table = self::table($role);
        $isStaff = $table === 'staff';
        $sql = 'SELECT * FROM ' . $table . ' WHERE id = ? AND deleted_at IS ' . ($deleted ? 'NOT NULL' : 'NULL')
             . ($isStaff ? ' AND role = ?' : '');
        return self::shape(self::one($sql, $isStaff ? [$id, $role] : [$id]), $table);
    }

    // Only if it has this role (admin lists: an id from the "admins" page must be an admin)
    public static function find($role, $id)
    {
        return self::findWithRole($role, $id, false);
    }

    // Live account by a column, staff first, then users.
    // live_mobile / live_email are NULL for deleted rows, so deleted people are never found.
    private static function findBy($column, $value)
    {
        foreach (['staff', 'users'] as $table) {
            $row = self::one('SELECT * FROM ' . $table . ' WHERE ' . $column . ' = ?', [$value]);
            if ($row) {
                return self::shape($row, $table);
            }
        }
        return null;
    }

    public static function findByMobile($mobile)
    {
        return self::findBy('live_mobile', $mobile);
    }

    public static function findByEmail($email)
    {
        return self::findBy('live_email', strtolower($email));
    }

    public static function mobileExists($mobile)
    {
        return self::findByMobile($mobile) !== null;
    }

    public static function emailExists($email)
    {
        return self::findByEmail($email) !== null;
    }

    // Mobile or email already used by another person (staff or user)?
    // $role + $id = the account being edited (it skips itself)
    public static function takenBy($column, $value, $role = '', $id = 0)
    {
        if ($value === '' || $value === null) {
            return false;
        }
        $liveColumn = $column === 'email' ? 'live_email' : 'live_mobile';
        foreach (['staff', 'users'] as $table) {
            $skipId = $role !== '' && self::table($role) === $table ? (int)$id : 0;
            if (self::one('SELECT id FROM ' . $table . ' WHERE ' . $liveColumn . ' = ? AND id <> ?', [$value, $skipId])) {
                return true;
            }
        }
        return false;
    }

    // ---- Passwords

    public static function hash($password)
    {
        return password_hash((string)$password, PASSWORD_DEFAULT);
    }

    // Is this the right password? (also upgrades the hash if PHP's default gets stronger later)
    public static function passwordMatches(array $account, $password)
    {
        $ok = password_verify((string)$password, (string)$account['password']);
        if ($ok && isset($account['id']) && password_needs_rehash($account['password'], PASSWORD_DEFAULT)) {
            self::run('UPDATE ' . self::table($account['role']) . ' SET password = ? WHERE id = ?', [self::hash($password), $account['id']]);
        }
        return $ok;
    }

    // Login with a mobile that doesn't exist still takes the same time (so nobody can guess which mobiles exist)
    public static function fakeVerify($password)
    {
        password_verify((string)$password, '$2y$10$H920nB84EaQwuF5C8lZFVuH/16zYK0h6lGZfI0cJ6SIkrZnC.wi1G');
    }

    // The person sets their own new password (profile, forgot password, first login).
    // The one-time password from the admin stops working, and "remember me" on every device is logged out.
    public static function setPassword($role, $id, $password)
    {
        $table = self::table($role);
        $hash = self::hash($password);
        $sql = 'UPDATE ' . $table . ' SET password = ?, must_change_password = 0 WHERE id = ?';
        self::run($sql, [$hash, $id]);
        self::forgetAllDevices($role, $id);

        // I changed my own password: this browser stays logged in, all others are out
        $auth = $_SESSION['auth'] ?? null;
        $kind = $table === 'staff' ? 'staff' : 'user';
        if ($auth && (int)$auth['id'] === (int)$id && ($auth['kind'] ?? '') === $kind) {
            $_SESSION['auth']['pw'] = pw_mark($hash);
        }
    }

    // ---- Too many wrong logins

    // Minutes to wait (0 = can try now)
    public static function loginLockedMinutes($mobile, $ip)
    {
        $recent = 'created_at > NOW() - INTERVAL ' . self::LOGIN_LOCK_MIN . ' MINUTE';
        $counts = self::one(
            'SELECT
               (SELECT COUNT(*) FROM login_attempts WHERE mobile = ? AND ip = ? AND ' . $recent . ') AS by_mobile,
               (SELECT COUNT(*) FROM login_attempts WHERE mobile = ? AND ' . $recent . ') AS by_mobile_all,
               (SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND ' . $recent . ') AS by_ip,
               (SELECT MIN(created_at) FROM login_attempts WHERE (mobile = ? OR ip = ?) AND ' . $recent . ') AS first_try',
            [$mobile, $ip, $mobile, $ip, $mobile, $ip]
        );
        $locked = (int)$counts['by_mobile'] >= self::LOGIN_MAX_MOBILE
               || (int)$counts['by_mobile_all'] >= self::LOGIN_MAX_MOBILE_ALL
               || (int)$counts['by_ip'] >= self::LOGIN_MAX_IP;
        if (!$locked) {
            return 0;
        }
        // the lock ends LOGIN_LOCK_MIN minutes after the oldest counted try
        $unlockAt = strtotime($counts['first_try']) + self::LOGIN_LOCK_MIN * 60;
        return max(1, (int)ceil(($unlockAt - time()) / 60));
    }

    public static function addLoginFail($mobile, $ip)
    {
        self::run('INSERT INTO login_attempts (mobile, ip) VALUES (?, ?)', [$mobile, $ip]);
        self::db()->exec('DELETE FROM login_attempts WHERE created_at < NOW() - INTERVAL 1 DAY');   // keep the table small
    }

    public static function clearLoginFails($mobile)
    {
        self::run('DELETE FROM login_attempts WHERE mobile = ?', [$mobile]);
    }

    // Saves the time of a successful login (shown in the admin lists)
    public static function touchLogin($role, $id)
    {
        self::run('UPDATE ' . self::table($role) . ' SET last_login_at = NOW() WHERE id = ?', [$id]);
    }

    // ---- Remember me: one row per device in remember_tokens (only the sha256 of the cookie secret is saved)

    public static function addDevice($role, $id, $tokenHash, $expires)
    {
        self::run('INSERT INTO remember_tokens (' . self::ownerColumn($role) . ', token_hash, expires_at) VALUES (?, ?, ?)', [$id, $tokenHash, $expires]);
        self::db()->exec('DELETE FROM remember_tokens WHERE expires_at < NOW()');   // old ones are useless
    }

    // The person this device's token belongs to (not deleted, token not expired), or null
    public static function findByDevice($tokenHash)
    {
        $token = self::one('SELECT staff_id, user_id FROM remember_tokens WHERE token_hash = ? AND expires_at > NOW()', [$tokenHash]);
        if (!$token) {
            return null;
        }
        if ($token['staff_id'] !== null) {
            return self::findByKind('staff', $token['staff_id']);
        }
        return self::findByKind('user', $token['user_id']);
    }

    public static function forgetDevice($tokenHash)
    {
        self::run('DELETE FROM remember_tokens WHERE token_hash = ?', [$tokenHash]);
    }

    // Logs the person out of "remember me" on every device (new password, made inactive, deleted)
    // ---- The office resets a forgotten password (for people with no email: the email code can't reach them).
    // A new one-time password is made and returned (shown once to the office, told to the person on the phone).
    // It works only to log in once: then the person must set their own (set-password.php). Every device is logged out.
    public static function resetPassword($role, $id)
    {
        $letters = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';     // no 0/O, 1/l/I: easy to read out on the phone
        $password = '';
        for ($i = 0; $i < 8; $i++) {
            $password .= $letters[random_int(0, strlen($letters) - 1)];
        }
        self::run('UPDATE ' . self::table($role) . ' SET password = ?, must_change_password = 1 WHERE id = ?', [self::hash($password), $id]);
        self::forgetAllDevices($role, $id);
        return $password;
    }

    public static function forgetAllDevices($role, $id)
    {
        self::run('DELETE FROM remember_tokens WHERE ' . self::ownerColumn($role) . ' = ?', [$id]);
    }

    // ---- Forgot password OTP

    // Seconds to wait before another OTP can be sent (0 = can send now)
    public static function otpWaitSeconds($role, $id)
    {
        $row = self::one('SELECT TIMESTAMPDIFF(SECOND, NOW(), otp_expires) AS left_s FROM ' . self::table($role)
                       . ' WHERE id = ? AND otp_expires IS NOT NULL', [$id]);
        if (!$row || $row['left_s'] === null) {
            return 0;
        }
        $sentAgo = self::OTP_MINUTES * 60 - (int)$row['left_s'];      // seconds since the last OTP was sent
        return max(0, self::OTP_WAIT_SECONDS - $sentAgo);
    }

    // Returns the new 6-digit OTP. Only its hash is saved (like a password), so even someone who sees the table can't use it.
    // The try counter starts again only when the old OTP has already run out.
    public static function newOtp($role, $id)
    {
        $otp = (string)random_int(100000, 999999);
        self::run('UPDATE ' . self::table($role) . ' SET otp = ?, otp_attempts = IF(otp_expires > NOW(), otp_attempts, 0),'
                . ' otp_expires = DATE_ADD(NOW(), INTERVAL ' . self::OTP_MINUTES . ' MINUTE) WHERE id = ?',
            [password_hash($otp, PASSWORD_DEFAULT), $id]);
        return $otp;
    }

    // Returns 'ok', 'wrong', 'expired' or 'too_many'
    public static function checkOtp($role, $id, $otp)
    {
        $table = self::table($role);
        $row = self::one('SELECT otp, otp_attempts, (otp_expires > NOW()) AS fresh FROM ' . $table . ' WHERE id = ?', [$id]);
        if (!$row || $row['otp'] === null || !$row['fresh']) {
            return 'expired';
        }
        // take one try first, in one step (two quick requests can't both use the same try)
        $st = self::db()->prepare('UPDATE ' . $table . ' SET otp_attempts = otp_attempts + 1 WHERE id = ? AND otp_attempts < ?');
        $st->execute([$id, self::MAX_OTP_TRIES]);
        if ($st->rowCount() === 0) {
            return 'too_many';
        }
        if (!password_verify($otp, $row['otp'])) {
            return (int)$row['otp_attempts'] + 1 >= self::MAX_OTP_TRIES ? 'too_many' : 'wrong';
        }
        return 'ok';
    }

    public static function otpTriesLeft($role, $id)
    {
        $row = self::one('SELECT otp_attempts FROM ' . self::table($role) . ' WHERE id = ?', [$id]);
        return max(0, self::MAX_OTP_TRIES - (int)($row['otp_attempts'] ?? 0));
    }

    public static function clearOtp($role, $id)
    {
        self::run('UPDATE ' . self::table($role) . ' SET otp = NULL, otp_expires = NULL, otp_attempts = 0 WHERE id = ?', [$id]);
    }

    // ---- Create / change

    // Info columns an account has. Same for every role (like the register page); $role is kept for callers.
    public static function infoColumns($role)
    {
        return ['name', 'mobile', 'email', 'area', 'gender'];
    }

    // Values of $cols from the form data, in the same order. Empty email is saved as NULL, others lower case.
    private static function values(array $cols, array $d)
    {
        return array_map(function ($col) use ($d) {
            if ($col === 'email') {
                return ($d['email'] ?? '') === '' ? null : strtolower($d['email']);
            }
            return $d[$col];
        }, $cols);
    }

    // UPDATE these columns of one account from the form data
    private static function updateColumns($role, $id, array $cols, array $d)
    {
        $args = self::values($cols, $d);
        $args[] = $id;
        self::run('UPDATE ' . self::table($role) . ' SET ' . implode(' = ?, ', $cols) . ' = ? WHERE id = ?', $args);
    }

    // New customer from the register page. Returns the new id.
    public static function createUser(array $d)
    {
        $cols = self::infoColumns('user');
        $args = self::values($cols, $d);
        $args[] = self::hash($d['password']);
        self::run('INSERT INTO users (' . implode(', ', $cols) . ', password) VALUES (' . self::placeholders(count($cols) + 1) . ')', $args);
        return (int)self::db()->lastInsertId();
    }

    // New admin / employee made by an admin. $d: info columns, password (one-time), status, photo, created_by.
    // must_change_password = 1: on the first login they must set their own password.
    public static function createStaff($role, array $d)
    {
        $cols = self::infoColumns($role);
        $args = array_merge([$role], self::values($cols, $d), [self::hash($d['password']), $d['status'], $d['photo'], $d['created_by']]);
        self::run('INSERT INTO staff (role, ' . implode(', ', $cols) . ', password, status, photo, created_by, must_change_password)
                   VALUES (?, ' . self::placeholders(count($cols)) . ', ?, ?, ?, ?, 1)', $args);
        return (int)self::db()->lastInsertId();
    }

    // Admin edits someone: info (+ status). Never the password (only its owner can change it).
    public static function update($role, $id, array $d)
    {
        self::updateColumns($role, $id, array_merge(self::infoColumns($role), ['status']), $d);
        if ($d['status'] !== 'active') {           // made inactive -> logged out of "remember me" everywhere
            self::forgetAllDevices($role, $id);
        }
    }

    // Own profile: everything except the mobile. Mobile and status only the admin can change.
    public static function updateProfile($role, $id, array $d)
    {
        self::updateColumns($role, $id, array_values(array_diff(self::infoColumns($role), ['mobile'])), $d);
    }

    // Customers only: date of birth + full address (empty = not given)
    public static function updateUserExtra($id, $birth, $address)
    {
        self::run('UPDATE users SET birth_date = ?, address = ? WHERE id = ?', [$birth !== '' ? $birth : null, $address !== '' ? $address : null, $id]);
    }

    public static function setPhoto($role, $id, $path)
    {
        self::run('UPDATE ' . self::table($role) . ' SET photo = ? WHERE id = ?', [$path, $id]);
    }

    // Employee -> admin or admin -> employee: only "role" changes, the id and everything linked to it stay.
    public static function changeRole($id, $role)
    {
        self::run('UPDATE staff SET role = ? WHERE id = ?', [$role, $id]);
    }

    // ---- Delete (soft): the row stays (so what this person added keeps its name), only marked deleted by admin $by.
    // They can't log in any more and their mobile / email can be used for a new account.
    public static function delete($role, $id, $by)
    {
        self::run('UPDATE ' . self::table($role) . ' SET deleted_at = NOW(), deleted_by = ?, otp = NULL, otp_expires = NULL'
                . ' WHERE id = ? AND deleted_at IS NULL', [$by, $id]);
        self::forgetAllDevices($role, $id);
    }

    // A deleted account with this role (for the "restore" button), or null
    public static function findDeleted($role, $id)
    {
        return self::findWithRole($role, $id, true);
    }

    public static function restore($role, $id)
    {
        self::run('UPDATE ' . self::table($role) . ' SET deleted_at = NULL, deleted_by = NULL WHERE id = ?', [$id]);
    }
}
