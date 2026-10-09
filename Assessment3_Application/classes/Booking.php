<?php
/**
 * Booking - the seat-hold and checkout engine.
 *
 * Flow:  choose seats -> holdSeats() -> (10 minute timer) -> checkout() -> tickets
 *
 * Double-selling is prevented at three levels:
 *   1. Locking: holdSeats() and checkout() lock the performance row with
 *      SELECT ... FOR UPDATE, so two requests for the same performance are
 *      processed one after the other, never interleaved.
 *   2. seat_holds has UNIQUE(performance_id, seat_id): two customers cannot
 *      hold the same seat at the same time.
 *   3. tickets has UNIQUE(performance_id, seat_id, is_active): even if
 *      application code were wrong, the database refuses a second live ticket.
 */
class Booking
{
    private mysqli $db;

    public function __construct()
    {
        $this->db = Database::get();
    }

    /**
     * Replace this customer's current selection for a performance with a new
     * set of held seats. Returns ['ok' => bool, 'message' => string].
     */
    public function holdSeats(int $userId, int $performanceId, array $seatIds): array
    {
        $seatIds = array_values(array_unique(array_map('intval', $seatIds)));
        if (count($seatIds) === 0) {
            return ['ok' => false, 'message' => 'Please select at least one seat.'];
        }
        if (count($seatIds) > MAX_SEATS_PER_ORDER) {
            return ['ok' => false, 'message' => 'You can select up to ' . MAX_SEATS_PER_ORDER . ' seats per order.'];
        }

        $this->db->begin_transaction();
        try {
            if (!$this->lockBookablePerformance($performanceId)) {
                $this->db->rollback();
                return ['ok' => false, 'message' => 'This performance is no longer on sale.'];
            }

            // Housekeeping: drop expired holds, then this user's previous selection.
            $stmt = $this->db->prepare('DELETE FROM seat_holds WHERE performance_id = ? AND expires_at <= NOW()');
            $stmt->bind_param('i', $performanceId);
            $stmt->execute();

            $stmt = $this->db->prepare('DELETE FROM seat_holds WHERE performance_id = ? AND user_id = ?');
            $stmt->bind_param('ii', $performanceId, $userId);
            $stmt->execute();

            // Every requested seat must exist in this venue and have a price.
            $placeholders = implode(',', array_fill(0, count($seatIds), '?'));
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) AS valid_seats
                 FROM seats st
                 JOIN sections sec   ON sec.section_id = st.section_id
                 JOIN performances p ON p.venue_id = sec.venue_id
                 JOIN price_tiers pt ON pt.performance_id = p.performance_id
                                    AND pt.section_id = sec.section_id
                 WHERE p.performance_id = ? AND st.seat_id IN ($placeholders)"
            );
            $stmt->bind_param('i' . str_repeat('i', count($seatIds)), $performanceId, ...$seatIds);
            $stmt->execute();
            if ((int)$stmt->get_result()->fetch_assoc()['valid_seats'] !== count($seatIds)) {
                $this->db->rollback();
                return ['ok' => false, 'message' => 'One or more of those seats does not exist for this performance.'];
            }

            // None of them may already be sold.
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) AS sold FROM tickets
                 WHERE performance_id = ? AND is_active = 1 AND seat_id IN ($placeholders)"
            );
            $stmt->bind_param('i' . str_repeat('i', count($seatIds)), $performanceId, ...$seatIds);
            $stmt->execute();
            if ((int)$stmt->get_result()->fetch_assoc()['sold'] > 0) {
                $this->db->rollback();
                return ['ok' => false, 'message' => 'Sorry - one of those seats has just been sold. Please choose again.'];
            }

            // Insert the holds. A duplicate-key error means another customer holds the seat.
            $minutes = HOLD_MINUTES;
            $stmt = $this->db->prepare(
                'INSERT INTO seat_holds (performance_id, seat_id, user_id, expires_at)
                 VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL ? MINUTE))'
            );
            foreach ($seatIds as $seatId) {
                $stmt->bind_param('iiii', $performanceId, $seatId, $userId, $minutes);
                $stmt->execute();
            }

            $this->db->commit();
            return ['ok' => true, 'message' => 'Seats held for ' . HOLD_MINUTES . ' minutes. Complete checkout before the timer runs out.'];
        } catch (mysqli_sql_exception $e) {
            $this->db->rollback();
            if ($e->getCode() === 1062) {
                return ['ok' => false, 'message' => 'Sorry - another customer is holding one of those seats. Please choose again.'];
            }
            error_log('holdSeats failed: ' . $e->getMessage());
            return ['ok' => false, 'message' => 'Something went wrong while holding your seats.'];
        }
    }

    /** This customer's unexpired holds for a performance, with prices and time left. */
    public function heldSeats(int $userId, int $performanceId): array
    {
        $stmt = $this->db->prepare(
            "SELECT h.seat_id, st.row_label, st.seat_number, sec.section_name, pt.price,
                    TIMESTAMPDIFF(SECOND, NOW(), h.expires_at) AS seconds_left
             FROM seat_holds h
             JOIN seats st       ON st.seat_id = h.seat_id
             JOIN sections sec   ON sec.section_id = st.section_id
             JOIN price_tiers pt ON pt.performance_id = h.performance_id
                                AND pt.section_id = sec.section_id
             WHERE h.user_id = ? AND h.performance_id = ? AND h.expires_at > NOW()
             ORDER BY st.row_label, st.seat_number"
        );
        $stmt->bind_param('ii', $userId, $performanceId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function releaseHolds(int $userId, int $performanceId): void
    {
        $stmt = $this->db->prepare('DELETE FROM seat_holds WHERE user_id = ? AND performance_id = ?');
        $stmt->bind_param('ii', $userId, $performanceId);
        $stmt->execute();
    }

    /**
     * Convert this customer's held seats into a paid order with one ticket per
     * seat. Payment is simulated. Returns ['ok' => bool, 'message' => string,
     * 'order_id' => int|null].
     */
    public function checkout(int $userId, int $performanceId): array
    {
        $this->db->begin_transaction();
        try {
            if (!$this->lockBookablePerformance($performanceId)) {
                $this->db->rollback();
                return ['ok' => false, 'message' => 'This performance is no longer on sale.', 'order_id' => null];
            }

            // Re-read the holds INSIDE the transaction; the timer may have run out.
            $stmt = $this->db->prepare(
                "SELECT h.seat_id, pt.price
                 FROM seat_holds h
                 JOIN seats st       ON st.seat_id = h.seat_id
                 JOIN price_tiers pt ON pt.performance_id = h.performance_id
                                    AND pt.section_id = st.section_id
                 WHERE h.user_id = ? AND h.performance_id = ? AND h.expires_at > NOW()
                 FOR UPDATE"
            );
            $stmt->bind_param('ii', $userId, $performanceId);
            $stmt->execute();
            $holds = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            if (!$holds) {
                $this->db->rollback();
                return ['ok' => false, 'message' => 'Your seat hold has expired. Please select your seats again.', 'order_id' => null];
            }

            $ref = 'HL-' . self::randomCode(6);
            $stmt = $this->db->prepare("INSERT INTO orders (booking_ref, user_id, status) VALUES (?, ?, 'Paid')");
            $stmt->bind_param('si', $ref, $userId);
            $stmt->execute();
            $orderId = $this->db->insert_id;

            $stmt = $this->db->prepare(
                'INSERT INTO tickets (order_id, performance_id, seat_id, price_paid, ticket_code)
                 VALUES (?, ?, ?, ?, ?)'
            );
            foreach ($holds as $hold) {
                $seatId = (int)$hold['seat_id'];
                $price = (float)$hold['price'];
                $code = 'T' . self::randomCode(9);
                $stmt->bind_param('iiids', $orderId, $performanceId, $seatId, $price, $code);
                $stmt->execute();
            }

            $this->releaseHolds($userId, $performanceId);
            $this->db->commit();
            return ['ok' => true, 'message' => "Payment received - booking $ref confirmed.", 'order_id' => $orderId];
        } catch (mysqli_sql_exception $e) {
            $this->db->rollback();
            if ($e->getCode() === 1062) {
                return ['ok' => false, 'message' => 'A seat in your selection was sold before checkout finished. You have not been charged.', 'order_id' => null];
            }
            error_log('checkout failed: ' . $e->getMessage());
            return ['ok' => false, 'message' => 'Checkout failed. You have not been charged.', 'order_id' => null];
        }
    }

    /** Every order belonging to a customer, newest first, with derived totals. */
    public function ordersForUser(int $userId): array
    {
        $cutoff = CANCEL_CUTOFF_HOURS;
        $stmt = $this->db->prepare(
            "SELECT o.order_id, o.booking_ref, o.status, o.created_at,
                    p.performance_id, p.starts_at, p.status AS performance_status, s.title,
                    COUNT(t.ticket_id)  AS ticket_count,
                    SUM(t.price_paid)   AS total,
                    (o.status = 'Paid' AND p.status = 'Scheduled'
                     AND p.starts_at > DATE_ADD(NOW(), INTERVAL ? HOUR)) AS can_cancel
             FROM orders o
             JOIN tickets t      ON t.order_id = o.order_id
             JOIN performances p ON p.performance_id = t.performance_id
             JOIN shows s        ON s.show_id = p.show_id
             WHERE o.user_id = ?
             GROUP BY o.order_id, o.booking_ref, o.status, o.created_at,
                      p.performance_id, p.starts_at, p.status, s.title
             ORDER BY o.created_at DESC"
        );
        $stmt->bind_param('ii', $cutoff, $userId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /** One order with its tickets - only if it belongs to the given user. */
    public function orderForUser(int $orderId, int $userId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT o.*, p.starts_at, p.status AS performance_status, s.title, v.venue_name, v.address
             FROM orders o
             JOIN tickets t      ON t.order_id = o.order_id
             JOIN performances p ON p.performance_id = t.performance_id
             JOIN shows s        ON s.show_id = p.show_id
             JOIN venues v       ON v.venue_id = p.venue_id
             WHERE o.order_id = ? AND o.user_id = ?
             LIMIT 1"
        );
        $stmt->bind_param('ii', $orderId, $userId);
        $stmt->execute();
        $order = $stmt->get_result()->fetch_assoc();
        if (!$order) {
            return null;
        }

        $stmt = $this->db->prepare(
            "SELECT t.*, st.row_label, st.seat_number, sec.section_name
             FROM tickets t
             JOIN seats st     ON st.seat_id = t.seat_id
             JOIN sections sec ON sec.section_id = st.section_id
             WHERE t.order_id = ?
             ORDER BY st.row_label, st.seat_number"
        );
        $stmt->bind_param('i', $orderId);
        $stmt->execute();
        $order['tickets'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $order['total'] = array_sum(array_column($order['tickets'], 'price_paid'));
        return $order;
    }

    /**
     * Customer cancels a paid order more than CANCEL_CUTOFF_HOURS before the
     * show. The tickets are deactivated (is_active = NULL), which frees the
     * seats for resale while keeping the history.
     */
    public function cancelOrder(int $orderId, int $userId): bool
    {
        $cutoff = CANCEL_CUTOFF_HOURS;
        $this->db->begin_transaction();
        try {
            $stmt = $this->db->prepare(
                "SELECT o.order_id FROM orders o
                 JOIN tickets t      ON t.order_id = o.order_id
                 JOIN performances p ON p.performance_id = t.performance_id
                 WHERE o.order_id = ? AND o.user_id = ? AND o.status = 'Paid'
                   AND p.status = 'Scheduled'
                   AND p.starts_at > DATE_ADD(NOW(), INTERVAL ? HOUR)
                 LIMIT 1
                 FOR UPDATE"
            );
            $stmt->bind_param('iii', $orderId, $userId, $cutoff);
            $stmt->execute();
            if (!$stmt->get_result()->fetch_assoc()) {
                $this->db->rollback();
                return false;
            }

            $stmt = $this->db->prepare("UPDATE orders SET status = 'Refunded' WHERE order_id = ?");
            $stmt->bind_param('i', $orderId);
            $stmt->execute();

            $stmt = $this->db->prepare('UPDATE tickets SET is_active = NULL WHERE order_id = ?');
            $stmt->bind_param('i', $orderId);
            $stmt->execute();

            $this->db->commit();
            return true;
        } catch (mysqli_sql_exception $e) {
            $this->db->rollback();
            return false;
        }
    }

    /* ----------------------------- helpers ----------------------------- */

    /**
     * Lock the performance row for the rest of the transaction and confirm it
     * can still be booked. Concurrent bookings for the same performance wait here.
     */
    private function lockBookablePerformance(int $performanceId): bool
    {
        $stmt = $this->db->prepare(
            "SELECT performance_id FROM performances
             WHERE performance_id = ? AND status = 'Scheduled' AND starts_at > NOW()
             FOR UPDATE"
        );
        $stmt->bind_param('i', $performanceId);
        $stmt->execute();
        return (bool)$stmt->get_result()->fetch_assoc();
    }

    /** Random code from an alphabet without look-alike characters (0/O, 1/I). */
    private static function randomCode(int $length): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        return $code;
    }
}
