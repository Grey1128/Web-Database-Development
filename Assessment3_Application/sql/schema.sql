-- =====================================================================
-- Harbourlight Theatre Box Office - database schema
-- MCS503 Web and Database Systems - Assessment 3 (Team)
-- Run this first, then seed_data.sql.
-- Target: MySQL 8 / MariaDB 10.4+ (InnoDB for transactions + foreign keys)
-- =====================================================================

DROP DATABASE IF EXISTS harbourlight_tickets;
CREATE DATABASE harbourlight_tickets CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE harbourlight_tickets;

-- People who use the system. One table, three roles.
CREATE TABLE users (
    user_id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name      VARCHAR(100) NOT NULL,
    email          VARCHAR(150) NOT NULL UNIQUE,
    password_hash  VARCHAR(255) NOT NULL,
    phone          VARCHAR(20)  NULL,
    role           ENUM('customer','staff','manager') NOT NULL DEFAULT 'customer',
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- A physical theatre.
CREATE TABLE venues (
    venue_id    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    venue_name  VARCHAR(100) NOT NULL,
    address     VARCHAR(200) NOT NULL
) ENGINE=InnoDB;

-- A priced area inside a venue (e.g. Stalls, Circle).
CREATE TABLE sections (
    section_id    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    venue_id      INT UNSIGNED NOT NULL,
    section_name  VARCHAR(50) NOT NULL,
    UNIQUE KEY uq_section_name (venue_id, section_name),
    CONSTRAINT fk_section_venue FOREIGN KEY (venue_id)
        REFERENCES venues(venue_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Every physical seat. A seat is identified by section + row + number.
CREATE TABLE seats (
    seat_id      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    section_id   INT UNSIGNED NOT NULL,
    row_label    CHAR(2) NOT NULL,
    seat_number  TINYINT UNSIGNED NOT NULL,
    UNIQUE KEY uq_seat_position (section_id, row_label, seat_number),
    CONSTRAINT fk_seat_section FOREIGN KEY (section_id)
        REFERENCES sections(section_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- A production (the thing being staged), independent of dates.
CREATE TABLE shows (
    show_id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title          VARCHAR(150) NOT NULL,
    genre          VARCHAR(50)  NOT NULL,
    description    TEXT NOT NULL,
    duration_mins  SMALLINT UNSIGNED NOT NULL,
    age_rating     VARCHAR(10) NOT NULL DEFAULT 'G'
) ENGINE=InnoDB;

-- One dated occurrence of a show at a venue.
CREATE TABLE performances (
    performance_id  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    show_id         INT UNSIGNED NOT NULL,
    venue_id        INT UNSIGNED NOT NULL,
    starts_at       DATETIME NOT NULL,
    status          ENUM('Scheduled','Cancelled') NOT NULL DEFAULT 'Scheduled',
    KEY idx_perf_start (starts_at),
    CONSTRAINT fk_perf_show FOREIGN KEY (show_id)
        REFERENCES shows(show_id) ON DELETE RESTRICT,
    CONSTRAINT fk_perf_venue FOREIGN KEY (venue_id)
        REFERENCES venues(venue_id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Price of a section for one performance (prices can differ per night).
CREATE TABLE price_tiers (
    tier_id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    performance_id  INT UNSIGNED NOT NULL,
    section_id      INT UNSIGNED NOT NULL,
    price           DECIMAL(8,2) NOT NULL,
    UNIQUE KEY uq_tier (performance_id, section_id),
    CONSTRAINT chk_price_positive CHECK (price >= 0),
    CONSTRAINT fk_tier_perf FOREIGN KEY (performance_id)
        REFERENCES performances(performance_id) ON DELETE CASCADE,
    CONSTRAINT fk_tier_section FOREIGN KEY (section_id)
        REFERENCES sections(section_id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Temporary reservation of a seat while a customer checks out.
-- The UNIQUE key means only ONE customer can hold a seat for a performance.
CREATE TABLE seat_holds (
    hold_id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    performance_id  INT UNSIGNED NOT NULL,
    seat_id         INT UNSIGNED NOT NULL,
    user_id         INT UNSIGNED NOT NULL,
    expires_at      DATETIME NOT NULL,
    UNIQUE KEY uq_hold_seat (performance_id, seat_id),
    KEY idx_hold_expiry (expires_at),
    CONSTRAINT fk_hold_perf FOREIGN KEY (performance_id)
        REFERENCES performances(performance_id) ON DELETE CASCADE,
    CONSTRAINT fk_hold_seat FOREIGN KEY (seat_id)
        REFERENCES seats(seat_id) ON DELETE CASCADE,
    CONSTRAINT fk_hold_user FOREIGN KEY (user_id)
        REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- A completed (simulated) purchase. The total is NOT stored: it is
-- derived from SUM(tickets.price_paid) to keep the table in 3NF.
CREATE TABLE orders (
    order_id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_ref  CHAR(9) NOT NULL UNIQUE,
    user_id      INT UNSIGNED NOT NULL,
    status       ENUM('Paid','Refunded') NOT NULL DEFAULT 'Paid',
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_order_user FOREIGN KEY (user_id)
        REFERENCES users(user_id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- One admission for one seat at one performance.
-- `is_active` is 1 for a live ticket and NULL once refunded. A UNIQUE key
-- ignores NULLs, so (performance, seat, 1) can exist only ONCE - the
-- database itself makes double-selling a seat impossible, while refunded
-- tickets stay in history without blocking the seat from being resold.
CREATE TABLE tickets (
    ticket_id       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id        INT UNSIGNED NOT NULL,
    performance_id  INT UNSIGNED NOT NULL,
    seat_id         INT UNSIGNED NOT NULL,
    price_paid      DECIMAL(8,2) NOT NULL,
    ticket_code     CHAR(10) NOT NULL UNIQUE,
    is_active       TINYINT(1) NULL DEFAULT 1,
    checked_in_at   DATETIME NULL,
    UNIQUE KEY uq_one_live_ticket (performance_id, seat_id, is_active),
    CONSTRAINT fk_ticket_order FOREIGN KEY (order_id)
        REFERENCES orders(order_id) ON DELETE CASCADE,
    CONSTRAINT fk_ticket_perf FOREIGN KEY (performance_id)
        REFERENCES performances(performance_id) ON DELETE RESTRICT,
    CONSTRAINT fk_ticket_seat FOREIGN KEY (seat_id)
        REFERENCES seats(seat_id) ON DELETE RESTRICT
) ENGINE=InnoDB;
