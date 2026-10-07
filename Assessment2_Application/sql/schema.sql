-- ============================================================
-- Library Book Reservation System - Database Schema
-- MCS503 Web and Database Systems - Assessment 2
-- Engine: MySQL / MariaDB, InnoDB, 3NF normalised design
-- ============================================================

DROP DATABASE IF EXISTS library_system;
CREATE DATABASE library_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE library_system;


CREATE TABLE users (
    user_id       INT AUTO_INCREMENT PRIMARY KEY,
    full_name     VARCHAR(100)  NOT NULL,
    email         VARCHAR(150)  NOT NULL UNIQUE,
    password_hash VARCHAR(255)  NOT NULL,
    phone         VARCHAR(20)   DEFAULT NULL,
    role          ENUM('member','librarian') NOT NULL DEFAULT 'member',
    join_date     DATE NOT NULL DEFAULT (CURRENT_DATE),
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;


CREATE TABLE categories (
    category_id   INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(80) NOT NULL UNIQUE
) ENGINE=InnoDB;


CREATE TABLE books (
    book_id          INT AUTO_INCREMENT PRIMARY KEY,
    title            VARCHAR(200) NOT NULL,
    author           VARCHAR(150) NOT NULL,
    isbn             VARCHAR(20)  DEFAULT NULL UNIQUE,
    category_id      INT NOT NULL,
    total_copies     INT NOT NULL DEFAULT 1,
    available_copies INT NOT NULL DEFAULT 1,
    added_on         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_books_category
        FOREIGN KEY (category_id) REFERENCES categories(category_id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT chk_copies CHECK (available_copies >= 0 AND available_copies <= total_copies)
) ENGINE=InnoDB;


CREATE TABLE reservations (
    reservation_id   INT AUTO_INCREMENT PRIMARY KEY,
    user_id          INT NOT NULL,
    book_id          INT NOT NULL,
    reservation_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    due_date         DATE     DEFAULT NULL,
    return_date      DATE     DEFAULT NULL,
    status           ENUM('Pending','Approved','Collected','Returned','Cancelled')
                     NOT NULL DEFAULT 'Pending',
    CONSTRAINT fk_res_user FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_res_book FOREIGN KEY (book_id) REFERENCES books(book_id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE INDEX idx_books_category ON books(category_id);
CREATE INDEX idx_res_user ON reservations(user_id);
CREATE INDEX idx_res_book ON reservations(book_id);
CREATE INDEX idx_res_status ON reservations(status);
