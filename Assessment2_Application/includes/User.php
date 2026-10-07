<?php
require_once __DIR__ . '/Database.php';

/*
 User
 Handles registration, authentication and lookups for the users table.
 */
class User
{
    private mysqli $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /*Register a new member. Returns true on success, or an error string. */
    public function register(string $fullName, string $email, string $password, string $phone)
    {
        if ($this->findByEmail($email)) {
            return 'An account with that email already exists.';
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare(
            "INSERT INTO users (full_name, email, password_hash, phone, role) VALUES (?, ?, ?, ?, 'member')"
        );
        $stmt->bind_param('ssss', $fullName, $email, $hash, $phone);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok ? true : 'Registration failed. Please try again.';
    }

    /* Login */
    public function login(string $email, string $password): ?array
    {
        $user = $this->findByEmail($email);
        if ($user && password_verify($password, $user['password_hash'])) {
            return $user;
        } 
        return null;
    }

    /* Search email*/
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result ?: null;
    }

    public function findById(int $userId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE user_id = ? LIMIT 1');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result ?: null;
    }
}
