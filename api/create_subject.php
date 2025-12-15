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

if (!$data || empty($data["title"])) {
    http_response_code(400);
    echo json_encode(['erro' => 'Título do assunto é obrigatório']);
    exit;
}

if (!isset($_SESSION['subject'])) {
    $_SESSION['subject'] = [];
}

$userId = $_SESSION["user"]["id"];

$validStatus = ['notstarted', 'ongoint', 'onhold', 'done'];
$validCategory = ['history', 'math', 'programming', 'computing', 'engineering', 'language', 'linguistics', 'science', 'economics', 'law', 'world', 'biology', 'humanities', 'politics', 'other'];
$validPriority = ['low', 'medium', 'high', 'urgent'];

$status = in_array($data['status'] ?? '', $validStatus) ? $data['status'] : 'notStarted';
$category = in_array($data['category'] ?? '', $validCategory) ? $data['category'] : 'other';
$priority = in_array($data['priority'] ?? '', $validPriority) ? $data['priority'] : 'medium';

try {
    $stmt = $conn->prepare(
        "INSERT INTO subjects
    (user_id, title, link, description, category, status, priority, tags)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->execute([
        $userId,
        trim($data["title"]),
        $data["link"] ?? null,
        $data["description"] ?? null,
        $category ?? null,
        $status ?? "notstarted",
        $priority ?? "medium",
        json_encode($data["tags"] ?? [])
    ]);

    $subjectId = $conn->lastInsertId();

    $subject = [
        $userId,
        "title" => $data["title"],
        "link" => $data["link"] ?? null,
        "description" => $data["description"] ?? null,
        "category" => $data["category"] ?? null,
        "status" => $data["status"] ?? null,
        "priority" => $data["priority"] ?? null,
        "tags" => $data["tags"] ?? null,
        "created_at" => $data["created_at"] ?? null
    ];

    $_SESSION["subjects"][] = $subject;

    echo json_encode([
        'sucesso' => true,
        'subject' => $subject
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "erro" => "Falha ao criar assunto",
        "detalhes" => $e->getMessage()
    ]);
}