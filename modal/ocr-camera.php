<style>
    /* =========================
       MODAL
    ========================= */

    #modal-ocr-camera .modal-dialog {
        max-width: 95%;
        width: 95%;
    }

    #modal-ocr-camera .modal-body {
        padding: 8px;
    }

    /* =========================
       LAYOUT
    ========================= */

    .ocr-layout {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .ocr-section {
        width: 100%;
        border: 1px solid #dcdcdc;
        border-radius: 8px;
        overflow: hidden;
        background: #fff;
    }

    /* =========================
       CAMARA
    ========================= */

    .ocr-camera-section {
        height: 23vh;
        min-height: 180px;
    }

    #video-container-ocr-camera {
        position: relative;
        width: 100%;
        height: 100%;
    }

    #video-ocr-camera {
        width: 100%;
        height: 100%;
        object-fit: contain;
        background: black;
    }

    /* =========================
       OVERLAY
    ========================= */

    #ocr-overlay {
        position: absolute;
        top: 28%;
        left: 3%;
        width: 93%;
        height: 45%;

        border: 3px solid #00ff44;
        border-radius: 8px;

        box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.45);

        pointer-events: none;
    }

    #ocr-overlay-label {
        position: absolute;

        top: 6%;
        left: 10%;

        color: #00ff44;
        font-size: 13px;
        font-weight: bold;

        background: rgba(0, 0, 0, 0.6);

        padding: 3px 8px;
        border-radius: 5px;
    }

    /* =========================
       PREVIEW
    ========================= */

    .ocr-preview-section {
        padding: 8px;
        background: #f5f5f5;
    }

    .ocr-preview-container {
        width: 100%;
        height: 140px;

        display: flex;
        align-items: center;
        justify-content: center;
    }

    #canvas-ocr-camera {
        max-width: 100%;
        max-height: 100%;

        object-fit: contain;

        border-radius: 6px;
        background: white;
    }

    /* =========================
       QR
    ========================= */

    .ocr-qr-preview {
        margin-top: 6px;

        display: flex;
        align-items: center;
        gap: 6px;

        font-size: 14px;
    }

    .ocr-qr-label {
        font-weight: bold;
        white-space: nowrap;
    }

    .ocr-qr-value {
        flex: 1;

        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;

        color: #333;
    }

    /* =========================
       BOTONES
    ========================= */

    .ocr-preview-actions {
        margin-top: 8px;

        display: flex;
        justify-content: center;
    }

    /* =========================
       DATOS
    ========================= */

    .ocr-data-section {
        padding: 10px;
    }

    .ocr-data-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px 12px;
    }

    .ocr-data-item.full-width {
        grid-column: span 2;
    }

    .ocr-data-label {
        font-size: 13px;
        font-weight: bold;
        margin-bottom: 3px;
        color: #444;
    }

    .ocr-data-value {
        min-height: 38px;

        padding: 8px;

        background: #f8f8f8;
        border: 1px solid #ddd;
        border-radius: 6px;

        font-size: 13px;
        color: #222;

        overflow-wrap: break-word;
    }

    /* =========================
       GUARDAR
    ========================= */

    .ocr-save-container {
        margin-top: 10px;

        display: flex;
        justify-content: center;
    }

    /* =========================
       MOBILE
    ========================= */

    @media(max-width:768px) {

        .ocr-camera-section {
            height: 20vh;
            min-height: 160px;
        }

        .ocr-preview-container {
            height: 110px;
        }

        .ocr-data-grid {
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }

        .ocr-data-value {
            font-size: 12px;
            padding: 6px;
        }

        .ocr-preview-actions button,
        .ocr-save-container button {
            padding: 6px 18px;
            font-size: 14px;
        }
    }
</style>

<div class="modal fade"
     id="modal-ocr-camera"
     tabindex="-1"
     role="dialog"
     aria-hidden="true">

    <div class="modal-dialog modal-lg"
         role="document">

        <div class="modal-content">

            <div class="modal-header py-2">

                <h5 class="modal-title">
                    <span id="modal-ocr-title"></span>
                </h5>

                <button id="btn-stop-ocr-camera"
                        type="button"
                        class="close"
                        data-dismiss="modal">

                    <span>&times;</span>

                </button>

            </div>

            <div class="modal-body">

                <div class="ocr-layout">

                    <!-- CAMARA -->

                    <div class="ocr-section ocr-camera-section">

                        <div id="video-container-ocr-camera">

                            <video id="video-ocr-camera"
                                   autoplay
                                   playsinline>
                            </video>

                            <div id="ocr-overlay"></div>

                            <div id="ocr-overlay-label">
                                Coloque destinatario aquí
                            </div>

                        </div>

                    </div>

                    <!-- PREVIEW -->

                    <div class="ocr-section ocr-preview-section">

                        <div class="ocr-preview-container">

                            <canvas id="canvas-ocr-camera"></canvas>

                        </div>

                        <!-- QR -->

                        <div class="ocr-qr-preview">

                            <div class="ocr-qr-label">
                                QR:
                            </div>

                            <div class="ocr-qr-value"
                                 id="ocr-qr">
                                -
                            </div>

                        </div>

                        <!-- BOTON -->

                        <div class="ocr-preview-actions">

                            <button id="btn-capture-ocr"
                                    type="button"
                                    class="btn btn-success">

                                Capturar

                            </button>

                        </div>

                    </div>

                    <!-- DATOS -->

                    <div class="ocr-section ocr-data-section">

                        <div class="ocr-data-grid">

                            <!-- NOMBRE -->

                            <div class="ocr-data-item">

                                <div class="ocr-data-label">
                                    Nombre:
                                </div>

                                <div class="ocr-data-value"
                                     id="ocr-name">
                                    -
                                </div>

                            </div>

                            <!-- TELEFONO -->

                            <div class="ocr-data-item">

                                <div class="ocr-data-label">
                                    Teléfono:
                                </div>

                                <div class="ocr-data-value"
                                     id="ocr-phone">
                                    -
                                </div>

                            </div>

                            <!-- DIRECCION -->

                            <div class="ocr-data-item full-width">

                                <div class="ocr-data-label">
                                    Dirección:
                                </div>

                                <div class="ocr-data-value"
                                     id="ocr-address">
                                    -
                                </div>

                            </div>

                        </div>

                        <!-- GUARDAR -->

                        <div class="ocr-save-container">

                            <button id="btn-save-ocr"
                                    type="button"
                                    class="btn btn-primary">

                                Guardar

                            </button>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>