<?php
// app/controllers/ProfileController.php
// Profile of the logged-in person: their info + change password under it.
//   user             -> profile.php
//   admin / employee -> ?page=profile inside their dashboard

require_once APP_PATH . '/validators/LoginValidator.php';
require_once APP_PATH . '/validators/RegisterValidator.php';
require_once APP_PATH . '/validators/AccountValidator.php';
require_once APP_PATH . '/models/AreaModel.php';
require_once APP_PATH . '/helpers/upload.php';

class ProfileController
{
    public function show()
    {
        $account = current_account();
        if (!$account) {
            redirect('login.php');
        }
        if ($account['role'] !== 'user') {
            redirect(dashboard_url($account['role']) . '?page=profile');
        }
        list($lang, $t) = lang_texts();
        $errors = [];
        $box = '';                               // which form had the error: 'info' or 'password'
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $action = (string)($_POST['action'] ?? '');
            if ($action === 'photo' || $action === 'photo_remove') {
                $box = 'photo';
                $errors = self::savePhoto($account, $action === 'photo_remove', $t);
                $done = $t['ph_saved'];
            } else {
                $box = $action === 'info' ? 'info' : 'password';
                $errors = $box === 'info' ? self::saveInfo($account, $_POST, $t) : self::changePassword($account, $_POST, $t);
                $done = $box === 'info' ? $t['pf_saved'] : $t['cp_done'];
            }
            if (!$errors) {
                flash_set('notice', $done);
                redirect('profile.php');
            }
        }
        view('auth/profile', [
            'lang'    => $lang,
            't'       => $t,
            'errors'  => $errors,
            'account' => $account,
            'notice'  => flash_get('notice'),
            'back'    => dashboard_url($account['role']),
            'box'     => $box,
            'old'     => $box === 'info' ? $_POST : [],
            'areas'   => $account['role'] === 'user' ? AreaModel::getAll() : [],
        ]);
    }

    // My own photo: new square picture (old one is deleted), or remove it ($remove, users only: staff must keep one).
    public static function savePhoto(array $account, $remove, array $t)
    {
        if (!csrf_check($_POST['csrf'] ?? '')) {
            return ['form' => $t['err_csrf']];
        }
        if ($remove && $account['role'] !== 'user') {
            return ['photo' => $t['ph_err_need']];
        }
        $path = null;
        if (!$remove) {
            $up = save_image_upload('photo', 'photos', ['png', 'jpg', 'webp'], $t, true, PHOTO);
            if ($up['error']) {
                return ['photo' => $up['error']];
            }
            $path = $up['path'];
        }
        try {
            AccountModel::setPhoto($account['role'], $account['id'], $path);
        } catch (PDOException $ex) {
            delete_upload($path);
            app_log('error', 'profile photo: ' . $ex->getMessage());
            return ['form' => $t['err_server']];
        }
        delete_upload($account['photo'] ?? null);
        return [];
    }

    // Edit your own info: name, email (+ area, gender for users). Returns errors ([] = saved).
    public static function saveInfo(array $account, array $post, array $t)
    {
        if (!csrf_check($post['csrf'] ?? '')) {
            return ['form' => $t['err_csrf']];
        }
        $role = $account['role'];
        list($d, $e) = AccountValidator::info($post, $role, $account['id'], $t);
        if ($e) {
            return $e;
        }
        try {
            AccountModel::updateProfile($role, $account['id'], $d);
        } catch (PDOException $ex) {
            app_log('error', 'profile info: ' . $ex->getMessage());
            return ['form' => $t['err_server']];
        }
        return [];
    }

    // Change your own password: current + new + new again. Returns errors ([] = saved).
    public static function changePassword(array $account, array $post, array $t)
    {
        $current = (string)($post['current'] ?? '');
        $new     = (string)($post['new'] ?? '');
        $again   = (string)($post['again'] ?? '');
        $errors  = [];
        if (!csrf_check($post['csrf'] ?? '')) {
            return ['form' => $t['err_csrf']];
        }
        if ($current === '') {
            $errors['current'] = $t['err_password_empty'];
        } elseif (!AccountModel::passwordMatches($account, $current)) {
            $errors['current'] = $t['cp_err_current'];
        }
        if ($m = LoginValidator::passwordError($new, $t)) {
            $errors['new'] = $m;
        } elseif ($new === $current) {
            $errors['new'] = $t['cp_err_same'];
        }
        if (empty($errors['new']) && $again !== $new) {
            $errors['again'] = $t['cp_err_again'];
        }
        if (!$errors) {
            try {
                AccountModel::setPassword($account['role'], $account['id'], $new);
            } catch (PDOException $ex) {
                app_log('error', 'change password: ' . $ex->getMessage());
                $errors['form'] = $t['err_server'];
            }
        }
        return $errors;
    }
}
