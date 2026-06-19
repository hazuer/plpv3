<div class="modal fade" id="modal-chat-w" tabindex="-1" role="dialog" aria-labelledby="modal-chat-w-title" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title"><span id="modal-chat-w-title"> </span></h3>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" title="Cerrar" id="btn-close-chatw-1">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                        <!-- <input type="text" id="tophone" value="" readonly> -->
                        <input type="hidden" id="tokenWaba" value="<?php echo $infoLocation[0]['token']?>" readonly>
                        <input type="hidden" id="phone_waba" value="<?php echo $infoLocation[0]['phone_waba']?>" readonly>
                        <input type="hidden" id="phone_number_id" value="<?php echo $infoLocation[0]['phone_number_id']?>" readonly>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="full progress_bar_inner chat-container" id="chat-container">
                        </div>
                    </div>
                </div>
        
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <textarea class="form-control" id="chat-input" name="chatt-input" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div id="preview-container"
                    style="display:none;margin:10px 0;">
                    <img id="preview-image"
                        src=""
                        style="max-width:200px;
                                max-height:200px;
                                border-radius:8px;
                                border:1px solid #ddd;">
                    <br>
                    <button type="button"
                            id="remove-preview"
                            class="btn btn-sm btn-danger mt-2">
                        Quitar
                    </button>
                </div>
                
            </div>
            <div class="modal-footer">
                <button id="btn-prev-chat" type="button" class="btn btn-info" title="Leer mensaje anterior"><i class="fa fa-chevron-circle-left"></i></button>
                <button id="btn-send" type="button" class="btn btn-success" title="Enviar"><i class="fa fa-paper-plane"></i></button>
                <button id="btn-read" type="button" class="btn btn-success" title="Leído"><i class="fa fa-check-circle"></i></button>
                <button id="info-guias" type="button" class="btn btn-success" title="Información"><i class="fa fa-cube"></i></button>
                <button id="btn-attach" type="button" class="btn btn-success" title="Adjuntar"><i class="fa fa-paperclip"></i></button>
                <input type="file"
                id="attach-image-file"
                accept="image/*"
                style="display:none;">
                <div id="attach-menu"
                    style="display:none;
                            position:absolute;
                            bottom:60px;
                            left:10px;
                            background:#fff;
                            border:1px solid #ddd;
                            border-radius:8px;
                            padding:8px;
                            z-index:9999;
                            box-shadow:0 2px 10px rgba(0,0,0,.2);">
                    <button type="button"
                            id="attach-image"
                            class="btn btn-sm btn-light btn-block">
                        🖼 Imagen
                    </button>
                    <button type="button"
                            class="btn btn-sm btn-light btn-block"
                            disabled>
                        📄 Documento
                    </button>
                    <button type="button"
                            class="btn btn-sm btn-light btn-block"
                            disabled>
                        🎵 Audio
                    </button>
                </div>
                <button id="btn-next-chat" type="button" class="btn btn-info" title="Leer mensaje siguiente"><i class="fa fa-chevron-circle-right"></i></button>
                <!-- <button id="btn-close-chatw" type="button" class="btn btn-danger" title="Cerrar" data-dismiss="modal">Cerrar</button> -->
            </div>
        </div>
    </div>
</div>