<?php
// app/models/ProviderAccountModel.php
// MODEL for the money between the office and each provider (admin "Provider accounts" page).
// For every finished job (bookings.status = completed):
//    provider earns   = bill - commission                (bill = final_total, or total when it did not change)
//    provider holds   = provider_cash                    (cash the customer gave the provider)
//    due to provider  = earns - holds                     (minus = the provider must give the office its commission)
// Settled money is in provider_payments: + = the office paid the provider, - = the provider paid the office.
//    balance = SUM(due) - SUM(payments)     > 0: the office must pay the provider   < 0: the provider must pay the office

class ProviderAccountModel
{
    const METHODS = ['cash', 'bkash', 'nagad', 'rocket', 'bank'];
    const BILL = 'CAST(COALESCE(b.final_total, b.total) AS SIGNED)';
    // due to the provider for one job (columns are UNSIGNED: count signed so a minus works)
    const DUE = '(CAST(COALESCE(b.final_total, b.total) AS SIGNED) - CAST(b.commission AS SIGNED) - CAST(b.provider_cash AS SIGNED))';

    private static function db()
    {
        return Database::get();
    }

    // ---- Every provider (not deleted) with its totals. $q = name / mobile, $onlyDue = only providers with money left.
    public static function summary($q = '', $onlyDue = false)
    {
        $bill = self::BILL;
        $sql = "SELECT p.id, p.name, p.mobile, p.area, p.status,
                       COALESCE(j.jobs, 0) AS jobs, COALESCE(j.work, 0) AS work, COALESCE(j.commission, 0) AS commission,
                       COALESCE(j.cash, 0) AS cash, COALESCE(j.due, 0) AS due, COALESCE(m.paid, 0) AS paid,
                       COALESCE(j.due, 0) - COALESCE(m.paid, 0) AS balance
                FROM providers p
                LEFT JOIN (SELECT b.provider_id, COUNT(*) AS jobs, SUM($bill) AS work, SUM(b.commission) AS commission,
                                  SUM(b.provider_cash) AS cash, SUM(" . self::DUE . ") AS due
                           FROM bookings b WHERE b.status = 'completed' AND b.provider_id IS NOT NULL GROUP BY b.provider_id) j ON j.provider_id = p.id
                LEFT JOIN (SELECT provider_id, SUM(amount) AS paid FROM provider_payments GROUP BY provider_id) m ON m.provider_id = p.id
                WHERE p.deleted_at IS NULL";
        $args = [];
        $q = trim((string)$q);
        if ($q !== '') {
            $sql .= ' AND (p.name LIKE ? OR p.mobile LIKE ? OR p.id = ?)';
            $like = '%' . addcslashes($q, '%_\\') . '%';
            array_push($args, $like, $like, ctype_digit($q) ? (int)$q : 0);
        }
        if ($onlyDue) {
            $sql .= ' HAVING balance <> 0';
        }
        $st = self::db()->prepare($sql . ' ORDER BY p.id');
        $st->execute($args);
        return $st->fetchAll();
    }

    // ---- One provider's statement: finished jobs + payments, newest first (each row has 'kind' = job | payment)
    public static function statement($providerId)
    {
        $bill = self::BILL;
        $st = self::db()->prepare("SELECT 'job' AS kind, b.id, b.completed_at AS at, $bill AS bill, b.commission, b.provider_cash AS cash,
                                          " . self::DUE . " AS amount, b.payment, NULL AS method, NULL AS note, NULL AS by_name
                                   FROM bookings b WHERE b.provider_id = ? AND b.status = 'completed'
                                   UNION ALL
                                   SELECT 'payment', pp.id, pp.created_at, NULL, NULL, NULL, -pp.amount, NULL, pp.method, pp.note, s.name
                                   FROM provider_payments pp JOIN staff s ON s.id = pp.created_by WHERE pp.provider_id = ?
                                   ORDER BY at DESC, id DESC");
        $st->execute([$providerId, $providerId]);
        return $st->fetchAll();
    }

    // ---- Money settled. $amount > 0: the office paid the provider; < 0: the provider paid the office.
    public static function addPayment($providerId, $amount, $method, $note, $staffId)
    {
        self::db()->prepare('INSERT INTO provider_payments (provider_id, amount, method, note, created_by) VALUES (?, ?, ?, ?, ?)')
            ->execute([$providerId, $amount, $method, $note, $staffId]);
    }
}
