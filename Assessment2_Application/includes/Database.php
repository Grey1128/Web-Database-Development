<?php
require_once __DIR__ . '/../config/database.php';

/*
    Database
    Thin OOP wrapper around mysqli using the singleton pattern so every page shares one connection.

*/
class Database
{
    private static ?mysqli $connection = null;

    public static function getConnection(): mysqli
    {
        if (self::$connection === null) {
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            if ($conn->connect_error) {
                die('Database connection failed: ' . $conn->connect_error);
            }
            $conn->set_charset('utf8mb4');
            self::$connection = $conn;
        }
        return self::$connection;
    }
}
