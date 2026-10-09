# Harbourlight Theatre Box Office

MCS503 Web and Database Systems, Assessment 3 (Team project)

A PHP/MySQL prototype for reserved-seat ticketing at a community theatre. Customers
choose exact seats on a live seat map. The seats are **held for 10 minutes** while the
customer checks out, and the system guarantees that **two people can never buy the same
seat**. Box-office staff check tickets in at the door. Managers schedule performances,
set prices per section, cancel performances (which refunds every ticket automatically)
and see sales reports.

## Folder structure
```
config/database.php        DB credentials + business rules (hold minutes, seat limit, cancel cut-off, timezone)
classes/                   Model layer (OOP, one class per domain concept)
  Database.php               mysqli singleton connection
  User.php                   registration, login, roles
  Show.php                   productions (CRUD)
  Performance.php            dated performances, prices, live seat map, cancel-with-refund
  Booking.php                seat holds, checkout, orders, customer cancellation
  Ticket.php                 door check-in
  Report.php                 sales figures (SQL aggregates)
includes/                  bootstrap.php (session, helpers, auth, CSRF), header.php, footer.php
staff/checkin.php          Door check-in (staff + manager)
manager/                   shows.php, performances.php, reports.php, users.php (manager only)
assets/css/style.css       Stylesheet
sql/
  schema.sql               CREATE DATABASE + 9 tables (run first)
  seed_data.sql            Sample data, with dates relative to the day you import it
  full_database_export.sql mysqldump of schema + data (alternative to the two files above)
index.php, register.php, login.php, logout.php, dashboard.php,
performances.php, performance.php (seat map), hold.php, checkout.php,
my_bookings.php, booking.php (tickets)
```

## How to run it (WAMP / XAMPP)
1. Copy this folder into the web root, e.g. `C:\wamp64\www\harbourlight-tickets`.
2. Start Apache and MySQL.
3. In phpMyAdmin, import `sql/schema.sql` and then `sql/seed_data.sql`.
   **Use these two files for the demo.** The seed data creates performances relative to
   *today*: one performance tonight (for the door check-in demo), several upcoming and
   one last week. `full_database_export.sql` holds a fixed snapshot, so its dates are
   frozen at the day it was exported.
4. Check `config/database.php` matches your MySQL login (WAMP default: `root`, no password).
5. Open `http://localhost/harbourlight-tickets/`.

Requires PHP 8.1+ with the mysqli extension, and MySQL 8 or MariaDB 10.4+.

## Demo accounts (password for all: `Password123`)
| Role     | Email                              |
|----------|------------------------------------|
| Manager  | maya.manager@harbourlight.test     |
| Staff    | sam.staff@harbourlight.test        |
| Customer | chris.customer@harbourlight.test   |
| Customer | jordan.lee@harbourlight.test       |

Ticket codes for tonight's performance, for testing check-in: `T7A2K9Q1MX`, `T4B8N2W6PL`.

## How double-selling is prevented
1. `holdSeats()` and `checkout()` run inside transactions and lock the performance row
   (`SELECT ... FOR UPDATE`), so concurrent requests for one performance run one at a time.
2. `seat_holds` has `UNIQUE(performance_id, seat_id)`: a seat can be held by only one customer.
3. `tickets` has `UNIQUE(performance_id, seat_id, is_active)`. A live ticket has
   `is_active = 1` and a refunded ticket has `NULL`. A unique index ignores NULLs, so only
   one live ticket can exist per seat, and refunded tickets stay in the history.
4. Expired holds are ignored by every query (`expires_at > NOW()`), so seats free
   themselves without a scheduled job.

## Security measures
Prepared statements for every query, `password_hash()` (bcrypt), session ID
regeneration on login, HttpOnly/SameSite cookies, CSRF tokens on every POST,
`htmlspecialchars()` on every output, role checks on every staff/manager page,
ownership checks on bookings (no IDOR), and generic error messages.

Payment is simulated. No card data is collected.
