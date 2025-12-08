<?php
include "headers.php";
include "db_conn.php";

$data = json_decode(file_get_contents("php://input"), true);

$firstname = $data["firstname"] ?? "";
$lastname = $data["lastname"] ?? "";
$username = $data["username"] ?? "";
$date_of_birth = $data["date_of_birth"] ?? "";
$email = $data["email"] ?? "";
$password = $data["password"] ?? "";

if (!$firstname || !$email || !$password) {
    echo json_encode(["status" => "error", "message" => "Dados incompletos"]);
    exit();
}

$stmt = $conn->prepare("SELECT * FROM user WHERE email = ?");
$stmt->execute([$email]);

if ($stmt->rowCount() > 0) {
    echo json_encode(["status" => "error", "message" => "Email já cadastrado"]);
    exit();
}

$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare(
    "INSERT INTO user 
    (firstname, lastname, username, date_of_birth, email, password) 
    VALUES (?, ?, ?, ?, ?, ?)"
);
$stmt->execute([$firstname, $lastname, $username, $date_of_birth, $email, $hash]);

echo json_encode(["status" => "success", "message" => "Usuário registrado com sucesso"]);