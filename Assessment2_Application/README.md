# Riverbend Community Library — Online Book Reservation System

MCS503 Web and Database Systems — Assessment 2 (Individual)

## What this is
A small full-stack PHP/MySQL prototype: members browse a book catalogue and
reserve available copies; librarians manage the catalogue and process
reservations (Pending -> Approved -> Collected -> Returned).

## Folder structure
```
config/               Database connection settings (config/database.php)
includes/              OOP model classes + shared header/footer/auth
  Database.php          mysqli singleton connection wrapper
  User.php               registration / login
  Book.php                catalogue search + librarian CRUD
  Reservation.php          reservation lifecycle
  auth.php, header.php, footer.php
admin/                  Librarian-only pages
  books.php, reservations.php
assets/css/style.css   Stylesheet
sql/
  schema.sql             CREATE DATABASE + tables (run this first)
  seed_data.sql           sample categories/books/users/reservations
  full_database_export.sql  complete mysqldump (schema + data) - alternative
                             to running schema.sql + seed_data.sql separately
index.php, login.php, register.php, logout.php,
dashboard.php, books.php, reserve.php, my_reservations.php
```

## How to run it (WAMP / XAMPP)
1. Copy this folder into your web server's document root
   (e.g. `C:\wamp64\www\library-system`).
2. Start Apache and MySQL in WAMP.
3. In phpMyAdmin (or the mysql CLI), import `sql/schema.sql` then
   `sql/seed_data.sql` — or import `sql/full_database_export.sql` alone,
   which contains both.
4. Check `config/database.php` matches your MySQL credentials
   (WAMP's default is usually host `localhost`, user `root`, empty password).
5. Visit `http://localhost/library-system/` in your browser.

## Demo accounts (password for both: Password123)
- Librarian: sarah.librarian@library.edu
- Member:    alex.student@library.edu

## Adding new Librarian Account (Not User Acc)
### Option 1 (Update existing role)
1. Access The database via phpMyAdmin 
2.  USE library_system;
    UPDATE users SET role = 'librarian' WHERE email = 'their.email@example.com';
### Option 2 (Insert directly)
1. Get the password hash with the cmd below. 
2. php -r "echo password_hash('YourPassword', PASSWORD_DEFAULT);"
3. Insert via SQL 
    INSERT INTO users (full_name, email, password_hash, phone, role)
    VALUES ('Jane Librarian', 'jane@library.edu', 'PASTE_HASH_HERE', '0400000000', 'librarian');

## Notes
- All queries use mysqli prepared statements.
- Passwords are hashed with PHP's password_hash() (bcrypt).
- Reservation creation is wrapped in a database transaction so a book's
  available_copies count can never go negative or be over-reserved.


