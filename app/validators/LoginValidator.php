<?php
// app/validators/LoginValidator.php
// Server-side checks for the login form (same rules as validation.js).
// Each function returns '' when OK, or the error text in the chosen language.

class LoginValidator
{
    // Bangladeshi mobile: digits only, exactly 11 digits, starts with 01[3-9]
    public static function mobileError($mobile, array $t)
    {
        $mobile = trim((string)$mobile);
        if ($mobile === '') {
            return $t['err_mobile_empty'];
        }
        if (!ctype_digit($mobile) || strlen($mobile) !== 11) {
            return $t['err_mobile_length'];
        }
        if (!preg_match('/^01[3-9]\d{8}$/', $mobile)) {
            return $t['err_mobile_format'];
        }
        return '';
    }

    // Password: required, English keyboard characters only, 8-128 characters
    public static function passwordError($password, array $t)
    {
        $password = (string)$password;
        if ($password === '') {
            return $t['err_password_empty'];
        }
        if (!preg_match('/^[\x21-\x7E]+$/', $password)) {
            return $t['err_password_ascii'];
        }
        if (strlen($password) < 8) {
            return $t['err_password_min'];
        }
        if (strlen($password) > 128) {
            return $t['err_password_max'];
        }
        return '';
    }

    // Returns an array of errors: ['mobile' => '...', 'password' => '...'] (empty = OK)
    public static function validate(array $post, array $t)
    {
        $errors = [];
        if ($m = self::mobileError($post['mobile'] ?? '', $t))     { $errors['mobile'] = $m; }
        if ($m = self::passwordError($post['password'] ?? '', $t)) { $errors['password'] = $m; }
        return $errors;
    }
}
