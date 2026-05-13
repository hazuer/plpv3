<?php
session_start();
define( '_VALID_MOS', 1 );
#error_reporting(E_ALL);
#ini_set('display_errors', '1');

require_once('includes/configuration.php');
require_once('includes/DB.php');
$db = new DB(HOST,USERNAME,PASSWD,DBNAME,PORT,SOCKET);
require_once('includes/session.php');

$id_location = $_SESSION['uLocation'];

# total de paquetes
$sql = "SELECT 
p.id_package 
FROM package p 
WHERE 1 
AND p.id_location IN ($id_location)
AND p.id_status IN(1,2,5,6,7,8)";
$tpackages = $db->select($sql);

$sqlpre = "SELECT 
p.id_package 
FROM package_tmp p 
WHERE 1 
AND p.id_location IN ($id_location)";
$tpre = $db->select($sqlpre);

$rParcel     = $_POST['rParcel'] ?? 99;
$andParcelIn = " AND p.id_cat_parcel IN (1,2,3)";
// Obtener el día actual del mes
$diaHoy       = date('j');
// Obtener el último día del mes actual con el día actual del mes
$ultimoDiaMes = date('t'); // Último día del mes actual
$ultimoD      = date('Y-m-' . min($diaHoy, $ultimoDiaMes)); // Ajusta al día actual del mes o al último día del mes si es mayor

// Convertir las fechas a objetos DateTime
#$diaInicial = new DateTime(date('Y-m-01')); // Primer día del mes actual
$diaInicial = new DateTime($ultimoD); // 'Y' es el año actual, 'm' es el mes actual y '01' es el primer día
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

//Porcentaje entrega por usuario
$sqlPorcentaje="SELECT id,user 
FROM users";
$rstpu = $db->select($sqlPorcentaje);
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
        <script src="<?php echo BASE_URL;?>/assets/js/dashboard.js?version=<?php echo time(); ?>"></script>
        <style>
            .counter_no:hover,
            .counter_no:hover .couter_icon {
                transform: scale(1.05);
            }

            .counter_no:hover .couter_icon {
                color: #007bff; /* ejemplo: cambia color del ícono */
                transition: all 0.3s ease;
            }
        </style>
    </head>
    <body class="dashboard dashboard_1">
        <div class="full_container">
            <div class="inner_container">
                <?php include_once('sidebar.php');?>
                <div id="content">
                    <?php include_once('topbar.php');?>
                    <div class="midde_cont">
                        <div class="container-fluid">
                            <div class="row column_title"><br>
                                <!-- <div class="col-md-12">
                                    <div class="page_title">
                                        <h2>Dashboard</h2>
                                    </div>
                                </div> -->
                            </div>
                            <div class="row column1">
                                <div class="col-md-12 col-lg-2 d-flex">
                                    <div class="card w-100 shadow-sm mb-4">
                                        <div class="card-header text-center bg-light">
                                            <h4 class="mb-0">En Ruta</h4>
                                        </div>
                                        <div class="card-body text-center">
                                            <br>
                                            <div class="d-flex justify-content-center flex-wrap gap-2">
                                                <span class="badge px-3 py-2" style="background:#03a9f4; color:#fff; font-size:14px; font-weight:bold;">
                                                <?php echo count($tpackages); ?></span>
                                            </div>
                                            <a href="packages.php" class="counter_no">
                                                <div class="couter_icon">
                                                <i class="fa fa-cubes blue1_color"></i>
                                                </div>
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-12 col-lg-2 d-flex">
                                    <div class="card w-100 shadow-sm mb-4">
                                        <div class="card-header text-center bg-light">
                                            <h4 class="mb-0">Sin rotular</h4>
                                        </div>
                                        <div class="card-body text-center">
                                            <br>
                                            <div class="d-flex justify-content-center flex-wrap gap-2">
                                                <span class="badge px-3 py-2" style="background:#ff9800 ; color:#fff; font-size:14px; font-weight:bold;">
                                                <?php echo count($tpre); ?>
                                            </div>
                                            <a href="prereg.php" class="counter_no">
                                                <div class="couter_icon">
                                                <i class="fa fa-cubes yellow_color"></i>
                                                </div>
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-12 col-lg-2 d-flex">
                                    <div class="card w-100 shadow-sm mb-4">
                                        <div class="card-header text-center bg-light">
                                            <h4 class="mb-0">Mensajes nuevos</h4>
                                        </div>
                                        <div class="card-body text-center">
                                            <br>
                                            <div class="d-flex justify-content-center flex-wrap gap-2">
                                                <span class="badge px-3 py-2" style="background:#009688  ; color:#fff; font-size:14px; font-weight:bold;">
                                                <?php echo $totalMensajeSinLeer; ?>
                                            </div>
                                            <a href="whatsapp.php" class="counter_no">
                                                <div class="couter_icon">
                                                <i class="fa fa-whatsapp green_color"></i>
                                                </div>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                 <div class="col-md-12 col-lg-3 d-flex">
                                    <div class="card w-100 shadow-sm mb-4">
                                        <div class="card-header text-center bg-light">

<?php 
$andFechasrotulacion = "";
$rFIniLib = date('Y-m-d');
$rFFinLib = date('Y-m-d');
$andFechasrotulacion = " AND p.v_date BETWEEN '$rFIniLib 00:00:00' AND '$rFFinLib 23:59:59'";

$rstRot = [];
$tpr=0;
foreach ($rstpu as $u) {
    $userId = $u['id'];
    $sql = "SELECT COUNT(p.id_package) AS total_p 
            FROM package p 
            WHERE 1
            AND p.v_user_id = $userId 
            AND p.id_location IN ($id_location) 
            $andFechasrotulacion";

    $row = $db->select($sql);
    
    if($row[0]['total_p']>0){
        $rstRot[] = [
            'id'      => $u['id'],
            'user'    => $u['user'],
            'rotulados_hoy' => $row[0]['total_p']
        ];
        $tpr = $tpr+$row[0]['total_p'];
    }
}

$lbl_rot = [];
$dt_pu   = [];
foreach ($rstRot as $r) {
    $lbl_rot[] = $r['user'];
    $dt_pu[]  = (int)$r['rotulados_hoy'];
}
?>
                                        <h4 class="mb-0">P. Rotulados <?php echo $tpr;?></h4>
                                        </div>
                                        <div class="card-body text-center">
<div style="width: 100%; max-width: 250px; height: 100%;">
    <canvas id="cht_rot"></canvas>
</div>
<script>
const lbl_rot    = <?php echo json_encode($lbl_rot, JSON_UNESCAPED_UNICODE); ?>;
const dt_val_rot = <?php echo json_encode($dt_pu); ?>;
if (!lbl_rot || lbl_rot.length === 0) {
    document.getElementById('cht_rot').insertAdjacentHTML('afterend', '<p>No hay entregas para mostrar.</p>');
} else {
    const dtx_rot = document.getElementById('cht_rot').getContext('2d');
const col_rot = lbl_rot.map(() => {
    const r = Math.floor(Math.random() * 255);
    const g = Math.floor(Math.random() * 255);
    const b = Math.floor(Math.random() * 255);
    return `rgba(${r}, ${g}, ${b}, 0.7)`;
});
const bor_rot = col_rot.map(c => c.replace("0.7", "1"));

    new Chart(dtx_rot, {
        type: 'bar',
        data: {
            labels: lbl_rot,
            datasets: [{
                label: 'Paquetes rotulados hoy',
                data: dt_val_rot,
                backgroundColor: col_rot,
                borderColor: bor_rot,
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return ' ' + context.parsed.y + ' rotulados';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { precision: 0,
                        stepSize: 10   // ← incrementos de 25 en 25
                    },
                    title: { display: true, text: 'P. rotulados' }
                },
                x: {
                    title: { display: true, text: 'Usuario' }
                }
            }
        }
    });
}
</script>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-12 col-lg-3 d-flex">
                                    <div class="card w-100 shadow-sm mb-4">
                                        <div class="card-header text-center bg-light">
<?php 
$andFechasLiberacion = "";
$rFIniLib = date('Y-m-d');
$rFFinLib = date('Y-m-d');
$andFechasLiberacion = " AND p.d_date BETWEEN '$rFIniLib 00:00:00' AND '$rFFinLib 23:59:59'";

$resultados = [];
$tpe=0;
foreach ($rstpu as $u) {
    $userId = $u['id'];
    $sql = "SELECT COUNT(p.id_package) AS total_p 
            FROM package p 
            WHERE p.id_status IN (3) 
            AND p.d_user_id = $userId 
            AND p.id_location IN ($id_location) 
            $andFechasLiberacion";

    $row = $db->select($sql);
    if($row[0]['total_p']>0){
        $resultados[] = [
            'id'      => $u['id'],
            'user'    => $u['user'],
            'entregados_hoy' => $row[0]['total_p']
        ];
        $tpe = $tpe+$row[0]['total_p'];
    }
}

$lbl_pu = [];
$dt_pu   = [];
foreach ($resultados as $r) {
    $lbl_pu[] = $r['user'];              // nombre del usuario
    $dt_pu[]  = (int)$r['entregados_hoy']; // paquetes entregados
}
?>
                                        <h4 class="mb-0">P. Entregados <?php echo $tpe;?></h4>
                                        </div>
                                        <div class="card-body text-center">
<div style="width: 100%; max-width: 250px; height: 100%;">
    <canvas id="chart_pu"></canvas>
</div>
<script>
const lbl_pu    = <?php echo json_encode($lbl_pu, JSON_UNESCAPED_UNICODE); ?>;
const dt_val_pu = <?php echo json_encode($dt_pu); ?>;
if (!lbl_pu || lbl_pu.length === 0) {
    document.getElementById('chart_pu').insertAdjacentHTML('afterend', '<p>No hay entregas para mostrar.</p>');
} else {
    const puctx = document.getElementById('chart_pu').getContext('2d');
const colors = lbl_pu.map(() => {
    const r = Math.floor(Math.random() * 255);
    const g = Math.floor(Math.random() * 255);
    const b = Math.floor(Math.random() * 255);
    return `rgba(${r}, ${g}, ${b}, 0.7)`;
});

const borders = colors.map(c => c.replace("0.7", "1"));
    new Chart(puctx, {
        type: 'bar',
        data: {
            labels: lbl_pu,
            datasets: [{
                label: 'Paquetes entregados hoy',
                data: dt_val_pu,
                backgroundColor: colors,
                borderColor: borders,
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return ' ' + context.parsed.y + ' entregados';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { precision: 0,
                        stepSize: 10   // ← incrementos de 25 en 25
                    },
                    title: { display: true, text: 'P. entregados' }
                },
                x: {
                    title: { display: true, text: 'Usuario' }
                }
            }
        }
    });
}
</script>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- ---------- -->
                        <?php
                            $statusCounts = [];
                            $sql ="SELECT DISTINCT
                                n.message_id
                            FROM package p
                            INNER JOIN notification n 
                                ON n.id_package = p.id_package
                            INNER JOIN (
                                SELECT 
                                    n2.id_package,
                                    MAX(n2.id_notification) AS last_notification
                                FROM notification n2
                                INNER JOIN package p2 
                                    ON n2.id_package = p2.id_package
                                WHERE 1
                                AND p2.id_location IN ($id_location) 
                                AND p2.id_status IN (1, 2, 6)
                                AND n2.message_id LIKE 'wamid%'
                                GROUP BY n2.id_package
                            ) ult 
                                ON n.id_package = ult.id_package
                            AND n.id_notification = ult.last_notification
                            ORDER BY n.id_notification DESC";
                            $phonesWabaUnicos = $db->select($sql);
                        ?>
                        <div class="row graph margin_bottom_30">
                            <div class="col-md-8 col-lg-8">
                                <div class="white_shd full" style="display: block !important;">
                                    <div class="full graph_head">
                                        <div class="heading1 margin_0">
                                            <h2><?php echo "Avance de entrega hoy: " . $ultimoD ; ?></h2>
                                        </div>
                                    </div>
                                    <div class="full graph_revenue" style="display: block !important;">
                                        <div class="row">
                                            <div class="col-12">
                                                <div class="content">
                                                    <div class="area_chart" style="padding:15px; text-align:left;">


                                                     <!-- ---------- chart -->
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
                                                                const labels1     = <?php echo $labelsJson; ?>;
                                                                const dataValues1 = <?php echo $dataJson; ?>;

                                                                // Crear el gráfico con Chart.js
                                                                const ctx = document.getElementById('myChart').getContext('2d');
                                                                new Chart(ctx, {
                                                                type: 'pie', // Puedes cambiar a 'pie' o 'doughnut'
                                                                data: {
                                                                    labels: labels1,
                                                                    datasets: [{
                                                                        label: 'Total',
                                                                        data: dataValues1,
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

                            <div class="col-md-4 col-lg-4">
                                <?php
                                foreach ($phonesWabaUnicos as $item) {
                                    $sqlGetWamid="SELECT 
                                        n.n_date,
                                        n.message_id,
                                        n.id_package,
                                        cc.phone,
                                        cc.contact_name 
                                    FROM 
                                        package p 
                                    INNER JOIN notification n ON n.id_package = p.id_package 
                                    INNER JOIN cat_contact cc ON cc.id_contact = p.id_contact 
                                    WHERE 1 
                                        AND n.message_id LIKE 'wamid%' 
                                        AND n.message_id IN ('".$item['message_id']."') 
                                    ORDER BY 
                                        n.n_date DESC LIMIT 1";
                                    $dtsWamid = $db->select($sqlGetWamid);

                                    // Buscar último estatus de este message_id
                                    $sqlWabaStatus = "SELECT status_name, datelog, raw_json
                                        FROM waba_status 
                                        WHERE message_id = '".$dtsWamid[0]['message_id']."' 
                                        ORDER BY FIELD(status_name, 'read', 'delivered', 'sent'), id_status DESC 
                                        LIMIT 1";
                                    $statusRow = $db->select($sqlWabaStatus);
                                    $statusName = $statusRow ? $statusRow[0]['status_name'] : 'Pendiente..';
                                    $errorMessage = '';
                                    if ($statusName == 'failed') {
                                        $rawJson = $statusRow[0]['raw_json'];
                                        $errorData = json_decode($rawJson, true);
                                        if ($errorData && isset($errorData['entry'][0]['changes'][0]['value']['statuses'][0]['errors'][0]['message'])) {
                                            $errorMessage = "\n".$errorData['entry'][0]['changes'][0]['value']['statuses'][0]['errors'][0]['message'];
                                        }
                                    }

                                    if (!isset($statusCounts[$statusName])) $statusCounts[$statusName] = 0;
                                    $statusCounts[$statusName]++;
                                }
                            ?>
                                <div class="white_shd full" style="display: block !important;">
                                    <div class="full graph_head">
                                        <div class="heading1 margin_0" style="text-align: center;">
                                            <h2>Estatus Mensajes Enviados Meta <br><?php echo count($phonesWabaUnicos); ?> - En Ruta</h2>
                                        </div>
                                    </div>
                                    <div class="full graph_revenue" style="display: block !important;">
                                        <div class="row">
                                            <div class="col-12">
                                                <?php
                                                    $labels = array_keys($statusCounts);
                                                    $data = array_values($statusCounts);
                                                ?>
                                                <div style="width: 100%; max-width: 600px; height: 300px;">
                                                    <canvas id="statusChart"></canvas>
                                                </div>
                                                <script>
                                                    // Datos pasados desde PHP
                                                    const labels     = <?php echo json_encode($labels, JSON_UNESCAPED_UNICODE); ?>;
                                                    const dataValues = <?php echo json_encode($data); ?>;

                                                    // Si no hay datos, mostrar mensaje
                                                    if (!labels || labels.length === 0) {
                                                        document.getElementById('statusChart').insertAdjacentHTML('afterend', '<p>No hay estatus para mostrar.</p>');
                                                    } else {
                                                        const ctx = document.getElementById('statusChart').getContext('2d');

                                                        // Colores fijos por estatus
                                                        const statusColors = {
                                                            "sent":        "rgba(54, 162, 235, 0.7)",  // azul
                                                            "delivered":   "rgba(255, 205, 86, 0.7)",  // amarillo
                                                            "read":        "rgba(75, 192, 192, 0.7)",  // verde
                                                            "failed":      "rgba(255, 99, 132, 0.7)",  // rojo
                                                            "SIN ESTATUS": "rgba(201, 203, 207, 0.7)"  // gris
                                                        };

                                                        // Construir arrays de colores según los labels que vengan de PHP
                                                        const backgroundColors = labels.map(label => statusColors[label] || "rgba(153, 102, 255, 0.7)");
                                                        const borderColors     = labels.map(label => statusColors[label] ? statusColors[label].replace("0.7", "1") : "rgba(153, 102, 255, 1)");

                                                        // Crear gráfico
                                                        new Chart(ctx, {
                                                            type: 'bar',
                                                            data: {
                                                                labels: labels,
                                                                datasets: [{
                                                                    label: 'Cantidad por estatus',
                                                                    data: dataValues,
                                                                    backgroundColor: backgroundColors,
                                                                    borderColor: borderColors,
                                                                    borderWidth: 1
                                                                }]
                                                            },
                                                            options: {
                                                                responsive: true,
                                                                plugins: {
                                                                    legend: { display: false },
                                                                    tooltip: {
                                                                        callbacks: {
                                                                            label: function(context) {
                                                                                return ' ' + context.parsed.y + ' mensajes';
                                                                            }
                                                                        }
                                                                    }
                                                                },
                                                                scales: {
                                                                    y: {
                                                                        beginAtZero: true,
                                                                        ticks: { precision: 0 },
                                                                        title: { display: true, text: 'Cantidad' }
                                                                    },
                                                                    x: {
                                                                        title: { display: true, text: 'Estatus' }
                                                                    }
                                                                }
                                                            }
                                                        });
                                                    }
                                                    </script>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                        <!-- ---------- -->
                    </div>
                </div>
            </div>
        </div>
        <?php
            require_once('footer.php');
        ?>
    </body>
</html>