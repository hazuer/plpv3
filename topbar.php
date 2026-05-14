<style>
.full {
    display: flex;
    align-items: center;
}
.logo_section {
    margin-right: 20px;
}
.left_topbar {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-right: auto; /* Empuja lo demás a la derecha */
}
.search_input {
    padding: 6px 12px;
    border: 1px solid #ccc;
    border-radius: 20px;
    font-size: 14px;
    outline: none;
    transition: all 0.3s ease;
}
.search_input:focus {
    border-color: #03a9f4;
    box-shadow: 0 0 5px rgba(3,169,244,0.4);
}
</style>
<div class="topbar">
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="full">
            <button type="button" id="sidebarCollapse" class="sidebar_toggle">
                <i class="fa fa-chevron-right"></i>
            </button>
            <!-- Logo -->
            <!--<div class="logo_section">
                <a href="index.html"><img class="img-responsive" src="images/logo/logo.png" alt="#" /></a>
            </div>
            -->
            <?php
            $colorActiveMenu = '';
            $nameOptionMenu      = '';
            if($pagina=='packages'){ $colorActiveMenu="#2196f3"; $nameOptionMenu='Paquetes'; }
            if($pagina=='whatsapp' || $pagina=='status_meta' || $pagina=='admin_templates'){$colorActiveMenu="#009688"; $nameOptionMenu='WhatsApp'; }
            if($pagina=='reports' || $pagina=='reportspe'|| $pagina=='prereg' || $pagina=='audit'){ $colorActiveMenu="#673ab7"; $nameOptionMenu='Reportes'; }
            if($pagina=='contacts'){ $colorActiveMenu="#e91e63"; $nameOptionMenu='Contactos'; }
            if($pagina=='admin'){ $colorActiveMenu="#ff9800"; $nameOptionMenu='Admin'; }
            ?>
            <div class="left_topbar" style="margin-left:0px;">
                <div class="icon_info">
                    <ul class="user_profile_dd" style="display: <?php echo $display; ?>">
                        <li style="background-color: <?php echo $colorActiveMenu;?> !important;"> <!-- #15283c !important -->
                            <a class="dropdown-toggle" data-toggle="dropdown">
                                <span class="xname_user"><i class="fa fa-bars"></i> <span id="name_user"><?php echo $nameOptionMenu;?></span></span>
                            </a>
                            <div class="dropdown-menu">
                                <?php
                                if($pagina=='packages'){
                                ?>
                                    <a href="packages.php"><span>Paquetes</span></a>
                                	<a href="#" id="btn-add-package"><span>Nuevo paquete</span></a>
                                    <?php if($host==NAME_HOST_LOCAL){?>
                                    <a href="#" id="btn-template"><span>Plantilla de bot</span></a>
									<a href="#" id="btn-bot"><span>Crear bot</span></a>
									<a href="handler.php"><span>Envio manual</span></a>
                                    <?php }?>
                                    <a href="#" id="btn-folio"><span>Folio</span></a>
                                    <a href="#" id="btn-ocurre"><span>Crear código barras</span></a>
                                    <a href="#" id="btn-sync"><span>Guías no liberadas</span></a>
                                <?php
                                }
                                if($pagina=='whatsapp' || $pagina=='status_meta' || $pagina=='admin_templates'){
                                ?>
                                 	<!--mesajes-->
									<a href="whatsapp.php"><span>Chats</span></a>
                                    <a href="#" id="new-template"><span>Nueva plantilla meta</span></a>
                                    <a href="admin_templates.php"><span>Admin plantillas meta</span></a>
									<a href="#" id="waba-template"><span>Envío de mensajes meta</span></a>
                                    <a href="status_meta.php"><span>Estatus mensajes enviados</span></a>
                                <?php
                                }
                                 if($pagina=='reports' || $pagina=='reportspe'|| $pagina=='prereg' || $pagina=='audit'){
                                    ?>
                                    <a href="reports.php"><span>Reporte personalizado</span></a>
									<a href="reportspe.php"><span>Porcentaje entrega</span></a>
									<a href="prereg.php"><span>Sin rotular</span></a>
									<a href="audit.php"><span>Auditoria</span></a>
                                <?php
                                }
                                if($pagina=='contacts'){
                                ?>
                                 	<a href="#" id="btn-add-contact"><span>Nuevo contacto</span></a>
                                <?php 
								}
                                if($pagina=='admin'){
								?>
                                 <a href="admin.php"><span>Inventario</span></a>
                                 <a href="#" id="onOffBot" data-enable="<?php echo $enable_bot; ?>"><span><?php echo $text_enable_bot; // from admin.php?></span></a>
                                 <!-- <a href="dashboard.html"><span>Cambiar estatus</span></a>
                                 <a href="dashboard.html"><span>Cambiar ubicación</span></a> -->
                                 <?php 
								}
								?>
                                <?php foreach($getSelectCatalog as $loc){ ?>

                                <?php if($loc['id_location'] != $_SESSION['uLocation']){ ?>

                                    <a 
                                        class="dropdown-item option-location-change" 
                                        href="#"
                                        data-slocation="<?php echo $loc['id_location']; ?>"
                                        data-slocationd="<?php echo $loc['location_desc']; ?>"
                                    >
                                        <span><?php echo $loc['location_desc']; ?></span>
                                    </a>

                                <?php } ?>

                            <?php } ?>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
            <input type="text" class="search_input" placeholder="Rotular/Verificar" id="vGuia">
            <!-- Menú de iconos y usuario a la derecha -->
            <div class="right_topbar">
                <div class="icon_info">
                    <ul>
                        <li><a href="#" id="btn-scan-ocr"><i class="fa fa-mobile blue1_color fa-lg"></i></a></li>
                        <li><a href="#" id="btn-scan-qr"><i class="fa fa-qrcode blue1_color fa-lg"></i></a></li>
                        <?php 
                        if($totalMensajeSinLeer>0){
                        ?>
                        <li><a href="whatsapp.php"><i class="fa fa-whatsapp green_color fa-lg"></i><span class="badge online_animation"><?php
                         echo $totalMensajeSinLeer;
                        }
                        ?></span></a></li>
                    </ul>
                    <ul class="user_profile_dd">
                        <li>
                            <a class="dropdown-toggle" data-toggle="dropdown">
                                <span class="name_user"><?php echo $desc_loc;?></span></a>
                              <div class="dropdown-menu">
                                <?php foreach($getSelectCatalog as $loc){ ?>
                                <?php if($loc['id_location'] != $_SESSION['uLocation']){ ?>
                                    <a 
                                        class="dropdown-item option-location-change" 
                                        href="#"
                                        data-slocation="<?php echo $loc['id_location']; ?>"
                                        data-slocationd="<?php echo $loc['location_desc']; ?>"
                                    >
                                        <span><?php echo $loc['location_desc']; ?></span>
                                    </a>
                                <?php } ?>

                            <?php } ?>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>
</div>
<?php 
#var_dump($fullSessionLocation); 
#var_dump("<br>");
?>

<select name="option-location" id="option-location" style="display: none;">
    <?php foreach($getSelectCatalog as $loc){ ?>
        <option 
            value="<?php echo $loc['id_location']; ?>"
            <?php echo ($_SESSION['uLocation'] == $loc['id_location']) ? 'selected' : ''; ?>>
            <?php echo $loc['location_desc']; ?>
        </option>
    <?php } ?>

</select>