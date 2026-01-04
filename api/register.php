<?php
include "headers.php";
session_start();
include "db_conn.php";

//$data = json_decode(file_get_contents("php://input"), true);

$firstname = $_POST["firstname"] ?? "";
$lastname = $_POST["lastname"] ?? "";
$username = $_POST["username"] ?? "";
$date_of_birth = $_POST["date_of_birth"] ?? "";
$email = $_POST["email"] ?? "";
$password = $_POST["password"] ?? "";

if (!$firstname || !$email || !$password) {
    echo json_encode(["status" => "error", "message" => "Dados incompletos"]);
    exit();
}

$avatar = null;

if (isset($_FILES["avatar"]) && $_FILES["avatar"]["error"] === 0) {
    $file = $_FILES["avatar"];

    $allowedTypes = ["image/jpeg", "image/png", "image/webp"];
    if (!in_array($file["type"], $allowedTypes)) {
        echo json_encode(["status" => "error", "message" => "Formato de imagem inválido"]);
        exit();
    }

    if ($file["size"] > 2 * 1024 * 1024) {
        echo json_encode(["status" => "error", "message" => "Imagem muito grande"]);
        exit();
    }

    if (!getimagesize($file["tmp_name"])) {
        echo json_encode(["status" => "error", "message" => "Arquivo inválido"]);
        exit();
    }

    $ext = pathinfo($file["name"], PATHINFO_EXTENSION);
    $filename = uniqid("avatar_", true) . "." . $ext;

    $uploadDir = __DIR__ . "/uploads/avatars/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    move_uploaded_file($file["tmp_name"], $uploadDir . $filename);

    $avatar = "/uploads/avatars/" . $filename;
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
    (firstname, lastname, avatar, username, date_of_birth, email, password) 
    VALUES (?, ?, ?, ?, ?, ?, ?)"
);
$stmt->execute([$firstname, $lastname, $avatar, $username, $date_of_birth, $email, $hash]);

echo json_encode(["status" => "success", "message" => "Usuário registrado com sucesso"]);