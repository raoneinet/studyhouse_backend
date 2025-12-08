<?php
include "headers.php";

$server = "localhost";
$dbname = "studyhouse";
$username = "root";
$password = "";
$charset = "utf8mb4";

$dsn_connection = "mysql:host=$server;dbname=$dbname;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $conn = new PDO($dsn_connection, $username, $password, $options);
    echo "connected successfully.";

} catch (PDOException $error) {
    echo "Connection error: " . $error->getMessage();
    exit();
}