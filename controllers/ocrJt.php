<?php

class OcrJt {

    public function getPhoneJt($fullText){
        // NIVEL 1
        if (preg_match('/(?:\/?Tel\s*:?\s*)(\d{10,12})/i', $fullText, $match)) {
            return substr($match[1], -10); // Toma últimos 10
        }

        // NIVEL 2
        $lines = preg_split('/\r\n|\r|\n/', $fullText);
        foreach ($lines as $line) {
            if (!preg_match('/te[l1i]/i', $line)) {
                continue;
            }
            $digits = preg_replace('/\D/', '', $line);
            if (strlen($digits) >= 10) {
                return substr($digits, -10);
            }
        }

        // NIVEL 3
        if (preg_match('/C\.?P\.?\s*:?\s*\d{5}.*?(\d{10})/i',$fullText,$match)) {
            return $match[1];
        }
        return '';
    }

    public function getNameJt($fullText){
        // Caso ideal:
        // Nombre: Ramón pescador
        // C.P: 62785 /Tel: 7341396293

        if ( preg_match('/Nombre\s*:\s*(.*?)\s*(?:C\.?P\.?|\/?\s*Tel\s*:)/isu',$fullText,$match)) {
            $name = trim($match[1]);

            // limpiar espacios múltiples
            $name = preg_replace('/\s+/', ' ', $name);

            return $name;
        }

        // Fallback:
        // OCR pudo separar líneas correctamente
        $lines = preg_split('/\r\n|\r|\n/', $fullText);
        foreach ($lines as $line) {

            if (stripos($line, 'Nombre:') === false) {
                continue;
            }
            $name = preg_replace('/^.*?Nombre\s*:\s*/iu','',trim($line));
            $name = preg_replace('/\s+/', ' ', $name);

            if (!empty($name)) {
                return $name;
            }
        }
        return '';
    }

    public function getPostalCodeJt($fullText){
        // Buscar CP explícito
        if (preg_match('/C\.?P\.?\s*:?\s*(\d{5})/i',$fullText,$match)) {
            return $match[1];
        }

        // Fallback: línea que contiene CP
        $lines = preg_split('/\r\n|\r|\n/', $fullText);
        foreach ($lines as $line) {
            if (!preg_match('/c\.?p\.?/i', $line)) {
                continue;
            }
            if (preg_match('/(\d{5})/', $line, $match)) {
                return $match[1];
            }
        }
        return '';
    }

    public function getAddressJt($fullText){
        $lines = preg_split('/\r\n|\r|\n/', trim($fullText));

        $capture      = false;
        $addressLines = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }
            // Comenzar después del teléfono
            if (!$capture) {
                if (preg_match('/\/?\s*Tel\s*:/i', $line)) {
                    $capture = true;
                }
                continue;
            }
            // Bloque siguiente (Temu u otra etiqueta)
            if (
                preg_match('/^Nombre\s*:/i', $line)
                ||
                preg_match('/JMX\d{12}/i', $line)
                ||
                stripos($line, 'YC - Log for Temu') !== false
            ) {
                break;
            }
            $addressLines[] = $line;
        }
        $address = implode(' ', $addressLines);

        return trim(preg_replace('/\s+/', ' ', $address));
    }

    public function validateRecipientJt($phone, $ocrName){
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
            $dbName = trim($row['contact_name']);
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