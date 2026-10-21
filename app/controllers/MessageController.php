<?php
// app/controllers/MessageController.php
// CONTROLLER for the user's chats with the office (messages.php). Every admin and employee can answer
// (Admin / employee dashboard -> মেসেজ).
//   messages.php                    chat list (filter: ?f=all|support|active|done, search: ?q=)
//   messages.php?support=1          open the "Shomadhan Support" chat
//   messages.php?booking=3          open the chat about my booking #SHM00003
//   messages.php?c=5                one chat
//   messages.php?c=5&after=12&live=1   JSON: messages newer than id 12 (the open chat checks every few seconds)
//   POST action=send (c, body)      send a message
// body(), sendError() and live() are also used by the office side (AdminController, page "messages").

require_once APP_PATH . '/controllers/UserHomeController.php';
require_once APP_PATH . '/models/BookingModel.php';
require_once APP_PATH . '/models/MessageModel.php';

class MessageController
{
    // ---- The user's messages page ----
    public function index()
    {
        $user = require_role('user');
        list($lang, $t) = lang_texts();
        $uid = (int)$user['id'];

        // open (or make) a chat, then go to it
        if (!empty($_GET['support'])) {
            redirect('messages.php?c=' . (int)MessageModel::open($uid, 0)['id']);
        }
        if (!empty($_GET['booking'])) {
            $booking = BookingModel::findForUser((int)$_GET['booking'], $uid);
            if (!$booking) {
                redirect('messages.php');
            }
            redirect('messages.php?c=' . (int)MessageModel::open($uid, $booking['id'])['id']);
        }

        // the open chat (only my own chats can be opened)
        $conv = !empty($_GET['c']) ? MessageModel::findForUser((int)$_GET['c'], $uid) : null;
        $errors = [];

        // send a message (at most 20 a minute), then show the chat again
        if (is_post() && ($_POST['action'] ?? '') === 'send') {
            $conv = MessageModel::findForUser((int)($_POST['c'] ?? 0), $uid);
            $errors = self::sendError($conv, $t);
            if (!$errors && too_many('msg_send', 20, 60)) {
                $errors['body'] = $t['err_too_many'];
            }
            if (!$errors) {
                MessageModel::send($conv['id'], 'user', null, self::body());
                redirect('messages.php?c=' . (int)$conv['id']);
            }
        }

        if ($conv && !empty($_GET['live'])) {               // live check from chat.js
            self::live($conv, 'user', $t);
            return;
        }
        if ($conv) {
            MessageModel::markSeen($conv['id'], 'user');
        }

        // chat list: filter + search, then the page inside the user frame
        $filter = in_array($_GET['f'] ?? '', MessageModel::FILTERS, true) ? $_GET['f'] : 'all';
        $mq = mb_substr(trim((string)($_GET['mq'] ?? '')), 0, 60, 'UTF-8');
        $data = UserHomeController::frame($user, $lang, $t, 'messages');
        $data += [
            'filter'  => $filter,
            'mq'      => $mq,
            'chats'   => MessageModel::forUser($uid, $filter, $mq),
            'fCounts' => MessageModel::countsForUser($uid),
            'conv'    => $conv,
            'msgs'    => $conv ? MessageModel::messages($conv['id']) : [],
            'errors'  => $errors,
            'draft'   => $errors ? (string)($_POST['body'] ?? '') : '',     // keep what was typed when sending failed
        ];
        view('user/home', $data);
    }

    // ---------- shared with the office side (AdminController)

    // ---- The typed message, cleaned ----
    // Windows line ends become "\n", spaces around are trimmed, at most one empty line in a row
    public static function body()
    {
        $body = trim(str_replace("\r\n", "\n", (string)($_POST['body'] ?? '')));
        return preg_replace("/\n{3,}/", "\n\n", $body);
    }

    // ---- Can this message be sent? ----
    // Returns ['body' => error text], or [] when it is OK. $conv = the chat (null = not found / not allowed).
    public static function sendError($conv, array $t)
    {
        if (!csrf_check($_POST['csrf'] ?? '')) {
            return ['body' => $t['err_csrf']];
        }
        if (!$conv) {
            return ['body' => $t['msg_err_chat']];
        }
        $len = mb_strlen(self::body(), 'UTF-8');
        if ($len < 1) {
            return ['body' => $t['msg_err_empty']];
        }
        if ($len > MessageModel::MAX_LEN) {
            return ['body' => sprintf($t['msg_err_long'], MessageModel::MAX_LEN)];
        }
        return [];
    }

    // ---- Live check (JSON) ----
    // For chat.js: {html: new bubbles, last: newest id}. New messages arriving = they are read.
    // $side = 'user' or 'staff' (who is looking).
    public static function live(array $conv, $side, array $t)
    {
        session_write_close();      // nothing to save in the session: let the visitor's other pages load at the same time
        json_headers();
        $msgs = MessageModel::messages($conv['id'], (int)($_GET['after'] ?? 0));
        if ($msgs) {
            MessageModel::markSeen($conv['id'], $side);
        }
        ob_start();
        view('partials/chat-bubbles', ['t' => $t, 'msgs' => $msgs, 'side' => $side]);
        echo json_encode(['html' => ob_get_clean(), 'last' => $msgs ? (int)end($msgs)['id'] : 0]);
    }
}
