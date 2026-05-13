<?php
$url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
$url .= "://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
$pagina = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_FILENAME);
$display='initial';
if($pagina=='dashboard'){
   $display='none';
}
$txtchg    = ($_SESSION['uLocation']==2) ? "Tlaquiltenango":"Zacatepec";
$txtchgval = ($_SESSION['uLocation']==2) ? 1:2;
$protocol  = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://";
// Host
$host = $_SERVER['HTTP_HOST'];

$patImg = 'user'; // valor por defecto
switch ($_SESSION["uId"]) {
    case 1:
        $patImg = 'isi';
        break;
    case 2:
        $patImg = 'karen';
        break;
    case 4:
        $patImg = 'josue';
        break;
    default:
        $patImg = 'user';
        break;
}
?>
<nav id="sidebar" class="active" style="display: none;">
   <div class="sidebar_blog_1">
      <div class="sidebar-header">
         <div class="logo_section">
            <a href="dashboard.php"><img id="profileImg" class="logo_icon img-responsive" src="images/logo/logo_icon.png" alt="#" /></a>
         </div>
      </div>
      <div class="sidebar_user_info">
         <div class="icon_setting"></div>
         <div class="user_profle_side">
            <div class="user_img"><img class="img-responsive" src="images/layout_img/<?php echo $patImg; ?>.jpg" alt="#" /></div>
            <div class="user_info">
               <h6><?php echo $_SESSION["uName"];?></h6>
               <p><?php echo $desc_loc?></p>
            </div>
         </div>
      </div>
   </div>
   <div class="sidebar_blog_2">
      <h4>Panel</h4>
      <ul class="list-unstyled components">
         <li>
            <a href="dashboard.php" class="onclikload"><i class="fa fa-dashboard blue2_color"></i> <span>Dashboard</span></a>
         </li>
         <li>
            <a href="packages.php" class="onclikload"><i class="fa fa-cubes blue1_color"></i> <span>Paquetes</span></a>
         </li>
         <li>
            <a href="whatsapp.php" class="onclikload"><i class="fa fa-whatsapp green_color"></i> <span>WhatsApp</span></a>
         </li>
		 <li>
            <a href="reports.php" class="onclikload"><i class="fa fa-bar-chart purple_color"></i> <span>Reportes</span></a>
         </li>
         <li>
            <a href="contacts.php" class="onclikload"><i class="fa fa-users red_color"></i> <span>Contactos</span></a>
         </li>
         <li>
            <a href="admin.php" class="onclikload"><i class="fa fa-gears  yellow_color"></i> <span>Admin</span></a>
         </li>
         <li>
			   <a href="#" id="logoff"><i class="fa fa-sign-out orange_color"></i> <span>Salir</span></a>
		   </li>
      </ul>
   </div>
</nav>