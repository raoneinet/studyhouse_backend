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

$links = [];

if (!empty($data['links'] && is_array($data['links']))) {
    foreach ($data['links'] as $item) {
        if (!empty($item['value'])) {
            $links[] = $item['value'];
        }
    }
}

$links = array_filter($links, function ($url) {
    return filter_var($url, FILTER_VALIDATE_URL);
});

if (!$data || empty($data["title"])) {
    http_response_code(400);
    echo json_encode(['erro' => 'Título do assunto é obrigatório']);
    exit;
}

if (!isset($_SESSION['subject'])) {
    $_SESSION['subject'] = [];
}

$userId = $_SESSION["user"]["id"];

$subjectId = $data["id"] ?? null;

if (!$subjectId) {
    http_response_code(400);
    echo json_encode(["erro" => "ID do subject é obrigatório"]);
    exit;
}

$validStatus = ['notstarted', 'ongoing', 'onhold', 'done'];
$validCategory = ['history', 'math', 'programming', 'computing', 'engineering', 'language', 'linguistics', 'science', 'economics', 'law', 'world', 'biology', 'humanities', 'politics', 'other'];
$validPriority = ['low', 'medium', 'high', 'urgent'];

$status = in_array($data['status'] ?? '', $validStatus) ? $data['status'] : 'notstarted';
$category = in_array($data['category'] ?? '', $validCategory) ? $data['category'] : 'other';
$priority = in_array($data['priority'] ?? '', $validPriority) ? $data['priority'] : 'medium';

try {
    $stmt = $conn->prepare(
        "UPDATE subjects
                SET
                title = ?,
                links = ?,
                description = ?,
                category = ?,
                status = ?,
                priority = ?,
                tags = ?
                WHERE id = ? AND user_id = ?"
    );

    $stmt->execute([
        trim($data["title"]),
        !empty($links) ? json_encode($links) : null,
        $data["description"] ?? null,
        $category,
        $status,
        $priority,
        json_encode($data["tags"] ?? []),
        $subjectId,
        $userId
    ]);

    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(["erro" => "Assunto não encontrado ou não pertence ao usuário"]);
        exit;
    }

    $subject = [
        "id" => $subjectId,
        "user_id" => $userId,
        "title" => trim($data["title"]),
        "links" => $links,
        "description" => $data["description"] ?? null,
        "category" => $category,
        "status" => $status,
        "priority" => $priority,
        "tags" => $data["tags"] ?? []
    ];

    echo json_encode([
        'sucesso' => true,
        'subject' => $subject
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "erro" => "Falha ao editar assunto",
        "detalhes" => $e->getMessage()
    ]);
}