<?php
include "headers.php";
session_start();
include "db_conn.php"; 

header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);

$email = $data["email"] ?? "";
$pass = $data["password"] ?? "";


$stmt = $conn->prepare("SELECT * FROM user WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($pass, $user["password"])) {
    echo json_encode([
        "status" => "error",
        "message" => "Email ou senha inválidos"
    ]);
    exit();
}

$_SESSION["user"] = [
    "id" => $user["id"],
    "firstname" => $user["firstname"],
    "lastname" => $user["lastname"],
    "avatar" => $user["avatar"] ?? "/uploads/default-avatar.png",
    "username" => $user["username"],
    "date_of_birth" => $user["date_of_birth"],
    "email" => $user["email"]
];

$token = bin2hex(random_bytes(32));
$_SESSION["token"] = $token;

echo json_encode([
    "status" => "success",
    "message" => "Login realizado",
    "user" => $_SESSION["user"],
    "token" => $token
]);
