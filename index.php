<?php
define( '_VALID_MOS', 1 );
require_once('includes/configuration.php');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title><?php echo PAGE_TITLE; ?></title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">

    <!-- Favicon -->
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo BASE_URL;?>/assets/img/favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo BASE_URL;?>/assets/img/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="<?php echo BASE_URL;?>/assets/img/favicon/favicon-16x16.png">
    <link rel="mask-icon" href="<?php echo BASE_URL;?>/assets/img/favicon/safari-pinned-tab.svg" color="#5bbad5">
    <meta name="msapplication-TileColor" content="#da532c">
    <meta name="theme-color" content="#ffffff">
    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">

    <!-- Customized Bootstrap Stylesheet -->
    <link href="<?php echo BASE_URL;?>/assets/css/index.css" rel="stylesheet">
    <script src="<?php echo BASE_URL;?>/assets/js/libraries/sweetalert.min.js"></script>

    <script>
        let base_url = '<?php echo BASE_URL;?>';
    </script>
</head>

<body>
    <!-- Navbar Start -->
    <div class="container-fluid p-0">
        <nav class="navbar navbar-expand-lg bg-light navbar-light py-3 py-lg-0 px-lg-5">
            <a href="index.php" class="navbar-brand ml-lg-3">
                <h1 class="m-0 display-5 text-uppercase text-primary"><i class="fa fa-tree mr-2"></i>Paquetería Los Pinos</h1>
            </a>
            <button type="button" class="navbar-toggler" data-toggle="collapse" data-target="#navbarCollapse">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-between px-lg-3" id="navbarCollapse">
                <div class="navbar-nav m-auto py-0">
                    <a href="index.php" class="nav-item nav-link active">Inicio</a>
                </div>
            </div>
        </nav>
    </div>
    <!-- Navbar End -->

    <!-- Header Start -->
    <div class="jumbotron jumbotron-fluid mb-5">
        <div class="container text-center py-5">
            <h1 class="text-primary mb-4">Seguro y Rápido</h1>
            <h1 class="text-white display-3 mb-5">Consultar Guía</h1>
            <div class="mx-auto" style="width: 100%; max-width: 600px;">
                <div class="input-group">
                    <input id="tracking" type="text" class="form-control border-light" style="padding: 30px;" placeholder="Ingresa Número de Guía J&T" autofocus autocomplete="off" value="">
                    <div class="input-group-append">
                        <button id="btn-search" class="btn btn-primary px-3">Buscar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Header End -->

    <!-- About Start -->
    <div class="container-fluid py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-5 pb-4 pb-lg-0">
                    <img class="img-fluid w-100" src="<?php echo BASE_URL;?>/assets/img/about.jpg" alt="">
                    <div class="bg-primary text-dark text-center p-4">
                        <h3 class="m-0">5+ Años de Experiencia</h3>
                    </div>
                </div>
                <div class="col-lg-7">
                    <h6 class="text-primary text-uppercase font-weight-bold">Acerca de Nosotros</h6>
                    <h1 class="mb-4">Proveedor de servicios logísticos más rápido y confiable</h1>
                    <p class="mb-4">En Paquetería Los Pinos, nos enorgullecemos de ser el proveedor líder de servicios logísticos, ofreciendo soluciones rápidas y confiables para todas tus necesidades de envío. Con años de experiencia en la industria, nuestra dedicación a la eficiencia y la excelencia nos ha convertido en la opción preferida para empresas y particulares que buscan un servicio de calidad.</p>
                </div>
            </div>
        </div>
    </div>
    <!-- About End -->

    <div class="container-fluid bg-secondary my-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-5">
                    <img class="img-fluid w-100" src="<?php echo BASE_URL;?>/assets/img/feature.jpg" alt="">
                </div>
                <div class="col-lg-7 py-5 py-lg-0">
                    <h6 class="text-primary text-uppercase font-weight-bold">Servicio de envío internacional</h6>
                    <h1 class="mb-4">¿Te urge enviar algo a los Estados Unidos?</h1>
                    <p class="mb-4">¿Necesitas que los envíos lleguen rápido? En Paquetería Los Pinos contamos con un servicio de envío internacional con entrega en <b>24 o 48 horas</b>, donde podrás enviar:</p>
                    <ul class="list-inline">
                        <li><h6><i class="far fa-dot-circle text-primary mr-3"></i>Cecina, Queso, Barbacoa, Carne de puerco</h6></li>
                        <li><h6><i class="far fa-dot-circle text-primary mr-3"></i>Medicina natural y de patente, Hierbas secas</h6></li>
                        <li><h6><i class="far fa-dot-circle text-primary mr-3"></i>Pan de muerto, Semillas, Mole, Dulces</h6></li>
                        <li><h6><i class="far fa-dot-circle text-primary mr-3"></i>Documentos, Oro, Plata</h6></li>
                        <li><h6><i class="far fa-dot-circle text-primary mr-3"></i>Y mucho más ... </h6></li>
                        <li><h6><i class="far fa-dot-circle text-primary mr-3"></i>Rápido, Seguro y Eficaz</h6></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer Start -->
    <div class="container-fluid bg-dark text-white mt-5 py-5 px-sm-3 px-md-5">
        <div class="row pt-5">
            <div class="col-lg-7 col-md-6">
                <div class="row">
                    <div class="col-md-6 mb-5">
                        <h3 class="text-primary mb-4">Sucursal - Tlaquiltenango</h3>
                        <p><i class="fa fa-map-marker-alt mr-2"></i>Nicolás Bravo 203, Gabriel Tepepa, 62980 Tlaquiltenango, Mor.</p>
                        <p><i class="fa fa-clock" aria-hidden="true"></i> Horario de Lunes a Viernes de 09:00am a 05:00pm</p>
                        <p><i class="fa fa-clock" aria-hidden="true"></i> Sábados: Horario variado</p>
                        <p><i class="fa fa-phone-alt mr-2"></i><a href="tel:+5217341326995">+52 1 734 132 6995</a></p>
                        <p><i class="fa fa-envelope mr-2"></i><a href="mailto:ciriloa@paqueterialospinos.com">ciriloa@paqueterialospinos.com</a></p>
                    </div>

                    <div class="col-md-6 mb-5">
                        <h3 class="text-primary mb-4">Sucursal - Zacatepec</h3>
                        <p><i class="fa fa-map-marker-alt mr-2"></i>Francisco I. Madero 6, Centro, 62780 Zacatepec de Hidalgo, Mor.</p>
                        <p><i class="fa fa-clock" aria-hidden="true"></i> Horario de Lunes a Viernes de 09:00am a 05:00pm</p>
                        <p><i class="fa fa-clock" aria-hidden="true"></i> Sábados de 09:00am a 02:00pm</p>
                        <p><i class="fa fa-phone-alt mr-2"></i><a href="tel:+5217341109763">+52 1 734 110 9763</a></p>
                        <p><i class="fa fa-envelope mr-2"></i><a href="mailto:josuea@paqueterialospinos.com">josuea@paqueterialospinos.com</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="container-fluid bg-dark text-white border-top py-4 px-sm-3 px-md-5" style="border-color: #3E3E4E !important;">
        <div class="row">
            <div class="col-lg-6 text-center text-md-left mb-3 mb-md-0">
                <p class="m-0 text-white">&copy; <a href="#">www.paqueterialospinos.com</a> All Rights Reserved. 
				<!--/*** This template is free as long as you keep the footer author’s credit link/attribution link/backlink. If you'd like to use the template without the footer author’s credit link/attribution link/backlink, you can purchase the Credit Removal License from "https://htmlcodex.com/credit-removal". Thank you for your support. ***/-->
				Designed by <a href="https://htmlcodex.com" target="_blank">HTML Codex</a>
                </p>
            </div>
        </div>
    </div>
    <!-- Footer End -->

    <!-- Back to Top -->
    <a href="#" class="btn btn-lg btn-primary back-to-top"><i class="fa fa-angle-double-up"></i></a>

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.bundle.min.js"></script>

    <!-- Template Javascript -->
    <script src="<?php echo BASE_URL;?>/assets/js/index.js"></script>
    <script src="<?php echo BASE_URL;?>/assets/js/functions.js"></script>
</body>
</html>