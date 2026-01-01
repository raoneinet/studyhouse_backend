<?php
include "headers.php";
session_start();
include "db_conn.php";

if (!isset($_SESSION["user"])) {
    http_response_code(401);
    echo json_encode(["erro" => "Não autorizado"]);
    exit;
}

if (!isset($_GET["id"])) {
    http_response_code(400);
    echo json_encode(["erro" => "ID não informado"]);
    exit;
}

$userId = $_SESSION["user"]["id"];
$subjectId = $_GET["id"];

try {
    $stmt = $conn->prepare(
        "SELECT * FROM subjects WHERE id = ? AND user_id = ? LIMIT 1"
    );

    $stmt->execute([$subjectId, $userId]);

    $subject = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$subject) {
        http_response_code(404);
        echo json_encode(["erro" => "Assunto não encontrado"]);
        exit;
    }

    $subject["tags"] = $subject["tags"]
        ? json_decode($subject["tags"], true)
        : [];

    $subject["links"] = $subject["links"]
        ? json_decode($subject["links"], true)
        : [];

    echo json_encode($subject);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "erro" => "Erro ao buscar assunto",
        "detalhes" => $e->getMessage()
    ]);
}
