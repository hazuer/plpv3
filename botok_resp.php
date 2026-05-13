<?php
// Token seguro para validación del webhook
$verify_token = "4f9d8a7c2b1e6f0d3a9c5b8e7d2f1a0b";

// Verificación inicial (GET)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $mode      = $_GET['hub_mode'] ?? '';
    $token     = $_GET['hub_verify_token'] ?? '';
    $challenge = $_GET['hub_challenge'] ?? '';

    if ($mode === 'subscribe' && $token === $verify_token) {
        echo $challenge;
    } else {
        http_response_code(403);
        echo "Error: Token inválido.";
    }
    exit;
}

// Recepción del webhook (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    define('_VALID_MOS', 1);

    require_once('includes/configuration.php');
    require_once('includes/DBW.php');
    $db = new DB(HOST, USERNAME, PASSWD, DBNAME, PORT, SOCKET);

    // Capturar el JSON crudo
    $input = file_get_contents('php://input');

    // Log para depuración
    #file_put_contents(date('Y-m-d').'general', "[" . date('Y-m-d H:i:s') . "] " . $input . PHP_EOL, FILE_APPEND);

    // Decodificar JSON
    $data = json_decode($input, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo "Invalid JSON";
        exit;
    }

       // ✅ 1. Procesar estados de mensajes enviados
    if (isset($data['entry'][0]['changes'][0]['value']['statuses'][0])) {
        http_response_code(200);
        echo "EVENT_RECEIVED"; // responder de inmediato a Meta

        $status = $data['entry'][0]['changes'][0]['value']['statuses'][0];

        $message_id  = $status['id'] ?? '';
        $status_name = $status['status'] ?? '';
        $recipient   = $status['recipient_id'] ?? '';
        $timestamp   = isset($status['timestamp']) ? date('Y-m-d H:i:s', $status['timestamp']) : date('Y-m-d H:i:s');

        if (!empty($message_id) && !empty($status_name)) {
            $sql = "INSERT INTO waba_status (datelog, message_id, status_name, recipient_phone, raw_json)
                VALUES ('$timestamp', '$message_id', '$status_name', '$recipient', '" . addslashes($input) . "')";
            try {
                $db->sqlPure($sql, false);
            } catch (Exception $e) {
                file_put_contents('webhook_log_estatus_error.txt', "DB ERROR STATUS: " . $e->getMessage() . PHP_EOL, FILE_APPEND);
            }

            // Cambio de estatus
            $n_user_id = 1;
            $nDate     = date('Y-m-d H:i:s'); // corregido 'i' minúscula
            $newStatusPackage = 6;
            if ($status_name == 'failed') {
                $sqlPackages="SELECT 
                    p.id_package 
                FROM 
                    package p 
                INNER JOIN notification n ON n.id_package = p.id_package 
                WHERE 1 
                    AND n.message_id LIKE 'wamid%' 
                    AND n.message_id IN ('".$message_id."') 
                ORDER BY p.id_package ASC";

                $dtsPks = $db->select($sqlPackages);
                $idsPks = array_column($dtsPks, "id_package");

                if (!empty($idsPks)) {

                    foreach ($dtsPks as $row) {
                        $id_package = $row['id_package'];

                        $sqlGetCurrentStatus = "SELECT id_status old_id_status FROM package WHERE id_package IN ($id_package)";
                        $records           = $db->select($sqlGetCurrentStatus);
                        $id_status_current = $records[0]['old_id_status'];

                        $sqlLogger = "INSERT INTO logger 
                        (datelog, id_package, id_user, new_id_status, old_id_status, desc_mov) 
                        VALUES 
                        ('$nDate', $id_package, $n_user_id, $newStatusPackage, $id_status_current, 'Error al enviar mensaje Meta Waba, ".$message_id."')";
                        $db->sqlPure($sqlLogger, false);
				    }

                    $listIdsP = implode(", ", $idsPks);
                    $sqlUpdatePackage = "UPDATE package SET 
                        n_date = '$nDate', n_user_id = '$n_user_id', id_status=$newStatusPackage 
                        WHERE id_package IN ($listIdsP)";
                    $db->sqlPure($sqlUpdatePackage, false);
                } 
            }
            //update type contact
            if ($status_name == 'read' || $status_name =='delivered') {
                $phone = substr($recipient, 3);
                $sqlUpdateTypeContact="UPDATE cat_contact 
                    SET id_contact_type=2, lastMessage='".$nDate."'
                    WHERE  phone='".$phone."' AND id_contact_type=1";
                $db->sqlPure($sqlUpdateTypeContact, false);
            }
        }
        exit;
    }

    // Verificar si hay mensajes
    if (isset($data['entry'][0]['changes'][0]['value']['messages'][0])) {
        http_response_code(200);
        echo "EVENT_RECEIVED";
        logger("============== S T A R T ==============");
        // Recuperar metadata de WABA
        $wabaPhone      = $data['entry'][0]['changes'][0]['value']['metadata']['display_phone_number'] ?? '';
        $phoneNumberId  = $data['entry'][0]['changes'][0]['value']['metadata']['phone_number_id'] ?? '';

        $sqlLocationInfo = "SELECT * FROM cat_location WHERE phone_waba = '".$wabaPhone."'";
        $infoLocation    = $db->select($sqlLocationInfo);
        $token           = $infoLocation[0]['token'];
        if (empty($infoLocation)) {
            logger("ERROR: cat_location no encontrado para: ".$wabaPhone);
            exit;
        }
        logger("infoLocation: ".json_encode($infoLocation));

        $message     = $data['entry'][0]['changes'][0]['value']['messages'][0];
        // Extraer datos básicos
        $from        = $message['from'] ?? '';
        $message_id  = $message['id']   ?? '';
        $type        = $message['type'] ?? '';
        $raw_json    = $input;
        logger("from: ".$from);

        // Determinar el contenido según tipo de mensaje
        $body = '';
        $typeTextBot = false;
        switch($type) {
            case 'text':
                $body = $message['text']['body'];
                insertCallbackMessage($db, $from, $message_id, $body, $raw_json);
                $typeTextBot = true;
                logger("body: ".$body);
                break;

            case 'image':
                $media_id = $message['image']['id'];
                $mediaInfo = getMediaUrl($media_id, $token);
                if (isset($mediaInfo['url'])) {
                    $savePath = "meta/image/".$media_id.".jpg";
                    downloadMedia($mediaInfo['url'], $token, $savePath);
                    $body = "[IMAGE SAVED] $savePath";
                } else {
                    $body = "[IMAGE] ID: ".$media_id;
                }
                insertCallbackMessage($db, $from, $message_id, $body, $raw_json);
                break;

            case 'audio':
                $media_id = $message['audio']['id'];
                $mediaInfo = getMediaUrl($media_id, $token);
                if (isset($mediaInfo['url'])) {
                    $savePath = "meta/audio/".$media_id.".ogg";
                    downloadMedia($mediaInfo['url'], $token, $savePath);
                    $body = "[AUDIO SAVED] $savePath";
                }
                insertCallbackMessage($db, $from, $message_id, $body, $raw_json);
                break;

            case 'document':
                $media_id = $message['document']['id'];
                $filename = $message['document']['filename'] ?? ($media_id.".pdf");
                $mediaInfo = getMediaUrl($media_id, $token);
                if (isset($mediaInfo['url'])) {
                    $savePath = "meta/document/".$filename;
                    downloadMedia($mediaInfo['url'], $token, $savePath);
                    $body = "[DOCUMENT SAVED] $savePath";
                }
                insertCallbackMessage($db, $from, $message_id, $body, $raw_json);
                break;

            case 'reaction':
                $reaction = $message['reaction'] ?? [];
                $emoji = $reaction['emoji'] ?? '';
                $msgReactedId = $reaction['message_id'] ?? '';

                if (!empty($emoji) && !empty($msgReactedId)) {
                    $body = "[REACTION] $emoji en mensaje $msgReactedId";
                } else {
                    $body = "[REACTION] sin datos";
                }
                insertCallbackMessage($db, $from, $message_id, $body, $raw_json);
                break;
                case 'interactive':
                    $interactive = $message['interactive'];
                    if ($interactive['type'] === "button_reply") {
                        $buttonId = $interactive['button_reply']['id'];
                        $buttonTitle = $interactive['button_reply']['title'];
                        $body = "👉 ".$buttonTitle;
                        insertCallbackMessage($db, $from, $message_id, $body, $raw_json);
                        logButtonClick($from, $buttonId);

                        if ($buttonId === "btn_horario") {
                            $horarioJson = $infoLocation[0]['schedule'] ?? '';
                            $respuestaHorario = getHorarioHoy($horarioJson);
                            sendText($from, $token, $phoneNumberId, "Nuestro horario de atención es:\n*".$respuestaHorario."*\nPuedes acudir por tu paquete en cualquier momento dentro de ese horario.", $wabaPhone);
                        }

                        if ($buttonId === "btn_ubicacion") {
                            $texto = "Nos encuentras en:\n*".($infoLocation[0]['address'] ?? '')."*\n\nPara ver cómo llegar, solo haz clic aquí:\n" . ($infoLocation[0]['address_share'] ?? '');

                            sendText($from, $token, $phoneNumberId, $texto, $wabaPhone);
                        }

                        if ($buttonId === "btn_paquete") {
                            // Tomar solo los últimos 10 dígitos del número
                            $phone10 = substr($from, -10);
                            $sql = "SELECT
                                p.tracking,
                                cp.parcel 
                            FROM
                                package p 
                                INNER JOIN cat_contact cc ON cc.id_contact = p.id_contact 
                                INNER JOIN cat_parcel cp ON cp.id_cat_parcel = p.id_cat_parcel 
                            WHERE 
                                cc.phone IN('".$phone10."') 
                                AND p.id_status NOT IN(3,4,8)";

                            $rst   = $db->select($sql);
                            $total = count($rst);
                            if ($total >= 1) {
                                $horarioJson = $infoLocation[0]['schedule'] ?? '';
                                $respuestaHorario = getHorarioHoy($horarioJson);
                                $texto = "¡Buenas noticias!\nTenemos información sobre tu(s) paquete(s):\n\n";

                                foreach ($rst as $row) {
                                    $texto .= "- ".$row['parcel']."\n*".$row['tracking']."*\n\n";
                                }
                                $texto .= "Tus paquetes están listos para ser recogidos en nuestra sucursal en el horario de atención. *".$respuestaHorario."*\n\n¡Te esperamos pronto!";
                                sendText($from, $token, $phoneNumberId, $texto, $wabaPhone);
                            }else{
                                $texto = "No tenemos noticias sobre tu(s) paquete(s) en este momento. \nSi crees que es un error, por favor contáctanos directamente.";
                                sendText($from, $token, $phoneNumberId, $texto, $wabaPhone);
                                // Desactivar el bot
                                enableDisableBot($from,0);
                                return;
                            }
                        }

                        if ($buttonId === "btn_delivery_q") {
                            $texto = "Desde hace más de dos años operamos únicamente con el método *Ocurre*, por eso le enviamos la ubicación y la dirección para que pueda pasar a recoger su paquete.";
                            sendText($from, $token, $phoneNumberId, $texto, $wabaPhone);
                        }

                        if ($buttonId === "btn_envios") {
                            $texto = "Sí, realizamos envíos a cualquier parte de la República Mexicana y también a los Estados Unidos.\nPara brindarte el costo exacto, por favor compártenos el *Código Postal* y el destino.";
                            sendText($from, $token, $phoneNumberId, $texto, $wabaPhone);
                            // Desactivar el bot
                            enableDisableBot($from,0);
                            return;
                        }
                        
                        if ($buttonId === "btn_no_puedo_ir") {
                            $horarioJson = $infoLocation[0]['schedule'] ?? '';
                            $respuestaHorario = getHorarioHoy($horarioJson);
                            $texto = "Si usted no puede pasar por su paquete, puede enviar a alguien con una *identificación* para recogerlo dentro del horario de atención.\n*".$respuestaHorario."*";
                            sendText($from, $token, $phoneNumberId, $texto, $wabaPhone);
                        }

                        if ($buttonId === "btn_no") {
                            $texto = "Perfecto 😊. Me alegra haber ayudado. Si necesitas algo más, aquí estaré.";
                            sendText($from, $token, $phoneNumberId, $texto, $wabaPhone);
                            // Desactivar el bot
                            enableDisableBot($from,0);
                            return; // Detener flujo
                        }
                    }
                break;
            default:
                $body = '[UNSUPPORTED TYPE] ' . $type;
                insertCallbackMessage($db, $from, $message_id, $body, $raw_json);
                break;
        }

        // ==========================================
        // 🔒 CONTROL DE PAUSA Y REACTIVACIÓN DEL BOT
        // ==========================================

        // Obtener estado del bot
       if ($type === 'text') {
        // Obtener estado del bot
            $sql = "SELECT bot_active FROM waba_user_buttons WHERE phone = '$from' LIMIT 1";
            $res = $db->select($sql);
            // Si está pausado…
            if (!empty($res) && $res[0]['bot_active'] == 0) {
                // Solo se reactiva si el usuario escribe:
                $reactivar = ["hola","ayuda"];
                $body = $message['text']['body'];
                $bodyClean = strtolower(trim($body));

                if (in_array($bodyClean, $reactivar)) {
                    // Reactivar bot
                    enableDisableBot($from,1);
                    sendText($from, $token, $phoneNumberId,"¡Estoy de vuelta! 😊\nAquí tienes el menú nuevamente:",$wabaPhone);
                    sendBotMenu($from, $token, $phoneNumberId, $wabaPhone);
                }
                // No bloquear el flujo si el mensaje no coincide
                return;
            }

            $graciasPatterns = [
                "gracias",
                "gracia", // para cubrir tildes o truncados
                "muchas gracias",
                "muchísimas gracias",
                "mil gracias",
                "te agradezco",
                "le agradezco",
                "agradezco",
                "gracias por",
            ];
            //Clean text
            $bodyClean = strtolower(trim($body));
            foreach ($graciasPatterns as $pattern) {
                if (strpos($bodyClean, $pattern) !== false) {
                    sendText($from,$token,$phoneNumberId,"¡De nada! 😊 Si necesitas algo más, aquí estoy.",$wabaPhone);
                    // Desactivar el bot
                    enableDisableBot($from, 0);
                    return;
                }
            }
        }

        // ======================================
        // 🟢 BOT ACTIVO → ENVIAR MENÚ NORMALMENTE
        // ======================================
        sendBotMenu($from, $token, $phoneNumberId, $wabaPhone);
        // ======================================

        exit;
    }
}

//----------------------------------------------- FUNCTIONS ----------------------------------//
function enableDisableBot($from, $status) {
    global $db;
    $sqlCheck = "SELECT phone FROM waba_user_buttons WHERE phone = '$from' LIMIT 1";
    $resCheck = $db->select($sqlCheck);
    if (empty($resCheck)) {
        // Insertar nuevo registro
        $date = date("Y-m-d H:i:s");
        $sqlInsert = "INSERT INTO waba_user_buttons (phone,button_id,bot_active, datelog)
            VALUES ('$from', 'btn_horario', $status, '$date')";
        $db->sqlPure($sqlInsert, false);
    } else {
        // Actualizar registro existente
        $sqlUpdate = "UPDATE waba_user_buttons SET bot_active = $status WHERE phone = '$from'";
        $db->sqlPure($sqlUpdate, false);
    }
}

function logButtonClick($from, $buttonId) {
    global $db;
    $date = date("Y-m-d H:i:s");
    $sql  = "INSERT INTO waba_user_buttons (phone, button_id, datelog)
        VALUES ('$from', '$buttonId', '$date')";
    $db->sqlPure($sql, false);
}

function getHorarioHoy($jsonHorario) {
    // Decodificar JSON
    $horarios = json_decode($jsonHorario, true);
    if (!$horarios) return "Horario no disponible.";

    // Obtener el día actual en español
    $dias = [
        "Monday"    => "lunes",
        "Tuesday"   => "martes",
        "Wednesday" => "miercoles",
        "Thursday"  => "jueves",
        "Friday"    => "viernes",
        "Saturday"  => "sabado",
        "Sunday"    => "domingo"
    ];

    $hoy_en = date("l");            // Ej: "Friday"
    $hoy_es = $dias[$hoy_en];       // Ej: "viernes"

    // Obtener horario de hoy
    if (!isset($horarios[$hoy_es])) {
        return "Hoy $hoy_es no tenemos horario registrado.";
    }

    $open  = $horarios[$hoy_es]['open'];
    $close = $horarios[$hoy_es]['close'];

    return "Hoy $hoy_es de $open a $close";
}

function sendText($to, $token, $phoneNumberId, $text, $wabaPhone){
    global $db;
    usleep(200000);
    $url = "https://graph.facebook.com/v21.0/$phoneNumberId/messages";

    $payload = [
        "messaging_product" => "whatsapp",
        "to"  => $to,
        "type"=> "text",
        "text"=> ["body" => $text]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $token",
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);

    logger("sendText: ".$response);
    $decoded = json_decode($response, true);
    if (isset($decoded['messages'][0]['id'])) {
        $message_id = $decoded['messages'][0]['id']; // ID que regresa la API
        $date    = date("Y-m-d H:i:s");
        $read_by = 21;
        $sent_by = 21;
        $sql = "INSERT INTO waba_callbacks (datelog, sender_phone, message_id, message_text, raw_json,is_read,read_at,read_by,source,sent_by) 
        VALUES ('$date', '$wabaPhone', '$message_id', '".addslashes($text)."', '".addslashes($response)."',1,'$date', $read_by,'bot',$sent_by)";
        $db->sqlPure($sql, false);
    }
}

function sendBotMenu($from, $token, $phoneNumberId, $wabaPhone){
    global $db;

    $noTexts = [
        "Todo bien, gracias 😊",
        "Gracias, eso es todo",
        "Nada más, gracias",
        "No, gracias"
    ];
    // 1. Lista total
    $allButtons = [
        ["id"=>"btn_horario",  "title"=>"🕔 Horario"],
        ["id"=>"btn_ubicacion","title"=>"📍 Ubicación"],
        ["id"=>"btn_paquete",  "title"=>"📦 ¿Llegó mi paquete?"],
        ["id"=>"btn_delivery_q",  "title"=>"¿Envío a dom.?"],
        ["id"=>"btn_envios",   "title"=>"🚚 ¿Hacen envíos?"],
        ['id' => "btn_no_puedo_ir",   'title' => '¿No puedo ir yo?'],
        ["id"=>"btn_no",  "title"=>$noTexts[array_rand($noTexts)]]
    ];

    // 2. Obtener botones usados
    $sql = "SELECT button_id FROM waba_user_buttons WHERE phone = '$from'";
    $used = $db->select($sql);
    $usedButtons = array_column($used, "button_id");
    $remaining = [];

    // 3. Filtrar botones que faltan
    foreach ($allButtons as $btn) {
        if (!in_array($btn["id"], $usedButtons)) {
            $remaining[] = $btn;
        }
    }

    // 4. Tomar máximo 3
    $nextButtons = array_slice($remaining, 0, 3);

    // 5. Si ya no hay botones → reiniciar
    if (empty($nextButtons)) {
        $nextButtons = array_slice($allButtons, 0, 3);
        // Borramos historial para reiniciar el ciclo
        $sql = "DELETE FROM waba_user_buttons WHERE phone = '$from'";
        $db->sqlPure($sql, false);
    }

    // ====== Construir el payload ======
    $mensajesPosibles = [
        "¿Qué más necesitas?",
        "¿Te ayudo con algo más?",
        "¿Qué más puedo hacer por usted?",
        "¿Algo más en lo que le apoye?",
        "¿Desea otra opción?"
    ];
    $mensaje = $mensajesPosibles[array_rand($mensajesPosibles)];
    $buttons = [];

    foreach ($nextButtons as $b) {
        $buttons[] = [
            "type" => "reply",
            "reply" => [
                "id" => $b["id"],
                "title" => $b["title"]
            ]
        ];
    }

    $payload = [
        "messaging_product" => "whatsapp",
        "to" => $from,
        "type" => "interactive",
        "interactive" => [
            "type" => "button",
            "body" => [
                "text" => $mensaje
            ],
            "action" => [
                "buttons" => $buttons
            ]
        ]
    ];

    // ====== Enviar petición ======
    $url = "https://graph.facebook.com/v21.0/$phoneNumberId/messages";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $token",
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    logger("Menu enviado: ".$response);

    // ====== Combinar texto y títulos ======
    $button_titles = array_map(function($b){ 
        return $b['reply']['title']; 
    }, $buttons);
    $concatenado = $mensaje . " | " . implode(" | ", $button_titles);
    // ====== Guardar en BD ======
    $decoded = json_decode($response, true);
    if (isset($decoded['messages'][0]['id'])) {

        $message_id = $decoded['messages'][0]['id'];
        $date = date("Y-m-d H:i:s");

        $sql = "INSERT INTO waba_callbacks 
        (datelog, sender_phone, message_id, message_text, raw_json, is_read, read_at, read_by, source, sent_by) 
        VALUES 
        ('$date','$wabaPhone','$message_id','".addslashes($concatenado)."','".addslashes($response)."',1,'$date',21,'bot',21)";
        $db->sqlPure($sql, false);
    }
}

//------------------------------ END FUNCTIONS ----------------------------------//

function insertCallbackMessage($db, $from, $message_id, $body, $raw_json) {
    if (empty($from) || empty($message_id)) {
        file_put_contents(
            'webhook_message_else.txt', 
            "Datos incompletos: from=$from, msg_id=$message_id, body=$body\n".print_r($raw_json, true).PHP_EOL, 
            FILE_APPEND
        );
        return;
    }
    $date = date("Y-m-d H:i:s");
    $bodyEscaped = addslashes($body);
    $raw = addslashes($raw_json);
    $sql = "INSERT INTO waba_callbacks (datelog, sender_phone, message_id, message_text, raw_json, source) 
            VALUES ('$date', '$from', '$message_id', '".$bodyEscaped."', '".$raw."', 'callback_user')";
            logger("SQL call back".$sql);
    try {
        $db->sqlPure($sql, false);
    } catch (Exception $e) {
        file_put_contents(
            'webhook_message_error.txt',
            "DB ERROR: ".$e->getMessage().PHP_EOL,
            FILE_APPEND
        );
    }
}

function logger($msg){
    file_put_contents('procesarRespuesta.txt', date("Y-m-d H:i:s").":".print_r($msg, true) . PHP_EOL, FILE_APPEND);
}

function getMediaUrl($media_id, $access_token) {
    $url = "https://graph.facebook.com/v21.0/$media_id";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $access_token"
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true);
}

function downloadMedia($file_url, $access_token, $save_path) {
    $ch = curl_init($file_url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $access_token"
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $data = curl_exec($ch);
    curl_close($ch);

    file_put_contents($save_path, $data);
}
?>
