<?php
require __DIR__ . '/../vendor/autoload.php';

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

// =====================================================
// LOG
// =====================================================

function writeLog($message) {
    $logFile = __DIR__ . '/ocr.log';

    file_put_contents(
        $logFile,
        '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL,
        FILE_APPEND
    );
}

function processImage(){
// =========================================
// CREDENCIALES
// =========================================
putenv('GOOGLE_APPLICATION_CREDENTIALS=' . __DIR__ . '/../plp-vision-032a0b5d2d49.json');

try {

    // =====================================================
    // VALIDAR IMAGEN
    // =====================================================
    if (!isset($_POST['image'])) {
        throw new Exception('Imagen no recibida');
    }
    $imageBase64 = $_POST['image'];
    // =====================================================
    // DETECTAR EXTENSION
    // =====================================================
    $extension = 'jpg';
    if (strpos($imageBase64, 'image/png') !== false) {
        $extension = 'png';

    }
    // =====================================================
    // LIMPIAR BASE64
    // =====================================================
    $imageBase64 = preg_replace('/^data:image\/\w+;base64,/', '', $imageBase64);
    $imageBase64 = str_replace(' ', '+', $imageBase64);

    // =====================================================
    // DECODIFICAR
    // =====================================================
    $imageContent = base64_decode($imageBase64);
    if (!$imageContent) {
        throw new Exception('Base64 inválido');
    }

    // =====================================================
    // GUARDAR TEMPORAL
    // =====================================================
    $imagePath = __DIR__ . '/temp_ocr.' . $extension;
    file_put_contents($imagePath, $imageContent);

    if (!file_exists($imagePath)) {
        throw new Exception(
            'No se pudo guardar imagen'
        );
    }

    // =====================================================
    // GOOGLE CLOUD VISION
    // =====================================================
    $imageAnnotator = new ImageAnnotatorClient();
    // IMAGE
    $image = new Image();
    $image->setContent($imageContent);
    // FEATURE
    $feature = new Feature();
    $feature->setType(
        Type::DOCUMENT_TEXT_DETECTION
    );
    // REQUEST
    $annotateRequest = new AnnotateImageRequest();
    $annotateRequest->setImage($image);
    $annotateRequest->setFeatures([
        $feature
    ]);

    // BATCH
    $batchRequest = new BatchAnnotateImagesRequest();
    $batchRequest->setRequests([$annotateRequest]);

    $response = $imageAnnotator->batchAnnotateImages($batchRequest);
    $responses = $response->getResponses();
    $fullText = '';

    if (count($responses) > 0) {
        $annotation = $responses[0]->getFullTextAnnotation();
        if ($annotation) {
            $fullText = $annotation->getText();
        }
    }

    // =====================================
// LIMPIAR TEXTO OCR
// =====================================

$fullText = trim((string)$fullText);

$lines = preg_split('/\r\n|\r|\n/', $fullText);

// limpiar líneas vacías
$lines = array_values(array_filter(array_map(function($line){

    return trim($line);

}, $lines)));

$name = '';
$phone = '';
$address = '';
$postalCode = '';

// =====================================
// NOMBRE
// =====================================

if(isset($lines[0])){

    $possibleName = trim($lines[0]);

    // palabras inválidas
    $invalidWords = [
        'calle',
        'direccion',
        'address',
        'colonia',
        'cp',
        'codigo'
    ];

    $isInvalid = false;

    foreach($invalidWords as $word){

        if(
            stripos($possibleName, $word) !== false
        ){
            $isInvalid = true;
            break;
        }
    }

    if(!$isInvalid){

        $name = $possibleName;

    }

}

// =====================================
// TELEFONO
// =====================================

if(isset($lines[1])){

    // dejar solo números
    $digits = preg_replace(
        '/\D/',
        '',
        $lines[1]
    );

    // tomar últimos 10 dígitos
    $phone = substr($digits, -10);

}

// =====================================
// DIRECCION
// =====================================

$addressLines = [];

for($i = 2; $i < count($lines); $i++){

    $line = trim($lines[$i]);

    // buscar CP
    if(
        preg_match('/\b(\d{5})\b/', $line, $matches)
    ){
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

// =====================================
// LIMPIAR DIRECCION
// =====================================

// eliminar CP duplicado
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
    // =====================================================
    // CLOSE CLIENT
    // =====================================================
    $imageAnnotator->close();
    // =====================================================
    // DELETE TEMP
    // =====================================================
    if (file_exists($imagePath)) {
        //unlink($imagePath);
    }
    // =====================================================
    // RESPONSE
    // =====================================================
    $responseDate= [
        'success' => true,
        'text' => $fullText,
        'name' => $name,
        'phone' => $phone,
        'address' => $address,
        'fullText' => $fullText,
    ];
    echo json_encode($responseDate);
     writeLog('result: ' . json_encode($responseDate));

} catch (Throwable $e) {
    writeLog('ERROR: ' . $e->getMessage());
    writeLog('LINE: ' . $e->getLine());
    writeLog('FILE: ' . $e->getFile());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'line' => $e->getLine(),
        'file' => $e->getFile()
    ]);
}
}


function saveDataOcr(){

    $qr = $_POST['qr'] ?? '';
    $name = $_POST['name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $address = $_POST['address'] ?? '';

    // =========================================
    // GUARDAR EN BD
    // =========================================

    // ejemplo temporal
    $initial = 'A';
    $folio = rand(100,999);

    echo json_encode([
        'success' => true,
        'message' => 'Datos guardados correctamente',

        'initial' => $initial,
        'folio' => $folio,

        'qr' => $qr,
        'name' => $name,
        'phone' => $phone,
        'address' => $address
    ]);

}