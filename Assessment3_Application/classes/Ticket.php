<?php
/**
 * Ticket - lookup and door check-in for box-office staff.
 */
class Ticket
{
    private mysqli $db;

    public function __construct()
    {
        $this->db = Database::get();
    }

    public function findByCode(string $code): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT t.*, o.booking_ref, o.status AS order_status, u.full_name,
                    p.starts_at, p.status AS performance_status, s.title,
                    st.row_label, st.seat_number, sec.section_name,
                    (DATE(p.starts_at) = CURDATE()) AS is_today
             FROM tickets t
             JOIN orders o       ON o.order_id = t.order_id
             JOIN users u        ON u.user_id = o.user_id
             JOIN performances p ON p.performance_id = t.performance_id
             JOIN shows s        ON s.show_id = p.show_id
             JOIN seats st       ON st.seat_id = t.seat_id
             JOIN sections sec   ON sec.section_id = st.section_id
             WHERE t.ticket_code = ?"
        );
        $stmt->bind_param('s', $code);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    /**
     * Admit a ticket holder. The UPDATE only succeeds when every rule holds
     * (valid, not used, performance is today and not cancelled), so two staff
     * scanning the same ticket at once cannot both admit it.
     * Returns ['ok' => bool, 'message' => string, 'ticket' => ?array].
     */
    public function checkIn(string $code): array
    {
        $code = strtoupper(trim($code));
        $stmt = $this->db->prepare(
            "UPDATE tickets t
             JOIN performances p ON p.performance_id = t.performance_id
             SET t.checked_in_at = NOW()
             WHERE t.ticket_code = ? AND t.is_active = 1 AND t.checked_in_at IS NULL
               AND p.status = 'Scheduled' AND DATE(p.starts_at) = CURDATE()"
        );
        $stmt->bind_param('s', $code);
        $stmt->execute();
        $admitted = $stmt->affected_rows === 1;
        $ticket = $this->findByCode($code);

        if ($admitted) {
            return ['ok' => true, 'message' => 'Admit - ticket checked in.', 'ticket' => $ticket];
        }
        // The update was refused: work out why so staff can explain it to the customer.
        $reason = match (true) {
            $ticket === null                              => 'No ticket exists with that code.',
            $ticket['is_active'] === null                 => 'This ticket was refunded and is no longer valid.',
            $ticket['performance_status'] === 'Cancelled' => 'This performance was cancelled.',
            $ticket['checked_in_at'] !== null             => 'Already used - checked in at ' . date('g:i a', strtotime($ticket['checked_in_at'])) . '.',
            !$ticket['is_today']                          => 'This ticket is for a different date (' . date('D j M', strtotime($ticket['starts_at'])) . ').',
            default                                       => 'Ticket could not be checked in.',
        };
        return ['ok' => false, 'message' => $reason, 'ticket' => $ticket];
    }

    /** Today's performances with sold vs. checked-in counts for the door team. */
    public function todaysAttendance(): array
    {
        return $this->db->query(
            "SELECT p.performance_id, p.starts_at, s.title,
                    COUNT(t.ticket_id)        AS sold,
                    COUNT(t.checked_in_at)    AS checked_in
             FROM performances p
             JOIN shows s ON s.show_id = p.show_id
             LEFT JOIN tickets t ON t.performance_id = p.performance_id AND t.is_active = 1
             WHERE DATE(p.starts_at) = CURDATE() AND p.status = 'Scheduled'
             GROUP BY p.performance_id, p.starts_at, s.title
             ORDER BY p.starts_at"
        )->fetch_all(MYSQLI_ASSOC);
    }
}
