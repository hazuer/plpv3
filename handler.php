<?php
session_start();
define( '_VALID_MOS', 1 );

require_once('includes/configuration.php');
require_once('includes/DB.php');
$db = new DB(HOST,USERNAME,PASSWD,DBNAME,PORT,SOCKET);
require_once('includes/session.php');
$id_location = $_SESSION['uLocation'];
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
    	let templateMsj =`<?php echo $templateMsj;?>`;
		let uMarker =`<?php echo $_SESSION["uMarker"];?>`;
		let uIdCatParcel =`<?php echo $_SESSION["uIdCatParcel"];?>`;
		</script>
		<style>
			.mensaje {
				color: gray;
				text-decoration: none; /* Quitar subrayado */
			}
			.mensaje-enviado {
				color: green;
				text-decoration: none; /* Quitar subrayado */
			}
		</style>
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
                     <div class="row column_title">
                        <div class="col-md-12">
                           <div class="page_title">
                              <h2 id="lbl-title-location">🤖 Envío Manual de Mensajes <?php echo $desc_loc;?></h2>
                           </div>
                        </div>
                     </div>
                     <!-- row -->
                     <div class="row">
                        <!-- table section -->
                        <div class="col-md-12">
                           <div class="white_shd full margin_bottom_30">

                              <form id="frm-package">
			
			<?php
				include('modal/handler.php');
			?>
			</form>
		</div>
		<script>
		$('#msjbt').hide();
		let baseController = 'controllers/packageController.php';
        let enlaces = document.querySelectorAll(".mensaje");

        enlaces.forEach(function(enlace) {
            enlace.addEventListener("click", function() {
                enlace.classList.add("mensaje-enviado");
            });
        });

		let enviar = document.querySelectorAll('.mensaje');
        enviar.forEach(function(enlace) {
            enlace.addEventListener('click', function(event) {
                event.preventDefault();
                let telefono = enlace.getAttribute('data-phone');
				let formData = new FormData();
				formData.append('id_location',$('#idlocbt').val());
				formData.append('uidbt',$('#uidbt').val());
				formData.append('msjbt',$('#msjbt').val());
				formData.append('telefono',telefono);
				formData.append('option','mensajeManual');
				$.ajax({
					url: `${base_url}/${baseController}`,
					type       : 'POST',
					data       : formData,
					cache      : false,
					contentType: false,
					processData: false,
				})
				.done(function(response) {
					if (response.success === 'true') {
						let msjbt = $('#msjbt').val();
						let folios = response.message;
						let fullMessage = encodeURIComponent(`🤖 ${msjbt} ${folios}`);
						var url = `https://api.whatsapp.com/send/?phone=${telefono}&text=${fullMessage}`;
						window.open(url);
					}else{
						swal('Atención.!', 'Ya has procesado el número '+ telefono, "warning");
					}
				}).fail(function(e) {
					console.log("Opps algo salio mal",e);
				});
            });
        });

    </script>


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
      	include('modal/package.php');
      	require_once('footer.php');
      ?>
   </body>
</html>