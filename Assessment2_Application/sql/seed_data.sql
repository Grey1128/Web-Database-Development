
-- Library Book Reservation System - Sample Data

USE library;

-- Default password for all sample accounts: "Password123"
-- Librarian login: sarah.librarian@library.edu / Password123
-- Member login:    alex.student@library.edu   / Password123
INSERT INTO users (full_name, email, password_hash, phone, role, join_date) VALUES
('Sarah Thompson', 'sarah.librarian@library.edu', '$2y$10$m6HbjZD1uuXTzKuYVg26geLKeJIz7ilEqrNjf2h55NgCE51kp4Ac2', '0400111222', 'librarian', '2024-01-10'),
('Alex Nguyen',     'alex.student@library.edu',    '$2y$10$m6HbjZD1uuXTzKuYVg26geLKeJIz7ilEqrNjf2h55NgCE51kp4Ac2', '0400333444', 'member',    '2025-02-14'),
('Priya Sharma',    'priya.sharma@library.edu',    '$2y$10$m6HbjZD1uuXTzKuYVg26geLKeJIz7ilEqrNjf2h55NgCE51kp4Ac2', '0400555666', 'member',    '2025-03-01'),
('Jordan Lee',      'jordan.lee@library.edu',      '$2y$10$m6HbjZD1uuXTzKuYVg26geLKeJIz7ilEqrNjf2h55NgCE51kp4Ac2', '0400777888', 'member',    '2025-05-20');

INSERT INTO categories (category_name) VALUES
('Computer Science'), ('Fiction'), ('Mathematics'), ('History'), ('Science');

INSERT INTO books (title, author, isbn, category_id, total_copies, available_copies) VALUES
('Clean Code', 'Robert C. Martin', '9780132350884', 1, 3, 3),
('Introduction to Algorithms', 'Cormen, Leiserson, Rivest, Stein', '9780262033848', 1, 2, 2),
('Database System Concepts', 'Silberschatz, Korth, Sudarshan', '9780078022159', 1, 2, 1),
('1984', 'George Orwell', '9780451524935', 2, 4, 4),
('To Kill a Mockingbird', 'Harper Lee', '9780061120084', 2, 3, 3),
('A Brief History of Time', 'Stephen Hawking', '9780553380163', 5, 2, 2),
('Sapiens', 'Yuval Noah Harari', '9780062316097', 4, 3, 2),
('Calculus: Early Transcendentals', 'James Stewart', '9781285741550', 3, 2, 2),
('The Pragmatic Programmer', 'David Thomas, Andrew Hunt', '9780135957059', 1, 2, 2),
('Linear Algebra Done Right', 'Sheldon Axler', '9783319110790', 3, 2, 2);

INSERT INTO reservations (user_id, book_id, reservation_date, due_date, return_date, status) VALUES
(2, 3, '2026-09-10 09:15:00', '2026-09-24', NULL, 'Collected'),
(3, 7, '2026-09-12 14:02:00', '2026-09-26', NULL, 'Approved'),
(4, 1, '2026-09-20 11:30:00', NULL, NULL, 'Pending'),
(2, 5, '2026-08-01 10:00:00', '2026-08-15', '2026-08-14', 'Returned');
