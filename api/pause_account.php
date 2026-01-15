<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include "headers.php";
session_start();
include "db_conn.php"; // conexão PDO em $conn

header("Content-Type: application/json");

// Verifica autenticação
if (!isset($_SESSION["user"]["id"])) {
    http_response_code(401);
    echo json_encode(["error" => "Usuário não autenticado"]);
    exit;
}

$user_id = $_SESSION["user"]["id"];

try {
    // Busca usuário
    $stmt = $conn->prepare("SELECT id, is_active FROM user WHERE id = :id");
    $stmt->bindParam(":id", $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(404);
        echo json_encode(["error" => "Usuário não encontrado"]);
        exit;
    }

    // Verifica se já está suspenso
    if ($user["is_active"] == 0) {
        echo json_encode(["message" => "Conta já está suspensa"]);
        exit;
    }

    // Suspende a conta (seta is_active = 0)
    $update = $conn->prepare("UPDATE user SET is_active = 0 WHERE id = :id");
    $update->bindParam(":id", $user_id, PDO::PARAM_INT);
    $update->execute();

    // Finaliza sessão do usuário
    session_destroy();

    echo json_encode(["success" => "Conta suspensa com sucesso"]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "error" => "Erro ao suspender a conta",
        "details" => $e->getMessage()
    ]);
}
