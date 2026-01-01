<?php
include "headers.php";
session_start();
include "db_conn.php";

if (!isset($_SESSION["user"])) {
    http_response_code(401);
    echo json_encode(["erro" => "Não autorizado"]);
    exit;
}

$userId = $_SESSION["user"]["id"];

try {

    $stmt = $conn->prepare(
        "SELECT id, title, links, description, category, status, priority, tags, created_at, is_favorite 
        FROM subjects 
        WHERE user_id = ?
        ORDER BY created_at DESC"
    );

    $stmt->execute([$userId]);

    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($subjects as &$subject) {
        $subject["tags"] = $subject["tags"]
            ? json_decode($subject["tags"], true)
            : [];
    }

    foreach($subjects as &$subject){
        $subject['links'] = $subject['links']
            ? json_decode($subject['links'], true)
            : [];
    }

    echo json_encode($subjects);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "erro" => "Falha ao buscar assuntos",
        "detalhes" => $e->getMessage()
    ]);
}