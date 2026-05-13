<style>
    /* Estilo del contenedor con scroll horizontal */
    .scroll-horizontal {
        overflow-x: auto; /* Habilita el scroll horizontal */
        overflow-y: hidden; /* Evita el scroll vertical */
        width: 100%; /* Ajusta el contenedor al 100% del ancho disponible */
        max-width: 100%; /* Asegura que no exceda el ancho del padre */
        white-space: nowrap; /* Mantiene los elementos en una sola línea */
        border: 1px solid #ddd; /* Agrega un borde para visualización */
    }

    /* Opcional: Estilo del canvas */
    canvas {
        margin: 10px; /* Margen alrededor del canvas */
    }
</style>
<div class="modal fade" id="modal-evidence-camera" tabindex="-1" role="dialog" aria-labelledby="modal-evidence-camera-title" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">

            <div class="modal-header">
                <h3 class="modal-title">
                    <span id="modal-evidence-title"></span>
                </h3>

                <button id="btn-stop-camera"
                        type="button"
                        class="close"
                        data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>

            <div class="modal-body">

                <div class="row">
                    <div id="video-container-camera">
                        <video id="video-camera"
                               width="<?php echo LARGO;?>"
                               height="<?php echo ALTO;?>"
                               autoplay>
                        </video>
                    </div>
                </div>

                <div class="scroll-horizontal row">
                    <canvas id="canvas-camera"
                            width="<?php echo LARGO;?>"
                            height="<?php echo ALTO;?>">
                    </canvas>
                </div>

            </div>

            <div class="modal-footer">
                <button id="btn-save-camera" type="button" class="btn btn-success"> Liberar</button>
            </div>
        </div>
    </div>
</div>