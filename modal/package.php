<div class="modal fade" id="modal-package" tabindex="-1" role="dialog" aria-labelledby="modal-package-title" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title"><span id="modal-package-title"> </span></h3>
                <button id="close-qr-x" type="button" class="close" data-dismiss="modal" aria-label="Close" title="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="form-modal-package" name="form-modal-package" class="form" enctype="multipart/form-data">
                    <div class="form-group">
                        <input type="hidden" name="id_package" id="id_package" value="" >
                        <input type="hidden" name="folio" id="folio" value="" >
                        <input type="hidden" name="id_contact" id="id_contact" value="" >
                        <input type="hidden" name="action" id="action" value="" >
                    </div>

                    <div class="row" id="div-keep-modal" style="margin-bottom: 20px;">
                        <div class="col-md-6">
                            <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="keepModal" id="opcMA" value="option1">
                            <label class="form-check-label" for="opcMA"><b>Repetir</b></label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="keepModal" id="opcGC" value="option2">
                            <label class="form-check-label" for="opcGC"><b>Guardar y cerrar</b></label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-check form-check-inline">
                                <label for="id_marcador"><b>Marcador:</b></label>
                                    <select name="id_marcador" id="id_marcador" class="form-control">
                                    <option value="black" <?php if($_SESSION["uMarker"]=='black'){echo "selected ";} ?> style="background-color:black;">Negro</option>
                                    <option value="red" <?php if($_SESSION["uMarker"]=='red'){echo "selected ";} ?>style="background-color:red;">Rojo</option>
                                    <option value="blue" <?php if($_SESSION["uMarker"]=='blue'){echo "selected ";} ?>style="background-color:blue;">Azul</option>
                                    <option value="green" <?php if($_SESSION["uMarker"]=='green'){echo "selected ";} ?>style="background-color:green;">Verde</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="id_location"><b>Ubicación:</b></label>
                                <select name="id_location" id="id_location" class="form-control" disabled>
                                    <option value="1">Tlaquiltenango</option>
                                    <option value="2">Zacatepec</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="c_date"><b>Fecha:</b></label>
                                <input type="text" class="form-control" name="c_date" id="c_date" value="" disabled>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="id_cat_parcel"><b>* Paquetería:</b></label>
                                <select name="id_cat_parcel" id="id_cat_parcel" class="form-control">
                                    <option value="1">J&T</option>
                                    <option value="2">IMILE</option>
                                    <option value="3">CNMEX</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="phone"><b>* Télefono:</b>
                                    <span class="input-group-addon" id="phone-loading" style="display:none; color:green;">
                                    <i class="fa fa-spinner fa-spin"></i>
                                    </span>
                                </label>
                                <input type="text" class="form-control" name="phone" id="phone" value="" autocomplete="off" >
                            </div>
                            <div id="coincidencias" style="display: none;"></div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="receiver"><b>* Nombre:</b></label>
                                <input type="receiver" class="form-control" name="receiver" id="receiver" value="" autocomplete="off">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-10">
                            <div class="form-group">
                                <label for="tracking"><b>* Guía:</b></label> 
                                <input type="text" class="form-control" name="tracking" id="tracking" value="" autocomplete="off">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" 
                                    type="checkbox" 
                                    name="checkG" 
                                    id="checkG" 
                                    value="1" checked>
                                <label class="form-check-label" for="checkG">
                                    <b>Validar</b>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="note"><b>Notas:</b></label>
                                <input type="text" class="form-control" name="note" id="note" value="" autocomplete="off">
                            </div>
                        </div>
                    </div>
                    <div class="row" id="div-status">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="id_status"><b>Estatus:</b></label>
                                <select name="id_status" id="id_status" class="form-control">
                                    <option value="1">Nuevo</option>
                                    <option value="2">Mensaje Enviado</option>
                                    <option value="4">Devuelto</option>
                                    <option value="5">Confirmado</option>
                                    <option value="6">Error al enviar mensaje</option>
                                    <option value="7">Contactado</option>
                                    <option value="8">Devolución en Proceso</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6" >
                            <?php if($host==NAME_HOST_REMOTE){?>
                            <label for="evidence"><b>Evidencia:</b></label>
                            <input type="file" id="evidence" name="evidence" accept="image/*,application/pdf" id="fileInput">
                            <?php }?>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button id="btn-erase" type="button" class="btn btn-default" title="Borrar">Borrar</button>
                <button id="btn-save" type="button" class="btn btn-success" title="Guardar">Guardar</button>
                <button type="button" class="btn btn-danger" title="Cerrar" data-dismiss="modal">Cerrar</button>
                <audio id="wrong" style="display: none;">
					<source src="<?php echo BASE_URL;?>/assets/wrong.mp3" type="audio/mpeg">
				</audio>
                <audio id="togroup" style="display: none;">
					<source src="<?php echo BASE_URL;?>/assets/togroup.mp3" type="audio/mpeg">
				</audio>
            </div>
        </div>
    </div>
</div>