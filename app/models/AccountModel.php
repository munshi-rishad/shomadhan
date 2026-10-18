<?php
// app/models/AccountModel.php
// MODEL for the 3 account tables: admins, employees, users.
// "role" tells which table: 'admin' -> admins, 'employee' -> employees, 'user' -> users.
// Every query uses prepared statements (safe from SQL injection).
// NOTE: passwords are plain text for now (asked for, so forgot-password can show them).

class AccountModel
{
    const TABLES = ['admin' => 'admins', 'employee' => 'employees', 'user' => 'users'];

    const OTP_MINUTES  = 10;   // OTP works for 10 minutes
    const MAX_OTP_TRIES = 5;   // 5 wrong OTPs -> must ask for a new one

    private static function table($role)
    {
        if (!isset(self::TABLES[$role])) {
            throw new InvalidArgumentException('Unknown role');
        }
        return self::TABLES[$role];
    }

    public static function find($role, $id)
    {
        $st = Database::get()->prepare('SELECT * FROM ' . self::table($role) . ' WHERE id = ?');
        $st->execute([$id]);
        $row = $st->fetch();
        return $row ? $row + ['role' => $role] : null;
    }

    // Looks in admins, then employees, then users. Returns the row with 'role' added, or null.
    private static function findBy($column, $value)
    {
        foreach (self::TABLES as $role => $table) {
            $st = Database::get()->prepare('SELECT * FROM ' . $table . ' WHERE ' . $column . ' = ? LIMIT 1');
            $st->execute([$value]);
            if ($row = $st->fetch()) {
                return $row + ['role' => $role];
            }
        }
        return null;
    }

    public static function findByMobile($mobile)
    {
        return self::findBy('mobile', $mobile);
    }

    public static function findByEmail($email)
    {
        return self::findBy('email', strtolower($email));
    }

    // Already used by ANY account (admin, employee or user)?
    public static function mobileExists($mobile)
    {
        return self::findByMobile($mobile) !== null;
    }

    public static function emailExists($email)
    {
        return self::findByEmail($email) !== null;
    }

    public static function passwordMatches(array $account, $password)
    {
        return hash_equals((string)$account['password'], (string)$password);
    }

    // New customer from the register page -> users table. Returns the new id.
    public static function createUser(array $data)
    {
        $st = Database::get()->prepare(
            'INSERT INTO users (name, mobile, email, password, area, gender, status) VALUES (?, ?, ?, ?, ?, ?, \'active\')'
        );
        $st->execute([
            $data['name'],
            $data['mobile'],
            $data['email'] !== '' ? strtolower($data['email']) : null,
            $data['password'],
            $data['area'],
            $data['gender'],
        ]);
        return (int)Database::get()->lastInsertId();
    }

    // ---- Remember me
    public static function setRememberToken($role, $id, $tokenHash, $expires)
    {
        $st = Database::get()->prepare('UPDATE ' . self::table($role) . ' SET remember_token = ?, remember_expires = ? WHERE id = ?');
        $st->execute([$tokenHash, $expires, $id]);
    }

    public static function clearRememberToken($role, $id)
    {
        self::setRememberToken($role, $id, null, null);
    }

    // ---- Forgot password OTP. Returns the new 6-digit OTP.
    public static function newOtp($role, $id)
    {
        $otp = (string)random_int(100000, 999999);
        $st = Database::get()->prepare(
            'UPDATE ' . self::table($role) . ' SET otp = ?, otp_attempts = 0,
             otp_expires = DATE_ADD(NOW(), INTERVAL ' . self::OTP_MINUTES . ' MINUTE) WHERE id = ?'
        );
        $st->execute([$otp, $id]);
        return $otp;
    }

    // Returns 'ok', 'wrong', 'expired' or 'too_many'
    public static function checkOtp($role, $id, $otp)
    {
        $st = Database::get()->prepare(
            'SELECT otp, otp_attempts, (otp_expires > NOW()) AS fresh FROM ' . self::table($role) . ' WHERE id = ?'
        );
        $st->execute([$id]);
        $row = $st->fetch();
        if (!$row || $row['otp'] === null || !$row['fresh']) {
            return 'expired';
        }
        if ((int)$row['otp_attempts'] >= self::MAX_OTP_TRIES) {
            return 'too_many';
        }
        if (!hash_equals($row['otp'], $otp)) {
            Database::get()->prepare('UPDATE ' . self::table($role) . ' SET otp_attempts = otp_attempts + 1 WHERE id = ?')->execute([$id]);
            return (int)$row['otp_attempts'] + 1 >= self::MAX_OTP_TRIES ? 'too_many' : 'wrong';
        }
        return 'ok';
    }

    public static function otpTriesLeft($role, $id)
    {
        $st = Database::get()->prepare('SELECT otp_attempts FROM ' . self::table($role) . ' WHERE id = ?');
        $st->execute([$id]);
        return max(0, self::MAX_OTP_TRIES - (int)$st->fetchColumn());
    }

    public static function clearOtp($role, $id)
    {
        Database::get()->prepare('UPDATE ' . self::table($role) . ' SET otp = NULL, otp_expires = NULL, otp_attempts = 0 WHERE id = ?')->execute([$id]);
    }
}
