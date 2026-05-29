<?php
require __DIR__ . '/../vendor/autoload.php';

use Google\Cloud\Vision\V1\Client\ImageAnnotatorClient;
use Google\Cloud\Vision\V1\AnnotateImageRequest;
use Google\Cloud\Vision\V1\BatchAnnotateImagesRequest;
use Google\Cloud\Vision\V1\Feature;
use Google\Cloud\Vision\V1\Feature\Type;
use Google\Cloud\Vision\V1\Image;

define( '_VALID_MOS', 1 );
require_once('../includes/configuration.php');
require_once('../includes/DB.php');
require_once('../includes/functions.php');
require_once('ocrJt.php');
$db = new DB(HOST,USERNAME,PASSWD,DBNAME,PORT,SOCKET);

header('Content-Type: application/json');
switch ($_POST['action']) {
    case 'processImage':
        processImage();
        break;
    case 'saveDataOcr':
        saveDataOcr();
        break;
}

function processImage(){
    putenv('GOOGLE_APPLICATION_CREDENTIALS=' . __DIR__ . '/../plp-vision-032a0b5d2d49.json');
    $filePath = '';
    try {
        if (!isset($_POST['image'])) {
            throw new Exception('Imagen no recibida');
        }
        $imageBase64 = $_POST['image'];
        // Clean BASE64
        $imageBase64 = preg_replace('/^data:image\/\w+;base64,/', '', $imageBase64);
        $imageBase64 = str_replace(' ', '+', $imageBase64);
        // Decode
        $imageContent = base64_decode($imageBase64);
        if (!$imageContent) {
            throw new Exception('Base64 inválido');
        }
        $idLocation = $_POST['idLocation'] ?? '';
        $imageName = $_POST['imageName'] ?? '';
        $pathLocation = '';
        $getSelectCatalog = getCatLocationById($idLocation);
        // Buscar el short_name según el id_location
        foreach ($getSelectCatalog as $location) {
            if ($location['id_location'] == $idLocation) {
                $pathLocation = strtolower(trim($location['location_desc']));
                break;
            }
        }
    
        // sanitizar nombre recibido
        $imageName = preg_replace('/[^a-zA-Z0-9_\-\.]/','',$imageName);

        // seguridad extra backend
        $nameFile = time().'_'.$imageName;
        $dirPath = '../evidence/'.$pathLocation.'/';

        // crear carpeta si no existe
        if(!is_dir($dirPath)){
            mkdir($dirPath, 0777, true);
        }

        $filePath = $dirPath.$nameFile;
        file_put_contents($filePath, $imageContent);

        if (!file_exists($filePath)) {
            throw new Exception(
                'No se pudo guardar imagen'
            );
        }

        // Google Vision API
        $imageAnnotator = new ImageAnnotatorClient();
        // Image
        $image = new Image();
        $image->setContent($imageContent);
        // Feature
        $feature = new Feature();
        $feature->setType(Type::DOCUMENT_TEXT_DETECTION);
        // Request
        $annotateRequest = new AnnotateImageRequest();
        $annotateRequest->setImage($image);
        $annotateRequest->setFeatures([$feature]);
        // Batch Request
        $batchRequest = new BatchAnnotateImagesRequest();
        $batchRequest->setRequests([$annotateRequest]);
        $response  = $imageAnnotator->batchAnnotateImages($batchRequest);
        $responses = $response->getResponses();
        $fullText  = '';

        if (count($responses) > 0) {
            $annotation = $responses[0]->getFullTextAnnotation();
            if ($annotation) {
                $fullText = $annotation->getText();
            }
        }
        // full text cleanup
        $fullText = trim((string)$fullText);
    
        $ocrJt      = new OcrJt();
        $trackingJT = $ocrJt->getTrackingJt($fullText);
        if(empty($trackingJT)){
            writeLog('No se pudo extraer tracking JT del OCR');
            jsonResponse([
                'success' => false,
                'message' => 'No se pudo extraer tracking JT del OCR',
            ]);
        }
        $phoneJt = $ocrJt->getPhoneJt($fullText);
        if(empty($phoneJt)){
            writeLog('No se pudo extraer teléfono del OCR');
            jsonResponse([
            'success' => false,
            'message' => 'No se pudo extraer teléfono del OCR',
            ]);
        }
        $nameJt = $ocrJt->getNameJt($fullText);

        $contactValidation = $ocrJt->validateRecipientJt($phoneJt,$nameJt);
        writeLog('OCR DB: ' . json_encode($contactValidation));

        $postalCode = $ocrJt->getPostalCodeJt($fullText);
        $address    = $ocrJt->getAddressJt($fullText);

        // close client
        $imageAnnotator->close();

        $responseData = [
            'success'  => true, //TODO
            'tracking' => $trackingJT,
            'phone'    => $phoneJt,
            'name'     => $nameJt,
            'address'  => $address,
            'postalCode' => $postalCode,
            //'fullText' => json_encode($contactValidation),
            'ocrDb'    => $contactValidation,
            'evidencePath'=> $filePath
        ];
        writeLog('result: ' . json_encode($responseData));
        jsonResponse($responseData);

    } catch (Throwable $e) {
        writeLog('ERROR: ' . $e->getMessage());
        writeLog('LINE: ' . $e->getLine());
        writeLog('FILE: ' . $e->getFile());
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage(),
            'line'    => $e->getLine(),
            'file'    => $e->getFile()
        ]);
    }
}

function saveDataOcr(){

    global $db;

    $idLocation    = $_POST['idLocation'] ?? '';
    $phone         = trim($_POST['phone'] ?? '');
    $tracking      = trim($_POST['qr'] ?? '');
    $name          = trim($_POST['name'] ?? '');
    $address       = trim($_POST['address'] ?? '');
    $postalCode    = trim($_POST['postalCode'] ?? '');
    $marker        = $_POST['packageColor'] ?? '';
    $id_cat_parcel = $_POST['courierType'] ?? '';
    $id_user       = $_SESSION["uId"];
    $evidencePath = trim($_POST['evidencePath'] ?? '');

    try{
        // =====================================
        // VALIDAR TRACKING DUPLICADO
        // =====================================
        $sqlCheck = "SELECT COUNT(tracking) total 
            FROM package 
            WHERE tracking IN ('".$tracking."')
        ";
        $rstCheck = $db->select($sqlCheck);
        $total    = $rstCheck[0]['total'];

        if($total > 0 ){
            $sqlPackage = "SELECT folio 
                FROM package 
                WHERE tracking = '".$tracking."' 
                LIMIT 1
            ";

            $rstPackage = $db->select($sqlPackage);
            writeLog('SQL: '.$sqlPackage);
            WriteLog('data: '.$rstPackage);
            $folioExistente =
            $rstPackage[0]['folio'] ?? '-';

            $initial =
                !empty($name)
                ? mb_strtoupper(
                    mb_substr(trim($name),0,1)
                )
                : '-';

            jsonResponse([
                'success'       => true,
                'alreadyExists' => true,
                'message'       => 'La guía ya existe',
                'initial'       => $initial,
                'folio'         => $folioExistente,
                'tracking'      => $tracking,
                'contact_name' => $name
            ]);
        }

        // =====================================
        // VALIDAR CONTACTO
        // =====================================
        $sqlCheck = "SELECT id_contact
            FROM cat_contact 
            WHERE 
                phone IN ('".$phone."') 
                AND contact_name IN('".$name."') 
                AND id_location IN(".$idLocation.") 
                AND id_contact_status = 1
        ";
        $existing = $db->select($sqlCheck);

        if(empty($existing)){

            $sqlCheckTypeContact = " SELECT COUNT(id_contact_type) AS total 
                FROM cat_contact 
                WHERE 
                    phone = '".$phone."' 
                    AND id_contact_status = 1 
                    AND id_contact_type IN(2)
            ";

            $rstCheck     = $db->select($sqlCheckTypeContact);
            $totalContact = $rstCheck[0]['total'];

            $id_contact_type =
                ($totalContact >= 1)
                ? 2
                : 1;

            $contact = [
                'id_location'       => $idLocation,
                'phone'             => $phone,
                'contact_name'      => $name,
                'id_contact_type'   => $id_contact_type,
                'id_contact_status' => 1,
                'id_contact'        => null,
                'id_type_mode'      => 3 //OCR
            ];

            $id_contact =
                $db->insert(
                    'cat_contact',
                    $contact
                );
        }else{
            $id_contact = $existing[0]['id_contact'];
        }

        // =====================================
        // VALIDAR CONTACTO
        // =====================================
        if(
            empty($id_contact)
            ||
            $id_contact == 0
        ){
            jsonResponse([
                'success' => false,
                'message' => 'No se pudo registrar contacto'
            ]);
        }

        // =====================================
        // GENERAR FOLIO
        // =====================================

        $db->sqlPure("UPDATE folio 
            SET folio = LAST_INSERT_ID( 
                CASE 
                    WHEN folio >= 999 THEN 1 
                    ELSE folio + 1 
                END 
            ) 
            WHERE id_location = ".(int)$idLocation."
        ");

        $records = $db->select("SELECT LAST_INSERT_ID() AS nuevo_folio");
        $folio   = $records[0]['nuevo_folio'];

        // =====================================
        // INSERT PACKAGE
        // =====================================

        $fecha_actual = date("Y-m-d H:i:s");
        $data = [
            'id_package'     => null,
            'id_location'    => $idLocation,
            'id_contact'     => $id_contact,
            'id_status'      => 1,
            'note'           => '',
            'folio'          => $folio,
            'c_date'         => $fecha_actual,
            'c_user_id'      => $id_user,
            'tracking'       => $tracking,
            'id_cat_parcel'  => $id_cat_parcel,
            'id_type_mode'   => 3, //OCR
            'marker'         => $marker,
            'address'        => $address,
            'cp'    => $postalCode
        ];

        $new_id_package = $db->insert('package',$data);
        if(
            !empty($evidencePath)
            &&
            file_exists($evidencePath)
        ){
            $evidence = [
                'id_package'  => $new_id_package,
                'id_user'     => $id_user,
                'path'        => $evidencePath,
                'id_location' => $idLocation
            ];

            $db->insert('evidence',$evidence);
        }

        // =====================================
        // RESPONSE
        // =====================================

        $initial =
            !empty($name)
            ? mb_strtoupper(
                mb_substr(
                    trim($name),
                    0,
                    1
                )
            )
            : '-';

        jsonResponse([
            'success' => true,
            'message' => 'Registrado correctamente',
            'initial' => $initial,
            'folio'   => $folio,
            'tracking'=> $tracking,
            'id_package' => $new_id_package,
            'contact_name' => $name
        ]);

    }catch(Exception $e){
        writeLog(
            'ERROR saveDataOcr: '
            .$e->getMessage()
        );
        jsonResponse([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}