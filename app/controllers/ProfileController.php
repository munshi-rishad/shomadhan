<?php
// app/controllers/ProfileController.php
// CONTROLLER for the profile of the logged-in person: their info, photo and change password.
//   user             -> profile.php (show())
//   admin / employee -> ?page=profile inside their dashboard; AdminController calls
//                       savePhoto(), saveInfo() and changePassword() from here.
// Each save function returns the errors ([] = saved), so both sides decide where to go next.

require_once APP_PATH . '/validators/LoginValidator.php';
require_once APP_PATH . '/validators/RegisterValidator.php';
require_once APP_PATH . '/validators/AccountValidator.php';
require_once APP_PATH . '/models/AreaModel.php';
require_once APP_PATH . '/helpers/upload.php';
require_once APP_PATH . '/models/HomeContentModel.php';
require_once APP_PATH . '/models/AccountChangeModel.php';
require_once APP_PATH . '/models/MessageModel.php';
require_once APP_PATH . '/controllers/UserHomeController.php';

class ProfileController
{
    // ---- The user's profile page (profile.php) ----
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
        $box = '';                               // which form had the error: 'photo', 'info' or 'password'
        if (is_post()) {
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
                redirect('profile.php#details');     // back to my info (not the form)
            }
        }
        // the user's profile sits in the same frame as the other user pages (left menu, top bar, phone tab bar)
        $data = UserHomeController::frame($account, $lang, $t, 'profile');
        $data += [
            'errors' => $errors,
            'box'    => $box,
            'old'    => $box === 'info' ? $_POST : [],
            'areas'  => AreaModel::getAll(),
            'favs'   => FavouriteModel::forUser($account['id']),
        ];
        view('user/home', $data);
    }

    // ---- Save or remove my photo ----
    // New square picture (old one is deleted), or remove it ($remove, users only: staff must keep one).
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

    // ---- Save my info ----
    // Edit your own info: name, email (+ area, gender for users). Returns errors ([] = saved).
    public static function saveInfo(array $account, array $post, array $t)
    {
        if (!csrf_check($post['csrf'] ?? '')) {
            return ['form' => $t['err_csrf']];
        }
        $role = $account['role'];
        list($data, $errors) = AccountValidator::info($post, $role, $account['id'], $t);
        if ($errors) {
            return $errors;
        }
        try {
            AccountModel::updateProfile($role, $account['id'], $data);
            if ($role === 'user') {          // only users have birth date + address
                AccountModel::updateUserExtra($account['id'], $data['birth_date'], $data['address']);
            }
        } catch (PDOException $ex) {
            app_log('error', 'profile info: ' . $ex->getMessage());
            return ['form' => $t['err_server']];
        }
        return [];
    }

    // ---- Change my password ----
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
