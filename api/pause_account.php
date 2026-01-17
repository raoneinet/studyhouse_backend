<?php
include "headers.php";
session_start();
include "db_conn.php";

header("Content-Type: application/json");

if (!isset($_SESSION["user"]["id"])) {
    http_response_code(401);
    echo json_encode(["error" => "Usuário não autenticado"]);
    exit;
}

$user_id = $_SESSION["user"]["id"];

try {
    $stmt = $conn->prepare("SELECT id, is_active FROM users WHERE id = :id");
    $stmt->bindParam(":id", $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(404);
        echo json_encode(["error" => "Usuário não encontrado"]);
        exit;
    }

    if ($user["is_active"] == 0) {
        echo json_encode(["message" => "Conta já está suspensa"]);
        exit;
    }

    $update = $conn->prepare("UPDATE users SET is_active = 0 WHERE id = :id");
    $update->bindParam(":id", $user_id, PDO::PARAM_INT);
    $update->execute();

    $_SESSION = [];

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    session_destroy();

    echo json_encode(["success" => "Conta suspensa com sucesso"]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "error" => "Erro ao suspender a conta",
        "details" => $e->getMessage()
    ]);
}
