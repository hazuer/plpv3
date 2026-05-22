<?php
session_start();
define('_VALID_MOS', 1);

require_once('includes/configuration.php');
require_once('includes/DB.php');

$db = new DB(HOST, USERNAME, PASSWD, DBNAME, PORT, SOCKET);

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
p.cp,
p.address,
p.marker,
p.id_cat_parcel,
cp.parcel 
FROM package p 
LEFT JOIN cat_contact cc ON cc.id_contact=p.id_contact 
LEFT JOIN cat_status cs ON cs.id_status=p.id_status 
LEFT JOIN cat_parcel cp ON cp.id_cat_parcel=p.id_cat_parcel 
WHERE 1 
AND p.id_location IN ($id_location)
AND p.id_status IN(1,2,5,6,7,8)
LIMIT 10";

$packages = $db->select($sql);
?>

<!DOCTYPE html>
<html lang="es-MX">

<head>

<?php include_once('head.php'); ?>

<style>

.panel-control{
    background:#fff;
    border-radius:12px;
    padding:20px;
    box-shadow:0 2px 10px rgba(0,0,0,.08);
}

#mapa-entregas{
    width:100%;
    height:700px;
    border-radius:12px;
    overflow:hidden;
    background:#f5f5f5;
}

.form-group{
    margin-bottom:18px;
}

.btn-block{
    width:100%;
}

.alert{
    font-size:14px;
    font-weight:600;
}

#mapLoader{
    position:absolute;
    top:0;
    left:0;
    width:100%;
    height:700px;
    background:rgba(255,255,255,.92);
    z-index:999;
    display:flex;
    align-items:center;
    justify-content:center;
    border-radius:12px;
}

.loader-content{
    text-align:center;
    font-size:18px;
    font-weight:600;
}

.custom-spinner{
    width:70px;
    height:70px;
    border:7px solid #e5e5e5;
    border-top:7px solid #007bff;
    border-radius:50%;
    animation:spin 1s linear infinite;
    margin:auto;
}

@keyframes spin{
    0%{
        transform:rotate(0deg);
    }
    100%{
        transform:rotate(360deg);
    }
}

@media(max-width:768px){

    #mapa-entregas{
        height:500px;
        margin-top:20px;
    }

}

</style>

</head>

<body class="dashboard dashboard_1">

<div class="full_container">

<div class="inner_container">

<?php include_once('sidebar.php'); ?>

<div id="content">

<?php include_once('topbar.php'); ?>

<div class="midde_cont">

<div class="container-fluid">

<div class="row" style="margin-bottom:15px;"></div>

<div class="row">

<!-- PANEL -->
<div class="col-md-3">

<div class="panel-control">

<h4 class="mb-4">
P. de control - <?php echo count($packages); ?> paquetes cargados
</h4>

<div class="row">

    <!-- FILTRO -->
    <div class="col-md-6">
        <div class="form-group">
            <label for="filtroCP">
                <b>Código Postal</b>
            </label>
            <select
                id="filtroCP"
                class="form-control"
            >
                <option value="all">
                    Selecciona
                </option>
            </select>
        </div>
    </div>

    <!-- SELECCIONAR -->
    <div class="col-md-3">
        <div class="form-group">
            <label>
                <b>&nbsp;</b>
            </label>
            <button
                id="btnSeleccionarCP"
                class="btn btn-success btn-block"
            >
                <i class="fa fa-check"></i>
            </button>
        </div>
    </div>

    <!-- DESMARCAR -->
    <div class="col-md-3">
        <div class="form-group">
            <label>
                <b>&nbsp;</b>
            </label>
            <button
                id="btnDesmarcar"
                class="btn btn-danger btn-block"
            >
                <i class="fa fa-remove"></i>
            </button>
        </div>
    </div>

</div>

<div
    id="contadorSeleccionados"
    class="alert alert-info text-center"
>
    0 paquetes seleccionados
</div>
<div class="row">

    <!-- FILTRO -->
    <div class="col-md-6">
<!-- REPARTIDOR -->
<div class="form-group">
<label for="repartidorSelect">
<b>Seleccionar repartidor</b>
</label>
<select
    id="repartidorSelect"
    class="form-control"
>
    <option value="">
        Seleccionar
    </option>
    <option value="repartidor_1">
        repartidor_1
    </option>
    <option value="repartidor_2">
        repartidor_2
    </option>
</select>
</div>
    </div>
<div class="col-md-6">
<!-- ASIGNAR -->
<div class="form-group">
     <label>
                <b>&nbsp;</b>
            </label>
<button
    id="btnAsignar"
    class="btn btn-success btn-block"
    disabled
>
    Asignar paquetes
</button>
</div>
</div>
</div>


<!-- CONTADORES -->

<div
    id="contadorNotificaciones"
    class="alert alert-success text-center"
>
    WhatsApp: 0 Notif. para enviar
</div>
<!-- ALERTAS -->
<div class="alert alert-warning text-center">
⚠ Sin localización exacta
</div>

<div class="alert alert-info text-center">
📍 Dirección confirmada
</div>


</div>

</div>

<!-- MAPA -->
<div class="col-md-9" style="position:relative;">

<div id="mapa-entregas"></div>

<div id="mapLoader">

    <div class="loader-content">

        <div class="custom-spinner"></div>

        <div
            id="loaderText"
            style="margin-top:15px;"
        >
            Cargando mapa...
        </div>

    </div>

</div>

</div>

</div>

</div>

</div>

</div>

</div>

</div>

<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAuO1OabBzuNL7LOecWWzpAOh1n_Om1ILc&loading=async&callback=iniciarMapaRuta" async defer></script>

<script>

async function iniciarMapaRuta() {

    const paquetesRaw = <?php echo json_encode($packages); ?>;

    // =========================
    // VARIABLES
    // =========================

    let markers = [];
    let paquetesValidos = [];
    let seleccionados = [];

    // =========================
    // OBTENER CP DISPONIBLES
    // =========================

    const cpsDisponibles = [
        ...new Set(
            paquetesRaw
                .map(pkg => String(pkg.cp || "").trim())
                .filter(cp => cp !== "")
        )
    ].sort();

    // =========================
    // LLENAR SELECT
    // =========================

    const filtroCP =
        document.getElementById("filtroCP");

    cpsDisponibles.forEach(cp => {

        filtroCP.innerHTML += `
            <option value="${cp}">
                ${cp}
            </option>
        `;

    });

    // =========================
    // MAPA
    // =========================

    const mapa = new google.maps.Map(
        document.getElementById("mapa-entregas"),
        {
            center: {
                lat: 18.6147,
                lng: -99.1786
            },

            zoom: 13,

            zoomControl: true,

            zoomControlOptions: {
                position:
                google.maps.ControlPosition.RIGHT_CENTER,
            },

            mapTypeControl: true,
            streetViewControl: false,
            fullscreenControl: true,
        }
    );

    const geocoder =
        new google.maps.Geocoder();

    const infoWindow =
        new google.maps.InfoWindow();

    const bounds =
        new google.maps.LatLngBounds();

    // =========================
    // RECORRER PAQUETES
    // =========================

    for (let i = 0; i < paquetesRaw.length; i++) {

        const pkg = paquetesRaw[i];

        document.getElementById(
            "loaderText"
        ).innerHTML =
            `Mapeando ${i + 1} de ${paquetesRaw.length}`;

        if (!pkg.address || pkg.address.trim() === "") {
            continue;
        }

        const direccion =
            `${pkg.address}, ${pkg.cp || ""}, México`;

        try {

            const response =
                await geocoder.geocode({
                    address: direccion
                });

            if (
                !response.results ||
                !response.results[0]
            ) {
                continue;
            }

            const result =
                response.results[0];

            const location =
                result.geometry.location;

            const cpPaquete =
                String(pkg.cp || "").trim();

            if (!cpPaquete) {
                continue;
            }

            // =========================
            // MARCADOR
            // =========================

            const marker =
                new google.maps.Marker({

                    position: location,

                    map: mapa,

                    title:
                        pkg.receiver || "Entrega",

                    animation:
                        google.maps.Animation.DROP

                });

            marker.cp = cpPaquete;

            marker.packageId =
                pkg.id_package;

            marker.selected = false;

            // =========================
            // POPUP
            // =========================

            const contenido = `
                <div style="min-width:220px;">

                    <h6>
                        ${pkg.receiver || "Sin nombre"}
                    </h6>

                    <p>
                        <b>Guía:</b>
                        ${pkg.tracking}
                    </p>

                    <p>
                        <b>CP:</b>
                        ${cpPaquete}
                    </p>

                    <p>
                        <b>Dirección:</b><br>
                        ${direccion}
                    </p>

                    <p>
                        <b>Paquetería:</b>
                        ${pkg.parcel || "N/A"}
                    </p>

                    <button
                        onclick="toggleSeleccion(${pkg.id_package})"
                        style="
                            background:#28a745;
                            color:white;
                            border:none;
                            padding:8px 12px;
                            border-radius:6px;
                            cursor:pointer;
                        "
                    >
                        Seleccionar
                    </button>

                </div>
            `;

            marker.addListener("click", () => {

                infoWindow.setContent(contenido);

                infoWindow.open(mapa, marker);

            });

            marker.addListener("dblclick", () => {

                toggleSeleccion(
                    pkg.id_package
                );

            });

            bounds.extend(location);

            markers.push(marker);

            paquetesValidos.push({
                pkg,
                marker,
                cp: cpPaquete
            });

        } catch (error) {

            console.error(error);

        }

    }

    // =========================
    // AJUSTAR MAPA
    // =========================

    if (!bounds.isEmpty()) {

        mapa.fitBounds(bounds);

    }

    // =========================
    // REMOVER LOADER
    // =========================

    document
        .getElementById("mapLoader")
        .remove();

    // =========================
    // FUNCION SELECCIONAR
    // =========================

    window.toggleSeleccion =
        function(idPackage) {

        const item =
            paquetesValidos.find(
                x =>
                x.pkg.id_package == idPackage
            );

        if (!item) return;

        const marker = item.marker;

        marker.selected =
            !marker.selected;

        if (marker.selected) {

            marker.setIcon(
                "http://maps.google.com/mapfiles/ms/icons/green-dot.png"
            );

            if (
                !seleccionados.includes(
                    idPackage
                )
            ) {

                seleccionados.push(
                    idPackage
                );

            }

        } else {

            marker.setIcon(null);

            seleccionados =
                seleccionados.filter(
                    id => id != idPackage
                );
        }

        actualizarContador();
    };

    // =========================
    // CONTADOR
    // =========================

    function actualizarContador() {

        document.getElementById(
            "contadorSeleccionados"
        ).innerHTML =
            `${seleccionados.length} paquetes seleccionados`;

        document.getElementById(
            "contadorNotificaciones"
        ).innerHTML =
            `WhatsApp: ${seleccionados.length} notificaciones`;

        document.getElementById(
            "btnAsignar"
        ).disabled =
            seleccionados.length === 0;
    }

    // =========================
    // FILTRO POR CP
    // =========================

    document
        .getElementById("filtroCP")
        .addEventListener(
            "change",
            function() {

                const cp = this.value;

                markers.forEach(marker => {

                    if (
                        cp === "all" ||
                        marker.cp === cp
                    ) {

                        marker.setMap(mapa);

                    } else {

                        marker.setMap(null);

                    }

                });

            }
        );

    // =========================
    // SELECCIONAR TODO POR CP
    // =========================

    document
        .getElementById(
            "btnSeleccionarCP"
        )
        .addEventListener(
            "click",
            () => {

                const cp =
                    document.getElementById(
                        "filtroCP"
                    ).value;

                if (cp === "all") {

                    alert(
                        "Selecciona un CP específico"
                    );

                    return;
                }

                paquetesValidos.forEach(item => {

                    if (item.cp === cp) {

                        if (
                            !item.marker.selected
                        ) {

                            item.marker.selected = true;

                            item.marker.setIcon(
                                "http://maps.google.com/mapfiles/ms/icons/green-dot.png"
                            );

                            if (
                                !seleccionados.includes(
                                    item.pkg.id_package
                                )
                            ) {

                                seleccionados.push(
                                    item.pkg.id_package
                                );

                            }

                        }

                    }

                });

                actualizarContador();

            }
        );

    // =========================
    // DESMARCAR TODO
    // =========================

    document
        .getElementById("btnDesmarcar")
        .addEventListener(
            "click",
            () => {

                paquetesValidos.forEach(item => {

                    item.marker.selected = false;

                    item.marker.setIcon(null);

                });

                seleccionados = [];

                actualizarContador();

            }
        );

    // =========================
    // ASIGNAR
    // =========================

    document
        .getElementById("btnAsignar")
        .addEventListener(
            "click",
            () => {

                const repartidor =
                    document.getElementById(
                        "repartidorSelect"
                    ).value;

                if (!repartidor) {

                    alert(
                        "Selecciona un repartidor"
                    );

                    return;
                }

                alert(
                    `Asignando ${seleccionados.length} paquetes a ${repartidor}`
                );

                console.log({
                    repartidor,
                    paquetes: seleccionados
                });

            }
        );

}

</script>

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
include('modal/qr-ocr.php');
require_once('footer.php');
?>

</body>
</html>