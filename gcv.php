<?php

//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);

header('Content-Type: application/json');

require __DIR__ . '/vendor/autoload.php';

use Google\Cloud\Vision\V1\Client\ImageAnnotatorClient;
use Google\Cloud\Vision\V1\AnnotateImageRequest;
use Google\Cloud\Vision\V1\BatchAnnotateImagesRequest;
use Google\Cloud\Vision\V1\Feature;
use Google\Cloud\Vision\V1\Feature\Type;
use Google\Cloud\Vision\V1\Image;
use Zxing\QrReader;

// =========================================
// LOG
// =========================================

function writeLog($message)
{
    file_put_contents(
        __DIR__ . '/gcv_log.txt',
        "[" . date('Y-m-d H:i:s') . "] " . $message . PHP_EOL,
        FILE_APPEND
    );
}

//writeLog('======================');
//writeLog('INICIO OCR AJAX');

// =========================================
// CREDENCIALES
// =========================================

putenv(
    'GOOGLE_APPLICATION_CREDENTIALS=' .
    __DIR__ . '/plp-vision-032a0b5d2d49.json'
);

try {

    // =====================================
    // VALIDAR IMAGEN
    // =====================================

    if (!isset($_POST['image'])) {

        throw new Exception('Imagen no recibida');

    }

    $imageBase64 = $_POST['image'];

    //writeLog('Imagen recibida por AJAX');

    // =====================================
    // DETECTAR EXTENSION
    // =====================================

    $extension = 'jpg';

    if (strpos($imageBase64, 'image/png') !== false) {

        $extension = 'png';

    }

    //writeLog('Extension detectada: ' . $extension);

    // =====================================
    // LIMPIAR BASE64
    // =====================================

    $imageBase64 = preg_replace(
        '/^data:image\/\w+;base64,/',
        '',
        $imageBase64
    );

    $imageBase64 = str_replace(' ', '+', $imageBase64);

    // =====================================
    // DECODIFICAR
    // =====================================

    $imageContent = base64_decode($imageBase64);

    if (!$imageContent) {

        throw new Exception('Base64 inválido');

    }

    //writeLog('Base64 decodificado');

    // =====================================
    // GUARDAR IMAGEN
    // =====================================

    $imagePath = __DIR__ . '/temp_ocr.' . $extension;

    file_put_contents($imagePath, $imageContent);

    //writeLog('Imagen guardada: ' . $imagePath);

    if (!file_exists($imagePath)) {

        throw new Exception('No se pudo guardar imagen');

    }

    // =====================================
    // LEER QR
    // =====================================

    $qr = '';

    try {

        //writeLog('Leyendo QR');

        $qrcode = new QrReader($imagePath);

        $qr = $qrcode->text();

        //writeLog('QR detectado: ' . $qr);

    } catch (Throwable $e) {

        //writeLog('ERROR QR: ' . $e->getMessage());

    }

    // =====================================
    // GOOGLE CLOUD VISION
    // =====================================

    //writeLog('Creando cliente Vision');

    $imageAnnotator = new ImageAnnotatorClient();

    //writeLog('Cliente creado');

    // IMAGE
    $image = new Image();

    $image->setContent($imageContent);

    // FEATURE OCR
    $feature = new Feature();

    $feature->setType(Type::TEXT_DETECTION);

    // REQUEST
    $annotateRequest = new AnnotateImageRequest();

    $annotateRequest->setImage($image);

    $annotateRequest->setFeatures([$feature]);

    // BATCH
    $batchRequest = new BatchAnnotateImagesRequest();

    $batchRequest->setRequests([$annotateRequest]);

    //writeLog('Ejecutando OCR');

    $response = $imageAnnotator->batchAnnotateImages($batchRequest);

    //writeLog('OCR ejecutado');

    $responses = $response->getResponses();

    $fullText = '';

    if (count($responses) > 0) {

        $texts = $responses[0]->getTextAnnotations();

        if (count($texts) > 0) {

            $fullText = $texts[0]->getDescription();

            writeLog('Texto detectado');

        }

    }

    // =====================================
    // EXTRAER DATOS
    // =====================================

    $name = '';
    $phone = '';
    $address = '';

    $lines = explode("\n", $fullText);

    foreach ($lines as $line) {

        $line = trim($line);

        if (!$line) {
            continue;
        }

        // =================================
        // TELEFONO
        // =================================

        if (
            preg_match('/(\+52|52)?[0-9]{10,13}/', $line)
            && !$phone
        ) {

            preg_match(
                '/(\+52|52)?[0-9]{10,13}/',
                $line,
                $matches
            );

            $phone = $matches[0];

            //writeLog('Telefono detectado: ' . $phone);

        }

        // =================================
        // NOMBRE
        // =================================

        if (
            preg_match('/^[A-ZÁÉÍÓÚÑ ]{8,}$/u', strtoupper($line))
            && !$name
        ) {

            $name = ucwords(strtolower($line));

            //writeLog('Nombre detectado: ' . $name);

        }

        // =================================
        // DIRECCION
        // =================================

        if (
            (
                stripos($line, '#') !== false ||
                stripos($line, 'col') !== false ||
                stripos($line, 'calle') !== false ||
                stripos($line, 'av') !== false ||
                stripos($line, 'avenida') !== false
            )
            && !$address
        ) {

            $address = $line;

            //writeLog('Direccion detectada: ' . $address);

        }

    }

    // =====================================
    // CERRAR CLIENTE
    // =====================================

    $imageAnnotator->close();

    //writeLog('FINAL OK');

    // =====================================
    // RESPUESTA JSON
    // =====================================

    echo json_encode([
        'success' => true,
        'qr' => $qr,
        'text' => $fullText,
        'name' => $name,
        'phone' => $phone,
        'address' => $address
    ]);

} catch (Throwable $e) {

    writeLog('ERROR: ' . $e->getMessage());
    writeLog('LINEA: ' . $e->getLine());
    writeLog('ARCHIVO: ' . $e->getFile());

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'line' => $e->getLine(),
        'file' => $e->getFile()
    ]);

}