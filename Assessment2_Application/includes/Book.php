<?php
require_once __DIR__ . '/Database.php';

/*
 
 Book
 Handles catalogue browsing/search and librarian CRUD on the books table.
 
*/
class Book
{
    private mysqli $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    //Search function 
    public function search(string $keyword = '', int $categoryId = 0): array
    {
        $sql = "SELECT b.*, c.category_name
                FROM books b
                JOIN categories c ON b.category_id = c.category_id
                WHERE 1=1";
        $params = [];
        $types = '';

        if ($keyword !== '') {
            $sql .= " AND (b.title LIKE ? OR b.author LIKE ?)";
            $like = "%$keyword%";
            $params[] = $like;
            $params[] = $like;
            $types .= 'ss';
        }
        if ($categoryId > 0) {
            $sql .= " AND b.category_id = ?";
            $params[] = $categoryId;
            $types .= 'i';
        }
        $sql .= " ORDER BY b.title ASC";

        $stmt = $this->db->prepare($sql);
        if ($params) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    public function findById(int $bookId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT b.*, c.category_name FROM books b
             JOIN categories c ON b.category_id = c.category_id
             WHERE b.book_id = ?"
        );
        $stmt->bind_param('i', $bookId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    //Creates new book
    public function create(string $title, string $author, string $isbn, int $categoryId, int $copies): bool
    {
        $isbn = ($isbn==='') ? null : $isbn; //Store blank isbn as Null so multi book can omit it
        try{
            $stmt = $this->db->prepare(
                "INSERT INTO books (title, author, isbn, category_id, total_copies, available_copies)
                VALUES (?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param('sssiii', $title, $author, $isbn, $categoryId, $copies, $copies);
            $ok = $stmt->execute();
            $stmt->close();
            return $ok;


        } catch (mysqli_sql_exception $e){
            return false; 
        }

    }

    //Update info
    public function update(int $bookId, string $title, string $author, string $isbn, int $categoryId, int $totalCopies): bool
    {
        $isbn = ($isbn === '') ? null : $isbn;
        try {
            // available_copies is assigned BEFORE total_copies so the expression sees the old total
            
            $stmt = $this->db->prepare(
                "UPDATE books
                SET title = ?, author = ?, isbn = ?, category_id = ?,
                    available_copies = available_copies + (? - total_copies),
                    total_copies = ?
                WHERE book_id = ?"
            );
            $stmt->bind_param('sssiiii', $title, $author, $isbn, $categoryId, $totalCopies, $totalCopies, $bookId);
            $ok = $stmt->execute();
            $stmt->close();
            return $ok;
        } catch (mysqli_sql_exception $e) {
            return false; // check constraint- cannot drop total below the copies currently on loan/reserved
        }
    }

    //Delete book
    public function delete(int $bookId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM books WHERE book_id = ?");
        $stmt->bind_param('i', $bookId);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function getAllCategories(): array
    {
        $result = $this->db->query("SELECT * FROM categories ORDER BY category_name ASC");
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    //Availability Management 

    public function decrementAvailability(int $bookId): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE books SET available_copies = available_copies - 1
             WHERE book_id = ? AND available_copies > 0"
        );
        $stmt->bind_param('i', $bookId);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
        return $affected > 0;
    }

    public function incrementAvailability(int $bookId): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE books SET available_copies = LEAST(available_copies + 1, total_copies)
             WHERE book_id = ?"
        );
        $stmt->bind_param('i', $bookId);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }
}
