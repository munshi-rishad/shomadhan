<?php
// app/controllers/AuthController.php
// CONTROLLER for login + register.
// All form checking is done HERE. If something is wrong, the error text goes to
// the view and shows under the field (in the chosen language).

require_once APP_PATH . '/models/AreaModel.php';

class AuthController
{
    // ---------------------------------------------------------------- LOGIN
    public function login()
    {
        list($lang, $t) = $this->texts();
        $errors = [];
        $notice = '';
        $old    = ['mobile' => ''];

        if ($this->isPost()) {
            $old['mobile'] = substr(preg_replace('/\D/', '', (string)($_POST['mobile'] ?? '')), 0, 11);

            if (!csrf_check($_POST['csrf'] ?? '')) {
                $errors['form'] = $t['err_csrf'];
            } else {
                if ($m = $this->mobileError((string)($_POST['mobile'] ?? ''), $t)) { $errors['mobile'] = $m; }
                if ($m = $this->passwordError((string)($_POST['password'] ?? ''), $t)) { $errors['password'] = $m; }
                if (!$errors) {
                    // TODO next step: UserModel (database) check + start user session
                    $notice = $t['login_ok_notice'];
                }
            }
        }

        view('auth/login', ['lang' => $lang, 't' => $t, 'errors' => $errors, 'notice' => $notice, 'old' => $old]);
    }

    // ------------------------------------------------------------- REGISTER
    public function register()
    {
        list($lang, $t) = $this->texts();
        $areas   = AreaModel::getAll();
        $genders = ['male', 'female', 'other'];
        $errors  = [];
        $notice  = '';
        $old     = ['name' => '', 'mobile' => '', 'email' => '', 'area' => '', 'gender' => '', 'terms' => true];

        if ($this->isPost()) {
            $old['name']   = mb_strtoupper(mb_substr(trim((string)($_POST['name'] ?? '')), 0, 50, 'UTF-8'), 'UTF-8');
            $old['mobile'] = substr(preg_replace('/\D/', '', (string)($_POST['mobile'] ?? '')), 0, 11);
            $old['email']  = strtolower(mb_substr(trim((string)($_POST['email'] ?? '')), 0, 60, 'UTF-8'));
            $old['area']   = (string)($_POST['area'] ?? '');
            $old['gender'] = (string)($_POST['gender'] ?? '');
            $old['terms']  = isset($_POST['terms']);

            if (!csrf_check($_POST['csrf'] ?? '')) {
                $errors['form'] = $t['err_csrf'];
            } else {
                // Name: English letters only, 2-50 characters (saved in CAPITAL letters)
                $name = preg_replace('/\s+/', ' ', trim((string)($_POST['name'] ?? '')));
                if ($name === '') {
                    $errors['name'] = $t['err_name_empty'];
                } elseif (!preg_match("/^[A-Za-z][A-Za-z .'-]{1,49}$/", $name)) {
                    $errors['name'] = $t['err_name_format'];
                }

                if ($m = $this->mobileError((string)($_POST['mobile'] ?? ''), $t)) { $errors['mobile'] = $m; }

                // Email: optional, but if written it must be a Gmail address
                $email = strtolower(trim((string)($_POST['email'] ?? '')));
                if ($email !== '' && !preg_match('/^[a-z0-9](?:[a-z0-9]|\.(?!\.)){4,28}[a-z0-9]@gmail\.com$/', $email)) {
                    $errors['email'] = $t['err_email'];
                }

                if ($m = $this->passwordError((string)($_POST['password'] ?? ''), $t)) { $errors['password'] = $m; }

                if (!in_array($old['area'], $areas, true))     { $errors['area']   = $t['err_area']; }
                if (!in_array($old['gender'], $genders, true)) { $errors['gender'] = $t['err_gender']; }
                if (!$old['terms'])                            { $errors['terms']  = $t['err_terms']; }

                if (!$errors) {
                    // TODO next step: save in the database (UserModel) with password_hash()
                    $notice = $t['reg_ok_notice'];
                }
            }
        }

        view('auth/register', [
            'lang' => $lang, 't' => $t, 'errors' => $errors, 'notice' => $notice,
            'old' => $old, 'areas' => $areas,
        ]);
    }

    // -------------------------------------------------------------- helpers
    private function isPost()
    {
        return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
    }

    // Language from the cookie (same as the landing page): returns [lang, texts]
    private function texts()
    {
        $all = [
            'bn' => require APP_PATH . '/lang/bn.php',
            'en' => require APP_PATH . '/lang/en.php',
        ];
        $lang = 'bn';
        if (isset($_COOKIE['lang']) && isset($all[$_COOKIE['lang']])) {
            $lang = $_COOKIE['lang'];
        }
        return [$lang, $all[$lang]];
    }

    // Bangladeshi mobile: digits only, exactly 11 digits, starts with 01[3-9]. Returns '' if OK.
    private function mobileError($mobile, array $t)
    {
        $mobile = trim($mobile);
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

    // Password: required, English keyboard characters only, 8-128 characters. Returns '' if OK.
    private function passwordError($password, array $t)
    {
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
}
