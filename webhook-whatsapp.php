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
        $enableBot       = $infoLocation[0]['enable_bot'];
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
                    $mime_type = $message['image']['mime_type'] ?? 'image/jpeg';
                    
                    // Determinar extensión según mime_type
                    $ext = 'jpg';
                    if (strpos($mime_type, 'png') !== false) $ext = 'png';
                    elseif (strpos($mime_type, 'jpeg') !== false || strpos($mime_type, 'jpg') !== false) $ext = 'jpg';
                    elseif (strpos($mime_type, 'webp') !== false) $ext = 'webp';
                    
                    $filename = $media_id.".".$ext;
                    
                    $mediaInfo = getMediaUrl($media_id, $token);
                    if (isset($mediaInfo['url'])) {
                            $savePath = "meta/image/".$filename;
                            downloadMedia($mediaInfo['url'], $token, $savePath);
                            $body = "[IMAGE SAVED] $savePath";
                            
                            // Enviar mensaje de confirmación de paquetes
                            $texto = "✅ *Confirmación recibida*\n\n";
                            $texto .= "*iMile:*\n";
                            $texto .= "✔️ Su identificación ha sido recibida.\n";
                            $texto .= "⏱️ Tiene *2 días* para recoger su paquete.\n\n";
                            $texto .= "*J&T:*\n";
                            $texto .= "✔️ Su paquete está listo.\n";
                            $texto .= "⏱️ Tiene hasta *3 días* para recogerlo.\n\n";
                            $texto .= "📍 Recuerde traer una identificación oficial al momento de recoger su paquete.";
                            insertCallbackMessage($db, $from, $message_id, $body, $raw_json);
                            sendText($from, $token, $phoneNumberId, $texto, $wabaPhone,$enableBot);
                        }
                
                    break;

            case 'audio':
                $media_id = $message['audio']['id'];
                $mediaInfo = getMediaUrl($media_id, $token);
                if (isset($mediaInfo['url'])) {
                    $savePath = "meta/audio/".$media_id.".ogg";
                    downloadMedia($mediaInfo['url'], $token, $savePath);
                    $body = "[AUDIO SAVED] $savePath";
                    insertCallbackMessage($db, $from, $message_id, $body, $raw_json);
                }
                break;

            case 'document':
                $media_id = $message['document']['id'];
                $filename = $message['document']['filename'] ?? ($media_id.".pdf");
                $mediaInfo = getMediaUrl($media_id, $token);
                if (isset($mediaInfo['url'])) {
                    $savePath = "meta/document/".$filename;
                    downloadMedia($mediaInfo['url'], $token, $savePath);
                    $body = "[DOCUMENT SAVED] $savePath";
                    insertCallbackMessage($db, $from, $message_id, $body, $raw_json);
                }
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
                            sendText($from, $token, $phoneNumberId, "Nuestro horario de atención es:\n".$respuestaHorario."\nPuedes acudir por tu paquete en cualquier momento dentro de ese horario.", $wabaPhone,$enableBot);
                        }

                        if ($buttonId === "btn_ubicacion") {
                            $texto = "Nos encuentras en:\n*".($infoLocation[0]['address'] ?? '')."*\n\nPara ver cómo llegar, solo haz clic aquí:\n" . ($infoLocation[0]['address_share'] ?? '');

                            sendText($from, $token, $phoneNumberId, $texto, $wabaPhone,$enableBot);
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
                                $texto .= "Tus paquetes están listos para ser recogidos en nuestra sucursal en el horario de atención. ".$respuestaHorario."\n\n¡Te esperamos pronto!";
                                sendText($from, $token, $phoneNumberId, $texto, $wabaPhone,$enableBot);
                            }else{
                                $texto = "No tenemos noticias sobre tu(s) paquete(s) en este momento. \nSi crees que es un error, por favor contáctanos directamente.";
                                sendText($from, $token, $phoneNumberId, $texto, $wabaPhone,$enableBot);
                                // Desactivar el bot
                                enableDisableBot($from,0);
                                return;
                            }
                        }

                        if ($buttonId === "btn_delivery_q") {
                            $texto = "Desde hace más de dos años operamos únicamente con el método *Ocurre*, por eso le enviamos la ubicación y la dirección para que pueda pasar a recoger su paquete.";
                            sendText($from, $token, $phoneNumberId, $texto, $wabaPhone,$enableBot);
                        }

                        if ($buttonId === "btn_envios") {
                            $texto = "Sí, realizamos envíos a cualquier parte de la República Mexicana y también a los Estados Unidos.\nPara brindarte el costo exacto, por favor compártenos el *Código Postal* y el destino.";
                            sendText($from, $token, $phoneNumberId, $texto, $wabaPhone,$enableBot);
                            // Desactivar el bot
                            enableDisableBot($from,0);
                            return;
                        }

                        if ($buttonId === "btn_no_puedo_ir") {
                            $horarioJson = $infoLocation[0]['schedule'] ?? '';
                            $respuestaHorario = getHorarioHoy($horarioJson);
                            $texto = "Si usted no puede pasar por su paquete, puede enviar a alguien con una *identificación* para recogerlo dentro del horario de atención.\n".$respuestaHorario."";
                            sendText($from, $token, $phoneNumberId, $texto, $wabaPhone,$enableBot);
                        }

                        if ($buttonId === "btn_devolucion") {
                            $texto = "Las devoluciones se gestionan directamente en la plataforma donde realizó su compra.\nDesde ahí podrá iniciar el proceso correspondiente.";
                            sendText($from, $token, $phoneNumberId, $texto, $wabaPhone,$enableBot);
                        }

                        if ($buttonId === "btn_confirmar") {
                            $texto = "📦 *Confirmación y recolección de paquetes*\n\n";
                            $texto .= "*iMile:*\n";
                            $texto .= "✔️ Es necesario enviar su *identificación* para confirmar el paquete.\n";
                            $texto .= "⏱️ Una vez enviada, tiene *2 días* para recogerlo.\n\n";
                            $texto .= "*J&T:*\n";
                            $texto .= "✔️ *No requiere identificación* para confirmar el paquete.\n";
                            $texto .= "⏱️ Tiene hasta *3 días* para recogerlo.\n\n";
                            $texto .= "¿Desea confirmar su paquete?";
                            
                            sendInteractiveButtons($from, $token, $phoneNumberId, $wabaPhone, $texto, [
                                ["id" => "btn_confirmar_si", "title" => "✅ Sí, confirmar"],
                                ["id" => "btn_confirmar_no", "title" => "❌ No, gracias"]
                            ],$enableBot);
                            enableDisableBot($from,0);
                            return;
                        }

                        if ($buttonId === "btn_no") {
                            $texto = "Perfecto 😊. Me alegra haber ayudado. Si necesitas algo más, aquí estaré.";
                            sendText($from, $token, $phoneNumberId, $texto, $wabaPhone,$enableBot);
                            // Desactivar el bot
                            enableDisableBot($from,0);
                            return; // Detener flujo
                        }

                        if ($buttonId === "btn_confirmar_si") {
                            $texto = "📄 Para completar el proceso, por favor adjunta una foto de tu identificación oficial.";
                            sendText($from, $token, $phoneNumberId, $texto, $wabaPhone,$enableBot);
                            // Desactivar el bot después de confirmar
                            enableDisableBot($from, 0);
                            return;
                        }

                        if ($buttonId === "btn_confirmar_no") {
                            $texto = "Entendido. Si cambia de opinión o necesita algo más, aquí estaré para ayudarle 😊";
                            sendText($from, $token, $phoneNumberId, $texto, $wabaPhone,$enableBot);
                            // Desactivar el bot
                            enableDisableBot($from, 0);
                            return;
                        }

                        #TODO: btn_confirmar_no
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

       if ($type === 'text') {

        if ($enableBot == 0) {
            logger("Bot deshabilitado desde cat_location.");
            return;
        }
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
                    sendText($from, $token, $phoneNumberId,"¿Hay algo más en lo que podamos ayudarte?",$wabaPhone);
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
                    sendText($from,$token,$phoneNumberId,"Estamos para servirle 😊",$wabaPhone);
                    sendBotMenu($from, $token, $phoneNumberId, $wabaPhone, $body,$enableBot);
                    // Desactivar el bot
                    //TODO: enableDisableBot($from, 0);
                    return;
                }
            }
        }

        // ======================================
        // 🟢 BOT ACTIVO → ENVIAR MENÚ NORMALMENTE
        // ======================================
        sendBotMenu($from, $token, $phoneNumberId, $wabaPhone,$body,$enableBot);
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
        "Wednesday" => "miércoles",
        "Thursday"  => "jueves",
        "Friday"    => "viernes",
        "Saturday"  => "sábado",
        "Sunday"    => "domingo"
    ];

    // === HOY ===
    $hoy_en = date("l");            // Ej: "Friday"
    $hoy_es = $dias[$hoy_en];       // Ej: "viernes"
    
    // Normalizar clave de hoy (sin tildes)
    $hoy_key = str_replace(
        ['á', 'é', 'í', 'ó', 'ú'],
        ['a', 'e', 'i', 'o', 'u'],
        strtolower($hoy_es)
    );

    // Buscar horario de hoy
    $horario_hoy = null;
    if (isset($horarios[$hoy_key])) {
        $horario_hoy = $horarios[$hoy_key];
    } elseif (isset($horarios[strtolower($hoy_es)])) {
        $horario_hoy = $horarios[strtolower($hoy_es)];
    }

    $texto_hoy = "*Hoy $hoy_es: ";
    if ($horario_hoy) {
        $texto_hoy .= "de {$horario_hoy['open']} hrs. a {$horario_hoy['close']} hrs.*";
    } else {
        $texto_hoy .= "no tenemos horario registrado";
    }

    // === MAÑANA ===
    $manana_en = date("l", strtotime("+1 day"));  // Ej: "Saturday"
    $manana_es = $dias[$manana_en];               // Ej: "sábado"
    
    // Normalizar clave de mañana (sin tildes)
    $manana_key = str_replace(
        ['á', 'é', 'í', 'ó', 'ú'],
        ['a', 'e', 'i', 'o', 'u'],
        strtolower($manana_es)
    );

    // Buscar horario de mañana
    $horario_manana = null;
    if (isset($horarios[$manana_key])) {
        $horario_manana = $horarios[$manana_key];
    } elseif (isset($horarios[strtolower($manana_es)])) {
        $horario_manana = $horarios[strtolower($manana_es)];
    }

    $texto_manana = "\n*Mañana $manana_es: ";
    if ($horario_manana) {
        $texto_manana .= "de {$horario_manana['open']} hrs. a {$horario_manana['close']} hrs.*";
    } else {
        $texto_manana .= "cerrado";
    }

    return $texto_hoy . $texto_manana;
}

function sendText($to, $token, $phoneNumberId, $text, $wabaPhone,$enableBot=1) {
    global $db;
    if ($enableBot == 0) {
        logger("Bot deshabilitado desde cat_location.");
        return;
    }
    usleep(200000);
    $url = "https://graph.facebook.com/v23.0/$phoneNumberId/messages";

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

function sendBotMenu($from, $token, $phoneNumberId, $wabaPhone, $body = '',$enableBot=1) {
    global $db;
    usleep(200000);
    if ($enableBot == 0) {
        logger("Bot deshabilitado desde cat_location.");
        return;
    }
    $bodyClean = normalizeText($body);
    
    $keywordMap = [
        // 1️⃣ Horario
        "btn_horario" => [
            "horario",
            "hora de",
            "abren",
            "cierran",
            "a que hora",
            "hasta que hora",
            "abierto",
            "siguen abiertos",
            "ya cerraron",
            "alcanzo",
            "alcanzo a recogerlo",
            "mañana"
        ],
        // 2️⃣ Ubicación
        "btn_ubicacion" => [
            "ubicacion", "ubicación",
            "donde estan", "dónde están",
            "donde se encuentran",
            "donde se ubican",
            "direccion", "dirección",
            "como llegar", "cómo llegar",
            "mapa",
            "ubicados"
        ],
        // 3️⃣ Paquete / Tracking
        "btn_paquete" => [
            "paquete",
            "mi paquete",
            "llego mi paquete",
            "llegó mi paquete",
            "pedido",
            "tracking",
            "rastreo",
            "seguimiento"
        ],
        // 4️⃣ Entrega a domicilio
        "btn_delivery_q" => [
            "entregan a domicilio",
            "envio a domicilio",
            "me lo llevan",
            "me lo entregan",
            "no me ha llegado",
            "no llego a casa"
        ],
        // 5️⃣ Envíos
        "btn_envios" => [
            "envian",
            "envios", "envíos",
            "quiero enviar",
            "mandar un paquete",
            "cotizar envio",
            "costo de envio",
            "costo de un envio",
            "precio de envio",
            "estados unidos",
            "republica mexicana",
        ],
        // 6️⃣ No puedo ir personalmente
        "btn_no_puedo_ir" => [
            "no puedo ir",
            "puede ir otra persona",
            "alguien mas puede ir",
            "otra persona puede recoger",
            "puede recoger alguien mas",
            "mandar a alguien",
            "recogerlo",
            "alguien mas",
            "alguien"
        ],
        // 7️⃣ Devolución
        "btn_devolucion" => [
            "devolucion", "devolución",
            "quiero devolver",
            "quiero regresar",
            "devolver pedido",
            "regresar pedido",
            "reembolso",
            "cancelar pedido",
            "no lo quiero",
            "no me sirve",
            "vino roto",
            "vino dañado",
            "vino mal"
        ],
        // 8️⃣ Confirmar paquete / identificación
        "btn_confirmar" => [
            "confirmar",
            "confirmar mi paquete",
            "ya envie mi identificacion",
            "ya mande mi identificacion",
            "ya envie mi ine",
            "mande mi ine",
            "envie mi id",
            "te mande mi id",
            "credencial",
            "ine"
        ]
    ];
    
    $noTexts = [
        "Todo bien, gracias 😊",
        "Gracias, eso es todo",
        "Nada más, gracias",
        "No, gracias"
    ];
    
    // Lista total de botones
    $allButtons = [
        ["id"=>"btn_horario",  "title"=>"🕔 Horario"],
        ["id"=>"btn_ubicacion","title"=>"📍 Ubicación"],
        ["id"=>"btn_paquete",  "title"=>"📦 ¿Llegó mi paquete?"],
        ["id"=>"btn_delivery_q",  "title"=>"¿Envío a dom.?"],
        ["id"=>"btn_envios",   "title"=>"🚚 ¿Hacen envíos?"],
        ["id"=> "btn_no_puedo_ir",   'title' => '¿No puedo ir yo?'],
        ["id"=>"btn_devolucion",   "title"=>"↩️ Devolución"],
        ["id"=>"btn_confirmar",   "title"=>"📦 Confirmar"],
        ["id"=>"btn_no",  "title"=>$noTexts[array_rand($noTexts)]]
    ];
    
    // Buscar TODAS las coincidencias con puntuación
    $matchedButtons = [];
    
    foreach ($keywordMap as $buttonId => $keywords) {
        $score = 0;
        $matchedKeywords = [];
        
        foreach ($keywords as $word) {
            if (strpos($bodyClean, $word) !== false) {
                $score++;
                $matchedKeywords[] = $word;
            }
        }
        
        if ($score > 0) {
            $matchedButtons[] = [
                'button_id' => $buttonId,
                'score' => $score,
                'keywords' => $matchedKeywords
            ];
        }
    }
    
    // Ordenar por puntuación (mayor a menor)
    usort($matchedButtons, function($a, $b) {
        return $b['score'] - $a['score'];
    });
    
    // Si NO hay coincidencias, enviar solo btn_no
    if (empty($matchedButtons)) {
        $nextButtons = [
            ["id"=>"btn_no", "title"=>$noTexts[array_rand($noTexts)]]
        ];
    } else {
        // Si HAY coincidencias, obtener botones usados
        $sql = "SELECT button_id FROM waba_user_buttons WHERE phone = '$from'";
        $used = $db->select($sql);
        $usedButtons = array_column($used, "button_id");
        
        // Crear array de botones priorizados basados en coincidencias
        $prioritizedButtons = [];
        
        // Primero agregar los botones con coincidencias (en orden de puntuación)
        foreach ($matchedButtons as $match) {
            $buttonId = $match['button_id'];
            
            // Buscar el botón en allButtons
            foreach ($allButtons as $btn) {
                if ($btn['id'] === $buttonId && !in_array($buttonId, $usedButtons)) {
                    $prioritizedButtons[] = $btn;
                    break;
                }
            }
        }
        
        // Luego agregar otros botones no usados
        foreach ($allButtons as $btn) {
            if (!in_array($btn["id"], $usedButtons)) {
                $alreadyAdded = false;
                foreach ($prioritizedButtons as $pb) {
                    if ($pb['id'] === $btn['id']) {
                        $alreadyAdded = true;
                        break;
                    }
                }
                if (!$alreadyAdded) {
                    $prioritizedButtons[] = $btn;
                }
            }
        }
        
        // Si ya no quedan botones sin usar, reiniciar
        if (empty($prioritizedButtons)) {
            $prioritizedButtons = $allButtons;
            $sql = "DELETE FROM waba_user_buttons WHERE phone = '$from'";
            $db->sqlPure($sql, false);
        }
        
        // Tomar máximo 3 botones
        $nextButtons = array_slice($prioritizedButtons, 0, 3);
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
    $url = "https://graph.facebook.com/v23.0/$phoneNumberId/messages";
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

function sendInteractiveButtons($from, $token, $phoneNumberId, $wabaPhone, $bodyText, $buttons, $enableBot=1) {
    global $db;
    usleep(200000);
    if ($enableBot == 0) {
        logger("Bot deshabilitado desde cat_location.");
        return;
    }
    $buttonsList = [];
    foreach ($buttons as $btn) {
        $buttonsList[] = [
            "type" => "reply",
            "reply" => [
                "id" => $btn["id"],
                "title" => $btn["title"]
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
                "text" => $bodyText
            ],
            "action" => [
                "buttons" => $buttonsList
            ]
        ]
    ];
    logger("infoButtons: ".json_encode($buttons));
       $button_titles = array_map(function($b){ 
        return $b['title']; 
    }, $buttons);
        logger("button_titles: ".json_encode($button_titles));
    $concatenado = $bodyText . " | " . implode(" | ", $button_titles);
    
    $url = "https://graph.facebook.com/v23.0/$phoneNumberId/messages";
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
    

    logger(">>>".$concatenado);
    logger("Botones interactivos enviados: ".$response);
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

    return $response;
}

//------------------------------ END FUNCTIONS ----------------------------------//
function normalizeText($text) {
    $text = mb_strtolower($text, 'UTF-8');
    $text = str_replace(
        ['á','é','í','ó','ú','ñ'],
        ['a','e','i','o','u','n'],
        $text
    );
    $text = preg_replace('/[^a-z0-9 ]/u', '', $text); // ← conserva espacios
    return trim($text);
}

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
    // file_put_contents('procesarRespuesta.txt', date("Y-m-d H:i:s").":".print_r($msg, true) . PHP_EOL, FILE_APPEND);
}

function getMediaUrl($media_id, $access_token) {
    $url = "https://graph.facebook.com/v23.0/$media_id";
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
