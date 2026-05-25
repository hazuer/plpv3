<?php
require __DIR__ . '/../vendor/autoload.php';
require_once('../includes/functions.php');

use Google\Cloud\Vision\V1\Client\ImageAnnotatorClient;
use Google\Cloud\Vision\V1\AnnotateImageRequest;
use Google\Cloud\Vision\V1\BatchAnnotateImagesRequest;
use Google\Cloud\Vision\V1\Feature;
use Google\Cloud\Vision\V1\Feature\Type;
use Google\Cloud\Vision\V1\Image;

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
    try {
        if (!isset($_POST['image'])) {
            throw new Exception('Imagen no recibida');
        }
        $imageBase64 = $_POST['image'];
        $extension = 'jpg';
        if (strpos($imageBase64, 'image/png') !== false) {
            $extension = 'png';

        }
        // Clean BASE64
        $imageBase64 = preg_replace('/^data:image\/\w+;base64,/', '', $imageBase64);
        $imageBase64 = str_replace(' ', '+', $imageBase64);
        // Decode
        $imageContent = base64_decode($imageBase64);
        if (!$imageContent) {
            throw new Exception('Base64 inválido');
        }
        // save temp image
        $imagePath = __DIR__ . '/temp_ocr.' . $extension;
        file_put_contents($imagePath, $imageContent);

        if (!file_exists($imagePath)) {
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
        $invalidLinesByAddress = ['TO','Q','KO','CVJS','CVL','MLX','MEX','MEY','Radi','it','JIU','FMD','000','0001'];
       
        $name       = getName($lines, $invalidLinesByName);
        $phone      = getPhone($fullText);
        $postalCode = getPostalCode($fullText);
        $address    = getAddress($lines, $name, $phone, $postalCode, $invalidLinesByAddress);

        // close client
        $imageAnnotator->close();
        // Delete temp image
        if (file_exists($imagePath)) {
            //unlink($imagePath);
        }

        $responseData= [
            'success'  => true,
            'text'     => $fullText,
            'name'     => $name,
            'phone'    => $phone,
            'address'  => $address,
            'fullText' => $fullText,
            'postalCode' => $postalCode
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
    $phone = '';
    // dividir líneas
    $lines = preg_split('/\r\n|\r|\n/',$fullText);

    foreach($lines as $line){
        if(preg_match('/tel|telefono|\+52|\d{10}/iu',$line)){

            // extraer solo números
            preg_match_all('/\d/',$line,$matches);

            $digits = implode('',$matches[0]);

            // tomar últimos 10
            if(strlen($digits) >= 10){
                $phone = substr($digits,-10);
                return $phone;
            }
        }
    }

    preg_match_all('/\d/',$fullText,$matches);
    $digits = implode('',$matches[0]);
    if(strlen($digits) >= 10){
        $phone = substr($digits,-10);
    }

    return $phone;
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
    if(preg_match('/\b(\d{5})\b/',$fullText,$cpMatch)){
        $postalCode = $cpMatch[1];
    }
    return $postalCode;
}

function saveDataOcr(){

    $qr      = $_POST['qr'] ?? '';
    $name    = $_POST['name'] ?? '';
    $phone   = $_POST['phone'] ?? '';
    $address = $_POST['address'] ?? '';
    $postalCode = $_POST['postalCode'] ?? '';

    // =========================================
    // GUARDAR EN BD
    // =========================================

    // ejemplo temporal
    $initial = !empty($name) ? mb_strtoupper(mb_substr(trim($name), 0, 1)) : '-';
    $folio = rand(100,999);

    $responseData = [
        'success' => true,
        'message' => 'Datos guardados correctamente',
        'initial' => $initial,
        'folio'   => $folio,
        'qr'      => $qr,
        'name'    => $name,
        'phone'   => $phone,
        'address' => $address,
        'postalCode' => $postalCode
    ];
    jsonResponse($responseData);
}