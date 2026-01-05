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

$firstname = $data["firstname"] ?? null;
$lastname = $data["lastname"] ?? null;
$email = $data["email"] ?? null;
$dateOfBirth = $data["date_of_birth"] ?? null;
$profession = $data["profession"] ?? null;
$country = $data["country"] ?? null;

$userId = $_SESSION["user"]["id"];

try {
    $fields = [];
    $values = [];

    if (isset($data["firstname"])) {
        $fields[] = "firstname = ?";
        $values[] = $data["firstname"];
    }

    if (isset($data["lastname"])) {
        $fields[] = "lastname = ?";
        $values[] = $data["lastname"];
    }

    if (isset($data["email"])) {
        $fields[] = "email = ?";
        $values[] = $data["email"];
    }

    if (isset($data["date_of_birth"])) {
        $fields[] = "date_of_birth = ?";
        $values[] = $data["date_of_birth"];
    }

    if (isset($data["profession"])) {
        $fields[] = "profession = ?";
        $values[] = $data["profession"];
    }

    if (isset($data["country"])) {
        $fields[] = "country = ?";
        $values[] = $data["country"];
    }

    if (empty($fields)) {
        http_response_code(400);
        echo json_encode(["erro" => "Nenhum dado para atualizar"]);
        exit;
    }

    $values[] = $userId;

    $sql = "UPDATE user SET " . implode(", ", $fields) . " WHERE id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->execute($values);

    echo json_encode([
        "sucesso" => true,
        "mensagem" => "Usuário atualizado com sucesso"
    ]);

} catch (PDOException $error) {
    http_response_code(500);
    echo json_encode([
        "erro" => "Falha ao atualizar usuário",
        "detalhes" => $error->getMessage()
    ]);
}