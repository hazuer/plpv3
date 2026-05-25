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
    position: absolute;
    top: 18%;
    left: 13%;
    width: 75%;
    height: 65%;
    border:2px solid #00ff44;
    border-radius:8px;
    box-shadow:0 0 0 9999px rgba(0,0,0,.45);
}
#ocr-overlay-label{
    position:absolute;
    top:3%;
    left:23%;
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
    display:grid;
    grid-template-columns:1fr 1fr 1fr;
    align-items:center;
    width:100%;
    margin-top:12px;
    margin-bottom: 10px;
}

.ocr-action-left{
    display:flex;
    justify-content:flex-start;
}

.ocr-action-center{
    display:flex;
    justify-content:center;
}

.ocr-action-right{
    display:flex;
    justify-content:flex-end;
}
.ocr-actions .btn,
.ocr-actions .btn-center{
    border:none !important;
    outline:none !important;
    box-shadow:none !important;
}

.ocr-actions .btn:focus,
.ocr-actions .btn:active,
.ocr-actions .btn-center:focus,
.ocr-actions .btn-center:active{
    outline:none !important;
    box-shadow:none !important;
}

.ocr-actions .btn{
    width:60px;
    height:60px;
    border-radius:50%;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:20px;
    padding:0;
}
.ocr-actions .btn-center{
    width:70px;
    height:70px;
    border-radius:50%;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:20px;
    padding:0;
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

.ocr-info{
    font-size:13px;
    line-height:1.3;
    margin-bottom:10px;
}

.ocr-info-row{
    margin-bottom:4px;
    word-break:break-word;
}

.ocr-info-row strong{
    color:#111;
}
.ocr-show-result .badge{
    font-size:15px;
    padding:10px 18px;
    /*margin:3px;
    border-radius:8px;*/
}
.ocr-show-result{
    display:flex;
    justify-content:center;
    align-items:center;
    gap:10px;
    /*margin-bottom:10px;*/
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

            <!-- Modal body -->
            <div class="modal-body">
                <!-- Ocr layout -->
                <div class="ocr-layout">
                    <!-- Camera -->
                    <div class="ocr-camera-section">
                        <div id="video-container-ocr-camera">
                            <video id="video-ocr-camera" autoplay playsinline></video>
                            <div id="ocr-overlay"></div>
                            <div id="ocr-overlay-label">
                                Coloque destinatario aquí
                            </div>
                        </div>
                    </div>

                    <!-- Preview -->
                    <div class="ocr-preview-section">
                        <div class="ocr-preview-container">
                            <canvas id="canvas-ocr-camera"></canvas>
                        </div>
                    </div>

                    <!-- Result -->
                    <div class="ocr-show-result" id="ocr-show-result"> 
                        <span class="badge badge-success" id="ocr-initial">A</span>
                        <span class="badge badge-primary" id="ocr-folio">127</span>
                    </div>

                    <!-- QR -->
                    <div class="ocr-info">
                        <div class="ocr-info-row">
                            <strong>QR:</strong><span id="ocr-qr">-</span>
                        </div>
                        <div class="ocr-info-row">
                            <strong>Nombre:</strong><span id="ocr-name">-</span>
                        </div>
                        <div class="ocr-info-row">
                            <strong>Tel:</strong><span id="ocr-phone">-</span>
                        </div>
                        <div class="ocr-info-row">
                            <strong>Dir:</strong><span id="ocr-address">-</span>
                        </div>
                        <div class="ocr-info-row">
                            <strong>CP:</strong><span id="ocr-postal-code">-</span>
                        </div>
                        <div class="ocr-info-row">
                            <strong>Full text:</strong><span id="ocr-full-text">-</span>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="ocr-actions">
                        <div class="ocr-action-left">
                            <button type="button"class="btn btn-primary"id="btn-save-ocr">
                                <i class="fa fa-save"></i>
                            </button>
                        </div>
                        <div class="ocr-action-center">
                            <button type="button" class="btn-center btn-success" id="btn-capture-ocr">
                                <i class="fa fa-camera"></i>
                            </button>
                        </div>
                        <div class="ocr-action-right">
                            <button type="button" class="btn btn-warning" id="btn-next-ocr">
                                <i class="fa fa-arrow-right"></i>
                            </button>
                        </div>
                    </div><!--Actions end-->
                </div><!-- Ocr layout end-->
            </div><!-- Ocr body end -->
        </div> <!-- modal-content end -->
    </div>
</div>