<?php
// app/validators/RegisterValidator.php
// Server-side checks for the register form (same rules as validation.js).

require_once __DIR__ . '/LoginValidator.php';

class RegisterValidator
{
    const GENDERS = ['male', 'female', 'other'];

    // Name: English letters only, 2-50 characters (saved in CAPITAL letters)
    public static function nameError($name, array $t)
    {
        $name = preg_replace('/\s+/', ' ', trim((string)$name));
        if ($name === '') {
            return $t['err_name_empty'];
        }
        if (!preg_match("/^[A-Za-z][A-Za-z .'-]{1,49}$/", $name)) {
            return $t['err_name_format'];
        }
        return '';
    }

    // Email: optional, but if written it must be a Gmail address
    public static function emailError($email, array $t)
    {
        $email = strtolower(trim((string)$email));
        if ($email !== '' && !preg_match('/^[a-z0-9](?:[a-z0-9]|\.(?!\.)){4,28}[a-z0-9]@gmail\.com$/', $email)) {
            return $t['err_email'];
        }
        return '';
    }

    // Returns [errors, clean data]
    public static function validate(array $post, array $t, array $areas)
    {
        $data = [
            'name'     => strtoupper(preg_replace('/\s+/', ' ', trim((string)($post['name'] ?? '')))),
            'mobile'   => trim((string)($post['mobile'] ?? '')),
            'email'    => strtolower(trim((string)($post['email'] ?? ''))),
            'password' => (string)($post['password'] ?? ''),
            'area'     => (string)($post['area'] ?? ''),
            'gender'   => (string)($post['gender'] ?? ''),
        ];
        $errors = [];
        if ($m = self::nameError($post['name'] ?? '', $t))                         { $errors['name'] = $m; }
        if ($m = LoginValidator::mobileError($data['mobile'], $t))                 { $errors['mobile'] = $m; }
        if ($m = self::emailError($data['email'], $t))                             { $errors['email'] = $m; }
        if ($m = LoginValidator::passwordError($data['password'], $t))             { $errors['password'] = $m; }
        if (!in_array($data['area'], $areas, true))                                { $errors['area'] = $t['err_area']; }
        if (!in_array($data['gender'], self::GENDERS, true))                       { $errors['gender'] = $t['err_gender']; }
        if (!isset($post['terms']))                                                { $errors['terms'] = $t['err_terms']; }
        return [$errors, $data];
    }
}
