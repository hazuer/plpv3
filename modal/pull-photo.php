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
<div class="modal fade" id="modal-pull-photo" tabindex="-1" role="dialog" aria-labelledby="modal-pull-photo-title" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title"><span id="modal-pull-photo-title"> </span></h3>
                <button id="stop-pull" type="button" class="close" data-dismiss="modal" aria-label="Close" title="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div id="video-container-pull">
                        <video id="video-pull" with="<?php echo LARGO;?>" height="<?php echo ALTO;?>" autoplay></video>
                    </div>
                </div>
                <div class="scroll-horizontal row">
                    <canvas id="canvas-pull" with="<?php echo LARGO;?>" height="<?php echo ALTO;?>"></canvas>
                </div>
                <div class="modal-footer">
                    <button id="btn-photo-pull-save" type="button" class="btn btn-success" title="Liberar">Liberar</button>
                </div>
            </div>
        </div>
    </div>
</div>