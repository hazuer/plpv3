<?php
session_start();
define( '_VALID_MOS', 1 );

require_once('includes/configuration.php');
require_once('includes/DB.php');
$db = new DB(HOST,USERNAME,PASSWD,DBNAME,PORT,SOCKET);
require_once('includes/session.php');
$id_location = $_SESSION['uLocation'];

$sql = "SELECT 
p.id_package,
p.tracking,
cc.phone,
p.id_location,
p.c_date,
p.folio,
DATEDIFF(NOW(), p.c_date) tdt,
cc.contact_name receiver,
cs.id_status,
IF(cs.id_status=6,'color:#DC143C;', '') colorErrorMessage,
cs.status_desc,
p.note,
IF(p.n_date IS NULL,'', (SELECT DATE_FORMAT(n.n_date, '%m-%d') FROM notification n WHERE n.id_package IN(p.id_package) ORDER BY id_notification ASC LIMIT 1)) n_date,
(SELECT count(n.id_notification) FROM notification n WHERE n.id_package IN(p.id_package)) t_sms_sent,
p.id_contact,
(SELECT 
    CASE 
        WHEN DATEDIFF(NOW(), n.n_date) = 0 OR DATEDIFF(NOW(), n.n_date) = 1 THEN '' 
        WHEN DATEDIFF(NOW(), n.n_date) = 2 THEN 'background-color: #FFFF99;' 
        WHEN DATEDIFF(NOW(), n.n_date) >= 3 THEN 'background-color: #FF9999;' 
        ELSE 'sin color' 
    END AS color 
FROM notification n 
WHERE n.id_package IN (p.id_package) 
ORDER BY n.id_notification ASC 
LIMIT 1) styleCtrlDays,
(SELECT DATEDIFF(NOW(), n_date) FROM notification n WHERE n.id_package IN(p.id_package) ORDER BY id_notification ASC LIMIT 1) dcolor,
p.marker,
(SELECT count(e.id_evidence) FROM evidence e WHERE e.id_package IN(p.id_package)) t_evidence,
p.id_cat_parcel,
cp.parcel 
FROM package p 
LEFT JOIN cat_contact cc ON cc.id_contact=p.id_contact 
LEFT JOIN cat_status cs ON cs.id_status=p.id_status 
LEFT JOIN cat_parcel cp ON cp.id_cat_parcel=p.id_cat_parcel 
WHERE 1 
AND p.id_location IN ($id_location)
AND p.id_status IN(1,2,5,6,7,8)";
$packages = $db->select($sql);

$sqlTemp     = "SELECT id_template,template FROM cat_template WHERE id_location IN ($id_location) AND type_template IN(1) LIMIT 1";
$user        = $db->select($sqlTemp);
$idTemplateMsj = $user[0]['id_template'];
$templateMsj   = $user[0]['template'];
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

         <script src="<?php echo BASE_URL;?>/assets/js/packages.js?version=<?php echo time(); ?>"></script>

		<link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
		<script src="<?php echo BASE_URL;?>/assets/js/libraries/jquery-ui.min.js"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-ui-timepicker-addon/1.6.3/jquery-ui-timepicker-addon.min.js"></script>
		<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-ui-timepicker-addon/1.6.3/jquery-ui-timepicker-addon.min.css">


		<script>
         let idTemplateMsj    = `<?php echo $idTemplateMsj;?>`;
		 let templateMsj    = `<?php echo $templateMsj;?>`;
         let uMarker        = `<?php echo $_SESSION["uMarker"];?>`;
         let uIdCatParcel   = `<?php echo $_SESSION["uIdCatParcel"];?>`;
         let largo          = `<?php echo LARGO;?>`;
         let alto           = `<?php echo ALTO;?>`;
         let rVoice         = `<?php echo $_SESSION["uVoice"]; ?>`
         </script>
        <style>

    		@media only screen and (max-width: 768px) {
                table.dataTable td:nth-child(4),
                table.dataTable th:nth-child(4) {
                    display: none;
                }
				table.dataTable td:nth-child(9),
                table.dataTable th:nth-child(9) {
                    display: none;
                }
				table.dataTable td:nth-child(10),
                table.dataTable th:nth-child(10) {
                    display: none;
                }

				.btn-liberar {
					display: none !important;
				}
                #lbl-title-location {
                    display: none;
                }
				table thead th {
				vertical-align: middle !important; /* centra vertical */
				text-align: center;                 /* centra horizontal (opcional) */
				}
				#tbl-packages_filter input[type="search"] {
					width: 150px; /* o el tamaño que quieras */
					font-size: 14px; /* opcional para reducir texto */
				}
				table.dataTable {
					color: black;
					font-size: 13px; /* Reducción del tamaño de letra en un 25% */
				}

				.dt-buttons .buttons-excel {
					display: none !important;
				}

			div.swal-footer {
				text-align: center !important;
				padding: 0px 0px !important;
			}


			#coincidencias {
				position: absolute;
				top: calc(100% - 13px); /* Posición debajo del campo #phone */
				/*left: 0;*/
				width: calc(100% - 5%);
				max-height: 200px; /* Altura máxima para evitar el desplazamiento */
				overflow-y: auto; /* Mostrar barra de desplazamiento vertical si es necesario */
				background-color: #F8F9FA; /* Color de fondo */
				border: 1px solid #ccc; /* Borde */
				box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); /* Sombra */
				z-index: 1000; /* Z-index para que se superponga a otros elementos */
			}

			#coincidencias p {
				padding: 10px;
				margin: 0;
				cursor: pointer; /* Cambiar el cursor al pasar sobre los elementos de la lista */
			}

			#coincidencias p:hover {
				background-color: #D4EDDA; /* Cambiar el color de fondo al pasar el cursor */
			}
		}
        </style>
        <script>
    function truncateText() {
        const table = document.getElementById('tbl-packages');
        if (!table) return; // Evita errores si la tabla no existe

        const rows = table.querySelectorAll('tbody tr');
        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            if (cells.length > 8) {
                const cell = cells[8];
                const text = cell.textContent.trim();

                if (text.length > 10 && !cell.dataset.truncated) {
                    cell.textContent = text.substring(0, 10) + '...';
                    cell.dataset.truncated = "true"; // Evita truncar varias veces
                }
            }
        });
    }

    let lastWidth = window.innerWidth;
    let resizeTimer;

    // Ejecuta al cargar
    document.addEventListener('DOMContentLoaded', () => {
        if (window.innerWidth <= 768) {
            truncateText();
        }
    });

    // Optimiza el evento resize
    window.addEventListener('resize', function() {
        if (Math.abs(window.innerWidth - lastWidth) > 50) { // Solo si cambia más de 50px
            lastWidth = window.innerWidth;
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(() => {
                if (window.innerWidth <= 768) {
                    truncateText();
                }
            }, 200);
        }
    });

	
</script>
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
                                    <table id="tbl-packages" class="table table-striped table-hover" cellspacing="0" style="width:100%">
										<thead class="thead-dark">
											<tr>
												<th></th>
												<th>guia</th>
												<th>phone</th>
												<th>id_location</th>
												<th>c_date</th>
												<th>folio</th>
												<th>receiver</th>
												<th>id_status</th>
												<th>status_desc</th>
												<th>note</th>
												<th>id_contact</th>
												<th>id_cat_parcel</th>
												<th>parcel</th>
												<th>messages</th>
												<th>tdiast</th>
												<th style="text-align: center; width:20%;">
													<button type="button" id="confirmg" name="confirmg" class="btn btn-success btn-sm btn-confirm" data-toggle="tooltip" data-placement="top" title="Confirmar Guías Seleccionadas">
														<i class="fa fa-flag-o fa-lg" aria-hidden="true"></i>
													</button>
													<button type="button" id="releaseg" name="releaseg" class="btn btn-success btn-sm" data-toggle="tooltip" data-placement="top" title="Liberar Guías Seleccionadas">
														<i class="fa fa-check-square-o fa-lg" aria-hidden="true"></i>
													</button>
												</th>
											</tr>
										</thead>
										<tbody>
											<?php foreach($packages as $d):
												?>
												<tr id="<?php echo 'row_id_'.$d['id_package']; ?>" style="<?php echo $d['id_status'] == 5 ? 'background-color:#A2D9A2' : $d['styleCtrlDays']; ?>" title="">
												<td><?php echo $d['id_package']; ?></td>
												<td><?php echo $d['tracking']; ?></td>
												<td><?php echo $d['phone']; ?></td>
												<td><?php echo $d['id_location']; ?></td>
												<td><?php echo $d['c_date']; ?></td>
												<td style="font-weight: bold; color: <?php echo $d['marker']; ?>;"><?php echo $d['folio']; ?></td>
												<td><?php echo $d['receiver']; ?></td>
												<td><?php echo $d['id_status']; ?></td>
												<td style="<?php echo $d['colorErrorMessage']; ?>" > <?php echo $d['status_desc']; ?>
											<?php
											if($d['note']){?><span class="badge badge-pill badge-default" style="cursor: pointer;" data-toggle="tooltip" data-placement="top" title="<?php echo $d['note'];?>"><i class="fa fa-sticky-note-o" aria-hidden="true"></i> </span><?php }?>
											</td>
												<td><?php echo $d['note']; ?></td>
												<td><?php echo $d['id_contact']; ?></td>
												<td><?php echo $d['id_cat_parcel']; ?></td>
												<td><?php echo $d['parcel']; ?></td>
												<td><?php if($d['t_sms_sent']!=0){ ?>
													<?php 
													echo $d['n_date']
													?>
													<span class="badge badge-pill badge-info" style="cursor: pointer;" id="btn-details-p" data-toggle="tooltip" data-placement="top" title="Leer Mensaje"><?php echo $d['t_sms_sent']; ?> </span>
												<?php }?></td>
												<td>
													<?php if($d['tdt']!=0){
													echo $d['tdt'];
													}?>
												</td>
												</td>
												<td style="text-align: center;">
													<div class="row">
														<div class="col-md-4">
														<?php if($d['id_status']==2 || $d['id_status']==5 || $d['id_status']==7){ ?>
															<span class="badge badge-pill badge-success btn-liberar" style="cursor: pointer;" id="btn-tbl-liberar" title="Liberar">
																<i class="fa fa-check-square-o fa-lg" aria-hidden="true"></i>
															</span>
														<?php }?>
														</div>
														<div class="col-md-4">
															<span class="badge badge-pill badge-info btn-edit" style="cursor: pointer;" id="btn-records" title="Editar">
																<i class="fa fa-edit fa-lg" aria-hidden="true"></i>
															</span>
														</div>
														<div class="col-md-4">
															<?php if($d['t_evidence']!=0){ ?>
																<span class="badge badge-pill badge-warning" style="cursor: pointer;" id="btn-evidence" title="Evidencia(s)">
																	<i class="fa fa-file-image-o fa-lg" aria-hidden="true"></i>
																</span>
															<?php
															}?>
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
	  <audio id="sound-snap" style="display: none;">
		<source src="<?php echo BASE_URL;?>/assets/snap.mp3" type="audio/mpeg">
	</audio>
      <?php
		include('modal/folio.php');
		include('modal/template.php');
		include('modal/package.php');
		include('modal/release.php');
		include('modal/sync.php');
		include('modal/bot.php');
		include('modal/sms-report.php');
		include('modal/evidence.php');
		include('modal/evidence-camera.php');
		include('modal/ocr-camera.php');
		require_once('footer.php');
      ?>
   </body>
</html>