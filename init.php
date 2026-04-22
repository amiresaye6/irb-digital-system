<?php
require_once __DIR__ . '/includes/env.php';
require_once __DIR__ . '/classes/Database.php';
require_once __DIR__ . '/classes/Auth.php';
if(session_status() === PHP_SESSION_NONE) {
    session_start();
}
// $database = new Database();
?>