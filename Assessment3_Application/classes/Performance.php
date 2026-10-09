<?php
/**
 * Performance - dated occurrences of shows, their prices and live seat maps.
 */
class Performance
{
    private mysqli $db;

    public function __construct()
    {
        $this->db = Database::get();
    }

    /**
     * Upcoming, bookable performances with the cheapest price and the number
     * of seats still free (not sold and not currently held).
     */
    public function upcoming(int $showId = 0, string $date = ''): array
    {
        $sql = "SELECT p.performance_id, p.starts_at, s.show_id, s.title, s.genre, s.age_rating,
                       s.duration_mins, v.venue_name,
                       (SELECT MIN(pt.price) FROM price_tiers pt
                         WHERE pt.performance_id = p.performance_id) AS from_price,
                       (SELECT COUNT(*) FROM seats st
                          JOIN sections sec ON sec.section_id = st.section_id
                         WHERE sec.venue_id = p.venue_id)
                     - (SELECT COUNT(*) FROM tickets t
                         WHERE t.performance_id = p.performance_id AND t.is_active = 1)
                     - (SELECT COUNT(*) FROM seat_holds h
                         WHERE h.performance_id = p.performance_id AND h.expires_at > NOW())
                       AS seats_left
                FROM performances p
                JOIN shows s  ON s.show_id  = p.show_id
                JOIN venues v ON v.venue_id = p.venue_id
                WHERE p.status = 'Scheduled' AND p.starts_at > NOW()";
        $types = '';
        $params = [];
        if ($showId > 0) {
            $sql .= ' AND p.show_id = ?';
            $types .= 'i';
            $params[] = $showId;
        }
        if ($date !== '') {
            $sql .= ' AND DATE(p.starts_at) = ?';
            $types .= 's';
            $params[] = $date;
        }
        $sql .= ' ORDER BY p.starts_at';

        $stmt = $this->db->prepare($sql);
        if ($params) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /** One performance with its show and venue details. */
    public function find(int $performanceId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT p.*, s.title, s.genre, s.description, s.duration_mins, s.age_rating,
                    v.venue_name, v.address,
                    (p.status = 'Scheduled' AND p.starts_at > NOW()) AS is_bookable
             FROM performances p
             JOIN shows s  ON s.show_id  = p.show_id
             JOIN venues v ON v.venue_id = p.venue_id
             WHERE p.performance_id = ?"
        );
        $stmt->bind_param('i', $performanceId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    /**
     * Every seat for a performance with its price and live state:
     * sold | mine (held by this user) | held (by someone else) | available.
     * Expired holds are ignored, so seats free themselves automatically.
     */
    public function seatMap(int $performanceId, int $userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT st.seat_id, st.row_label, st.seat_number, sec.section_name, pt.price,
                    CASE
                        WHEN t.ticket_id IS NOT NULL THEN 'sold'
                        WHEN h.hold_id IS NOT NULL AND h.user_id = ? THEN 'mine'
                        WHEN h.hold_id IS NOT NULL THEN 'held'
                        ELSE 'available'
                    END AS state
             FROM performances p
             JOIN sections sec   ON sec.venue_id = p.venue_id
             JOIN seats st       ON st.section_id = sec.section_id
             JOIN price_tiers pt ON pt.performance_id = p.performance_id
                                AND pt.section_id = sec.section_id
             LEFT JOIN tickets t     ON t.performance_id = p.performance_id
                                    AND t.seat_id = st.seat_id AND t.is_active = 1
             LEFT JOIN seat_holds h  ON h.performance_id = p.performance_id
                                    AND h.seat_id = st.seat_id AND h.expires_at > NOW()
             WHERE p.performance_id = ?
             ORDER BY sec.section_id, st.row_label, st.seat_number"
        );
        $stmt->bind_param('ii', $userId, $performanceId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function priceTiers(int $performanceId): array
    {
        $stmt = $this->db->prepare(
            'SELECT sec.section_name, pt.price FROM price_tiers pt
             JOIN sections sec ON sec.section_id = pt.section_id
             WHERE pt.performance_id = ? ORDER BY pt.price DESC'
        );
        $stmt->bind_param('i', $performanceId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /* ------------------------------ manager ------------------------------ */

    public function venues(): array
    {
        return $this->db->query('SELECT * FROM venues ORDER BY venue_name')->fetch_all(MYSQLI_ASSOC);
    }

    public function sectionsForVenue(int $venueId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM sections WHERE venue_id = ? ORDER BY section_id');
        $stmt->bind_param('i', $venueId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function allForManager(): array
    {
        return $this->db->query(
            "SELECT p.performance_id, p.starts_at, p.status, s.title, v.venue_name,
                    (SELECT COUNT(*) FROM tickets t
                      WHERE t.performance_id = p.performance_id AND t.is_active = 1) AS sold
             FROM performances p
             JOIN shows s  ON s.show_id = p.show_id
             JOIN venues v ON v.venue_id = p.venue_id
             ORDER BY p.starts_at DESC"
        )->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Schedule a performance and its section prices in one transaction:
     * either the performance AND every price exist, or nothing is saved.
     * $prices is [section_id => price].
     */
    public function create(int $showId, int $venueId, string $startsAt, array $prices): bool
    {
        $this->db->begin_transaction();
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO performances (show_id, venue_id, starts_at) VALUES (?, ?, ?)"
            );
            $stmt->bind_param('iis', $showId, $venueId, $startsAt);
            $stmt->execute();
            $performanceId = $this->db->insert_id;

            $tier = $this->db->prepare(
                'INSERT INTO price_tiers (performance_id, section_id, price) VALUES (?, ?, ?)'
            );
            foreach ($prices as $sectionId => $price) {
                $sectionId = (int)$sectionId;
                $price = (float)$price;
                $tier->bind_param('iid', $performanceId, $sectionId, $price);
                $tier->execute();
            }
            $this->db->commit();
            return true;
        } catch (mysqli_sql_exception $e) {
            $this->db->rollback();
            return false;
        }
    }

    /**
     * Cancel a performance: every live ticket is refunded, every paid order
     * for it is marked Refunded, and outstanding holds are released - all
     * inside one transaction so the data can never be half-cancelled.
     */
    public function cancel(int $performanceId): bool
    {
        $this->db->begin_transaction();
        try {
            $stmt = $this->db->prepare(
                "UPDATE performances SET status = 'Cancelled'
                 WHERE performance_id = ? AND status = 'Scheduled'"
            );
            $stmt->bind_param('i', $performanceId);
            $stmt->execute();
            if ($stmt->affected_rows === 0) {
                $this->db->rollback();
                return false;
            }

            $stmt = $this->db->prepare(
                "UPDATE orders o SET o.status = 'Refunded'
                 WHERE o.status = 'Paid' AND o.order_id IN
                       (SELECT order_id FROM tickets WHERE performance_id = ? AND is_active = 1)"
            );
            $stmt->bind_param('i', $performanceId);
            $stmt->execute();

            $stmt = $this->db->prepare(
                'UPDATE tickets SET is_active = NULL WHERE performance_id = ? AND is_active = 1'
            );
            $stmt->bind_param('i', $performanceId);
            $stmt->execute();

            $stmt = $this->db->prepare('DELETE FROM seat_holds WHERE performance_id = ?');
            $stmt->bind_param('i', $performanceId);
            $stmt->execute();

            $this->db->commit();
            return true;
        } catch (mysqli_sql_exception $e) {
            $this->db->rollback();
            return false;
        }
    }
}
