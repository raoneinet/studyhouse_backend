<?php
include "headers.php";
session_start();
include "db_conn.php";

$firstname = $_POST["firstname"] ?? "";
$lastname = $_POST["lastname"] ?? "";
$username = $_POST["username"] ?? "";
$date_of_birth = $_POST["date_of_birth"] ?? "";
$profession = $_POST["profession"] ?? "";
$country = $_POST["country"] ?? "";
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

    //MUST BE PUT BACK TO ROOT WHEN GOES TO PROD
    $uploadDir = $_SERVER["DOCUMENT_ROOT"] . "/studyhouse_backend/uploads/avatars/";

    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            echo json_encode([
                "status" => "error",
                "message" => "Não foi possível criar diretório de upload"
            ]);
            exit();
        }
    }

    if (!move_uploaded_file($file["tmp_name"], $uploadDir . $filename)) {
        echo json_encode([
            "status" => "error",
            "message" => "Falha ao salvar a imagem"
        ]);
        exit();
    }

    $avatar = "/uploads/avatars/" . $filename;
}

$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);

if ($stmt->rowCount() > 0) {
    echo json_encode(["status" => "error", "message" => "Email já cadastrado"]);
    exit();
}

$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare(
    "INSERT INTO users 
    (firstname, lastname, avatar, username, date_of_birth, profession, country, email, password) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
);
$stmt->execute([$firstname, $lastname, $avatar, $username, $date_of_birth, $profession, $country, $email, $hash]);

echo json_encode(["status" => "success", "message" => "Usuário registrado com sucesso"]);