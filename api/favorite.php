<?php
include "headers.php";
session_start();
include "db_conn.php";

if (!isset($_SESSION["user"])|| !isset($_SESSION["user"]["id"])) {
    http_response_code(401);
    echo json_encode(["erro" => "Não autorizado"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data["id"]) || !isset($data["isFavorite"])) {
    http_response_code(400);
    echo json_encode(["erro" => "id ou isFavorite ausente"]);
    exit;
}

$id = intval($data["id"]);
$isFavorite = $data["isFavorite"] ? 1 : 0;
$userId = $_SESSION["user"]["id"];

try {
    $stmt = $conn->prepare(
        "UPDATE subjects
            SET is_favorite = :is_favorite
            WHERE id = :id
            AND user_id = :user_id"
    );

    $stmt->execute([
        ":is_favorite" => $isFavorite,
        ":id" => $id,
        ":user_id" => $userId
    ]);

    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode([
            "erro" => "Registro não encontrado ou não pertence ao usuário"
        ]);
        exit;
    }

    echo json_encode([
        "success" => true,
        "id" => $id,
        "isFavorite" => (bool) $isFavorite
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "erro" => "Falha ao adicionar como favorito",
        "detalhes" => $e->getMessage()
    ]);
}