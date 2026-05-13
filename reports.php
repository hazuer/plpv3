<?php
session_start();
define( '_VALID_MOS', 1 );


require_once('includes/configuration.php');
require_once('includes/DB.php');
$db = new DB(HOST,USERNAME,PASSWD,DBNAME,PORT,SOCKET);
require_once('includes/session.php');
$id_location = $_SESSION['uLocation'];

$rFstatus = $_POST['rFstatus'] ?? 3;
$rFIni    = $_POST['rFIni'] ?? null;
$rFFin    = $_POST['rFFin'] ?? null;
$rGuia    = $_POST['rGuia'] ?? null;
$rFolio   = $_POST['rFolio'] ?? null;
$rTelefono = $_POST['rTelefono'] ?? null;
$rParcel   = $_POST['rParcel'] ?? 99;

$rFIniLib    = $_POST['rFIniLib'] ?? date('Y-m-d');
$rFFinLib    = $_POST['rFFinLib'] ?? date('Y-m-d');

$andStatusIn = "";
if(isset($rFstatus)){
	if($rFstatus!='99'){
		$andStatusIn = " AND p.id_status IN ($rFstatus)";
	}else{
		$andStatusIn =" AND p.id_status IN (1,2,3,4,5,6,7,8)";
	}
}

$andFechasRegistro = "";
if(!empty($rFIni) && !empty($rFFin)){
	$andFechasRegistro = " AND p.c_date BETWEEN '$rFIni 00:00:00' AND '$rFFin 23:59:59'";
}

$andGuia ='';
if(!empty($rGuia)){
	$andGuia = " AND p.tracking IN('$rGuia')";
}

$andFolio ='';
if(!empty($rFolio)){
	$andFolio = " AND p.folio IN('$rFolio')";
}

$andTelefono ='';
if(!empty($rTelefono)){
	$andTelefono = " AND cc.phone IN('$rTelefono')";
}

$andFechasLiberacion = "";
if(!empty($rFIniLib) && !empty($rFFinLib)){
	$andFechasLiberacion = " AND p.d_date BETWEEN '$rFIniLib 00:00:00' AND '$rFFinLib 23:59:59'";
}

$andParcelIn = "";
if(isset($rParcel)){
	if($rParcel!=99){
		$andParcelIn = " AND p.id_cat_parcel IN ($rParcel)";
	}else{
		$andParcelIn = " AND p.id_cat_parcel IN (1,2,3)";
	}
}

$sql = "SELECT 
p.id_package,
cl.location_desc,
p.c_date c_date,
uc.user registro,
p.tracking,
cc.phone,
p.folio,
p.marker,
cc.contact_name receiver,
cs.status_desc,
DATE_FORMAT(p.n_date, '%Y-%m-%d') n_date,
un.user sms_by_user,
(SELECT count(n.id_notification) FROM notification n WHERE n.id_package in(p.id_package)) t_sms_sent,
p.d_date,
ud.user user_libera,
p.note,
(SELECT count(e.id_evidence) FROM evidence e WHERE e.id_package IN(p.id_package)) t_evidence,
cp.parcel parcel_desc,
(SELECT COUNT(pk.id_package) FROM package pk LEFT JOIN cat_contact cpk ON cpk.id_contact = pk.id_contact WHERE cpk.phone = cc.phone AND pk.id_status IN (3)) AS t_pk_delivery,
cct.contact_type,
CASE p.id_type_mode
    WHEN 1 THEN 'Manual'
    WHEN 2 THEN 'Automático'
END AS tipo_modo,
p.v_date,
uv.user user_rotulo,
p.address 
FROM package p 
LEFT JOIN cat_contact cc ON cc.id_contact=p.id_contact 
LEFT JOIN cat_status cs ON cs.id_status=p.id_status 
LEFT JOIN users uc ON uc.id = p.c_user_id 
LEFT JOIN cat_location cl ON cl.id_location = p.id_location 
LEFT JOIN users un ON un.id = p.n_user_id 
LEFT JOIN users ud ON ud.id = p.d_user_id 
LEFT JOIN cat_parcel cp ON cp.id_cat_parcel = p.id_cat_parcel 
LEFT JOIN cat_contact_type cct ON cct.id_contact_type = cc.id_contact_type 
LEFT JOIN users uv ON uv.id = p.v_user_id 
WHERE 1 
AND p.id_location IN ($id_location) 
$andStatusIn 
$andFechasRegistro 
$andGuia 
$andFolio 
$andTelefono 
$andFechasLiberacion 
$andParcelIn 
ORDER BY p.id_package DESC";
$packages = $db->select($sql);
?>
<!DOCTYPE html>
<html lang="es-MX">
      <head>
         <?php include_once('head.php');?>

		<link href="<?php echo BASE_URL;?>/assets/css/libraries/jquery.dataTables.min.css" rel="stylesheet">
		<script src="<?php echo BASE_URL;?>/assets/js/libraries/jquery.dataTables.min.js"></script>

		<link href="<?php echo BASE_URL;?>/assets/css/libraries/buttons.dataTables.min.css" rel="stylesheet">
		<script src="<?php echo BASE_URL;?>/assets/js/libraries/dataTables.buttons.min.js"></script>
		<script src="<?php echo BASE_URL;?>/assets/js/libraries/jszip.min.js"></script>
		<script src="<?php echo BASE_URL;?>/assets/js/libraries/pdfmake.min.js"></script>
		<script src="<?php echo BASE_URL;?>/assets/js/libraries/buttons.html5.min.js"></script>
		<link type="text/css" href="<?php echo BASE_URL;?>/assets/css/libraries/dataTables.checkboxes.css" rel="stylesheet"/>
		<script type="text/javascript" src="<?php echo BASE_URL;?>/assets/js/libraries/dataTables.checkboxes.min.js"></script>

		<script src="<?php echo BASE_URL;?>/assets/js/reports.js?version=<?php echo time(); ?>"></script>
      </head>
	<body class="dashboard dashboard_1">
      <div class="full_container">
         <div class="inner_container">
            <?php include_once('sidebar.php');?>
            <div id="content">
               <?php include_once('topbar.php');?>
               <div class="midde_cont">
                  <div class="container-fluid">
                    <div class="row column_title">
                        <div class="col-md-12">
                           <div class="page_title">
                              <h2>Reporte personalizado</h2>
                           </div>
                        </div>
                     </div>
                     <!-- row -->
                     <div class="row">
                        <!-- table section -->
                        <div class="col-md-12">
                           <div class="white_shd full margin_bottom_30">

                              <div class="table_section padding_infor_info">
                                 <div class="table-responsive-sm">

									<form id="frm-reports" action="<?php echo BASE_URL;?>/reports.php" method="POST">
										<div class="row">
											<div class="col-md-3">
												<div class="form-group">
													<label for="rFstatus"><b>Estatus:</b></label>
													<select name="rFstatus" id="rFstatus" class="form-control">
														<option value="99" <?php echo ($rFstatus==99) ? 'selected': ''; ?>>Todos</option>
														<option value="1" <?php echo ($rFstatus==1) ? 'selected': ''; ?>>Nuevo</option>
														<option value="2" <?php echo ($rFstatus==2) ? 'selected': ''; ?>>Mensaje Enviado</option>
														<option value="3" <?php echo ($rFstatus==3) ? 'selected': ''; ?>>Entregado</option>
														<option value="4" <?php echo ($rFstatus==4) ? 'selected': ''; ?>>Devuelto</option>
														<option value="5" <?php echo ($rFstatus==5) ? 'selected': ''; ?>>Confirmado</option>
														<option value="6" <?php echo ($rFstatus==6) ? 'selected': ''; ?>>Error al enviar mensaje</option>
														<option value="7" <?php echo ($rFstatus==7) ? 'selected': ''; ?>>Contactado</option>
														<option value="8" <?php echo ($rFstatus==8) ? 'selected': ''; ?>>Devolución en Proceso</option>
													</select>
												</div>
											</div>
											<div class="col-md-2">
												<div class="form-group">
													<label for="rFIni"><b>Fecha Ini. Registro:</b></label>
													<input type="date" class="form-control" name="rFIni" id="rFIni" value="<?php echo $rFIni; ?>">
												</div>
											</div>
											<div class="col-md-2">
												<div class="form-group">
													<label for="rFFin"><b>Fecha Fin Registro:</b></label>
													<input type="date" class="form-control" name="rFFin" id="rFFin" value="<?php echo $rFFin; ?>">
												</div>
											</div>
											<div class="col-md-3">
												<div class="form-group">
													<label for="rGuia"><b>Guía:</b></label>
													<input type="text" class="form-control" name="rGuia" id="rGuia" value="<?php echo $rGuia; ?>" autocomplete="off">
												</div>
											</div>
											<div class="col-md-2">
												<div class="form-group">
													<label for="rFolio"><b>Folio:</b></label>
													<input type="text" class="form-control" name="rFolio" id="rFolio" value="<?php echo $rFolio; ?>" autocomplete="off">
												</div>
											</div>
										</div>
										<div class="row">
											<div class="col-md-3">
												<div class="form-group">
													<label for="rParcel"><b>Paquetería:</b></label>
													<select name="rParcel" id="rParcel" class="form-control">
														<option value="99" <?php echo ($rParcel==99) ? 'selected': ''; ?>>Todas</option>
														<option value="1" <?php echo ($rParcel==1) ? 'selected': ''; ?>>J&T</option>
														<option value="2" <?php echo ($rParcel==2) ? 'selected': ''; ?>>IMILE</option>
														<option value="3" <?php echo ($rParcel==3) ? 'selected': ''; ?>>CNMEX</option>
													</select>
												</div>
											</div>
											<div class="col-md-2">
												<div class="form-group">
													<label for="rTelefono"><b>Télefono:</b></label>
													<input type="text" class="form-control" name="rTelefono" id="rTelefono" value="<?php echo $rTelefono; ?>" autocomplete="off">
												</div>
											</div>
											<div class="col-md-2">
												<div class="form-group">
													<label for="rFIniLib"><b>Fecha Ini. Entrega:</b></label>
													<input type="date" class="form-control" name="rFIniLib" id="rFIniLib" value="<?php echo $rFIniLib; ?>">
												</div>
											</div>
											<div class="col-md-2">
												<div class="form-group">
													<label for="rFFinLib"><b>Fecha Fin Entrega:</b></label>
													<input type="date" class="form-control" name="rFFinLib" id="rFFinLib" value="<?php echo $rFFinLib; ?>">
												</div>
											</div>
											<div class="col-md-1"><br>
												<div class="form-group">
													<button id="btn-filter-rep" type="submit" class="btn btn-success" data-dismiss="modal">Filtrar</button>
												</div>
											</div>
											<div class="col-md-1"><br>
												<button id="btn-f-erase" type="button" class="btn btn-default">Borrar</button>
											</div>
										</div>
									</form>
									<hr>
									<table id="tbl-reports" class="table table-striped table-hover" cellspacing="0" style="width:100%">
										<thead class="thead-dark">
											<tr>
												<th>id_package</th>
												<th>location_desc</th>
												<th>parcel_desc
												<th>fecha_registro</th>
												<th>registrado_por</th>
												<th>guia</th>
												<th>folio</th>
												<th>phone</th>
												<th>receiver</th>
												<th>status_desc</th>
												<th>fecha_envio_sms</th>
												<th>sms_enviado_por</th>
												<th>total_sms</th>
												<th>fecha_liberacion</th>
												<th>libero</th>
												<th>note</th>
												<th>evidence</th>
												<th>t_pk_delivery</th>
												<th>contact_type</th>
												<th>tipo_modo</th>
												<th>v_date</th>
												<th>user_rotulo</th>
												<th>address</th>
											</tr>
										</thead>
										<tbody>
											<?php foreach($packages as $d):
												$folioColor = "<span style='font-weight: bold; color:".$d['marker']."'>".$d['folio']."</span>";
												?>
												<tr>
												<td title="Ver Historial" id="id-logger" style="cursor: pointer; text-decoration: underline;"><?php echo $d['id_package']; ?></td>
												<td><?php echo $d['location_desc']; ?></td>
												<td><?php echo $d['parcel_desc']; ?></td>
												<td><?php echo $d['c_date']; ?></td>
												<td><?php echo $d['registro']; ?></td>
												<td><?php echo $d['tracking']; ?></td>
												<td><?php echo $folioColor; ?></td>
												<td><?php echo $d['contact_type']; ?></td>
												<td><?php echo $d['phone']; ?></td>
												<td><?php echo $d['receiver']; ?></td>
												<td><?php echo $d['status_desc']; ?></td>
												<td><?php echo $d['n_date']; ?></td>
												<td><?php echo $d['sms_by_user']; ?></td>
												<td>
													<?php if($d['t_sms_sent']==0){ echo "0";}else{ ?>
														<span class="badge badge-pill badge-info" style="cursor: pointer;" id="btn-details" data-toggle="tooltip" data-placement="top" title="Ver"><?php echo $d['t_sms_sent']; ?></span>
													<?php
													} ?>
												</td>
												<td><?php echo $d['d_date']; ?></td>
												<td><?php echo $d['user_libera']; ?></td>
												<td><?php echo $d['note']; ?></td>
												<td>
													<?php if($d['t_evidence']!=0){ ?>
														<span class="badge badge-pill badge-warning" style="cursor: pointer;" id="btn-evidence" data-toggle="tooltip" data-placement="top" title="Evidencia(s)">
															<?php echo $d['t_evidence']; ?> <i class="fa fa-file-image-o fa-lg" aria-hidden="true"></i>
														</span>
													<?php
													} ?>
												</td>
												<td><?php echo $d['t_pk_delivery']; ?></td>
												<td><?php echo $d['tipo_modo']; ?></td>
												<td><?php echo $d['v_date']; ?></td>
												<td><?php echo $d['user_rotulo']; ?></td>
												<td><?php echo $d['address']; ?></td>
												</tr>
											<?php endforeach; ?>
										</tbody>
									</table>
									
                                 </div>
                              </div>
                           </div>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
         </div>
      </div>
      <?php
      	include('modal/sms-report.php');
		include('modal/logger.php');
		include('modal/evidence.php');
      	require_once('footer.php');
      ?>
   </body>
</html>