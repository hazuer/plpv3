<?php
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

$sqlTemplates ="SELECT id_template,name FROM cat_template WHERE type_template IN(2) AND status IN (1)";
$template = $db->select($sqlTemplates);

$sqlListTemplateWaba="SELECT * FROM cat_template WHERE type_template IN(2)";
$listTemplates = $db->select($sqlListTemplateWaba);
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
                              <h2>Admin Plantillas Meta</h2>
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
                                 <table id="tbl-list-templates"class="table table-striped table-hover" cellspacing="0" style="width:100%">
                                    <thead class="thead-dark">
                                       <tr>
                                          <th>ID</th>
                                          <th>Nombre</th>
                                          <th>Plantilla</th>
                                          <th>Acciones</th>
                                       </tr>
                                    </thead>
                                    <tbody>
                                       <?php foreach($listTemplates as $d):

                                          echo "<tr>";
                                          echo "<td>".$d['id_template']."</td>";
                                          echo "<td>".$d['name']."</td>";
                                          echo "<td>".$d['template']."</td>";
                                          echo "<td><button class=\"btn btn-sm btn-danger btn-delete-template\"
                                                data-id=\"".$d['id_template']."\"
                                                data-name=\"".$d['name']."\">
                                             <i class=\"fa fa-trash\"></i> Eliminar
                                          </button>
                                                </td>";
                                          echo "</tr>";
                                       ?> 
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
        include('modal/chat-w.php');
        require_once('modal/waba-template.php');
        require_once('modal/admin-template.php');
      	require_once('footer.php');
    ?>
   </body>
</html>