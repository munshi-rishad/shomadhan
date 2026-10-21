<?php
// app/models/MessageModel.php
// MODEL for chats between a user and the office (conversations + messages).
//   Each user has one "Shomadhan Support" chat (booking_id NULL) and can have one chat per booking.
//   Every admin and employee sees every chat and can answer. Unread:
//     for the user   = office messages newer than user_seen_at
//     for the office = user messages newer than staff_seen_at
// Used by the user's Message pages and the office Messages page (also the live update requests).

class MessageModel
{
    const MAX_LEN = 1000;                                       // longest message (characters)
    const FILTERS = ['all', 'support', 'active', 'done'];     // all chats / support / active bookings / completed

    // ---- Small database helpers

    private static function db()
    {
        return Database::get();
    }

    private static function all($sql, array $args = [])
    {
        $st = self::db()->prepare($sql);
        $st->execute($args);
        return $st->fetchAll();
    }

    private static function one($sql, array $args = [])
    {
        $st = self::db()->prepare($sql);
        $st->execute($args);
        return $st->fetch() ?: null;
    }

    // Text for a LIKE search: % and _ typed by the user are searched as normal letters
    private static function likePattern($q)
    {
        return '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q) . '%';
    }

    // Booking id typed in a search box ("12", "#12", "SHM00012", "#SHM00012"), or 0
    private static function idFromSearch($q)
    {
        return preg_match('/^#?(SHM)?0*(\d+)$/i', $q, $m) ? (int)$m[2] : 0;
    }

    // Seen-time column of one side of the chat
    private static function seenColumn($side)
    {
        return $side === 'user' ? 'user_seen_at' : 'staff_seen_at';
    }

    // A chat row + its booking / service / user + last message + unread numbers (both sides)
    private static function sql($where, $order = 'c.last_message_at DESC, c.id DESC')
    {
        return 'SELECT c.*, b.status AS booking_status, b.scheduled_at, x.name_bn, x.name_en, s.name_bn AS service_bn, s.name_en AS service_en,
                       s.icon AS service_icon, s.color AS service_color, u.name AS user_name, u.mobile AS user_mobile, u.photo AS user_photo,
                       (SELECT m.body FROM messages m WHERE m.conversation_id = c.id ORDER BY m.id DESC LIMIT 1) AS last_body,
                       (SELECT m.sender FROM messages m WHERE m.conversation_id = c.id ORDER BY m.id DESC LIMIT 1) AS last_sender,
                       (SELECT COUNT(*) FROM messages m WHERE m.conversation_id = c.id AND m.sender = "staff"
                           AND (c.user_seen_at IS NULL OR m.created_at > c.user_seen_at)) AS unread_user,
                       (SELECT COUNT(*) FROM messages m WHERE m.conversation_id = c.id AND m.sender = "user"
                           AND (c.staff_seen_at IS NULL OR m.created_at > c.staff_seen_at)) AS unread_staff
                FROM conversations c
                JOIN users u ON u.id = c.user_id
                LEFT JOIN bookings b ON b.id = c.booking_id
                LEFT JOIN sub_services x ON x.id = b.sub_service_id
                LEFT JOIN services s ON s.id = x.service_id
                ' . $where . ' ORDER BY ' . $order;
    }

    // Filter button + search box part of a chat list (added after the WHERE). Adds its values to $args.
    // Only chats with at least one message are listed. The office can also search by user name / mobile.
    private static function filterSql($filter, $q, array &$args, $staff)
    {
        $where = ' AND c.last_message_at IS NOT NULL';
        if ($filter === 'support') {
            $where .= ' AND c.booking_id IS NULL';
        }
        if ($filter === 'active') {
            $where .= ' AND b.status IN ("pending", "ongoing")';
        }
        if ($filter === 'done') {
            $where .= ' AND b.status IN ("completed", "cancelled")';
        }

        $q = trim((string)$q);
        if ($q === '') {
            return $where;
        }
        $like = self::likePattern($q);
        $where .= ' AND (c.booking_id = ? OR x.name_bn LIKE ? OR x.name_en LIKE ? OR s.name_bn LIKE ? OR s.name_en LIKE ?
                         OR EXISTS (SELECT 1 FROM messages m WHERE m.conversation_id = c.id AND m.body LIKE ?)'
                . ($staff ? ' OR u.name LIKE ? OR u.mobile LIKE ?' : '') . ')';
        array_push($args, self::idFromSearch($q), $like, $like, $like, $like, $like);
        if ($staff) {
            array_push($args, $like, $like);
        }
        return $where;
    }

    // ------------------------------------------------------------ USER

    // How many chats each filter has (the numbers on the filter buttons), in one query
    public static function countsForUser($userId)
    {
        $counts = self::one('SELECT COUNT(*) AS `all`, COALESCE(SUM(c.booking_id IS NULL), 0) AS support,
                                    COALESCE(SUM(b.status IN ("pending", "ongoing")), 0) AS active,
                                    COALESCE(SUM(b.status IN ("completed", "cancelled")), 0) AS done
                             FROM conversations c LEFT JOIN bookings b ON b.id = c.booking_id
                             WHERE c.user_id = ? AND c.last_message_at IS NOT NULL', [$userId]);
        return array_map('intval', $counts);
    }

    // The user's chats (only ones with at least one message), newest first
    public static function forUser($userId, $filter = 'all', $q = '')
    {
        $args = [$userId];
        $where = 'WHERE c.user_id = ?' . self::filterSql($filter, $q, $args, false);
        return self::all(self::sql($where), $args);
    }

    // The chat of this user about this booking (0 = support). Made the first time it is opened.
    // topic_key is booking_id or 0 (one chat per user + topic).
    public static function open($userId, $bookingId = 0)
    {
        self::db()->prepare('INSERT IGNORE INTO conversations (user_id, booking_id) VALUES (?, ?)')
            ->execute([$userId, $bookingId ?: null]);
        return self::one(self::sql('WHERE c.user_id = ? AND c.topic_key = ?'), [$userId, (int)$bookingId]);
    }

    // One chat of this user only (a user can never open someone else's chat)
    public static function findForUser($id, $userId)
    {
        return self::one(self::sql('WHERE c.id = ? AND c.user_id = ?'), [$id, $userId]);
    }

    // How many office messages the user has not read yet (red number on the Message tab)
    public static function unreadForUser($userId)
    {
        $row = self::one('SELECT COUNT(*) AS n FROM messages m JOIN conversations c ON c.id = m.conversation_id
                          WHERE c.user_id = ? AND m.sender = "staff" AND (c.user_seen_at IS NULL OR m.created_at > c.user_seen_at)', [$userId]);
        return (int)$row['n'];
    }

    // ------------------------------------------------------------ OFFICE (every admin + employee)

    // All chats: waiting for an answer first, then newest (at most 200)
    public static function inbox($filter = 'all', $q = '')
    {
        $args = [];
        $where = 'WHERE 1' . self::filterSql($filter, $q, $args, true);
        return self::all(self::sql($where, 'unread_staff > 0 DESC, c.last_message_at DESC, c.id DESC') . ' LIMIT 200', $args);
    }

    public static function find($id)
    {
        return self::one(self::sql('WHERE c.id = ?'), [$id]);
    }

    // Chats with a user message nobody in the office has read yet (red number in the office menu)
    public static function unreadForStaff()
    {
        $row = self::one('SELECT COUNT(DISTINCT c.id) AS n FROM conversations c JOIN messages m ON m.conversation_id = c.id
                          WHERE m.sender = "user" AND (c.staff_seen_at IS NULL OR m.created_at > c.staff_seen_at)');
        return (int)$row['n'];
    }

    // ------------------------------------------------------------ BOTH

    // Messages of a chat (oldest first) with the office sender's name. $afterId > 0 = only newer ones (live update)
    public static function messages($convId, $afterId = 0)
    {
        return self::all('SELECT m.*, s.name AS staff_name, s.role AS staff_role, s.photo AS staff_photo
                          FROM messages m LEFT JOIN staff s ON s.id = m.staff_id
                          WHERE m.conversation_id = ? AND m.id > ? ORDER BY m.id ASC', [$convId, (int)$afterId]);
    }

    // $sender = 'user' or 'staff'. Sending also means the sender has read everything in it. Returns the new message id.
    public static function send($convId, $sender, $staffId, $body)
    {
        $db = self::db();
        $db->prepare('INSERT INTO messages (conversation_id, sender, staff_id, body) VALUES (?, ?, ?, ?)')
           ->execute([$convId, $sender, $sender === 'staff' ? $staffId : null, $body]);
        $id = (int)$db->lastInsertId();
        $db->prepare('UPDATE conversations SET last_message_at = NOW(3), ' . self::seenColumn($sender) . ' = NOW(3) WHERE id = ?')
           ->execute([$convId]);
        return $id;
    }

    // $side ('user' / 'staff') opened the chat: everything in it counts as read
    public static function markSeen($convId, $side)
    {
        self::db()->prepare('UPDATE conversations SET ' . self::seenColumn($side) . ' = NOW(3) WHERE id = ?')->execute([$convId]);
    }
}
