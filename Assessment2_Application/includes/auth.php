<?php

//Authentication 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}

function isLibrarian(): bool
{
    return isLoggedIn() && $_SESSION['role'] === 'librarian';
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function requireLibrarian(): void
{
    requireLogin();
    if (!isLibrarian()) {
        header('Location: ../dashboard.php');
        exit;
    }
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
