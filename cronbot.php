<?php
// session_start();
//ini_set('display_errors',1);
//error_reporting(E_ALL);

define( '_VALID_MOS', 1 );
//echo date("Y-m-d H:i:s")."\n";
require_once('includes/configuration.php');
require_once('includes/DBW.php');
date_default_timezone_set('America/Mexico_City');



$db = new DB(HOST,USERNAME,PASSWD,DBNAME,PORT,SOCKET);

$dataLocation = ["1","2"];
foreach ($dataLocation as $location) {
    $id_location = $location;

    $sqlLocationInfo = "SELECT * FROM cat_location WHERE id_location = '".$id_location."'";
    $infoLocation    = $db->select($sqlLocationInfo);
    $token           = $infoLocation[0]['token'];
    $wabaPhone       = $infoLocation[0]['phone_waba'];
    $phoneNumberId   = $infoLocation[0]['phone_number_id'];
    $enableBot       = $infoLocation[0]['enable_bot'];
    $location_desc   = $infoLocation[0]['location_desc'];
    $horarioJson     = $infoLocation[0]['schedule'] ?? '';
    
    logger("Location: ".$id_location." - Enable Bot: ".$enableBot." - WABA Phone: ".$wabaPhone." - Location Desc: ".$location_desc);

    $sql = "SELECT 
        cc.phone 
    FROM package p 
    LEFT JOIN cat_contact cc ON cc.id_contact = p.id_contact 
    WHERE p.id_location IN ($id_location) 
    AND p.id_status IN (2) 
    GROUP BY cc.phone
    ";
    $packages = $db->select($sql);
    
    foreach ($packages as $data) {
        $from = "521".$data['phone'];
        $sql = "SELECT datelog,source 
        FROM waba_callbacks 
        WHERE 1 
        AND (raw_json LIKE '%".$from."%') 
        AND (source = 'template') 
        ORDER BY id_log DESC 
        LIMIT 1";
        $result = $db->select($sql);
        $t      = count($result);
        if($t>0){
            $dateNow   = date("Y-m-d H:i:s");
            $datelog   = $result[0]['datelog'];
            $aun_no_24hrs = (strtotime($dateNow) - strtotime($datelog)) < 86400;
            if ($aun_no_24hrs) {
                
                $respuestaHorario = getHorarioHoy($horarioJson);

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
                $txtGuias="Total de paquetes listos para recoger: ".$total."\n";
                if ($total >= 1) {
                    foreach ($rst as $row) {
                        $txtGuias .= "- ".$row['parcel']."\n*".$row['tracking']."*\n";
                    }
                }
                logger("dateNow: ".$dateNow);
                logger("datelog: ".$datelog);
                logger("valor aun_no_24hrs: ".$aun_no_24hrs);
                logger("Aun no han pasado 24 hrs para: ".$from);
                $texto="Hola ✋ \n
 Solo para recordarte que tu pedido sigue listo para recoger en:\n*".($infoLocation[0]['address'] ?? '')."*\nPara ver cómo llegar, solo haz clic aquí:\n" . ($infoLocation[0]['address_share'] ?? '') . "\nNuestro horario de atención es:\n".$respuestaHorario."\nPuedes acudir por tu paquete en cualquier momento dentro de ese horario.\n".$txtGuias."¡Te esperamos pronto!";
logger("from: ".$from);
logger("texto: ".$texto);
                sendText($from, $token, $phoneNumberId, $texto, $wabaPhone,$enableBot);
            }else{
                //var_dump("Ya pasaron 24 hrs para: ".$from);
            }   
        }
        usleep(500000); // 0.5 segundos
    }
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

function logger($msg){
    file_put_contents('cronbot.txt', date("Y-m-d H:i:s").":".print_r($msg, true) . PHP_EOL, FILE_APPEND);
}