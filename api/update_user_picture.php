<?php
include "headers.php";
session_start();
include "db_conn.php";

if (!isset($_SESSION["user"])) {
    http_response_code(401);
    echo json_encode(["erro" => "Não autorizado"]);
    exit;
}

if (!isset($_FILES["avatar"]) || $_FILES["avatar"]["error"] !== 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Nenhuma imagem enviada"
    ]);
    exit();
}

$file = $_FILES["avatar"];

$allowedTypes = ["image/jpeg", "image/png", "image/webp"];
if (!in_array($file["type"], $allowedTypes)) {
    echo json_encode(["status" => "error", "message" => "Formato inválido"]);
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

$uploadDir = $_SERVER["DOCUMENT_ROOT"] . "/studyhouse_backend/uploads/avatars/";

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

if (!move_uploaded_file($file["tmp_name"], $uploadDir . $filename)) {
    echo json_encode(["status" => "error", "message" => "Falha ao salvar imagem"]);
    exit();
}

$avatar = "/uploads/avatars/" . $filename;

try {
    $userId = $_SESSION["user"]["id"];

    $oldAvatar = $_SESSION["user"]["avatar"] ?? null;
    if ($oldAvatar && file_exists($_SERVER["DOCUMENT_ROOT"] . "/studyhouse_backend" . $oldAvatar)) {
        unlink($_SERVER["DOCUMENT_ROOT"] . "/studyhouse_backend" . $oldAvatar);
    }

    $stmt = $conn->prepare(
        "UPDATE user SET avatar = ? WHERE id = ?"
    );

    $stmt->execute([$avatar, $userId]);

    $_SESSION["user"]["avatar"] = $avatar;

    echo json_encode([
        "status" => "success",
        "message" => "Avatar atualizado com sucesso",
        "avatar" => $avatar
    ]);

} catch (PDOException $error) {
    http_response_code(500);
    echo json_encode([
        "erro" => "Falha ao atualizar avatar de usuário",
        "detalhes" => $error->getMessage()
    ]);
}