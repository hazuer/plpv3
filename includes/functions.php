<?php
function getCatLocationById($id_location){
	global $db;
	$sqlCatLocation = "SELECT * FROM cat_location WHERE id_location = $id_location";
	$records        = $db->select($sqlCatLocation);
	return $records;
}

function getSelectCatLocationAll(){
	global $db;
	$sqlCatLocation = "SELECT id_location,location_desc FROM cat_location ORDER BY id_location ASC";
	$records        = $db->select($sqlCatLocation);
	return $records;
}

/**
 * Function to write messages to a log file
 * @param string $message The message to write in the log
 * @param string $nameFile The name of the log file (without extension)
 */
function writeLog($message, $nameDir='logs',$nameFile = 'log') {
    $logFile = __DIR__ . '/../' . $nameDir . '/' . $nameFile . '.txt';

    // Asegurar que la carpeta logs existe
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0777, true);
    }

    if (is_array($message) || is_object($message)) {
        $message = json_encode($message, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    file_put_contents(
        $logFile,
        '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL,
        FILE_APPEND
    );
}

/**
 * Function to send a JSON response and terminate the script
 * @param array $data The data to send in the response
 */
function jsonResponse($data=[]) {
	header('Content-Type: application/json');
	echo json_encode($data);
	die();
}