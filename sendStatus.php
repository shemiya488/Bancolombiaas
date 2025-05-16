<?php
// sendStatus.php

$botToken = "8153542950:AAER3soWgrkQDu_cVSUZR4x9dJKjavcGSDE";
$chatId = "-4692441235";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $data = json_decode(file_get_contents("php://input"), true);
    $status = $data["status"] ?? "Sin estado";

    $message = "✅ Acceso aprobado tras acción del botón: $status";

    $url = "https://api.telegram.org/bot$botToken/sendMessage";
    $postData = http_build_query([
        "chat_id" => $chatId,
        "text" => $message
    ]);

    $options = [
        "http" => [
            "method"  => "POST",
            "header"  => "Content-Type: application/x-www-form-urlencoded",
            "content" => $postData
        ]
    ];

    $context = stream_context_create($options);
    file_get_contents($url, false, $context);

    echo json_encode(["ok" => true]);
} else {
    echo json_encode(["error" => "Método no permitido"]);
}
?>
