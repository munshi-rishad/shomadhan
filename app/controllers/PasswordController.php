<?php
// app/controllers/PasswordController.php
// CONTROLLER for "Forgot password". 3 steps on one page (forgot-password.php):
//   1. email -> is this email in the database?  No: "Email not found".  Yes: Next sends a 6-digit OTP
//   2. otp   -> does the OTP match?             No: password is NOT shown (5 tries).  Yes: step 3
//   3. show  -> the current password is shown one time, then a button goes to the login page
// Works for admins, employees and users (whichever table has the email).

require_once APP_PATH . '/helpers/mail.php';

class PasswordController
{
    public function forgot()
    {
        redirect_if_logged_in();
        if (isset($_GET['restart'])) {
            unset($_SESSION['reset']);
            redirect('forgot-password.php');
        }
        list($lang, $t) = lang_texts();
        $reset  = $_SESSION['reset'] ?? null;
        $step   = $reset['step'] ?? 'email';
        $errors = [];
        $old    = ['email' => ''];

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            if (!csrf_check($_POST['csrf'] ?? '')) {
                $errors['form'] = $t['err_csrf'];
            } else {
                try {
                    $action = (string)($_POST['action'] ?? '');
                    if ($action === 'email' && $step === 'email') {
                        $old['email'] = strtolower(trim((string)($_POST['email'] ?? '')));
                        $errors = $this->stepEmail($old['email'], $t);
                    } elseif ($action === 'resend' && $step === 'otp') {
                        $errors = $this->sendOtp(AccountModel::find($reset['role'], $reset['id']), $t);
                    } elseif ($action === 'otp' && $step === 'otp') {
                        $errors = $this->stepOtp($reset, (string)($_POST['otp'] ?? ''), $t);
                    }
                } catch (PDOException $ex) {
                    app_log('error', 'forgot: ' . $ex->getMessage());
                    $errors['form'] = $t['err_server'];
                }
            }
            if (!$errors) {
                redirect('forgot-password.php');      // show the next step (refresh won't re-send the form)
            }
            $reset = $_SESSION['reset'] ?? null;
            $step  = $reset['step'] ?? 'email';
        }

        // STEP 3: show the current password once, then forget this reset
        $shownPassword = '';
        if ($step === 'show') {
            unset($_SESSION['reset']);
            $account = AccountModel::find($reset['role'], $reset['id']);
            if ($account) {
                $shownPassword = $account['password'];
            } else {
                $step = 'email';
            }
        }

        view('auth/forgot', [
            'lang'   => $lang,
            't'      => $t,
            'step'   => $step,
            'errors' => $errors,
            'old'    => $old,
            'notice' => flash_get('notice'),
            'masked' => $reset ? $this->maskEmail($reset['email']) : '',
            'shownPassword' => $shownPassword,
        ]);
    }

    // STEP 1: email -> OTP
    private function stepEmail($email, array $t)
    {
        if ($email === '') {
            return ['email' => $t['err_email_empty']];
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['email' => $t['err_email_invalid']];
        }
        $account = AccountModel::findByEmail($email);
        if (!$account) {
            return ['email' => $t['err_email_not_found']];
        }
        if ($account['status'] === 'blocked') {
            return ['form' => $t['err_account_blocked']];
        }
        return $this->sendOtp($account, $t);
    }

    // Makes an OTP and sends it to the email (Gmail settings: config/mail.php). The OTP is never shown on screen.
    private function sendOtp($account, array $t)
    {
        if (!$account) {
            unset($_SESSION['reset']);
            return ['form' => $t['err_server']];
        }
        $otp  = AccountModel::newOtp($account['role'], $account['id']);
        $html = '<div style="font-family:Arial,sans-serif;font-size:15px;color:#222">'
              . '<p>' . e($t['otp_mail_hello']) . ' ' . e($account['name']) . ',</p>'
              . '<p>' . e($t['otp_mail_line']) . '</p>'
              . '<p style="font-size:28px;font-weight:bold;letter-spacing:6px;color:#0b6b4c">' . $otp . '</p>'
              . '<p>' . e(sprintf($t['otp_mail_expire'], AccountModel::OTP_MINUTES)) . '</p></div>';
        $text = $t['otp_mail_line'] . ' ' . $otp . "\n" . sprintf($t['otp_mail_expire'], AccountModel::OTP_MINUTES);
        $sent = send_mail($account['email'], $account['name'], $t['otp_mail_subject'], $html, $text);

        if (!$sent) {
            AccountModel::clearOtp($account['role'], $account['id']);
            return ['form' => $t['err_mail_failed']];
        }
        $_SESSION['reset'] = ['role' => $account['role'], 'id' => (int)$account['id'], 'email' => $account['email'], 'step' => 'otp'];
        flash_set('notice', sprintf($t['otp_sent'], $this->maskEmail($account['email'])));
        return [];
    }

    // STEP 2: check the OTP
    private function stepOtp(array $reset, $otp, array $t)
    {
        $otp = preg_replace('/\D/', '', $otp);
        if (strlen($otp) !== 6) {
            return ['otp' => $t['err_otp_format']];
        }
        $result = AccountModel::checkOtp($reset['role'], $reset['id'], $otp);
        if ($result === 'expired') {
            return ['otp' => $t['err_otp_expired']];
        }
        if ($result === 'too_many') {
            return ['otp' => $t['err_otp_too_many']];
        }
        if ($result === 'wrong') {
            return ['otp' => sprintf($t['err_otp_wrong'], AccountModel::otpTriesLeft($reset['role'], $reset['id']))];
        }
        AccountModel::clearOtp($reset['role'], $reset['id']);
        $_SESSION['reset']['step'] = 'show';
        flash_set('notice', $t['otp_ok']);
        return [];
    }

    // rishad.ahmed@gmail.com -> ri**********@gmail.com
    private function maskEmail($email)
    {
        $parts = explode('@', (string)$email, 2);
        if (count($parts) !== 2) {
            return '';
        }
        return substr($parts[0], 0, 2) . str_repeat('*', max(2, strlen($parts[0]) - 2)) . '@' . $parts[1];
    }
}
