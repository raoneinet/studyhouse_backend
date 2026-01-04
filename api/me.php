<?php
include "headers.php";
session_start();
include "db_conn.php"; 

if (!isset($_SESSION["user"])) {
    http_response_code(401);
    echo json_encode(["authenticated" => false]);
    exit;
}

echo json_encode([
    "authenticated" => true,
    "user" => $_SESSION["user"]
]);