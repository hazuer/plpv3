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
	p.is_verified,
	p.folio,
	cc.contact_name receiver,
	UPPER(SUBSTRING(TRIM(REPLACE(
		REPLACE(
			REPLACE(
				REPLACE(
					REPLACE(
						REPLACE(
							REPLACE(
								REPLACE(cc.contact_name, 'á', 'a'),
							'é', 'e'),
						'í', 'i'),
					'ó', 'o'),
				'ú', 'u'),
			'Á', 'A'),
		'Ñ', 'N'),
	'É', 'E')), 1, 1)) AS initial,
	p.marker 
	FROM package p 
	INNER JOIN cat_contact cc ON cc.id_contact = p.id_contact 
	WHERE p.id_location IN ($id_location) 
	AND p.id_status IN (1, 2, 5, 6, 7, 8) 
	ORDER BY initial, p.folio
";

$result = $db->select($sql);
$groupedPackages = [];
// Contadores
$countJMX1   = 0;
$countCN1    = 0;
$countImile1 = 0;

foreach($result as $row){
	$initial = $row['initial'];  // La primera letra del nombre
	$folio   = $row['folio'];      // El folio del paquete

	// Recorrer el array y contar los que comienzan con "JMX"
	if (strpos($row['tracking'], 'JMX') === 0) {
		$countJMX1++;
	}else if(strpos($row['tracking'], 'CNMEX') === 0) {
		$countCN1++;
	} else {
		$countImile1++;
	}
	// Agrupar los paquetes por inicial
	if (!isset($groupedPackages[$initial])) {
		$groupedPackages[$initial] = [];
	}

	// Dentro de cada inicial, agrupar por folio
	$groupedPackages[$initial][] = [
		'tracking' => $row['tracking'],
		'folio'    => $folio,
		'receiver' => $row['receiver'],
		'marker'   => $row['marker'],
		'is_verified'   => $row['is_verified']
	];
}

$sqlStatus = "SELECT enable_bot FROM cat_location where id_location IN ($id_location)";
$resultStatus = $db->select($sqlStatus);
$enable_bot = $resultStatus[0]['enable_bot'] ?? 0;
$text_enable_bot = ($enable_bot==1) ? "Desactivar Bot":"Activar Bot";		

?>
<!DOCTYPE html>
<html lang="es-MX">
	<head>
	<?php include_once('head.php');?>
	<script src="<?php echo BASE_URL;?>/assets/js/admin.js?version=<?php echo time(); ?>"></script>
	<style>
		.checkbox-container {
		display: grid;
		grid-template-columns: repeat(2, 1fr); /* Por defecto 2 columnas */
		gap: 5px;
		margin-top: 8px;
		justify-items: center;  /* centra cada checkbox */
		align-items: start;     /* los alinea arriba */
		height: auto;           /* 🔑 evita el espacio vacío */
		min-height: auto;       /* 🔑 elimina altura mínima */
		}

		/* Cuando sea <= 425px cambia a 4 columnas */
		@media (max-width: 425px) {
		.checkbox-container {
			grid-template-columns: repeat(4, 1fr);
		}
		}
	</style>
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
                              <h2>Inventario | <?php echo "Total: ".count($result)." Paquetes |";
								if($countJMX1>0){echo "J&T:".$countJMX1." | "; }
								if($countCN1>0){echo "CNMEX:".$countCN1." | "; }
								if($countImile1>0){echo " IMILE:".$countImile1; }?>
								</h2>
                           </div>
                        </div>
                     </div>

					<div class="row ccolumn1">
					<?php foreach ($groupedPackages as $initial => $packages): ?>
						<div class="col-md-12 col-lg-4 d-flex">
							<div class="card w-100 shadow-sm mb-4">

								<!-- Cabecera -->
								<div class="card-header text-center bg-light">
									<h4 class="mb-0" style="font-weight:bold; font-size:28px; color:#333;">
										<?php echo $initial; ?>:<?php echo count($packages); ?>
									</h4>
								</div>

								<!-- Body (etiquetas JT, CN, IM) -->
								<div class="card-body text-center">
									<div class="d-flex justify-content-center flex-wrap gap-2">
										<?php
										$countJMX   = 0;
										$countCN    = 0;
										$countImile = 0;
										foreach ($packages as $item) {
											if (strpos($item['tracking'], 'JMX') === 0) {
												$countJMX++;
											} else if (strpos($item['tracking'], 'CNMEX') === 0) {
												$countCN++;
											} else {
												$countImile++;
											}
										}
										?>

										<?php if($countJMX > 0): ?>
											<span class="badge px-3 py-2" style="background:#f8d568; font-size:14px; font-weight:bold;">
												JT: <?php echo $countJMX; ?>
											</span>
										<?php endif; ?>

										<?php if($countCN > 0): ?>
											<span class="badge px-3 py-2" style="background:#007bff; color:#fff; font-size:14px; font-weight:bold;">
												CN: <?php echo $countCN; ?>
											</span>
										<?php endif; ?>

										<?php if($countImile > 0): ?>
											<span class="badge px-3 py-2" style="background:#03a9f4; color:#fff; font-size:14px; font-weight:bold;">
												IM: <?php echo $countImile; ?>
											</span>
										<?php endif; ?>
									</div>
								</div>

								<!-- Footer (checkbox + folio) -->
								<div class="card-footer bg-white border-0">
									<div class="row">
										<?php foreach ($packages as $package): ?>
											<div class="col-3 col-sm-4 col-md-3 col-xxs-3 text-center mb-2" 
												data-toggle="tooltip" 
												data-placement="top" 
												title="<?php echo $package['tracking'];?>-<?php echo $package['receiver'];?>">
												<div class="d-flex flex-column align-items-center" style="color:<?php echo $package['marker'];?>; font-weight:bold;">
													<input 
														type="checkbox" 
														autocomplete="off" 
														class="mb-1 chk-package" 
														data-tracking="<?php echo $package['tracking']; ?>" 
														<?php echo ($package['is_verified'] == 1) ? 'checked' : ''; ?>
													>
													<span><?php echo $package['folio']; ?></span>
												</div>
											</div>
										<?php endforeach; ?>
									</div>
								</div>

							</div>
						</div>
					<?php endforeach; ?>
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