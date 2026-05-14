<?php
#error_reporting(E_ALL);
#ini_set('display_errors', '1');
session_start();
define( '_VALID_MOS', 1 );

require_once('includes/configuration.php');
require_once('includes/DBW.php');
$db = new DB(HOST,USERNAME,PASSWD,DBNAME,PORT,SOCKET);
require_once('includes/session.php');
$id_location = $_SESSION['uLocation'];

#Template
$sqlLocationInfo ="SELECT * FROM cat_location WHERE id_location IN($id_location)";
$infoLocation = $db->select($sqlLocationInfo);
$fechaDev = date("d/m/Y", strtotime("+2 days"));

$hoy = date("d/m/Y");
$tomorrow = date("d/m/Y", strtotime("+1 days"));

$sqlTemplates ="SELECT id_template,name,id_cat_parcel FROM cat_template WHERE type_template IN(2) AND status IN (1)";
$template = $db->select($sqlTemplates);

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

      <link rel="stylesheet" href="<?php echo BASE_URL;?>/assets/css/waba.css?version=<?php echo time(); ?>"/>
         <script src="<?php echo BASE_URL;?>/assets/js/whatsapp.js?version=<?php echo time(); ?>"></script>
         <style>
            .dynamic-field.dragover {
    border: 2px dashed #5bc0de;
    background: #f0fcff;
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
                              <h2>Chats</h2>
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
                                    <?php 
                                    $chPhone = $_POST['chPhone'] ?? null;
                                    ?>

                                 <form id="frm-msj-meta" action="<?php echo BASE_URL;?>/whatsapp.php" method="POST">
										      <div class="row">

                                       <div class="col-md-2">
                                          <div class="form-group">
                                             <label for=""><b></b></label>
                                             <input type="text" class="form-control" placeholder="Buscar chat por número" name="chPhone" id="chPhone" value="<?php echo $chPhone; ?>" autocomplete="off">
                                          </div>
                                       </div>
                                       <div class="col-md-1"><br>
                                          <div class="form-group">
                                             <button id="btn-filter-msj" type="submit" class="btn btn-success" data-dismiss="modal">Filtrar</button>
                                          </div>
                                       </div>
                                       <div class="col-md-1"><br>
                                          <button id="btn-cln" type="button" class="btn btn-default">Borrar</button>
                                       </div>
                                    </div>
                                 </form>
									<table id="tbl-msj-whats" class="table table-striped table-hover" cellspacing="0" style="width:100%">
										<thead class="thead-dark">
											<tr>
                                    <th>sender_phone</th>
												<th>contact_name</th>
												<th>last_message</th>
												<th>last_date</th>
                                    <th>opc</th>
											</tr>
										</thead>
										<tbody>
                                 <?php 
                                    if(!empty($chPhone)){
                                       $sql ="SELECT 
                                    sender_phone, 
                                    MAX(datelog) AS last_date, 
                                    SUBSTRING_INDEX( 
                                       GROUP_CONCAT(message_text ORDER BY datelog DESC SEPARATOR '|'),
                                       '|',
                                       1
                                    ) AS last_message 
                                 FROM 
                                    waba_callbacks 
                                 WHERE 1
                                    AND sender_phone LIKE '%$chPhone%' 
                                 GROUP BY 
                                    sender_phone 
                                 ORDER BY 
                                    last_date DESC;";
                                    $busCahts = $db->select($sql);
                                    #$htmlBusHTML = '';
                                    if(count($busCahts)>=1){
                                       $idlx = $_SESSION['uLocation'];
                                       foreach ($busCahts as $chat) {
                                          // Obtener número sin prefijo
                                             $numero = substr($chat['sender_phone'], -10);

                                             // Obtener información del contacto
                                             $sqlGetContac = "SELECT 
                                                c.id_location,
                                                c.contact_name 
                                             FROM cat_contact c 
                                             WHERE 
                                                c.id_location IN ($idlx) 
                                                AND c.phone IN('$numero')
                                                AND c.id_contact_status IN (1)
                                             ORDER BY c.c_date DESC 
                                             LIMIT 1";

                                             $rstCheck = $db->select($sqlGetContac);
                                             $contact_name = $rstCheck[0]['contact_name'] ?? '';

                                                // Formatear fecha
                                                $formatter = new IntlDateFormatter(
                                                      'es_ES',
                                                      IntlDateFormatter::FULL,
                                                      IntlDateFormatter::SHORT,
                                                      'America/Mexico_City',
                                                      IntlDateFormatter::GREGORIAN,
                                                      'EEE, dd MMM, HH:mm'
                                                );
                                                $last_date = $formatter->format(strtotime($chat['last_date']));

                                                // Escapar mensaje para HTML
                                                $lastMessage = htmlspecialchars($chat['last_message']);

                                                // Concatenar HTML
                                                echo $htmlBusHTML = "<tr>
                                                      <td>{$chat['sender_phone']}</td>
                                                      <td>{$contact_name}</td>
                                                      <td>{$lastMessage}</td>
                                                      <td>{$last_date} ...</td>
                                                      <td style='text-align: center;'>
                                                         <div class='row'>
                                                            <div class='col-md-4'>
                                                                  <span class='badge badge-pill badge-warning' style='cursor: pointer;' id='btn-read-w' title='Leer' data-phone='{$chat['sender_phone']}'>
                                                                     <i class='fa fa-whatsapp fa-lg' aria-hidden='true'></i>
                                                                  </span>
                                                            </div>
                                                         </div>
                                                      </td>
                                                </tr>";
                                          }
                                    }
                                 }else{
                                    echo $htmlChat;
                                 }
                                 ?>
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
	  	   include('modal/chat-w.php');
         require_once('modal/waba-template.php');
         require_once('modal/admin-template.php');
      	require_once('footer.php');
      ?>
   </body>
</html>