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
    // ---- Check the account info form ----
    // Returns [$clean values, $errors]. $id = the account being edited (0 = new account).
    public static function info(array $post, $role, $id, array $t)
    {
        $data = [
            'name'   => RegisterValidator::cleanName($post['name'] ?? ''),
            'email'  => strtolower(trim((string)($post['email'] ?? ''))),
            'area'   => (string)($post['area'] ?? ''),
            'gender' => (string)($post['gender'] ?? ''),
        ];
        $errors = [];

        // fields every role has (email is optional for everyone, but must not belong to another account)
        if ($m = RegisterValidator::nameError($post['name'] ?? '', $t)) {
            $errors['name'] = $m;
        }
        if ($m = RegisterValidator::emailError($data['email'], $t)) {
            $errors['email'] = $m;
        } elseif (AccountModel::takenBy('email', $data['email'], $role, $id)) {
            $errors['email'] = $t['acc_err_email_used'];
        }
        if (!in_array($data['area'], AreaModel::getAll(), true)) {
            $errors['area'] = $t['err_area'];
        }
        if (!in_array($data['gender'], RegisterValidator::GENDERS, true)) {
            $errors['gender'] = $t['err_gender'];
        }

        // customers also have date of birth + full address (both optional)
        if ($role === 'user') {
            $address = preg_replace('/\s+/u', ' ', (string)($post['address'] ?? ''));
            $data['birth_date'] = trim((string)($post['birth_date'] ?? ''));
            $data['address']    = mb_substr(trim($address), 0, 250, 'UTF-8');

            if ($data['birth_date'] !== '' && !self::birthDateOk($data['birth_date'])) {
                $errors['birth_date'] = $t['pf_err_birth'];
            }
            $len = mb_strlen($data['address'], 'UTF-8');
            if ($len > 200 || ($len > 0 && $len < 5)) {
                $errors['address'] = $t['bk_err_address'];
            }
        }
        return [$data, $errors];
    }

    // ---- Date of birth ----
    // A real 'Y-m-d' date, and the person is between 10 and 100 years old
    private static function birthDateOk($date)
    {
        $parsed = DateTime::createFromFormat('!Y-m-d', $date);
        if (!$parsed || $parsed->format('Y-m-d') !== $date) {
            return false;
        }
        return $date <= date('Y-m-d', strtotime('-10 years'))
            && $date >= date('Y-m-d', strtotime('-100 years'));
    }
}
