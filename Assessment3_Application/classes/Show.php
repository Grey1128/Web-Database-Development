<?php
/**
 * Show - a production (title, genre, description) independent of dates.
 */
class Show
{
    private mysqli $db;

    public function __construct()
    {
        $this->db = Database::get();
    }

    public function all(): array
    {
        return $this->db->query(
            'SELECT s.*,
                    (SELECT COUNT(*) FROM performances p WHERE p.show_id = s.show_id) AS performance_count
             FROM shows s ORDER BY s.title'
        )->fetch_all(MYSQLI_ASSOC);
    }

    public function find(int $showId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM shows WHERE show_id = ?');
        $stmt->bind_param('i', $showId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public function create(string $title, string $genre, string $description, int $duration, string $rating): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO shows (title, genre, description, duration_mins, age_rating) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('sssis', $title, $genre, $description, $duration, $rating);
        $stmt->execute();
    }

    public function update(int $showId, string $title, string $genre, string $description, int $duration, string $rating): void
    {
        $stmt = $this->db->prepare(
            'UPDATE shows SET title = ?, genre = ?, description = ?, duration_mins = ?, age_rating = ?
             WHERE show_id = ?'
        );
        $stmt->bind_param('sssisi', $title, $genre, $description, $duration, $rating, $showId);
        $stmt->execute();
    }

    /**
     * Delete a show. The foreign key on performances is ON DELETE RESTRICT,
     * so a show that has performances cannot be removed - we return false.
     */
    public function delete(int $showId): bool
    {
        try {
            $stmt = $this->db->prepare('DELETE FROM shows WHERE show_id = ?');
            $stmt->bind_param('i', $showId);
            $stmt->execute();
            return $stmt->affected_rows > 0;
        } catch (mysqli_sql_exception $e) {
            return false; // 1451: row is referenced by a foreign key
        }
    }
}
