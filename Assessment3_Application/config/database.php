<?php
// Database connection settings. Change these to match your MySQL server.
// WAMP/XAMPP default: host localhost, user root, empty password.
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'harbourlight_tickets');

// Business rules kept in one place so they are easy to explain and change.
define('HOLD_MINUTES', 10);        // how long selected seats are held
define('MAX_SEATS_PER_ORDER', 8);  // limit per customer per checkout
define('CANCEL_CUTOFF_HOURS', 24); // customers cannot cancel inside this window

// PHP and MySQL must agree on "now" for hold expiry and show times.
// Database::get() copies this zone's current UTC offset into the MySQL session.
define('APP_TIMEZONE', 'Australia/Melbourne');
date_default_timezone_set(APP_TIMEZONE);
