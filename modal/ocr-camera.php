<style>

    /* =========================
       MODAL
    ========================= */

    #modal-ocr-camera .modal-dialog{
        max-width: 95%;
        width: 95%;
        margin: 10px auto;
    }

    #modal-ocr-camera .modal-body{
        padding: 10px;
        background: #f2f2f2;
    }

    /* =========================
       LAYOUT
    ========================= */

    .ocr-layout{
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .ocr-section{
        background: white;
        border-radius: 10px;
        border: 1px solid #ddd;
        overflow: hidden;
    }

    /* =========================
       CAMARA
    ========================= */

    .ocr-camera-section{
        height: 32vh;
        min-height: 220px;
        position: relative;
        background: black;
    }

    #video-container-ocr-camera{
        position: relative;
        width: 100%;
        height: 100%;
        overflow: hidden;
        background: black;
    }

    #video-ocr-camera{
        width: 100%;
        height: 100%;
        object-fit: cover;
        background: black;
        display: block;
    }

    /* =========================
       OVERLAY
    ========================= */

    #ocr-overlay{
        position: absolute;

        top: 30%;
        left: 5%;

        width: 90%;
        height: 35%;

        border: 4px solid #00ff88;
        border-radius: 12px;

        box-shadow:
            0 0 0 9999px rgba(0,0,0,0.45);

        pointer-events: none;
    }

    #ocr-overlay-label{
        position: absolute;

        top: 18%;
        left: 5%;

        color: #00ff88;
        font-size: 14px;
        font-weight: bold;

        background: rgba(0,0,0,0.6);

        padding: 5px 10px;
        border-radius: 6px;
    }

    /* =========================
       PREVIEW
    ========================= */

    .ocr-preview-section{
        height: 22vh;
        min-height: 170px;

        display: flex;
        flex-direction: column;

        padding: 10px;
        background: #f8f8f8;
    }

    .ocr-preview-container{
        flex: 1;

        display: flex;
        align-items: center;
        justify-content: center;

        overflow: hidden;
    }

    #canvas-ocr-camera{
        max-width: 100%;
        max-height: 100%;

        object-fit: contain;

        background: white;
        border-radius: 8px;
    }

    .ocr-preview-actions{
        text-align: center;
        padding-top: 10px;
    }

    /* =========================
       DATOS OCR
    ========================= */

    .ocr-data-section{
        height: 32vh;
        min-height: 250px;

        padding: 12px;

        overflow-y: auto;
    }

    .ocr-data-item{
        margin-bottom: 12px;
    }

    .ocr-data-label{
        font-weight: bold;
        color: #444;
        margin-bottom: 4px;
    }

    .ocr-data-value{
        min-height: 42px;

        padding: 10px;

        border-radius: 6px;
        border: 1px solid #ddd;

        background: #fafafa;
    }

    /* =========================
       MOBILE
    ========================= */

    @media(max-width:768px){

        .ocr-camera-section{
            height: 30vh;
        }

        .ocr-preview-section{
            height: 20vh;
        }

        .ocr-data-section{
            height: 34vh;
        }

    }

</style>

<div class="modal fade"
     id="modal-ocr-camera"
     tabindex="-1"
     role="dialog"
     aria-hidden="true">

    <div class="modal-dialog modal-lg" role="document">

        <div class="modal-content">

            <div class="modal-header">

                <h3 class="modal-title">
                    <span id="modal-ocr-title"></span>
                </h3>

                <button id="btn-stop-ocr-camera"
                        type="button"
                        class="close"
                        data-dismiss="modal">

                    <span>&times;</span>

                </button>

            </div>

            <div class="modal-body">

                <div class="ocr-layout">

                    <!-- =========================
                         CAMARA
                    ========================== -->

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

                    <!-- =========================
                         PREVIEW
                    ========================== -->

                    <div class="ocr-section ocr-preview-section">

                        <div class="ocr-preview-container">

                            <canvas id="canvas-ocr-camera"></canvas>

                        </div>

                        <div class="ocr-preview-actions">

                            <button id="btn-capture-ocr"
                                    type="button"
                                    class="btn btn-success btn-lg">

                                Capturar

                            </button>

                        </div>

                    </div>

                    <!-- =========================
                         DATOS OCR
                    ========================== -->

                    <div class="ocr-section ocr-data-section">

                        <div class="ocr-data-item">
                            <div class="ocr-data-label">QR:</div>
                            <div class="ocr-data-value" id="ocr-qr">
                                ---
                            </div>
                        </div>

                        <div class="ocr-data-item">
                            <div class="ocr-data-label">Nombre:</div>
                            <div class="ocr-data-value" id="ocr-name">
                                ---
                            </div>
                        </div>

                        <div class="ocr-data-item">
                            <div class="ocr-data-label">Teléfono:</div>
                            <div class="ocr-data-value" id="ocr-phone">
                                ---
                            </div>
                        </div>

                        <div class="ocr-data-item">
                            <div class="ocr-data-label">Dirección:</div>
                            <div class="ocr-data-value" id="ocr-address">
                                ---
                            </div>
                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>