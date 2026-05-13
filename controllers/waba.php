<?php
session_start();
#error_reporting(E_ALL);
#ini_set('display_errors', '1');
// Cambia el límite de ejecución a 600 segundos (10 minutos)
ini_set('max_execution_time', 800);
set_time_limit(800);
ini_set('memory_limit', '512M');

define( '_VALID_MOS', 1 );

require_once('../includes/configuration.php');
require_once('../includes/DBW.php');
$db = new DB(HOST,USERNAME,PASSWD,DBNAME,PORT,SOCKET);

header('Content-Type: application/json; charset=utf-8');


switch ($_POST['option']) {
	case 'sendTemplate':
		$result   = [];
		$success  = 'false';
		$dataJson = [];
		$message  = 'Error al enviar el mensaje';

		$id_location   = $_POST['id_location'];
		$id_estatus    = $_POST['mBEstatus'];
		$mbIdCatParcel = $_POST['mbIdCatParcel'];
		$nameTemplate = $_POST['nameTemplate'];
		$camposPlantilla = json_decode($_POST['campos_plantilla'] ?? "{}", true);
		$locationLnk = $_POST['location_lnk'];
		$txtTemplate   = $_POST['txtTemplate'];
		$tokenWaba     = $_POST['tokenWaba'];
		$wabaPhone     = $_POST['phoneWaba'];
		$phoneNumberId = $_POST['phoneNumberId'];

		$idParceIn = ($mbIdCatParcel==99) ? '1,2,3': $mbIdCatParcel;
		$number    = $_POST['number']; // Solo un número
		$n_user_id = $_SESSION["uId"];

		$sql ="SELECT 
			cc.phone,
			cc.id_contact_type,
			GROUP_CONCAT(p.id_package) AS ids,
			GROUP_CONCAT('(',p.folio,')-',p.tracking,'' SEPARATOR '\n') AS folioGuias 
			FROM package p 
			INNER JOIN cat_contact cc ON cc.id_contact=p.id_contact 
			WHERE 
			p.id_location IN (".$id_location.") 
			AND p.id_status IN (".$id_estatus.") 
			AND cc.phone IN(".$number.") 
			AND p.id_cat_parcel IN(".$idParceIn.") 
			AND p.is_verified IN(1) 
			GROUP BY cc.phone
		";
		$rst = $db->select($sql);
		#var_dump($rst);
		#die();

		if(count($rst)>0){
			$ids             = $rst[0] ? $rst[0]['ids'] : 0;
			$id_contact_type = $rst[0] ? $rst[0]['id_contact_type'] : 0;
			$folioGuias      = $rst[0] ? $rst[0]['folioGuias'] : 0;

			$sqlGetName="SELECT c.contact_name 
			FROM cat_contact c 
				WHERE 
				c.id_location IN ($id_location) 
				AND c.phone IN('$number') 
				AND c.id_contact_status IN(1)
				ORDER BY c.c_date DESC LIMIT 2";
			$contacts       = $db->select($sqlGetName);
			$customNameUser = $number;
			if(count($contacts)==1){
				$customNameUser = $contacts[0]['contact_name'];
			}

			$idsArray       = explode(',', $ids);
			$totalRegistros = count($idsArray);
			$tguias         = "Total:".$totalRegistros." (Folio)-Guía: ".$folioGuias;

			$fullTemplate = str_replace("usuario_db", $customNameUser, $txtTemplate);
			$fullTemplate = str_replace("location_db", $locationLnk, $fullTemplate);
			$fullTemplate = str_replace("folios_db", $tguias, $fullTemplate);

			// valores de BD
			$bdValues = [
				"usuario_db"  => $customNameUser,
				// "location_db" => $locationLnk,
				"folios_db"   => $tguias
			];

			$componentParams = [];
			// recorrer exactamente los placeholders tal como vienen
			foreach ($camposPlantilla as $num => $valorCampo) {
				// si es un placeholder marcado como valor de BD
				if (substr($valorCampo, -3) === "_db") {

					// si existe en el arreglo, se reemplaza
					$realValue = $bdValues[$valorCampo] ?? "";
					$componentParams[] = [
						"type" => "text",
						"text" => $realValue
					];
				} else {
					// si no es de BD, se usa el valor ingresado por el usuario
					$componentParams[] = [
						"type" => "text",
						"text" => $valorCampo
					];
				}
			}

			$data = [
				"messaging_product" => "whatsapp",
				"to" => "521".$number,
				"type" => "template",
				"template" => [
					"name" => $nameTemplate,
					"language" => ["code" => "es"],
					"components" => [
						[
							"type" => "body",
							"parameters" => $componentParams
						]
					]
				]
			];
	
			$url = "https://graph.facebook.com/v23.0/".$phoneNumberId."/messages";
			#var_dump($url);
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, [
				"Authorization: Bearer $tokenWaba",
				"Content-Type: application/json"
			]);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

			// Ejecutar petición
			$response = curl_exec($ch);
			$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);

			// JSON de respuesta
			$fullLog    = json_encode(json_decode($response, true), JSON_UNESCAPED_UNICODE);
			$decoded    = json_decode($response, true);
			$message_id = $decoded['messages'][0]['id'] ?? 0; // devuelve null si no existe

			$newStatusPackage = 1;
			if ($httpCode >= 200 && $httpCode < 300) {
				//var_dump('Okas');
				$success  = 'true';
				$message  = "Mensaje enviado a $number";
				$newStatusPackage = ($id_estatus == 5 ? 5 : 2);

				//restart bot buttons
				$sql = "DELETE FROM waba_user_buttons WHERE phone = '521".$number."'";
            	$db->sqlPure($sql, false);
			} else {
				//var_dump('false');
				$success  = 'false';
				$message  = 'Error al enviar el mensaje';
				$newStatusPackage = 6;
			}

			$listIds = explode(",", $ids);
			$nDate   = date('Y-m-d H:I:s');
			foreach ($listIds as $id_package) {
			//var_dump('id_package',$id_package);
				$sqlSaveNotification = "INSERT INTO notification 
				(id_location,n_date,n_user_id,message,id_contact_type,sid,id_package,message_id) 
				VALUES 
				($id_location,'$nDate',$n_user_id,'$fullTemplate',$id_contact_type,'$fullLog',$id_package,'$message_id')";
				$db->sqlPure($sqlSaveNotification, false);

				$sqlLogger = "INSERT INTO logger 
				(datelog, id_package, id_user, new_id_status, old_id_status, desc_mov) 
				VALUES 
				('$nDate', $id_package, $n_user_id, $newStatusPackage, $id_estatus, 'Envío de Mensaje Meta, ".$message_id."')";
				$db->sqlPure($sqlLogger, false);

				$sqlUpdatePackage = "UPDATE package SET 
				n_date = '$nDate', n_user_id = '$n_user_id', id_status=$newStatusPackage 
				WHERE id_package IN ($id_package)";
				$db->sqlPure($sqlUpdatePackage, false);

				$date    = date("Y-m-d H:i:s");
				$read_by = intval($_SESSION['uId']);
				$sent_by = intval($_SESSION['uId']);
				$sql = "INSERT INTO waba_callbacks (datelog, sender_phone, message_id, message_text, raw_json,is_read,read_at,read_by,source,id_location,sent_by) 
				VALUES ('$date', '$wabaPhone', '$message_id', '".addslashes($fullTemplate)."', '$response',1,'$date', $read_by, 'template',$id_location,$sent_by)";
				$inserted = $db->sqlPure($sql, false);
			}
		}else{
			$success  = 'false';
			$message  = "Número $number sin guías para procesar, mensaje no enviado";
		}
		echo json_encode(['success' => $success, 'message' => $message]);
	break;

	case 'getAllMessagesToRead':
		$id_location   = $_POST['id_location'];
		$phone     = $_POST['phone'] ?? '';
		$wabaPhone = $_POST['phoneWaba'];

		if (!$phone) {
			echo json_encode([]);
			exit;
		}

		$sql="SELECT 
			id_log,
			datelog,
			sender_phone,
			message_text,
			source,
			message_id,
			CASE 
				WHEN sender_phone = '".$wabaPhone."' THEN 'outgoing' 
				ELSE 'incoming' 
			END AS message_type,
			CASE
				WHEN source IN ('template', 'waba_response_to_user','bot')
				THEN (SELECT user FROM users WHERE id = sent_by LIMIT 1)
				ELSE ''
			END AS who_sent 
		FROM waba_callbacks 
		WHERE 
			(sender_phone = '".$wabaPhone."' AND raw_json LIKE '%".$phone."%') 
			OR (sender_phone = '".$phone."') 
		GROUP BY message_id 
		ORDER BY id_log DESC 
		LIMIT 50
		";
		$mensajes = $db->select($sql);

		echo json_encode($mensajes);
		break;

	case 'markAsRead':
		$tophone    = $_POST['tophone'] ?? '';

		if (!$tophone) {
			echo json_encode([]);
			exit;
		}
		// Marcar como leídos
		$sql = "UPDATE waba_callbacks 
		SET is_read = 1, 
			read_at = '" . date("Y-m-d H:i:s") . "', 
			read_by = " . intval($_SESSION['uId']) . " 
		WHERE sender_phone = '" .$tophone . "' AND is_read = 0";
		$rst = $db->sqlPure($sql, false);

		$sql  = "INSERT INTO waba_user_buttons (phone, button_id, datelog, bot_active) 
        		VALUES ('$tophone', 'btn_horario', '$date', 0)";
    	$db->sqlPure($sql, false);
		echo json_encode(['success' => true, 'data' => $rst]);
	break;
		
	case 'sendMessage':
		$id_location   = $_POST['id_location'];
		$tophone    = $_POST['tophone'] ?? '';
		$msj        = $_POST['msj'] ?? '';
		$tokenWaba  = $_POST['tokenWaba'];
		$wabaPhone  = $_POST['phoneWaba'];
		$phoneNumberId = $_POST['phoneNumberId'];

		if (empty($tophone) || empty($msj)) {
			echo json_encode(['success' => false, 'message' => 'Faltan datos.']);
			exit;
		}

		$url = "https://graph.facebook.com/v23.0/".$phoneNumberId."/messages";

		$payload = [
			"messaging_product" => "whatsapp",
			"to" => $tophone,
			"type" => "text",
			"text" => [
				"body" => $msj
			]
		];

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			"Authorization: Bearer $tokenWaba",
			"Content-Type: application/json"
		]);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

		$response = curl_exec($ch);
		$error = curl_error($ch);
		curl_close($ch);

		if ($error) {
			echo json_encode(['success' => false, 'message' => "cURL Error: $error"]);
			exit;
		}

		$decoded = json_decode($response, true);

		if (isset($decoded['messages'][0]['id'])) {
			$message_id = $decoded['messages'][0]['id']; // ID que regresa la API
			$date    = date("Y-m-d H:i:s");
			$read_by = intval($_SESSION['uId']);
			$sent_by = intval($_SESSION['uId']);
			$sql = "INSERT INTO waba_callbacks (datelog, sender_phone, message_id, message_text, raw_json,is_read,read_at,read_by,source,id_location,sent_by) 
            VALUES ('$date', '$wabaPhone', '$message_id', '".addslashes($msj)."', '$response',1,'$date', $read_by,'waba_response_to_user',$id_location,$sent_by)";
			$inserted = $db->sqlPure($sql, false);

			if ($inserted) {
				$sql  = "INSERT INTO waba_user_buttons (phone, button_id, datelog, bot_active) 
        		VALUES ('$tophone', 'btn_horario', '$date', 0)";
    			$db->sqlPure($sql, false);
				echo json_encode(['success' => true, 'data' => $decoded]);
			} else {
				echo json_encode(['success' => false, 'message' => 'Error al guardar en DB']);
			}
		} else {
			echo json_encode(['success' => false, 'message' => 'Error al enviar mensaje', 'response' => $decoded]);
		}

	break;

	case 'saveTempMeta':
		$result   = [];
		$success  = 'false';
		$dataJson = [];
		$message  = 'Error al guardar la plantilla';

		$name     = $_POST['mAdtName'];
		$id_cat_parcel = $_POST['mAdtParcel'];
		$template = $_POST['mAdtPlantilla'];
		try{

			$sql= "INSERT INTO cat_template (name, template, id_location, type_template, id_cat_parcel) 
				VALUES 
			('".$name."', '".$template."', 1, 2, ".$id_cat_parcel.")";
			$newId = $db->sqlPure($sql, false);
			$result = [
				'success'  => "true",
				'dataJson' => [$newId],
				'message'  => "Plantilla guardada"
			];
		}catch (Exception $e) {
			$result = [
				'success'  => $success,
				'dataJson' => $dataJson,
				'message'  => $message.": ".$e->getMessage()
			];
		}
		echo json_encode($result);

	break;

	case 'getTemplateMeta':
		$idTemplate     = $_POST['idTemplate'];
		$sql  = "SELECT template FROM cat_template WHERE 1 AND id_template = ".$idTemplate;
		$rstT = $db->select($sql);
		$template = $rstT[0]['template'];
		$result = [
				'success'  => "true",
				'dataJson' => $template,
				'message'  => ''
			];
		echo json_encode($result);
	break;

	case 'infoGuias':
		$tophone    = $_POST['tophone'] ?? '';

		if (!$tophone) {
			echo json_encode([]);
			exit;
		}
		$phone10 = substr($tophone, -10);
		$sql = "SELECT 
			cp.parcel,
			p.tracking,
			p.folio,
			s.status_desc
		FROM
			package p 
			INNER JOIN cat_contact cc ON cc.id_contact = p.id_contact 
			INNER JOIN cat_parcel cp ON cp.id_cat_parcel = p.id_cat_parcel 
			INNER JOIN cat_status s ON s.id_status = p.id_status 
		WHERE 
			cc.phone IN('".$phone10."') 
			AND p.id_status NOT IN(3,4,8)";
		$rst   = $db->select($sql);
		$total = count($rst);

		$jsonRst = ['success' => false, 'data' => []];
		if ($total >= 1) {
			$jsonRst = ['success' => true, 'data' => $rst];
		}
		echo json_encode($jsonRst);
	break;

	case 'deleteTemplateWaba':
		$id_template     = $_POST['id_template'];
		$sql = "DELETE FROM cat_template WHERE id_template = $id_template";
		$db->sqlPure($sql, false);
		echo json_encode(['success' => true, 'data' => '']);
	break;
	
	case 'loadPhonesAuto':
		$id_location   = $_POST['id_location'];
		$mbIdCatParcel   = $_POST['mbIdCatParcel'];
		$idParceIn       = ($mbIdCatParcel==99) ? '1,2,3': $mbIdCatParcel;
		$id_estatus       = $_POST['mBEstatus'];
		$sql = "SELECT DISTINCT cc.phone 
			FROM package p 
			INNER JOIN cat_contact cc 
				ON cc.id_contact = p.id_contact 
			WHERE 
				p.id_location IN (".$id_location.") 
				AND p.id_status IN (".$id_estatus.") 
				AND p.id_cat_parcel IN (".$idParceIn.") 
				ORDER BY cc.phone ASC";
		$rst   = $db->select($sql);
		$total = count($rst);
		if ($total >= 1) {
			$jsonRst = ['success' => true, 'data' => $rst];
		}else{
			$jsonRst = ['success' => false, 'data' => []];
		}
		echo json_encode($jsonRst);
	break;

}