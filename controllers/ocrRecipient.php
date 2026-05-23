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

        // Clean Ocr Text
        $fullText = trim((string)$fullText);
        $lines    = preg_split('/\r\n|\r|\n/', $fullText);
        // Clean empty lines and trim
        $lines    = array_values(array_filter(array_map(function($line){
            return trim($line);
        }, $lines)));

        $name       = '';
        $phone      = '';
        $address    = '';
        $postalCode = '';

        // Name
        if(isset($lines[0])){
            $possibleName = trim($lines[0]);
            // inavlaid words
            $invalidWords = [
                'calle',
                'direccion',
                'address',
                'colonia',
                'cp',
                'codigo',
            ];

            $isInvalid = false;
            foreach($invalidWords as $word){
                if(stripos($possibleName, $word) !== false){
                    $isInvalid = true;
                    break;
                }
            }
            if(!$isInvalid){
                $name = $possibleName;
            }
        }

        // Phone
        if(isset($lines[1])){
            // only numbers
            $digits = preg_replace(
                '/\D/',
                '',
                $lines[1]
            );
            // take last 10 digits
            $phone = substr($digits, -10);
        }

        // Address
        $addressLines = [];
        for($i = 2; $i < count($lines); $i++){
            $line = trim($lines[$i]);
            // buscar CP
            if(preg_match('/\b(\d{5})\b/', $line, $matches)){
                $postalCode = $matches[1];
            }
            // quitar "To"
            $line = preg_replace(
                '/^To\s+/i',
                '',
                $line
            );
            $addressLines[] = $line;
        }

        // unir dirección
        $address = implode(', ', $addressLines);
        // Clean address
        if($postalCode){
            $address = preg_replace(
                '/\b'.$postalCode.'\b/',
                '',
                $address
            );
        }
        // limpiar comas dobles
        $address = preg_replace(
            '/\s+,/',
            ',',
            $address
        );
        $address = preg_replace(
            '/,+/',
            ',',
            $address
        );
        $address = trim($address, ', ');

        // volver agregar CP al final
        if($postalCode){
            $address .= ', '.$postalCode;
        }
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

    $qr      = $_POST['qr'] ?? '';
    $name    = $_POST['name'] ?? '';
    $phone   = $_POST['phone'] ?? '';
    $address = $_POST['address'] ?? '';

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
        'address' => $address
    ];
    jsonResponse($responseData);
}