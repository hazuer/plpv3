<?php
// Lista de dominios permitidos
$allowed_origins = [
    'https://jmx.jtjms-mx.com',
    'https://ds.imile.com'
];

// Detecta el origen de la petición
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

// Verifica si está en la lista
if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: $origin");
}

header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

// Manejo de preflight request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

define( '_VALID_MOS', 1 );

require_once('../includes/configuration.php');
require_once('../includes/DB.php');
$db = new DB(HOST,USERNAME,PASSWD,DBNAME,PORT,SOCKET);
$json = file_get_contents('php://input');
$request = json_decode($json, true); // Convierte a array asociativo
$_POST = array_merge($_POST, $request);

header('Content-Type: application/json; charset=utf-8');

switch ($_POST['option']) {
	case 'store':
		$result   = [];
		$success  = 'false';
		$dataJson = [];
		$message  = 'Error al guardar la infomación del paquete';
		$id_location         = $_POST['id_location'];
		$tracking            = $_POST['tracking'];
		$phone               = $_POST['phone'];
		$receiver            = $_POST['receiver'];
		$address             = $_POST['address'] ?? "";
		$id_user             = $_POST['id_user'];
		$marker              = $_POST['marker'];
		$id_cat_parcel       = $_POST['id_cat_parcel'];
		// Elimina los espacios al inicio y final
		$receiver = trim($receiver);
		// Reemplaza espacios múltiples entre palabras con un solo espacio
		$receiver = preg_replace('/\s+/', ' ', $receiver);
		$data['id_status']   = 1;
		$data['note']        = "";

		try {
			// Normalizamos el nombre y el teléfono (opcional pero útil)
			$phone    = trim($phone);
			$receiver = trim($receiver);

			// Validar si ya existe el contacto
			$sqlCheck = "SELECT id_contact FROM cat_contact WHERE phone IN ('".$phone."') AND contact_name IN('".$receiver."')  AND id_location IN(".$id_location.") AND id_contact_status = 1";
			$existing = $db->select($sqlCheck);

			if (empty($existing)) {
				$sqlCheckTypeContact="SELECT COUNT(id_contact_type) AS total FROM cat_contact AS cc WHERE phone = '".$phone."' AND id_contact_status = 1 AND id_contact_type IN(2)";
				$rstCheck = $db->select($sqlCheckTypeContact);
				$total = $rstCheck[0]['total'];
				$id_contact_type = ($total >= 1) ? 2 : 1;

				// Contacto nuevo, se inserta
				$contact = [
					'id_location'        => $id_location,
					'phone'              => $phone,
					'contact_name'       => $receiver,
					'id_contact_type'    => $id_contact_type, // SMS
					'id_contact_status'  => 1,
					'id_contact'         => null,
					'id_type_mode'       => 2
				];
				$id_contact = $db->insert('cat_contact', $contact);
			} else {
				// Ya existe, usamos el ID existente
				$id_contact = $existing[0]['id_contact'];
			}

			// Se asigna el contacto al dato actual
			$data['id_contact'] = $id_contact;

			if (empty($data['id_contact']) || $data['id_contact'] == 0 || $data['id_contact'] === null) {
				$success  = 'false';
				$dataJson = [];
				$message  = 'No se registro el usuario, vuelve a intentarlo';
			}else{
				$sqlCheck = "SELECT COUNT(tracking) total FROM package WHERE tracking IN ('".$tracking."')";
				$rstCheck = $db->select($sqlCheck);
				$total    = $rstCheck[0]['total'];
				$tmpSql    = "SELECT COUNT(tracking) total FROM package_tmp WHERE tracking IN ('".$tracking."')";
				$tmpResult = $db->select($tmpSql);
				$tmpTotal  = $tmpResult[0]['total'];

				if($total==0 && $tmpTotal==0){
					// Incrementa el folio o lo reinicia a 1 si llegó a 999, de forma segura
					$db->sqlPure("UPDATE folio 
						SET folio = LAST_INSERT_ID(
							CASE 
								WHEN folio >= 999 THEN 1 
								ELSE folio + 1 
							END
						)
						WHERE id_location = " . (int)$id_location
					);
					// Obtener el nuevo folio generado de forma segura para esta conexión
					$records = $db->select("SELECT LAST_INSERT_ID() AS nuevo_folio");
					$folio   = $records[0]['nuevo_folio'];
					$data['id_location'] = $id_location;
					$fecha_actual        = date("Y-m-d H:i:s");
					$data['id_package']  = null;
					$data['folio']       = $folio;
					$data['c_date']      = $fecha_actual;
					$data['c_user_id']   = $id_user;
					$data['tracking']    = $tracking;
					$data['id_cat_parcel']  = $id_cat_parcel;
					$data['id_type_mode']   = 2;
					$data['marker']         = $marker;
					$data['address']        = $address;
					$titleMsj  = 'Registrado';
					$msjFolios = "";

					$new_id_package = $db->insert('package_tmp',$data); //tmp table

					$success  = 'true';
					$dataJson = $msjFolios;
					$message  = $titleMsj;
				}else{
					$success  = 'false';
					$dataJson = [];
					$message  = 'El número de guía: '.$data['tracking'].' ya está registrado';
				}
			}
			$result = [
				'success'  => $success,
				'dataJson' => $dataJson,
				'message'  => $message
			];
		} catch (Exception $e) {
			$result = [
				'success'  => $success,
				'dataJson' => $dataJson,
				'message'  => $message.": ".$e->getMessage()
			];
		}
		echo json_encode($result);
	break;
}
