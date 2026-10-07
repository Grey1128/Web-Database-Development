<?php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Book.php';

/*
 Reservation
 Handles the reservation lifecycle: Pending -> Approved -> Collected -> Returned
 (or Cancelled at any point before Collected).


 */
class Reservation
{
    private mysqli $db;
    private Book $bookModel;

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->bookModel = new Book();
    }

    /* Member reserves a book. Fails if no copies are available. */
    public function create(int $userId, int $bookId): bool
    {
        $book = $this->bookModel->findById($bookId);
        if (!$book || (int)$book['available_copies'] <= 0) {
            return false;
        }

        $this->db->begin_transaction();
        try {
            if (!$this->bookModel->decrementAvailability($bookId)) {
                throw new Exception('No copies available.');
            }
            $stmt = $this->db->prepare(
                "INSERT INTO reservations (user_id, book_id, status) VALUES (?, ?, 'Pending')"
            );
            $stmt->bind_param('ii', $userId, $bookId);
            $stmt->execute();
            $stmt->close();
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            return false;
        }
    }

    public function findByUser(int $userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT r.*, b.title, b.author FROM reservations r
             JOIN books b ON r.book_id = b.book_id
             WHERE r.user_id = ? ORDER BY r.reservation_date DESC"
        );
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    public function findAll(): array
    {
        $sql = "SELECT r.*, b.title, u.full_name, u.email
                FROM reservations r
                JOIN books b ON r.book_id = b.book_id
                JOIN users u ON r.user_id = u.user_id
                ORDER BY r.reservation_date DESC";
        return $this->db->query($sql)->fetch_all(MYSQLI_ASSOC);
    }

    public function findById(int $reservationId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM reservations WHERE reservation_id = ?");
        $stmt->bind_param('i', $reservationId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    /* Member (or librarian) cancels a reservation and the copy becomes available again. */
    public function cancel(int $reservationId, int $requestingUserId, bool $isLibrarian = false): bool
    {
        $res = $this->findById($reservationId);
        if (!$res) return false;
        if (!$isLibrarian && (int)$res['user_id'] !== $requestingUserId) return false;
        if (!in_array($res['status'], ['Pending', 'Approved'])) return false;

        $stmt = $this->db->prepare("UPDATE reservations SET status = 'Cancelled' WHERE reservation_id = ?");
        $stmt->bind_param('i', $reservationId);
        $ok = $stmt->execute();
        $stmt->close();

        if ($ok) {
            $this->bookModel->incrementAvailability((int)$res['book_id']);
        }
        return $ok;
    }

    /*Librarian approves a pending reservation and sets a 14-day due date. */
    public function approve(int $reservationId): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE reservations SET status = 'Approved', due_date = DATE_ADD(CURDATE(), INTERVAL 14 DAY)
             WHERE reservation_id = ? AND status = 'Pending'"
        );
        $stmt->bind_param('i', $reservationId);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function markCollected(int $reservationId): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE reservations SET status = 'Collected' WHERE reservation_id = ? AND status = 'Approved'"
        );
        $stmt->bind_param('i', $reservationId);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    /* Librarian marks a book returned; the copy becomes available again. */
    public function markReturned(int $reservationId): bool
    {
        $res = $this->findById($reservationId);
        if (!$res || $res['status'] !== 'Collected') return false;

        $stmt = $this->db->prepare(
            "UPDATE reservations SET status = 'Returned', return_date = CURDATE() WHERE reservation_id = ?"
        );
        $stmt->bind_param('i', $reservationId);
        $ok = $stmt->execute();
        $stmt->close();

        if ($ok) {
            $this->bookModel->incrementAvailability((int)$res['book_id']);
        }
        return $ok;
    }
}
