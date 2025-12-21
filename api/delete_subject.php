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

if(!isset($data['id'])){
    http_response_code(400);
    echo json_encode(["erro" => "Id do assunto é obrigatório"]);
    exit;
}

$userId = $_SESSION["user"]["id"];
$subjectId = (int) $data["id"];

try {
    $stmt = $conn->prepare("DELETE FROM subjects WHERE id = ? AND user_id = ?");

    $stmt->execute([$subjectId, $userId]);

    if($stmt -> rowCount() === 0){
        http_response_code(404);
        echo json_encode(["erro" => "Assunto não encontrado"]);
        exit;
    }

    echo json_encode([
        "Sucesso" => true,
        "id" => $subjectId,
        "message" => "Assunto removido com sucesso"
    ]);

} catch (PDOException $error) {
    http_response_code(500);
    echo json_encode([
        "erro" => "Falha ao eliminar assunto",
        "detalhes" => $error->getMessage()
    ]);
}