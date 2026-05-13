<?php
session_start();
define( '_VALID_MOS', 1 );

require_once('includes/configuration.php');
require_once('includes/DB.php');
$db = new DB(HOST,USERNAME,PASSWD,DBNAME,PORT,SOCKET);
require_once('includes/session.php');

$packages  = [];
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
	'Pre-registrado' status_desc,
	'' n_date,
	'' sms_by_user,
	'' t_sms_sent,
	p.d_date,
	'' user_libera,
	'Pendiente por rotular' note,
	'0' t_evidence,
	cp.parcel parcel_desc,
	'' t_pk_delivery,
	cct.contact_type,
	'Automático' tipo_modo,
	p.v_date,
	'' user_rotulo,
	p.address 
	FROM 
		package_tmp p 
	LEFT JOIN cat_contact cc ON cc.id_contact = p.id_contact 
	LEFT JOIN users uc ON uc.id = p.c_user_id 
	LEFT JOIN cat_location cl ON cl.id_location = p.id_location 
	LEFT JOIN cat_parcel cp ON cp.id_cat_parcel = p.id_cat_parcel 
	LEFT JOIN cat_contact_type cct ON cct.id_contact_type = cc.id_contact_type";
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

         <script src="<?php echo BASE_URL;?>/assets/js/prereg.js?version=<?php echo time(); ?>"></script>
      </head>
	<body class="dashboard dashboard_1"><body class="dashboard dashboard_1">
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
                    <div class="row column_title">
                        <div class="col-md-12">
                           <div class="page_title">
                              <h2>Sin rotular</h2>
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
									<form id="frm-pre">
										<div class="row">
                                             <div class="col-md-2"><br>
												<div class="form-group">
													<button id="btn-create-cb-pre" type="button" class="btn btn-success">Crear códigos de barras</button>
												</div>
                                            </div>
                                            <div class="col-md-1"><br>
                                                <button id="btn-borrar-pre" type="button" class="btn btn-warning">Borrar</button>
                                            </div>
                                        </div>
                                    </form>
                                    		
                                 <table id="tbl-reports"class="table table-striped table-hover" cellspacing="0" style="width:100%">
										<thead class="thead-dark">
											<tr>
												<th>id_package</th>
												<th>location_desc</th>
												<th>parcel_desc
												<th>fecha_registro</th>
												<th>registrado_por</th>
												<th>guia</th>
												<!-- <th>folio</th> -->
												<th>phone</th>
												<th>receiver</th>
												<!-- <th>status_desc</th> -->
												<th>note</th>
												<th>contact_type</th>
												<!-- <th>tipo_modo</th> -->
												<th>address</th>
											</tr>
										</thead>
										<tbody>
											<?php foreach($packages as $d):
												$folioColor = "<span style='font-weight: bold; color:".$d['marker']."'>".$d['folio']."</span>";
												?>
												<tr>
												<td><?php echo $d['id_package']; ?></td>
												<td><?php echo $d['location_desc']; ?></td>
												<td><?php echo $d['parcel_desc']; ?></td>
												<td><?php echo $d['c_date']; ?></td>
												<td><?php echo $d['registro']; ?></td>
												<td><?php echo $d['tracking']; ?></td>
												<!-- <td><?php echo $folioColor; ?></td> -->
												<td><?php echo $d['contact_type']; ?></td>
												<td><?php echo $d['phone']; ?></td>
												<td><?php echo $d['receiver']; ?></td>
												<!-- <td><?php echo $d['status_desc']; ?></td> -->
												<td><?php echo $d['note']; ?></td>
												<!-- <td><?php echo $d['tipo_modo']; ?></td> -->
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
               <!-- end dashboard inner -->
            </div>
         </div>
      </div>
      <?php
      require_once('footer.php');
      ?>
   </body>
</html>