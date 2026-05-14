<?php
require __DIR__ . '/../vendor/autoload.php';

use Google\Cloud\Vision\V1\Client\ImageAnnotatorClient;
use Google\Cloud\Vision\V1\AnnotateImageRequest;
use Google\Cloud\Vision\V1\BatchAnnotateImagesRequest;
use Google\Cloud\Vision\V1\Feature;
use Google\Cloud\Vision\V1\Feature\Type;
use Google\Cloud\Vision\V1\Image;

header('Content-Type: application/json');

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

    // =====================================================
    // EXTRAER DATOS
    // =====================================================

    $name = '';
    $phone = '';
    $address = '';
    $lines = explode("\n", $fullText);

    foreach ($lines as $line) {
        $line = trim($line);
        if (!$line) {
            continue;
        }
        // =============================================
        // PHONE
        // =============================================
        if (
            preg_match('/(\+52|52)?[0-9]{10,13}/', $line)
            && !$phone
        ) {
            preg_match('/(\+52|52)?[0-9]{10,13}/', $line, $matches);
            $phone = $matches[0];
            $phone = substr(preg_replace('/\D/', '', $phone), -10);

        }
        // =============================================
        // NAME
        // =============================================
        if (
            preg_match('/^[A-ZÁÉÍÓÚÑ ]{8,}$/u', strtoupper($line))
            && !$name
        ) {
            $name = ucwords(strtolower($line));
        }
        // =============================================
        // ADDRESS
        // =============================================

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
        }

    }

    // =====================================================
    // CLOSE CLIENT
    // =====================================================
    $imageAnnotator->close();
    // =====================================================
    // DELETE TEMP
    // =====================================================
    if (file_exists($imagePath)) {
        unlink($imagePath);
    }
    // =====================================================
    // RESPONSE
    // =====================================================
    echo json_encode([
        'success' => true,
        'text' => $fullText,
        'name' => $name,
        'phone' => $phone,
        'address' => $address

    ]);

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