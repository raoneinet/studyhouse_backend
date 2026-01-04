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

    /* =========================
       1️⃣ STATS GERAIS
    ========================= */
    $statsStmt = $conn->prepare("
        SELECT
            COUNT(*) AS total,
            SUM(priority = 'urgent') AS urgent,
            SUM(status = 'ongoing') AS ongoing,
            SUM(status = 'done') AS done
        FROM subjects
        WHERE user_id = ?
    ");
    $statsStmt->execute([$userId]);
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);


    /* =========================
       2️⃣ TOTAIS POR CATEGORIA
    ========================= */
    $categoriesStmt = $conn->prepare("
        SELECT category, COUNT(*) AS total
        FROM subjects
        WHERE user_id = ?
        GROUP BY category
        ORDER BY total DESC
    ");
    $categoriesStmt->execute([$userId]);
    $categories = $categoriesStmt->fetchAll(PDO::FETCH_ASSOC);


    /* =========================
       3️⃣ CONTINUAR ESTUDANDO (4 ONGOING)
    ========================= */
    $continueStmt = $conn->prepare("
        SELECT id, title, category, description, status, priority, is_favorite
        FROM subjects
        WHERE user_id = ? AND status = 'ongoing'
        LIMIT 4
    ");
    $continueStmt->execute([$userId]);
    $continueStudying = $continueStmt->fetchAll(PDO::FETCH_ASSOC);


    /* =========================
       4️⃣ FAVORITOS (4 ÚLTIMOS)
    ========================= */
    $favoritesStmt = $conn->prepare("
        SELECT id, title, category, description, status, priority, is_favorite
        FROM subjects
        WHERE user_id = ? AND is_favorite = 1
        LIMIT 4
    ");
    $favoritesStmt->execute([$userId]);
    $favorites = $favoritesStmt->fetchAll(PDO::FETCH_ASSOC);


    /* =========================
       5️⃣ ATIVIDADE RECENTE (4 ÚLTIMOS CRIADOS)
    ========================= */
    $recentStmt = $conn->prepare("
        SELECT id, title, category, description, status, created_at
        FROM subjects
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT 4
    ");
    $recentStmt->execute([$userId]);
    $recentActivity = $recentStmt->fetchAll(PDO::FETCH_ASSOC);


    /* =========================
       RESPONSE FINAL
    ========================= */
    echo json_encode([
        "stats" => $stats,
        "categories" => $categories,
        "continueStudying" => $continueStudying,
        "favorites" => $favorites,
        "recentActivity" => $recentActivity
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "erro" => "Erro ao carregar dashboard",
        "detalhes" => $e->getMessage()
    ]);
}
