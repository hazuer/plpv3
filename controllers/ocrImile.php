<?php
use Zxing\QrReader;


class OcrImile {

    public function getTrackingImile($imagePath, $fullText = ''){
        // 1. Intentar QR
        $tracking = $this->getTrackingImileFromQR($imagePath);
        if (!empty($tracking)) {
            writeLog('Tracking iMile obtenido desde QR: ' . $tracking);
            return $tracking;
        }
//TODO:: read from barcode from o like HTML5 QR Code Scanner

        writeLog('No se pudo leer QR, intentando OCR...');
        // 2. Intentar OCR
        $tracking = $this->getTrackingImileFromOCR($fullText);
        if (!empty($tracking)) {
            writeLog('Tracking iMile obtenido desde OCR: ' . $tracking);
            return $tracking;
        }
        return null;
    }

    public function getTrackingImileFromQR($imagePath){
        try {
            $qrcode = new QrReader($imagePath);
            $textQR = trim((string)$qrcode->text());

            if (empty($textQR)) {
                return null;
            }

            writeLog('QR iMile detectado: ' . $textQR);

            if (preg_match('/\b(IM\d{14}|\d{13,14})\b/i', $textQR, $match)) {
                return strtoupper($match[1]);
            }

            return null;

        } catch (Exception $e) {
            writeLog('Error leyendo QR iMile: ' . $e->getMessage());
            return null;
        }
    }

    public function getTrackingImileFromOCR($fullText){
        $text = strtoupper($fullText);

        // Prioridad 1: códigos IM
        if (preg_match('/\b(IM\d{14})\b/i', $text, $match)) {
            return strtoupper($match[1]);
        }

        preg_match_all('/\b(\d{13,14})\b/', $text, $matches);

        if (empty($matches[1])) {
            return null;
        }

        foreach ($matches[1] as $candidate) {

            // Prefijos observados en las guías iMile
            if (
                preg_match('/^(605|604|487|488)/', $candidate)
            ) {
                return $candidate;
            }
        }
        return $matches[1][0];
    }

    public function getPhoneImile($fullText){
        $lines = array_values(
            array_filter(
                array_map('trim', preg_split('/\r\n|\r|\n/', strtoupper($fullText)))
            )
        );
        $anchorIndex = null;

        /*
        * PRIORIDAD 1: Último CP encontrado
        */
        foreach ($lines as $index => $line) {
            if (preg_match('/^\d{5}$/', $line)) {
                $anchorIndex = $index;
            }
        }

        /*
        * PRIORIDAD 2: MORELOS + MEX
        */
        if ($anchorIndex === null) {
            foreach ($lines as $index => $line) {

                $normalized = preg_replace('/[^A-Z]/', '', $line);

                if (
                    strpos($normalized, 'MORELOS') !== false &&
                    strpos($normalized, 'MEX') !== false
                ) {
                    $anchorIndex = $index;
                }
            }
        }

        /*
        * PRIORIDAD 3: MORELOS
        */
        if ($anchorIndex === null) {
            foreach ($lines as $index => $line) {

                $normalized = preg_replace('/[^A-Z]/', '', $line);

                if (strpos($normalized, 'MORELOS') !== false) {
                    $anchorIndex = $index;
                }
            }
        }

        /*
        * PRIORIDAD 4: CVJS34
        */
        if ($anchorIndex === null) {
            foreach ($lines as $index => $line) {

                if (strpos($line, 'CVJS34') !== false) {
                    $anchorIndex = $index;
                }
            }
        }

        if ($anchorIndex === null) {
            writeLog('No se encontró ancla para teléfono iMile');
            return null;
        }

        writeLog('Ancla iMile encontrada en línea: ' . $lines[$anchorIndex]);

        /*
        * Buscar teléfono hacia arriba desde el ancla
        */
        for ($i = $anchorIndex; $i >= 0; $i--) {

            preg_match_all('/(?:\+?52)?\d{10}/', $lines[$i], $matches);

            if (empty($matches[0])) {
                continue;
            }

            foreach ($matches[0] as $candidate) {

                $phone = preg_replace('/\D/', '', $candidate);

                // 52 + 10 dígitos
                if (strlen($phone) == 12 && substr($phone, 0, 2) == '52') {
                    return substr($phone, 2);
                }

                // 10 dígitos
                if (strlen($phone) == 10) {
                    return $phone;
                }
            }
        }
        return null;
    }

    public function getNameImile($fullText,$phone){
        $lines = array_values(
            array_filter(
                array_map('trim', preg_split('/\r\n|\r|\n/', $fullText))
            )
        );

        foreach ($lines as $index => $line) {
            $normalizedLine = preg_replace('/\D/', '', $line);
            // Comparar contra teléfono encontrado
            if (strpos($normalizedLine, $phone) !== false) {
                // Buscar hacia arriba el primer texto válido
                for ($i = $index - 1; $i >= 0; $i--) {
                    $candidate = trim($lines[$i]);
                    if (empty($candidate)) {
                        continue;
                    }
                    // Ignorar tracking
                    if (preg_match('/^(IM\d{14}|\d{13,14})$/i', $candidate)) {
                        continue;
                    }
                    // Ignorar CVJS34
                    if (stripos($candidate, 'CVJS34') !== false) {
                        continue;
                    }
                    return $candidate;
                }
            }
        }
        return null;
    }

    public function getPostalCodeImile($fullText){
        $text      = strtoupper($fullText);
        $anchorPos = false;

        if (($anchorPos = strripos($text, 'MORELOS')) === false) {
            $anchorPos = strripos($text, 'MEX');
        }

        if ($anchorPos !== false) {
            $tail = substr($text, $anchorPos);
            if (preg_match('/\b(\d{5})\b/', $tail, $match)) {
                return $match[1];
            }
        }

        // Fallback
        preg_match_all('/\b\d{5}\b/', $text, $matches);

        if (!empty($matches[0])) {
            return end($matches[0]);
        }

        return null;
    }

    public function getAddressImile($fullText,$phone){

        if (empty($phone)) {
            return null;
        }

        $lines = array_values(
            array_filter(
                array_map('trim', preg_split('/\r\n|\r|\n/', $fullText))
            )
        );

        $phoneIndex = null;
        $endIndex   = null;

        // Buscar teléfono
        foreach ($lines as $index => $line) {
            $normalized = preg_replace('/\D/', '', $line);
            if (strpos($normalized, $phone) !== false) {
                $phoneIndex = $index;
                break;
            }
        }

        if ($phoneIndex === null) {
            return null;
        }

        // Buscar delimitador final (CP o Morelos)
        for ($i = $phoneIndex + 1; $i < count($lines); $i++) {
            $line = strtoupper(trim($lines[$i]));
            if (
                preg_match('/^\d{5}$/', $line) ||
                strpos($line, 'MORELOS') !== false
            ) {
                $endIndex = $i;
                break;
            }
        }

        if ($endIndex === null) {
            return null;
        }

        $addressParts = [];
        for ($i = $phoneIndex + 1; $i < $endIndex; $i++) {
            $line = trim($lines[$i]);
            if (empty($line)) {
                continue;
            }
            if (strtoupper($line) === 'MEX') {
                continue;
            }
            $addressParts[] = $line;
        }

        return trim(implode(' ', $addressParts));
    }

    public function validateRecipientImile($phone, $ocrName){
        global $db;

        $response = [
            'phoneExists'       => false,
            'phoneStatus'       => 'new',
            'nameStatus'        => 'new',
            'allowAutoRegister' => false,
            'status'            => 'new_contact',
            'ocrName'           => $ocrName,
            'suggestedName'     => '',
            'score'             => 0
        ];

        $phone = preg_replace('/\D/', '', $phone);
        if(strlen($phone) != 10){
            return $response;
        }

        $sql = "SELECT contact_name 
            FROM cat_contact 
            WHERE phone = '".$phone."'
        ";

        $rows = $db->select($sql);
        if(empty($rows)){
            return $response;
        }

        $response['phoneExists'] = true;
        $response['phoneStatus'] = 'found';
        $ocrClean                = $this->normalizeText($ocrName);
        $bestScore               = 0;
        $bestMatch               = '';

        foreach($rows as $row){
            $dbName  = trim($row['contact_name']);
            $dbClean = $this->normalizeText($dbName);
            // MATCH EXACTO
            if($ocrClean === $dbClean){

                $response['allowAutoRegister'] = true;
                $response['status']            = 'auto_register';
                $response['nameStatus']        = 'exact';
                $response['suggestedName']     = $dbName;
                $response['score']             = 100;

                return $response;
            }
            similar_text($ocrClean,$dbClean,$score);
            // BONUS POR PREFIJO
            if(
                strpos($dbClean, $ocrClean) === 0
                ||
                strpos($ocrClean, $dbClean) === 0
            ){
                $score += 20;
            }
            // BONUS POR PALABRAS
            $ocrWords = explode(' ', $ocrClean);
            $dbWords  = explode(' ', $dbClean);
            $matches  = 0;

            foreach($ocrWords as $i => $ocrWord){
                if(!isset($dbWords[$i])){
                    continue;
                }
                if(
                    strpos($dbWords[$i], $ocrWord) === 0
                    ||
                    strpos($ocrWord, $dbWords[$i]) === 0
                ){
                    $matches++;
                }
            }
            $score += ($matches * 5);
            if($score > 100){
                $score = 100;
            }

            if($score > $bestScore){
                $bestScore = $score;
                $bestMatch = $dbName;
            }
        }
        $response['suggestedName'] = $bestMatch;
        $response['score']         = round($bestScore, 2);
        // REGLAS DE NEGOCIO
        if($bestScore >= 90){
            $response['allowAutoRegister'] = true;
            $response['status']            = 'auto_register';
            $response['nameStatus']        = 'exact';

        }elseif($bestScore >= 80){
            $response['status']     = 'suggest_name';
            $response['nameStatus'] = 'similar';
        }else{

            $response['status']     = 'new_variant';
            $response['nameStatus'] = 'variant';
        }
        return $response;
    }

    public function normalizeText($text){
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
}