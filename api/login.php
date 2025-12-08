<?php
include "headers.php";
include "db_conn.php";

$data = json_decode(file_get_contents("php://input"), true);

$email = $data["email"] ?? "";
$pass = $data["password"] ?? "";

$stmt = $conn->prepare("SELECT * FROM user WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($pass, $user["password"])) {
    echo json_encode(["status" => "error", "message" => "Email ou senha inválidos"]);
    exit();
}

$token = bin2hex(random_bytes(32));

echo json_encode([
    "status" => "success",
    "message" => "Login realizado",
    "user" => [
        "id" => $user["id"],
        "firstname" => $user["firstname"],
        "lastname" => $user["lastname"],
        "username" => $user["username"],
        "date_of_birth" => $data["date_of_birth"],
        "email" => $user["email"]
    ],
    "token" => $token
]);