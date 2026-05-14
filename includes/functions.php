<?php
function getCatLocationById($id_location){
	global $db;
	$sqlCatLocation="SELECT * FROM cat_location WHERE id_location =$id_location";
	$records           = $db->select($sqlCatLocation);
	return $records;
}

function getSelectCatLocation(){
	global $db;
	$sqlCatLocation="SELECT id_location,location_desc  FROM cat_location ORDER BY id_location ASC";
	$records           = $db->select($sqlCatLocation);
	return $records;
}