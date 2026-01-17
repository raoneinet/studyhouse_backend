<?php
include "headers.php";
session_start();
include "db_conn.php";

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($_SESSION["user"]["id"])) {
    http_response_code(401);
    echo json_encode(["error" => "Usuário não autenticado"]);
    exit;
}

$user_id = $_SESSION["user"]["id"];

if (empty($data["actualPassword"]) || empty($data["newPassword"])) {
    http_response_code(400);
    echo json_encode(["error" => "Senha atual e nova senha são obrigatórias"]);
    exit;
}

$actual_password = $data["actualPassword"];
$new_password = $data["newPassword"];

try {
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = :id");
    $stmt->bindParam(":id", $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(404);
        echo json_encode(["error" => "Usuário não encontrado"]);
        exit;
    }

    if (!password_verify($actual_password, $user["password"])) {
        http_response_code(401);
        echo json_encode(["error" => "Senha atual incorreta"]);
        exit;
    }

    $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);

    $update = $conn->prepare("
        UPDATE users
        SET password = :password 
        WHERE id = :id
    ");
    $update->bindParam(":password", $new_password_hash, PDO::PARAM_STR);
    $update->bindParam(":id", $user_id, PDO::PARAM_INT);
    $update->execute();

    echo json_encode(["success" => "Senha alterada com sucesso"]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "error" => "Erro ao atualizar a senha",
        "details" => $e->getMessage()
    ]);
}
