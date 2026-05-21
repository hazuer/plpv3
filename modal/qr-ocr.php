<!-- =========================================================
QR MODAL
========================================================= -->
<div class="modal fade" id="modal-scan-qr-ocr" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fa fa-qrcode"></i>
                    Escanear Guía
                </h5>
                <button type="button" class="close" id="btn-close-qr-modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="qr-reader-ocr"></div>
            </div>
        </div>
    </div>
</div>

<!-- =========================================================
OCR MODAL
========================================================= -->

<style>
#modal-ocr-camera .modal-dialog{
    max-width:95%;
    width:95%;
}
.ocr-layout{
    display:flex;
    flex-direction:column;
    gap:10px;
}
.ocr-camera-section{
    height:24vh;
    min-height:190px;
    border-radius:10px;
    overflow:hidden;
    background:#000;
}
#video-container-ocr-camera{
    position:relative;
    width:100%;
    height:100%;
}
#video-ocr-camera{
    width:100%;
    height:100%;
    object-fit:contain;
    background:#000;
}
#ocr-overlay{
    position:absolute;
    top:15%;
    left:10%;
    width:80%;
    height:70%;
    border:2px solid #00ff44;
    border-radius:8px;
    box-shadow:0 0 0 9999px rgba(0,0,0,.45);
}
#ocr-overlay-label{
    position:absolute;
    top:3%;
    left:25%;
    color:#00ff44;
    /*background:rgba(0,0,0,.6);*/
    padding:4px 8px;
    border-radius:5px;
    font-size:13px;
    font-weight:bold;
}
.ocr-preview-section{
    padding:10px;
    background:#f5f5f5;
    border-radius:10px;
}
.ocr-preview-container{
    width:100%;
    height:130px;
    display:flex;
    align-items:center;
    justify-content:center;
}
#canvas-ocr-camera{
    max-width:100%;
    max-height:100%;
    background:#fff;
    border-radius:8px;
}

.ocr-data-grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:10px;
}
.ocr-data-item.full-width{
    grid-column:span 2;
}
.ocr-data-label{
    font-weight:bold;
    font-size:13px;
    margin-bottom:4px;
}
.ocr-data-value{
    min-height:40px;
    background:#f8f8f8;
    border:1px solid #ddd;
    border-radius:6px;
    padding:8px;
    font-size:13px;
}
.ocr-actions{
    margin-top:12px;
    display:flex;
    justify-content:center;
    gap:10px;
}
@media(max-width:768px){

    .ocr-camera-section{
        height:20vh;
    }

    .ocr-data-grid{
        grid-template-columns:1fr 1fr;
    }

    .ocr-data-item.full-width{
        grid-column:1 / -1;
    }
}
.full-width{
    grid-column:1 / -1;
}

.full-width .ocr-data-value{
    width:100%;
}
</style>

<div class="modal fade" id="modal-ocr-camera" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h5 class="modal-title">
                    Escanear Destinatario
                </h5>
                <button type="button" class="close" id="btn-close-ocr-modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="ocr-layout">
                    <!-- CAMERA -->
                    <div class="ocr-camera-section">
                        <div id="video-container-ocr-camera">
                            <video id="video-ocr-camera" autoplay playsinline></video>
                            <div id="ocr-overlay"></div>
                            <div id="ocr-overlay-label">
                                Coloque destinatario aquí
                            </div>
                        </div>
                    </div>

                    <!-- PREVIEW -->
                    <div class="ocr-preview-section">
                        <div class="ocr-preview-container">
                            <canvas id="canvas-ocr-camera"></canvas>
                        </div>
                        <div style="margin-top:10px;">
                            <strong>QR:</strong>
                            <span id="ocr-qr">-</span>
                        </div>

                    </div>
                    <!-- DATA -->
                    <div class="ocr-data-grid">
                        <div class="ocr-data-item">
                            <div class="ocr-data-label">Nombre</div>
                            <div class="ocr-data-value" id="ocr-name">-</div>
                        </div>

                        <div class="ocr-data-item">
                            <div class="ocr-data-label">Teléfono</div>
                            <div class="ocr-data-value" id="ocr-phone">-</div>
                        </div>

                        <div class="ocr-data-item full-width">
    <div class="ocr-data-label">Dirección</div>
    <div class="ocr-data-value" id="ocr-address">-</div>
</div>

                    </div>
                    <div class="ocr-actions">
                        <button id="btn-save-ocr" class="btn btn-primary"><i class="fa fa-floppy-o" aria-hidden="true"></i></button>
                        <button id="btn-capture-ocr" class="btn btn-success"><i class="fa fa-camera" aria-hidden="true"></i></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>