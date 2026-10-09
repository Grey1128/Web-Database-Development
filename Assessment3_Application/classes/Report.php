<?php
/**
 * Report - read-only sales figures for managers, computed with SQL aggregates.
 */
class Report
{
    private mysqli $db;

    public function __construct()
    {
        $this->db = Database::get();
    }

    /** Tickets sold, revenue, attendance and occupancy for every performance. */
    public function byPerformance(): array
    {
        return $this->db->query(
            "SELECT p.performance_id, p.starts_at, p.status, s.title,
                    cap.capacity,
                    COUNT(t.ticket_id)               AS sold,
                    COALESCE(SUM(t.price_paid), 0)   AS revenue,
                    COUNT(t.checked_in_at)           AS attended,
                    ROUND(100 * COUNT(t.ticket_id) / cap.capacity, 1) AS occupancy_pct
             FROM performances p
             JOIN shows s ON s.show_id = p.show_id
             JOIN (SELECT sec.venue_id, COUNT(*) AS capacity
                     FROM seats st JOIN sections sec ON sec.section_id = st.section_id
                    GROUP BY sec.venue_id) cap ON cap.venue_id = p.venue_id
             LEFT JOIN tickets t ON t.performance_id = p.performance_id AND t.is_active = 1
             GROUP BY p.performance_id, p.starts_at, p.status, s.title, cap.capacity
             ORDER BY p.starts_at"
        )->fetch_all(MYSQLI_ASSOC);
    }

    /** Totals per show (live tickets only). */
    public function byShow(): array
    {
        return $this->db->query(
            "SELECT s.title,
                    COUNT(DISTINCT p.performance_id) AS performances,
                    COUNT(t.ticket_id)               AS sold,
                    COALESCE(SUM(t.price_paid), 0)   AS revenue
             FROM shows s
             LEFT JOIN performances p ON p.show_id = s.show_id
             LEFT JOIN tickets t      ON t.performance_id = p.performance_id AND t.is_active = 1
             GROUP BY s.show_id, s.title
             ORDER BY revenue DESC"
        )->fetch_all(MYSQLI_ASSOC);
    }
}
