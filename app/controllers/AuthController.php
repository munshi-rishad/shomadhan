<?php
// app/controllers/AuthController.php
// CONTROLLER for login (register will be added here later).
// The login form is checked HERE (validateLogin). If something is wrong,
// the error text goes to the view and shows under the field.

class AuthController
{
    public function login()
    {
        // Language: same cookie as the landing page ('bn' default, 'en' if chosen)
        $allTexts = [
            'bn' => require APP_PATH . '/lang/bn.php',
            'en' => require APP_PATH . '/lang/en.php',
        ];
        $lang = 'bn';
        if (isset($_COOKIE['lang']) && isset($allTexts[$_COOKIE['lang']])) {
            $lang = $_COOKIE['lang'];
        }
        $t = $allTexts[$lang];                          // all login texts + error messages

        $errors = [];
        $notice = '';
        $old    = ['mobile' => ''];

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $old['mobile'] = substr(trim((string)($_POST['mobile'] ?? '')), 0, 11);

            if (!csrf_check($_POST['csrf'] ?? '')) {
                $errors['form'] = $t['err_csrf'];
            } else {
                $errors = $this->validateLogin($_POST, $t);
                if (!$errors) {
                    // TODO next step: UserModel (database) check + start user session
                    $notice = $t['login_ok_notice'];
                }
            }
        }

        view('auth/login', ['lang' => $lang, 't' => $t, 'errors' => $errors, 'notice' => $notice, 'old' => $old]);
    }

    // Checks mobile + password. Returns an array of error texts (empty array = all good).
    private function validateLogin(array $input, array $t)
    {
        $errors = [];

        // ---- Mobile: digits only, exactly 11 digits, starts with 01[3-9]
        $mobile = trim((string)($input['mobile'] ?? ''));
        if ($mobile === '') {
            $errors['mobile'] = $t['err_mobile_empty'];
        } elseif (!ctype_digit($mobile) || strlen($mobile) !== 11) {
            $errors['mobile'] = $t['err_mobile_length'];
        } elseif (!preg_match('/^01[3-9]\d{8}$/', $mobile)) {
            $errors['mobile'] = $t['err_mobile_format'];
        }

        // ---- Password: required, minimum 8 characters (mb_strlen counts Bangla letters correctly)
        $password = (string)($input['password'] ?? '');
        $length   = mb_strlen($password, 'UTF-8');
        if ($password === '') {
            $errors['password'] = $t['err_password_empty'];
        } elseif ($length < 8) {
            $errors['password'] = $t['err_password_min'];
        } elseif ($length > 128) {
            $errors['password'] = $t['err_password_max'];
        }

        return $errors;
    }
}
