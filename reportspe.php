<?php
session_start();
define( '_VALID_MOS', 1 );

require_once('includes/configuration.php');
require_once('includes/DB.php');
$db = new DB(HOST,USERNAME,PASSWD,DBNAME,PORT,SOCKET);
require_once('includes/session.php');

$id_location = $_SESSION['uLocation'];

$rParcel   = $_POST['rParcel'] ?? 99;
$andParcelIn = "";
if(isset($rParcel)){
	if($rParcel!=99){
		$andParcelIn = " AND p.id_cat_parcel IN ($rParcel)";
	}else{
		$andParcelIn = " AND p.id_cat_parcel IN (1,2,3)";
	}
}
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

        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
         <script src="<?php echo BASE_URL;?>/assets/js/reports.js?version=<?php echo time(); ?>"></script>
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
                              <h2>Porcentaje de Entrega <?php echo date('F');?> <?php echo $desc_loc;?></h2>
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
                                    			
                                <form id="frm-reports" action="<?php echo BASE_URL;?>/reportspe.php" method="POST">
                                                <div class="row">
                                                    <div class="col-md-2">
                                                        <div class="form-group">
                                                            <label for="rParcel"><b>Paquetería:</b></label>
                                                            <select name="rParcel" id="rParcel" class="form-control">
                                                                <option value="99" <?php echo ($rParcel==99) ? 'selected': ''; ?>>Todas</option>
                                                                <option value="1" <?php echo ($rParcel==1) ? 'selected': ''; ?>>J&T</option>
                                                                <option value="2" <?php echo ($rParcel==2) ? 'selected': ''; ?>>IMILE</option>
                                                                <option value="3" <?php echo ($rParcel==3) ? 'selected': ''; ?>>CNMEX</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-1"><br>
                                                        <div class="form-group">
                                                            <button type="submit" class="btn btn-success" title="Filtrar" data-dismiss="modal">Filtrar</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </form>
                                            <hr>
                                            <?php
                                            // Obtener el día actual del mes
                                            $current_day = date('j');

                                            // Obtener el último día del mes actual con el día actual del mes
                                            $last_day_of_month = date('t'); // Último día del mes actual
                                            $end_date = date('Y-m-' . min($current_day, $last_day_of_month)); // Ajusta al día actual del mes o al último día del mes si es mayor

                                            // Crear un objeto DateTime con la fecha actual
                                            $start = new DateTime();
                                            // Restar meses a la fecha actual
                                            $start->modify('0 months');
                                            // Opcionalmente, establecer el primer día del mes resultante
                                            $start->modify('first day of this month');
                                            // Formatear la fecha para mostrarla o usarla en el formato que necesites
                                            $lastMonth=$start->format('Y-m-d'); 

                                            $start = new DateTime(date($lastMonth)); // Primer día del mes actual
                                            $end   = new DateTime($end_date); // Último día del mes actual con el día actual del mes
                                            $c=0;
                                            // Bucle desde la fecha de y/ hasta la fecha de fin

                                            for ($date = $end;  $start <= $date; $date->modify('-1 day')) {
                                                $c++;
                                                // Formatear la fecha para la consulta
                                                $current_date = $date->format('Y-m-d');
                                                $start_datetime = $current_date . ' 00:00:00';
                                                $end_datetime = $current_date . ' 23:59:59';

                                                // Consulta SQL
                                                $sql = "SELECT 
                                                    p.id_status,
                                                    s.status_desc,
                                                    COUNT(*) AS count,
                                                    SUM(CASE 
                                                        WHEN DATE(p.c_date) = DATE(p.d_date) THEN 1 
                                                        ELSE 0 
                                                    END) AS same_day_delivery,
                                                    SUM(CASE 
                                                        WHEN DATE(p.c_date) != DATE(p.d_date) THEN 1 
                                                        ELSE 0 
                                                    END) AS different_day_delivery
                                                FROM package p
                                                JOIN cat_status s ON p.id_status = s.id_status
                                                WHERE 
                                                    p.c_date BETWEEN '$start_datetime' 
                                                    AND '$end_datetime'
                                                    AND p.id_location IN ($id_location) 
                                                    $andParcelIn
                                                GROUP BY p.id_status, s.status_desc
                                                ORDER BY p.id_status";

                                                $result = $db->select($sql);
                                                echo "<div class='row'>";
                                                if (count($result) > 0) {
                                                    $total_count = 0;

                                                    foreach ($result as $item) {
                                                        $total_count += intval($item['count']); // Convertir a entero antes de sumar
                                                    }

                                                    echo "<div class='col-md-6'>
                                                    <table class='table table-striped table-bordered nowrap table-hover' cellspacing='0' style='width:100%'>
                                                    <tr><td colspan='6'>Paquetes registrados el día $current_date, Total:<b> ".$total_count." (100%)</b></td></tr>";
                                                    foreach ($result as $row) {
                                                        $p= round(((100/$total_count)*$row["count"]),1);
                                                        if($row["id_status"]==3){
                                                            $pmd=round(((100/$row["count"])*$row["same_day_delivery"]),1);
                                                            $pdd=round(((100/$row["count"])*$row["different_day_delivery"]),1);
                                                            echo "<tr>
                                                            <td>".$row["status_desc"]."(s)</td>
                                                            <td>".$row["count"]." (".$p."%)</td>
                                                            <td>Entregados mismo día</td>
                                                            <td>".$row["same_day_delivery"]." (".$pmd."%)</td>
                                                            <td>Entregados después del día </td>
                                                            <td>".$row["different_day_delivery"]." (".$pdd."%)</td></tr>";
                                                        }else{
                                                                echo "<tr><td>".$row["status_desc"]."(s)</td><td>".$row["count"]." (".$p."%)</tr>";
                                                        }
                                                    }
                                                    echo "</table>
                                                    </div>
                                                    <div class='col-md-6'>";

                                                    // Extraer etiquetas y valores
                                                    $labels = [];
                                                    $data = [];

                                                    foreach ($result as $item) {
                                                        $labels[] = $item['status_desc']; // Nombre del estado
                                                        $data[]   = (int)$item['count'];    // Cantidad (convertida a número)
                                                    }

                                                    // Convertir arrays PHP a JSON para JavaScript
                                                    $labelsJson  = json_encode($labels);
                                                    $dataJson    = json_encode($data);
                                                    $nameDateDiv = str_replace("-", "", $current_date);
                                                    ?>
                                                    <canvas id="myChart_<?php echo $nameDateDiv;?>"></canvas>

                                                    <script>
                                                    // Convertir los datos de PHP a JavaScript
                                                    const labels_<?php echo $nameDateDiv;?> = <?php echo $labelsJson; ?>;
                                                    const dataValues_<?php echo $nameDateDiv;?> = <?php echo $dataJson; ?>;

                                                    // Crear el gráfico con Chart.js
                                                    const ctx_<?php echo $nameDateDiv;?> = document.getElementById('myChart_<?php echo $nameDateDiv;?>').getContext('2d');
                                                    new Chart(ctx_<?php echo $nameDateDiv;?>, {
                                                        type: 'pie', // Puedes cambiar a 'pie' o 'doughnut'
                                                        data: {
                                                            labels: labels_<?php echo $nameDateDiv;?>,
                                                            datasets: [{
                                                                label: 'Total',
                                                                data: dataValues_<?php echo $nameDateDiv;?>,
                                                                backgroundColor: [
                                                                    'rgba(54, 162, 235, 0.6)',   // azul claro
                                                                    'rgba(75, 192, 192, 0.6)',   // verde agua
                                                                    'rgba(255, 99, 132, 0.6)',   // rojo rosado
                                                                    'rgba(255, 206, 86, 0.6)',   // amarillo
                                                                    'rgba(153, 102, 255, 0.6)',  // morado
                                                                    'rgba(255, 159, 64, 0.6)',   // naranja
                                                                    'rgba(60, 179, 113, 0.6)',   // verde medio
                                                                    'rgba(199, 199, 199, 0.6)'   // gris claro
                                                                ],
                                                                borderColor: [
                                                                    'rgba(54, 162, 235, 1)',   // azul claro
                                                                    'rgba(75, 192, 192, 1)',   // verde agua
                                                                    'rgba(255, 99, 132, 1)',   // rojo rosado
                                                                    'rgba(255, 206, 86, 1)',   // amarillo
                                                                    'rgba(153, 102, 255, 1)',  // morado
                                                                    'rgba(255, 159, 64, 1)',   // naranja
                                                                    'rgba(60, 179, 113, 1)',   // verde medio
                                                                    'rgba(199, 199, 199, 1)'   // gris claro
                                                                ],
                                                                borderWidth: 1
                                                            }]
                                                        },
                                                        options: {
                                                            responsive: true,
                                                            plugins: {
                                                                title: {
                                                                    display: true,          // Muestra el título
                                                                    text: 'Paquetes registrados el día <?php echo $current_date?>, Total: <?php echo $total_count?>',  // Título del gráfico
                                                                    font: {
                                                                        size: 18,           // Tamaño de la fuente
                                                                        family: 'Arial',     // Familia tipográfica
                                                                    },
                                                                    padding: 20             // Espaciado alrededor del título
                                                                },
                                                                legend: {
                                                                    position: 'top',      // Posición de la leyenda
                                                                }
                                                            },
                                                            // Establecer la altura directamente
                                                            maintainAspectRatio: false,
                                                        }
                                                    });
                                                </script>

                                                <?php
                                                    echo "</div>
                                                ";
                                                } else {
                                                    echo "<div class='col-md-12'><table class='table table-striped table-bordered nowrap table-hover' cellspacing='0' style='width:49%'>
                                                    <tr><td>Día $current_date, sin registro de paquetes</td></tr>
                                                    </table>
                                                    </div>";
                                                }
                                                echo "</div>";
                                            }
                                    ?>
                                    <hr>
                                    <?php
                                    // Obtener el día actual del mes
$diaHoy       = date('j');
// Obtener el último día del mes actual con el día actual del mes
$ultimoDiaMes = date('t'); // Último día del mes actual
$ultimoD      = date('Y-m-' . min($diaHoy, $ultimoDiaMes)); // Ajusta al día actual del mes o al último día del mes si es mayor

// Convertir las fechas a objetos DateTime
#$diaInicial = new DateTime(date('Y-m-01')); // Primer día del mes actual
$diaInicial = new DateTime(date('Y-m-01')); // 'Y' es el año actual, 'm' es el mes actual y '01' es el primer día
$diaFinal   = new DateTime($ultimoD); // Último día del mes actual con el día actual del mes

$fini = $diaInicial->format('Y-m-d');
$f1   = $fini . ' 00:00:00';

$ffin = $diaFinal->format('Y-m-d');
$f2   = $ffin . ' 23:59:59';

$sql2="SELECT 
    p.id_status, 
    s.status_desc, 
    COUNT(*) AS count 
    FROM package p 
    JOIN cat_status s ON p.id_status = s.id_status 
    WHERE 
        p.c_date BETWEEN '$f1' AND '$f2' 
        AND p.id_location IN ($id_location) 
        $andParcelIn 
    GROUP BY p.id_status, s.status_desc 
    ORDER BY p.id_status";
$rst = $db->select($sql2);
$tpm = 0;

// Recorrer el arreglo y sumar los valores de 'count'
foreach ($rst as $item) {
    $tpm += intval($item['count']); // Convertir a entero antes de sumar
}
                                    ?>


 <!-- ---------- chart -->
                                                      <div class="row" style="text-align: center;">
                                                        <div class='col-md-12'><h4><?php echo "Resumen del mes de ".date('F')." ".$desc_loc." Periodo del ".$fini." al ".$ffin;?></h4></div>
                                                      </div>
                                                <div class="row">
                                                        <div class='col-md-6'>
                                                            <?php
                                                            // Crear la tabla HTML
                                                            echo "<table class='table table-striped table-bordered nowrap table-hover' cellspacing='0' style='width:100%'>";
                                                            echo "<tr><th colspan='3' style='text-align:center;'>Total: ".$tpm." paquetes</th></tr>";
                                                            echo "<tr><th>Estatus</th><th>Total</th><th>Porcentaje</th></tr>";

                                                            // Recorrer el arreglo y generar las filas de la tabla
                                                            foreach ($rst as $row) {
                                                                $p= round(((100/$tpm)*$row["count"]),2);
                                                                echo "<tr>";
                                                                echo "<td>" . $row["status_desc"] . "</td>";
                                                                echo "<td>" . $row["count"] . "</td>";
                                                                echo "<td>" . $p . "%</td>";
                                                                echo "</tr>";
                                                            }
                                                            echo "</table>";

                                                            $labels = [];
                                                            $data = [];
                                                            ?>
                                                        </div>
                                                        <div class='col-md-6'>
                                                            <canvas id="myChart"></canvas>
                                                            <?php
                                                                foreach ($rst as $item) {
                                                                $labels[] = $item['status_desc']; // Nombre del estado
                                                                $data[]   = (int)$item['count'];    // Cantidad (convertida a número)
                                                                }

                                                                // Convertir arrays PHP a JSON para JavaScript
                                                                $labelsJson = json_encode($labels);
                                                                $dataJson   = json_encode($data);
                                                            ?>
                                                            <script>
                                                                // Convertir los datos de PHP a JavaScript
                                                                const labels     = <?php echo $labelsJson; ?>;
                                                                const dataValues = <?php echo $dataJson; ?>;

                                                                // Crear el gráfico con Chart.js
                                                                const ctx = document.getElementById('myChart').getContext('2d');
                                                                new Chart(ctx, {
                                                                type: 'pie', // Puedes cambiar a 'pie' o 'doughnut'
                                                                data: {
                                                                    labels: labels,
                                                                    datasets: [{
                                                                        label: 'Total',
                                                                        data: dataValues,
                                                                        backgroundColor: [
                                                                            'rgba(54, 162, 235, 0.6)',   // azul claro
                                                                            'rgba(75, 192, 192, 0.6)',   // verde agua
                                                                            'rgba(255, 99, 132, 0.6)',   // rojo rosado
                                                                            'rgba(255, 206, 86, 0.6)',   // amarillo
                                                                            'rgba(153, 102, 255, 0.6)',  // morado
                                                                            'rgba(255, 159, 64, 0.6)',   // naranja
                                                                            'rgba(60, 179, 113, 0.6)',   // verde medio
                                                                            'rgba(199, 199, 199, 0.6)'   // gris claro
                                                                        ],
                                                                        borderColor: [
                                                                            'rgba(54, 162, 235, 1)',   // azul claro
                                                                            'rgba(75, 192, 192, 1)',   // verde agua
                                                                            'rgba(255, 99, 132, 1)',   // rojo rosado
                                                                            'rgba(255, 206, 86, 1)',   // amarillo
                                                                            'rgba(153, 102, 255, 1)',  // morado
                                                                            'rgba(255, 159, 64, 1)',   // naranja
                                                                            'rgba(60, 179, 113, 1)',   // verde medio
                                                                            'rgba(199, 199, 199, 1)'   // gris claro
                                                                            ],
                                                                        borderWidth: 1
                                                                    }]
                                                                },
                                                                options: {
                                                                    responsive: true,
                                                                    plugins: {
                                                                        title: {
                                                                            display: true,          // Muestra el título
                                                                            text: 'Total: <?php echo $tpm; ?> paquetes',  // Título del gráfico
                                                                            font: {
                                                                                size: 18,           // Tamaño de la fuente
                                                                                family: 'Arial',     // Familia tipográfica
                                                                            },
                                                                            padding: 20             // Espaciado alrededor del título
                                                                        },
                                                                        legend: {
                                                                        position: 'top',      // Posición de la leyenda
                                                                        }
                                                                    },
                                                                    // Establecer la altura directamente
                                                                    maintainAspectRatio: false,
                                                                }
                                                                });
                                                            </script>
                                                        </div>
                                                    </div>
                                                                                                        <!-- ---------- chart -->

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
        include('modal/sms-report.php');
		include('modal/logger.php');
		include('modal/evidence.php');
        require_once('footer.php');
      ?>
   </body>
</html>