<?php
// Incluir configuración
require_once("settings.php");

// URL de tu aplicación en Heroku
$webhookUrl = "https://avan1204-bd64ccf26180.herokuapp.com/bot.php";

// Configurar el webhook
$url = "https://api.telegram.org/bot{$token}/setWebhook";
$params = [
    'url' => $webhookUrl,
    'drop_pending_updates' => true,
    'allowed_updates' => json_encode(['message', 'callback_query'])
];

// Usar cURL para configurar el webhook
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Mostrar el resultado
echo "<h1>Configuración del Webhook para Heroku</h1>";
echo "<p>URL del webhook: " . htmlspecialchars($webhookUrl) . "</p>";
echo "<p>Respuesta del servidor (HTTP {$httpcode}):</p>";
echo "<pre>" . htmlspecialchars($response) . "</pre>";

// Verificar si hay errores
$responseData = json_decode($response, true);
if (isset($responseData['ok']) && $responseData['ok'] === true) {
    echo "<p style='color: green;'>✅ Webhook configurado correctamente</p>";
    
    // Obtener información del webhook
    $infoUrl = "https://api.telegram.org/bot{$token}/getWebhookInfo";
    $webhookInfo = json_decode(file_get_contents($infoUrl), true);
    
    echo "<h2>Información del Webhook:</h2>";
    echo "<pre>" . print_r($webhookInfo, true) . "</pre>";
} else {
    echo "<p style='color: red;'>❌ Error al configurar el webhook</p>";
    if (isset($responseData['description'])) {
        echo "<p>Error: " . htmlspecialchars($responseData['description']) . "</p>";
    }
}
?>
