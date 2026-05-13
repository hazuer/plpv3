<?php
session_start();
define( '_VALID_MOS', 1 );

require_once('includes/configuration.php');
require_once('includes/DB.php');
$db = new DB(HOST,USERNAME,PASSWD,DBNAME,PORT,SOCKET);
require_once('includes/session.php');
$id_location = $_SESSION['uLocation'];

$cTelefono = $_POST['cTelefono'] ?? null;
$cNombre   = $_POST['cNombre'] ?? null;

$andTelefono = '';
if (!empty($cTelefono)) {
    $andTelefono = " AND c.phone LIKE '%$cTelefono%'";
}

$andNombre = '';
if (!empty($cNombre)) {
    $andNombre = " AND c.contact_name LIKE '%$cNombre%'";
}

$sql="SELECT 
c.id_contact,
c.id_location,
c.phone,
c.contact_name,
c.id_contact_type,
ct.contact_type,
c.id_contact_status,
CASE 
	WHEN c.id_contact_status = 1 THEN 'Activo' 
	WHEN c.id_contact_status = 2 THEN 'Inactivo' 
	END AS desc_estatus,
c.c_date,
CASE 
	c.id_type_mode WHEN 1 THEN 'Manual'
	WHEN 2 THEN 'Automático'
END AS tipo_modo,
c.id_type_mode 
FROM cat_contact c 
INNER JOIN cat_contact_type ct ON ct.id_contact_type = c.id_contact_type 
WHERE 
c.id_location IN ($id_location) 
$andTelefono 
$andNombre 
ORDER BY c.c_date DESC LIMIT 200";
$contacts = $db->select($sql);
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

         <script src="<?php echo BASE_URL;?>/assets/js/contacts.js?version=<?php echo time(); ?>"></script>
      </head>
	<body class="dashboard dashboard_1">
      <div class="full_container">
         <div class="inner_container">
            <!-- Sidebar  -->
                <?php include_once('sidebar.php');?>
            <!-- end sidebar -->
            <!-- right content -->
            <div id="content">
               <!-- topbar -->
               <?php include_once('topbar.php');?>
               <!-- end topbar -->
               <!-- dashboard inner -->
               <div class="midde_cont">
                  <div class="container-fluid">
                     <div class="row" style="margin-bottom: 15px;"></div>
                     <!-- row -->
                     <div class="row">
                        <!-- table section -->
                        <div class="col-md-12">
                           <div class="white_shd full margin_bottom_30">

                              <div class="table_section padding_infor_info">
                                 <div class="table-responsive-sm">
                                    <form id="frm-contacts" action="<?php echo BASE_URL;?>/contacts.php" method="POST">
										<div class="row">
											<div class="col-md-2">
												<div class="form-group">
													<label for="cTelefono"><b>Télefono:</b></label>
													<input type="text" class="form-control" name="cTelefono" id="cTelefono" value="<?php echo $cTelefono; ?>" autocomplete="off">
												</div>
											</div>
											<div class="col-md-2">
												<div class="form-group">
													<label for="cNombre"><b>Nombre:</b></label>
													<input type="text" class="form-control" name="cNombre" id="cNombre" value="<?php echo $cNombre; ?>" autocomplete="off">
												</div>
											</div>
											<div class="col-md-1"><br>
												<div class="form-group">
													<button id="btn-filter-contact" type="submit" class="btn btn-success" title="Filtrar" data-dismiss="modal">Filtrar</button>
												</div>
											</div>
											<div class="col-md-1"><br>
												<button id="btn-c-erase" type="button" class="btn btn-default" title="Borrar">Borrar</button>
											</div>

										</div>
									</form>
									<hr>
									<table id="tbl-contacts" class="table table-striped table-bordered nowrap table-hover" cellspacing="0" style="width:100%">
										<thead>
											<tr>
												<th>id_contact</th>
												<th>id_location</th>
												<th>phone</th>
												<th>contact_name</th>
												<th>id_contact_type</th>
												<th>contact_type</th>
												<th>c_date</th>
												<th>tipo_modo</th>
												<th>id_type_mode</th>
												<th>id_contact_status</th>
												<th>desc_estatus</th>
												<th>ubicacion</th>
												<th>Opciones</th>
											</tr>
										</thead>
										<tbody>
											<?php foreach($contacts as $d):
											$ubicacion = ($d['id_location']==1)? 'Tlaquiltenango':' Zacatepec';
											?>
												<tr>
												<td><?php echo $d['id_contact']; ?></td>
												<td><?php echo $d['id_location']; ?></td>
												<td><?php echo $d['phone']; ?></td>
												<td><?php echo $d['contact_name']; ?></td>
												<td><?php echo $d['id_contact_type']; ?></td>
												<td><?php echo $d['contact_type']; ?></td>
												<td><?php echo $d['c_date']; ?></td>
												<td><?php echo $d['tipo_modo']; ?></td>
												<td><?php echo $d['id_type_mode']; ?></td>
												<td><?php echo $d['id_contact_status']; ?></td>
												<td><?php echo $d['desc_estatus']; ?></td>
												<td><?php echo $ubicacion ?></td>
												<td style="text-align: center;">
													<div class="row">
														<div class="col-md-12">
															<span class="badge badge-pill badge-success" style="cursor: pointer;" id="btn-tbl-edit-contact" title="Editar">
																<i class="fa fa-edit fa-lg" aria-hidden="true"></i>
															</span>
														</div>
													</div>
												</td>
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
               <!-- end dashboard inner -->
            </div>
         </div>
      </div>
      <?php
      	include('modal/contact.php');
      	require_once('footer.php');
      ?>
   </body>
</html>