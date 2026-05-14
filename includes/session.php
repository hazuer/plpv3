<?php
defined('_VALID_MOS') or die('Restricted access');
#error_reporting(E_ALL);
#ini_set('display_errors', '1');

if(!isset($_SESSION["uActive"])){
	// check if exist cookie
	if (isset($_COOKIE['uActive'])) {
		echo "<h1>reasignado session</h1>";
		$_SESSION["uId"]              = $_COOKIE['uId'] ?? null;
		$_SESSION["uName"]            = $_COOKIE['uName'] ?? null;
		$_SESSION["uLocation"]        = $_COOKIE['uLocation'] ?? null;
		$_SESSION["uLocationDefault"] = $_COOKIE['uLocationDefault'] ?? null;
		$_SESSION["uActive"]          = $_COOKIE['uActive'] ?? null;
		$_SESSION["uMarker"]          = $_COOKIE['uMarker'] ?? null;
		$_SESSION["uIdCatParcel"]     = $_COOKIE['uIdCatParcel'] ?? null;
		$_SESSION["session_token"]    = $_COOKIE['session_token'] ?? null;
		echo "de la cookie a la session";
	} else {
		echo "cerrar session1";
		header('Location: '.BASE_URL.'/login.php');
		die();
	}
}
$sql     = "SELECT session_token FROM users WHERE id = ".$_SESSION["uId"];
$dtstkn  = $db->select($sql);
$tokenDB = $dtstkn[0]['session_token'];

if (
    ($_SESSION['uName'] ?? '') !== 'isidoroc' &&
    (
        empty($_SESSION['session_token']) ||
        $_SESSION['session_token'] !== $tokenDB
    )
) {
    session_unset();
    session_destroy();

    // destroy cookies
    setcookie('uId', '', time() - 3600, '/');
    setcookie('uName', '', time() - 3600, '/');
    setcookie('uLocationDefault', '', time() - 3600, '/');
    setcookie('uLocation', '', time() - 3600, '/');
    setcookie('uActive', '', time() - 3600, '/');
    setcookie('uMarker', '', time() - 3600, '/');
    setcookie('session_token', '', time() - 3600, '/');

    header('Location: '.BASE_URL.'/login.php');
    die();
}

if(isset($_SESSION['uLocation'])){
	$_SESSION['uLocation'] = $_SESSION['uLocation'];
}else{
	$_SESSION['uLocation'] = $_SESSION['uLocationDefault'];
}

include_once('functions.php');
$fullSessionLocation = getCatLocationById($_SESSION['uLocation']);
$desc_loc = $fullSessionLocation[0]['location_desc'];


$sql ="SELECT 
    sender_phone,
    MAX(datelog) AS last_date,
    SUBSTRING_INDEX(
        GROUP_CONCAT(message_text ORDER BY datelog DESC SEPARATOR '|'),
        '|',
        1
    ) AS last_message 
FROM 
    waba_callbacks 
WHERE 
    is_read = 0 
GROUP BY 
    sender_phone 
ORDER BY 
    last_date DESC;";
$chats = $db->select($sql);
$htmlChat = '';
$totalMensajeSinLeer=0;

$idlx = $_SESSION['uLocation'];
foreach ($chats as $chat) {
    // Obtener número sin prefijo
    $numero = substr($chat['sender_phone'], -10);

    // Obtener información del contacto
    $sqlGetContac = "SELECT 
                        c.id_location,
                        c.contact_name 
                     FROM cat_contact c 
                     WHERE 
                        c.id_location IN ($idlx) 
                        AND c.phone IN('$numero')
                        AND c.id_contact_status IN (1)
                     ORDER BY c.c_date DESC 
                     LIMIT 1";

    $rstCheck = $db->select($sqlGetContac);
    $contact_name = $rstCheck[0]['contact_name'] ?? '';
    $locId = $rstCheck[0]['id_location'] ?? 0;

    // Solo agregar si pertenece a la ubicación seleccionada
    if ($locId == $idlx) {
		$totalMensajeSinLeer++;

        // Formatear fecha
        $formatter = new IntlDateFormatter(
            'es_ES',
            IntlDateFormatter::FULL,
            IntlDateFormatter::SHORT,
            'America/Mexico_City',
            IntlDateFormatter::GREGORIAN,
            'EEE, dd MMM, HH:mm'
        );
        $last_date = $formatter->format(strtotime($chat['last_date']));

        // Escapar mensaje para HTML
        $lastMessage = htmlspecialchars($chat['last_message']);

        // Concatenar HTML
        $htmlChat .= "<tr>
            <td>{$chat['sender_phone']}</td>
            <td>{$contact_name}</td>
            <td>{$lastMessage}</td>
            <td>{$last_date}</td>
            <td style='text-align: center;'>
                <div class='row'>
                    <div class='col-md-4'>
                        <span class='badge badge-pill badge-info' style='cursor: pointer;' id='btn-read-w' title='Leer' data-phone='{$chat['sender_phone']}'>
                            <i class='fa fa-whatsapp fa-lg' aria-hidden='true'></i>
                        </span>
                    </div>
                </div>
            </td>
        </tr>";
    }else{
        $sqlGetContac = "SELECT 
        c.id_location,
        c.contact_name 
        FROM cat_contact c 
        WHERE 1
        AND c.id_location IN(1,2)
        AND c.phone IN('$numero')
        AND c.id_contact_status IN (1)
        ORDER BY c.c_date DESC 
        LIMIT 1";
        $rstCheckx = $db->select($sqlGetContac);
        if(count($rstCheckx)==0){
            $formatter = new IntlDateFormatter(
                'es_ES',
                IntlDateFormatter::FULL,
                IntlDateFormatter::SHORT,
                'America/Mexico_City',
                IntlDateFormatter::GREGORIAN,
                'EEE, dd MMM, HH:mm'
            );
            $last_date = $formatter->format(strtotime($chat['last_date']));

            // Escapar mensaje para HTML
            $lastMessage = htmlspecialchars($chat['last_message']);

            // Concatenar HTML
            $htmlChat .= "<tr style=\"background-color:#FFB347;\">
                <td>{$chat['sender_phone']}</td>
                <td>Otro</td>
                <td>{$lastMessage}</td>
                <td>{$last_date}</td>
                <td style='text-align: center;'>
                    <div class='row'>
                        <div class='col-md-4'>
                            <span class='badge badge-pill badge-danger' style='cursor: pointer;' id='btn-read-w' title='Leer' data-phone='{$chat['sender_phone']}'>
                                <i class='fa fa-whatsapp fa-lg' aria-hidden='true'></i>
                            </span>
                        </div>
                    </div>
                </td>
            </tr>";
        }
    }
}

