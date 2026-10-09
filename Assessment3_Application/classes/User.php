<?php
/**
 * User - registration, authentication and role management.
 */
class User
{
    public const ROLES = ['customer', 'staff', 'manager'];

    private mysqli $db;

    public function __construct()
    {
        $this->db = Database::get();
    }

    /** Create a customer account. Returns true, or an error message. */
    public function register(string $fullName, string $email, string $password, string $phone): bool|string
    {
        if ($this->findByEmail($email)) {
            return 'An account with that email already exists.';
        }
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $phone = $phone === '' ? null : $phone;
        $stmt = $this->db->prepare(
            "INSERT INTO users (full_name, email, password_hash, phone, role)
             VALUES (?, ?, ?, ?, 'customer')"
        );
        $stmt->bind_param('ssss', $fullName, $email, $hash, $phone);
        $stmt->execute();
        return true;
    }

    /** Returns the user row when the email/password pair is correct, otherwise null. */
    public function authenticate(string $email, string $password): ?array
    {
        $user = $this->findByEmail($email);
        if ($user && password_verify($password, $user['password_hash'])) {
            return $user;
        }
        return null;
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public function all(): array
    {
        return $this->db->query(
            'SELECT user_id, full_name, email, role, created_at FROM users ORDER BY role, full_name'
        )->fetch_all(MYSQLI_ASSOC);
    }

    /** Manager changes a user's role (e.g. promotes a new box-office staff member). */
    public function setRole(int $userId, string $role): bool
    {
        if (!in_array($role, self::ROLES, true)) {
            return false;
        }
        $stmt = $this->db->prepare('UPDATE users SET role = ? WHERE user_id = ?');
        $stmt->bind_param('si', $role, $userId);
        $stmt->execute();
        return $stmt->affected_rows > 0;
    }
}
