<?php
include "headers.php";
session_start();
include "db_conn.php";

header("Content-Type: application/json");

try {
    // 1️⃣ Verifica se o usuário está logado
    if (!isset($_SESSION["user"]["id"])) {
        http_response_code(401);
        echo json_encode([
            "status" => "error",
            "message" => "Não autenticado"
        ]);
        exit;
    }

    $user_id = $_SESSION["user"]["id"];

    // 2️⃣ Verifica se já existe exclusão agendada
    $check = $conn->prepare("
        SELECT delete_scheduled_at
        FROM user
        WHERE id = :id
        LIMIT 1
    ");
    $check->bindParam(":id", $user_id, PDO::PARAM_INT);
    $check->execute();
    $user = $check->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(404);
        echo json_encode([
            "status" => "error",
            "message" => "Usuário não encontrado"
        ]);
        exit;
    }

    // 3️⃣ Corrige caso delete_scheduled_at seja '0000-00-00 00:00:00'
    $deleteScheduled = $user["delete_scheduled_at"];
    if (!empty($deleteScheduled) && $deleteScheduled !== '0000-00-00 00:00:00') {
        echo json_encode([
            "status" => "info",
            "message" => "Exclusão da conta já foi solicitada"
        ]);
        exit;
    }

    // 4️⃣ Atualiza banco: agenda exclusão em 2 dias e desativa conta
    $stmt = $conn->prepare("
        UPDATE user
        SET
            is_active = 0,
            delete_requested_at = NOW(),
            delete_scheduled_at = DATE_ADD(NOW(), INTERVAL 2 DAY)
        WHERE id = :id
    ");
    $stmt->bindParam(":id", $user_id, PDO::PARAM_INT);

    if (!$stmt->execute()) {
        $error = $stmt->errorInfo();
        throw new Exception("Falha ao atualizar banco: " . implode(" | ", $error));
    }

    // 5️⃣ Encerra sessão corretamente
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

    // 6️⃣ Retorna resposta de sucesso
    echo json_encode([
        "status" => "success",
        "message" => "Conta será apagada em 2 dias. Faça login para cancelar."
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Erro ao solicitar exclusão da conta",
        "details" => $e->getMessage()
    ]);
}
