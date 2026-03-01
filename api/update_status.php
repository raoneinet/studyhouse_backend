<?php
include "headers.php";
session_start();
include "db_conn.php";

if (!isset($_SESSION["user"])) {
    http_response_code(401);
    echo json_encode(["erro" => "Não autorizado"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$userId = $_SESSION["user"]["id"];
$subjectId = $data["id"] ?? null;

if (!$subjectId) {
    http_response_code(400);
    echo json_encode(["erro" => "ID do subject é obrigatório"]);
    exit;
}

$validStatus = ['notstarted', 'ongoing', 'onhold', 'done'];

if (!isset($data['status']) || !in_array($data['status'], $validStatus)) {
    http_response_code(400);
    echo json_encode(["erro" => "Status inválido"]);
    exit;
}

$status = $data['status'];

try {

    $stmt = $conn->prepare("
        UPDATE subjects
        SET status = :status
        WHERE id = :id AND user_id = :user_id
    ");

    $stmt->execute([
        ':status' => $status,
        ':id' => $subjectId,
        ':user_id' => $userId
    ]);

    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode([
            "erro" => "Assunto não encontrado ou não pertence ao usuário"
        ]);
        exit;
    }

    echo json_encode([
        "sucesso" => true,
        "id" => $subjectId,
        "status" => $status
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "erro" => "Falha ao atualizar status",
        "detalhes" => $e->getMessage()
    ]);
}