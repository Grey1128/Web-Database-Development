<?php
require_once __DIR__ . '/../config/database.php';

/**
 * Database
 * Provides one shared mysqli connection per request (singleton pattern).
 * mysqli is switched to exception mode so failed queries can be caught and
 * rolled back inside transactions instead of failing silently.
 */
class Database
{
    private static ?mysqli $conn = null;

    public static function get(): mysqli
    {
        if (self::$conn === null) {
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            try {
                $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            } catch (mysqli_sql_exception $e) {
                // Do not leak credentials or stack traces to the browser.
                error_log('DB connection failed: ' . $e->getMessage());
                http_response_code(500);
                exit('The box office is temporarily unavailable. Please try again later.');
            }
            $conn->set_charset('utf8mb4');
            // Make NOW()/CURDATE() in SQL match PHP's clock (see APP_TIMEZONE).
            $offset = (new DateTime())->format('P');
            $conn->query("SET time_zone = '$offset'");
            self::$conn = $conn;
        }
        return self::$conn;
    }
}
