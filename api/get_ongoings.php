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

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = isset($_GET['limit']) ? max(1, intval($_GET['limit'])) : 10;
$offset = ($page - 1) * $limit;

try {
    $countStmt = $conn->prepare("SELECT COUNT(*) as total FROM subjects WHERE user_id = ? AND status = 'ongoing'");
    $countStmt->execute([$userId]);
    $totalItems = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    $totalPages = ceil($totalItems / $limit);

    $stmt = $conn->prepare(
        "SELECT id, title, links, description, category, status, priority, tags, created_at, is_favorite
         FROM subjects 
         WHERE user_id = ? AND status = 'ongoing'
         ORDER BY created_at DESC
         LIMIT ? OFFSET ?"
    );

    $stmt->bindValue(1, $userId, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->bindValue(3, $offset, PDO::PARAM_INT);
    $stmt->execute();

    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($subjects as &$subject) {
        $subject["tags"] = $subject["tags"] ? json_decode($subject["tags"], true) : [];
        $subject["links"] = $subject["links"] ? json_decode($subject["links"], true) : [];
    }

    echo json_encode([
        "data" => $subjects,
        "page" => $page,
        "limit" => $limit,
        "totalItems" => $totalItems,
        "totalPages" => $totalPages
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "erro" => "Falha ao buscar assuntos",
        "detalhes" => $e->getMessage()
    ]);
}
