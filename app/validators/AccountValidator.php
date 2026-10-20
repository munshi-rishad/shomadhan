<?php
// app/validators/AccountValidator.php
// Name, email, area, gender check (same for every role, like the register page), used by BOTH:
//   the admin's account form (AdminController::saveAccount) and "my profile" (ProfileController::saveInfo).
// One place, so both forms always accept and reject the same things.

require_once APP_PATH . '/validators/LoginValidator.php';
require_once APP_PATH . '/validators/RegisterValidator.php';
require_once APP_PATH . '/models/AreaModel.php';

class AccountValidator
{
    // returns [$clean values, $errors]. $id = the account being edited (0 = new account)
    public static function info(array $post, $role, $id, array $t)
    {
        $d = [
            'name'   => strtoupper(preg_replace('/\s+/', ' ', trim((string)($post['name'] ?? '')))),
            'email'  => strtolower(trim((string)($post['email'] ?? ''))),
            'area'   => (string)($post['area'] ?? ''),
            'gender' => (string)($post['gender'] ?? ''),
        ];
        $e = [];
        if ($m = RegisterValidator::nameError($post['name'] ?? '', $t))      { $e['name'] = $m; }
        if ($m = RegisterValidator::emailError($d['email'], $t))              { $e['email'] = $m; }   // optional for everyone
        elseif (AccountModel::takenBy('email', $d['email'], $role, $id))       { $e['email'] = $t['acc_err_email_used']; }
        if (!in_array($d['area'], AreaModel::getAll(), true))                  { $e['area'] = $t['err_area']; }
        if (!in_array($d['gender'], RegisterValidator::GENDERS, true))         { $e['gender'] = $t['err_gender']; }
        return [$d, $e];
    }
}
