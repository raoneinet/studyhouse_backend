<?php
include "headers.php";
session_start();
include "db_conn.php";

header("Content-Type: application/json");

try {
    $data = json_decode(file_get_contents("php://input"), true);

    $email = $data["email"] ?? "";
    $pass  = $data["password"] ?? "";

    if (empty($email) || empty($pass)) {
        http_response_code(400);
        echo json_encode([
            "status" => "error",
            "message" => "Email e senha são obrigatórios"
        ]);
        exit;
    }

    // 1️⃣ Busca usuário
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
    $stmt->bindParam(":email", $email);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($pass, $user["password"])) {
        http_response_code(401);
        echo json_encode([
            "status" => "error",
            "message" => "Email ou senha inválidos"
        ]);
        exit;
    }

    $now = new DateTime();

    // 2️⃣ Verifica exclusão agendada
    $deleteScheduled = $user["delete_scheduled_at"];
    if (!empty($deleteScheduled) && $deleteScheduled !== '0000-00-00 00:00:00') {
        $scheduled = new DateTime($deleteScheduled);

        if ($now < $scheduled) {
            // Ainda dentro do prazo → cancela exclusão e reativa conta
            $reactivate = $conn->prepare("
                UPDATE user
                SET
                    is_active = 1,
                    delete_requested_at = NULL,
                    delete_scheduled_at = NULL
                WHERE id = :id
            ");
            $reactivate->bindParam(":id", $user["id"], PDO::PARAM_INT);
            $reactivate->execute();

            $user["is_active"] = 1;
        } else {
            // Prazo passou → bloqueia login se a conta está inativa
            if ((int)$user["is_active"] === 0) {
                http_response_code(403);
                echo json_encode([
                    "status" => "error",
                    "message" => "Conta excluída permanentemente"
                ]);
                exit;
            }
        }
    }

    // 3️⃣ Conta pausada/suspensa (is_active = 0 e sem exclusão agendada)
    if ((int)$user["is_active"] === 0) {
        $update = $conn->prepare("
            UPDATE user
            SET is_active = 1
            WHERE id = :id
        ");
        $update->bindParam(":id", $user["id"], PDO::PARAM_INT);
        $update->execute();

        $user["is_active"] = 1;
    }

    // 4️⃣ Cria sessão
    $_SESSION["user"] = [
        "id"            => $user["id"],
        "firstname"     => $user["firstname"],
        "lastname"      => $user["lastname"],
        "avatar"        => $user["avatar"],
        "username"      => $user["username"],
        "date_of_birth" => $user["date_of_birth"],
        "email"         => $user["email"],
        "profession"    => $user["profession"],
        "country"       => $user["country"],
        "is_active"     => $user["is_active"]
    ];

    $_SESSION["token"] = bin2hex(random_bytes(32));

    echo json_encode([
        "status" => "success",
        "message" => "Login realizado",
        "user"    => $_SESSION["user"],
        "token"   => $_SESSION["token"]
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Erro interno no servidor",
        "details" => $e->getMessage()
    ]);
}
