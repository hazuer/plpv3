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
        /*$extension = 'jpg';
        if (strpos($imageBase64, 'image/png') !== false) {
            $extension = 'png';

        }*/
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
        // dividir líneas
        $lines = preg_split(
            '/\r\n|\r|\n/',
            $fullText
        );
        // limpiar líneas
        $lines = array_values(
            array_filter(
                array_map(
                    function($line){

                        return trim($line);

                    },
                    $lines
                )
            )
        );

        $invalidLinesByName    = ['TO','Q','KO','CVJS','CVL','MLX','MEX','MEY','Radi','it','JIU','FMD','000','0001','Zac','Zacatepec','tlaquiltenango'];
        $invalidLinesByAddress = ['TO','Q','KO','CVJS','CVL','MLX','MEX','MEY','Radi','it','JIU','NCB','FMD','000','0001'];
       
        $phone      = getPhone($fullText);
        if(empty($phone)){
            writeLog('No se pudo extraer teléfono del OCR');
            jsonResponse([
            'success' => false,
            'message' => 'No se pudo extraer teléfono del OCR',
            ]);
        }
        $ocrName    = getName($lines, $invalidLinesByName);
        //validar que el nombre del ocr coincida con alguno desde bd
        $ocrDb       = resolveRecipientName($phone, $ocrName, $fullText);
        $name = $ocrDb['name'];
            /*$ocrDb = [
        'name'           => $ocrName,
        'phoneExists'    => false,
        'isNewPhone'     => true,
        'exactMatch'     => false,
        'similarMatch'   => false,
        'bestSimilarity' => 0,
        'status'         => 'new_phone'
    ];*/
    writeLog('OCR DB: ' . json_encode($ocrDb));
        $postalCode = getPostalCode($fullText);
        $address    = getAddress($lines, $name, $phone, $postalCode, $invalidLinesByAddress);

        // close client
        $imageAnnotator->close();
        // Delete temp image
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        $responseData= [
            'success'  => true,
            'fullText' => $fullText.'<br>'.json_encode($ocrDb),
            'phone'    => $phone,
            'name'     => $name,
            'address'  => $address,
            'postalCode' => $postalCode,
            'ocrDb'    => $ocrDb
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
    }finally {

        if(
            !empty($filePath)
            &&
            file_exists($filePath)
        ){
            unlink($filePath);
        }
    }
}


function resolveRecipientName($phone, $ocrName, $fullText){

    global $db;

    // =========================================
    // RESPUESTA BASE
    // =========================================
    $response = [
        'name'           => $ocrName,
        'phoneExists'    => false,
        'isNewPhone'     => true,
        'exactMatch'     => false,
        'similarMatch'   => false,
        'bestSimilarity' => 0,
        'status'         => 'new_phone',
        'allowAutoRegister' => false
    ];

    // =========================================
    // LIMPIAR TELEFONO
    // =========================================
    $phone = preg_replace('/\D/', '', $phone);

    if(strlen($phone) != 10){
        return $response;
    }

    // =========================================
    // BUSCAR CONTACTOS
    // =========================================
    $sql = "
        SELECT id_contact, contact_name
        FROM cat_contact
        WHERE phone = '".$phone."'
    ";

    writeLog('SQL: '.$sql);

    $rows = $db->select($sql);

    // =========================================
    // TELEFONO NUEVO
    // =========================================
    if(empty($rows)){
        return $response;
    }

    // =========================================
    // TELEFONO EXISTE
    // =========================================
    $response['phoneExists'] = true;
    $response['isNewPhone']  = false;
    $response['status']      = 'phone_exists';

    // =========================================
    // DIVIDIR OCR EN LINEAS
    // =========================================
    $lines = preg_split('/\r\n|\r|\n/', $fullText);

    // agregar OCR name
    $lines[] = $ocrName;

    $bestMatch      = '';
    $bestSimilarity = 0;

    // =========================================
    // RECORRER CONTACTOS
    // =========================================
    writeLog('CONTACTOS ENCONTRADOS: '.json_encode($rows));
    foreach($rows as $row){

        $dbName = trim($row['contact_name']);
        $dbNameClean = normalizeText($dbName);

        // =====================================
        // RECORRER LINEAS OCR
        // =====================================
        foreach($lines as $line){

            $lineClean = normalizeText($line);

            // ignorar líneas pequeñas
            if(strlen($lineClean) < 5){
                continue;
            }

            // =================================
            // MATCH EXACTO
            // =================================
            if($lineClean == $dbNameClean){

                $response['name']           = $dbName;
                $response['exactMatch']     = true;
                $response['similarMatch']   = true;
                $response['bestSimilarity'] = 100;
                $response['status']         = 'exact_match';
                $response['allowAutoRegister'] = true;
                return $response;
            }

            if(
                strpos($dbNameClean, $lineClean) !== false
                ||
                strpos($lineClean, $dbNameClean) !== false
            ){

                $percent = 95;

                // priorizar nombres más largos
                $percent += strlen($dbNameClean) * 0.1;

                if($percent > $bestSimilarity){

                    $bestSimilarity = $percent;
                    $bestMatch      = $dbName;
                }
            }

            // =================================
            // SIMILITUD
            // =================================
            similar_text($lineClean, $dbNameClean, $percent);

            // =================================
            // BONUS POR PALABRAS
            // =================================
            $dbWords = explode(' ', $dbNameClean);

            $matches = 0;

            foreach($dbWords as $word){

                if(strlen($word) >= 4 && strpos($lineClean, $word) !== false){
                    $matches++;
                }
            }

            // bonus similitud
            $percent += ($matches * 20);
            if($percent > 100){
                $percent = 100;
            }

            // =================================
            // GUARDAR MEJOR MATCH
            // =================================
            if($percent > $bestSimilarity){

                $bestSimilarity = $percent;
                $bestMatch      = $dbName;
            }
        }
    }

    // =========================================
    // MATCH SIMILAR
    // =========================================
    if($bestSimilarity >= 55){

        $response['name']           = $bestMatch;
        $response['similarMatch']   = true;
        $response['bestSimilarity'] = $bestSimilarity;
        $response['status']         = 'similar_match';

        if($bestSimilarity >= 95){
            $response['allowAutoRegister'] = true;
        }

        return $response;
    }

    // =========================================
    // TELEFONO EXISTE
    // PERO NOMBRE NO COINCIDE
    // =========================================
    $response['bestSimilarity'] = $bestSimilarity;
    $response['status']         = 'phone_exists_name_no_match';

    return $response;
}


function normalizeText($text){
    // convertir a minúsculas
    $text = mb_strtolower(trim($text),'UTF-8');
    // quitar acentos
    $text = iconv('UTF-8','ASCII//TRANSLIT',$text);
    // quitar caracteres especiales
    $text = preg_replace('/[^a-z0-9\s]/',' ',$text);
    // quitar espacios múltiples
    $text = preg_replace('/\s+/',' ',$text);
    // trim final
    $text = trim($text);
    
    return $text;
}

function getName($lines, $invalidLines){

    $name = '';
    foreach($lines as $line){
        $candidate = trim($line);
        // quitar Nombre:
        $candidate = preg_replace('/^Nombre\s*:\s*/iu','',$candidate);
        // quitar TO
        $candidate = preg_replace('/^TO\s+/iu','',$candidate);
        // excluir basura
        if(in_array(mb_strtoupper($candidate),array_map('mb_strtoupper', $invalidLines))){
            continue;
        }

        // excluir líneas muy cortas
        if(mb_strlen($candidate) < 4){
            continue;
        }

        // excluir si contiene calle
        if(preg_match('/calle|direccion|address/iu',$candidate)){
            continue;
        }

        // excluir si tiene demasiados números
        if(preg_match_all('/\d/', $candidate) > 3){
            continue;
        }

        // debe tener letras
        if(!preg_match('/[a-záéíóúñ]/iu', $candidate)){
            continue;
        }
        $name = $candidate;
        break;
    }
    return $name;
}

function getPhone($fullText){

    $text = mb_strtolower($fullText,'UTF-8');
    $lines = preg_split('/\r\n|\r|\n/',$text);
    $candidates = [];
    foreach($lines as $line){
        // línea posible teléfono
        if(
            preg_match('/tel|telefono|cel|celular|movil|\+52|\d{10}/iu',$line)){
            // extraer grupos numéricos
            preg_match_all('/[\d\+\-\s\/]{10,20}/',$line,$matches);
            if(empty($matches[0])){
                continue;
            }
            foreach($matches[0] as $raw){
                // limpiar
                $digits = preg_replace('/\D/','',$raw);

                // =================================
                // quitar /+52 al final
                // =================================
                if(preg_match('/\/\+52\s*$/', $raw)){
                    if(substr($digits,-2) == '52'){
                        $digits = substr($digits,0,-2);
                    }
                }

                // =================================
                // quitar 52 al inicio
                // =================================
                if(substr($digits,0,2) == '52'&&strlen($digits) > 10){
                    $digits = substr($digits,2);
                }

                // =================================
                // quitar 01
                // =================================
                if(substr($digits,0,2) == '01'&&strlen($digits) > 10){
                    $digits = substr($digits,2);
                }

                // =================================
                // tomar SOLO primeros 10
                // =================================
                if(strlen($digits) > 10){
                    $digits = substr($digits,0,10);
                }

                // =================================
                // validar
                // =================================
                if(
                    preg_match('/^[0-9]{10}$/',$digits)
                    &&
                    !preg_match('/^(\d)\1{9}$/',$digits)
                    &&
                    !preg_match('/^800/',$digits)
                ){
                    $candidates[] = $digits;
                }
            }
        }
    }
    // =========================================
    // SIN CANDIDATOS
    // =========================================
    if(empty($candidates)){
        return '';
    }

    // =========================================
    // PRIORIZAR CELULARES MX
    // =========================================
    foreach($candidates as $phone){
        if(
            preg_match(
                '/^(72|73|74|75|76|77|55|56|81|33)/',
                $phone
            )
        ){
            return $phone;
        }
    }
    // fallback
    return $candidates[0];
}

function getAddress($lines, $name, $phone, $postalCode, $invalidLines){
    $address = '';
    $addressLines = [];
    foreach($lines as $line){
        $candidate = trim($line);

        // excluir nombre
        if( stripos($candidate, $name) !== false){
            continue;
        }

        // excluir teléfono
        if(strpos($candidate, $phone) !== false){
            continue;
        }

        // excluir CP
        if(strpos($candidate, $postalCode) !== false){
            $candidate = preg_replace(
                '/\b'.$postalCode.'\b/',
                '',
                $candidate
            );
        }

        // excluir basura OCR
        if(in_array(strtoupper($candidate),$invalidLines)){
            continue;
        }

        // excluir líneas muy cortas
        if(mb_strlen($candidate) < 4){
            continue;
        }
        // excluir Nombre:
        if(preg_match('/^Nombre\s*:/iu',$candidate)){
            continue;
        }
        // excluir líneas TEL
        if(preg_match('/tel|cp:/iu',$candidate)){
            continue;
        }
        // quitar TO
        $candidate = preg_replace('/^TO\s+/iu','',$candidate);
        $addressLines[] = $candidate;
    }

    // unir dirección
    $address = implode(', ',$addressLines);

    // limpiar múltiples espacios
    $address = preg_replace('/\s+/',' ',$address);

    // limpiar comas dobles
    $address = preg_replace('/,+/',',', $address);

    return $address;
}

function getPostalCode($fullText){
    $postalCode = '';
    // solo CP que inicien con 62
    if(preg_match('/\b(62\d{3})\b/',$fullText,$cpMatch)){
        $postalCode = $cpMatch[1];
    }

    return $postalCode;
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

    try{

        // =====================================
        // VALIDAR TRACKING DUPLICADO
        // =====================================

        $sqlCheck = "SELECT COUNT(tracking) total 
            FROM package 
            WHERE tracking IN ('".$tracking."')
        ";
        $rstCheck = $db->select($sqlCheck);
        $total = $rstCheck[0]['total'];

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
                'tracking'      => $tracking
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

            $rstCheck = $db->select($sqlCheckTypeContact);

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

            $id_contact =
                $existing[0]['id_contact'];
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

        $folio = $records[0]['nuevo_folio'];

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
            'address'        => $address
        ];

        $new_id_package =
            $db->insert(
                'package',
                $data
            );

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
            'id_package' => $new_id_package
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