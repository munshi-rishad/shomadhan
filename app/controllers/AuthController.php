<?php
// app/controllers/AuthController.php
// CONTROLLER for login, register, logout and the live "already used?" check.
// Pages: login.php, register.php, logout.php, check-availability.php (AJAX).
// Form rules live in app/validators/. Database work lives in app/models/.
// If something is wrong, the error text goes to the view and shows under the field
// (in the chosen language).

require_once APP_PATH . '/models/AreaModel.php';
require_once APP_PATH . '/validators/LoginValidator.php';
require_once APP_PATH . '/validators/RegisterValidator.php';

class AuthController
{
    // ---------------------------------------------------------------- LOGIN
    // Shows the login form; on POST checks it and logs in (tryLogin() redirects on success).
    public function login()
    {
        redirect_if_logged_in();
        list($lang, $t) = lang_texts();
        $errors = [];
        $notice = flash_get('notice');
        $old    = ['mobile' => flash_get('mobile'), 'remember' => true];

        if (is_post()) {
            $old['mobile']   = self::typedMobile();
            $old['remember'] = isset($_POST['remember']);

            if (!csrf_check($_POST['csrf'] ?? '')) {
                $errors['form'] = $t['err_csrf'];
            } else {
                $errors = LoginValidator::validate($_POST, $t);
                if (!$errors) {
                    try {
                        $errors = $this->tryLogin($old['mobile'], (string)$_POST['password'], $old['remember'], $t);
                    } catch (PDOException $ex) {
                        app_log('error', 'login: ' . $ex->getMessage());
                        $errors['form'] = $t['err_server'];
                    }
                }
            }
        }

        view('auth/login', ['lang' => $lang, 't' => $t, 'errors' => $errors, 'notice' => $notice, 'old' => $old]);
    }

    // ---- Check mobile + password ----
    // Looks in the accounts tables (admin, employee or user).
    // Correct -> that account's dashboard (redirect, never returns). Wrong -> errors for the form.
    private function tryLogin($mobile, $password, $remember, array $t)
    {
        $ip = substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);

        // Too many wrong tries? Wait first (stops password guessing)
        if ($wait = AccountModel::loginLockedMinutes($mobile, $ip)) {
            return ['form' => sprintf($t['err_login_locked'], $wait)];
        }

        $account = AccountModel::findByMobile($mobile);
        if (!$account) {
            AccountModel::fakeVerify($password);     // same time as a real check
        }

        // Inactive: the admin turned this account off. Said before the password check,
        // so it doesn't tell if a guess was right.
        if ($account && $account['status'] !== 'active') {
            AccountModel::addLoginFail($mobile, $ip);
            return ['form' => $t['err_account_inactive']];
        }
        // Same message for "no such mobile" and "wrong password"
        if (!$account || !AccountModel::passwordMatches($account, $password)) {
            AccountModel::addLoginFail($mobile, $ip);
            return ['form' => $t['err_login_wrong']];
        }

        // Correct: forget the wrong tries and go to the dashboard
        AccountModel::clearLoginFails($mobile);
        login_account($account, $remember);
        flash_set('notice', $t['login_success']);
        redirect(dashboard_url($account['role']));
    }

    // ------------------------------------------------------------- REGISTER
    // Shows the register form; on POST checks it, makes the user account and goes to the login page.
    public function register()
    {
        redirect_if_logged_in();
        list($lang, $t) = lang_texts();
        $areas  = AreaModel::getAll();
        $errors = [];
        $notice = '';
        $old    = ['name' => '', 'mobile' => '', 'email' => '', 'area' => '', 'gender' => '', 'terms' => true];

        if (is_post()) {
            // what the person typed, shown again in the form if something is wrong
            $old['name']   = mb_strtoupper(mb_substr(trim((string)($_POST['name'] ?? '')), 0, 50, 'UTF-8'), 'UTF-8');
            $old['mobile'] = self::typedMobile();
            $old['email']  = strtolower(mb_substr(trim((string)($_POST['email'] ?? '')), 0, 60, 'UTF-8'));
            $old['area']   = (string)($_POST['area'] ?? '');
            $old['gender'] = (string)($_POST['gender'] ?? '');
            $old['terms']  = isset($_POST['terms']);

            if (!csrf_check($_POST['csrf'] ?? '')) {
                $errors['form'] = $t['err_csrf'];
            } else {
                list($errors, $data) = RegisterValidator::validate($_POST, $t, $areas);
                try {
                    // Already used? (checked here too, the live check in the browser can be skipped)
                    if (empty($errors['mobile']) && AccountModel::mobileExists($data['mobile'])) {
                        $errors['mobile'] = $t['err_mobile_taken'];
                    }
                    if (empty($errors['email']) && $data['email'] !== '' && AccountModel::emailExists($data['email'])) {
                        $errors['email'] = $t['err_email_taken'];
                    }
                    if (!$errors) {
                        AccountModel::createUser($data);
                        flash_set('notice', $t['reg_success']);
                        flash_set('mobile', $data['mobile']);
                        redirect('login.php');
                    }
                } catch (PDOException $ex) {
                    if ($ex->getCode() === '23000') {            // UNIQUE key: someone used it a moment ago
                        $errors['form'] = $t['err_already_used'];
                    } else {
                        app_log('error', 'register: ' . $ex->getMessage());
                        $errors['form'] = $t['err_server'];
                    }
                }
            }
        }

        view('auth/register', [
            'lang' => $lang, 't' => $t, 'errors' => $errors, 'notice' => $notice,
            'old' => $old, 'areas' => $areas,
        ]);
    }

    // --------------------------------------------------------------- LOGOUT
    // Only a POST with a valid CSRF token logs out (a link on another site can't log you out).
    public function logout()
    {
        if (is_post() && csrf_check($_POST['csrf'] ?? '')) {
            list(, $t) = lang_texts();
            logout_account();
            flash_set('notice', $t['logout_success']);
        }
        redirect('login.php');
    }

    // ------------------------------------------- LIVE CHECK (AJAX, register)
    // POST check-availability.php  field=mobile|email  value=...  csrf=...
    // Answer (JSON): {"ok":true,"available":false,"message":"..."}
    public function checkAvailability()
    {
        json_headers();
        list(, $t) = lang_texts();

        if (!is_post() || !csrf_check($_POST['csrf'] ?? '')) {
            http_response_code(403);
            echo json_encode(['ok' => false]);
            return;
        }

        // Who asks? Office staff may check any account (admin forms); a logged-in person may skip only himself (profile).
        // kind=account: staff + users tables, kind=provider: service providers (staff only). role + id = the row being edited (not counted).
        $me      = current_account();
        $isStaff = $me && in_array($me['role'], ['admin', 'employee'], true);
        $kind    = (string)($_POST['kind'] ?? 'account');
        $role    = (string)($_POST['role'] ?? '');
        $id      = (int)($_POST['id'] ?? 0);
        $isMyself = $me && $role === $me['role'] && $id === (int)$me['id'];
        if (!$isStaff && !$isMyself) {
            $role = '';
            $id   = 0;
        }

        // limit per browser (stops someone listing all mobiles): 40 checks in 10 minutes, office staff 300
        if (too_many('avail_hits', $isStaff ? 300 : 40, 600)) {
            http_response_code(429);
            echo json_encode(['ok' => false]);
            return;
        }

        $field = (string)($_POST['field'] ?? '');
        $value = trim((string)($_POST['value'] ?? ''));
        // logged in (profile / admin forms) and register use different texts for "already used"
        $msgs  = $me ? ['mobile' => 'acc_err_mobile_used', 'email' => 'acc_err_email_used']
                     : ['mobile' => 'err_mobile_taken',    'email' => 'err_email_taken'];
        // $taken stays null when the value is not valid yet (nothing to check)
        try {
            $taken = null;
            if ($kind === 'provider') {
                require_once APP_PATH . '/models/ProviderModel.php';
                if ($isStaff && $field === 'mobile' && LoginValidator::mobileError($value, $t) === '') {
                    $taken = ProviderModel::mobileTaken($value, $id);
                } elseif ($isStaff && $field === 'email' && $value !== '' && RegisterValidator::emailError($value, $t) === '') {
                    $taken = ProviderModel::emailTaken($value, $id);
                }
            } elseif ($field === 'mobile' && LoginValidator::mobileError($value, $t) === '') {
                $taken = AccountModel::takenBy('mobile', $value, $role, $id);
            } elseif ($field === 'email' && $value !== '' && RegisterValidator::emailError($value, $t) === '') {
                $taken = AccountModel::takenBy('email', strtolower($value), $role, $id);
            }
            if ($taken !== null) {
                $answer = ['ok' => true, 'available' => !$taken, 'message' => $taken ? $t[$msgs[$field]] : ''];
                echo json_encode($answer, JSON_UNESCAPED_UNICODE);
                return;
            }
        } catch (PDOException $ex) {
            app_log('error', 'availability: ' . $ex->getMessage());
        }
        echo json_encode(['ok' => false]);
    }

    // -------------------------------------------------------------- helpers
    // The mobile from the form: digits only, at most 11 (shown again in the form)
    private static function typedMobile()
    {
        return substr(preg_replace('/\D/', '', (string)($_POST['mobile'] ?? '')), 0, 11);
    }
}
