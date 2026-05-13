<div class="modal fade" id="modal-bot" tabindex="-1" role="dialog" aria-labelledby="modal-bot-title" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title"><span id="modal-bot-title"> </span></h3>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" title="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="mBIdLocation"><b>Ubicación:</b></label>
                            <select name="mBIdLocation" id="mBIdLocation" class="form-control" disabled>
                                <option value="1">Tlaquiltenango</option>
                                <option value="2">Zacatepec</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="mBContactType"><b>Tipo:</b></label>
                            <select name="mBContactType" id="mBContactType" class="form-control" disabled>
                                <option value="2">WhatsApp</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="mBEstatus"><b>* Estatus del Paquete:</b></label>
                            <select name="mBEstatus" id="mBEstatus" class="form-control">
                                <option value="99">Selecciona</option>
                                <option value="1">Nuevo</option>
                                <option value="2">Mensaje Enviado (Recordatorio)</option>
                                <option value="5">Confirmados (Recordatorio)</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                            <div class="form-group">
                                <label for="mbIdCatParcel"><b>Paquetería:</b></label>
                                <select name="mbIdCatParcel" id="mbIdCatParcel" class="form-control">
                                    <option value="99">TODAS</option>
                                    <option value="1">J&T</option>
                                    <option value="2">IMILE</option>
                                    <option value="3">CNMEX</option>
                                </select>
                            </div>
                        </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="mBMessage"><b>Mensaje:</b></label>
                            <textarea class="form-control" id="mBMessage" name="mBMessage" rows="4" readonly></textarea>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="mBListTelefonos"><b>* Lista de Telefonos (Excel):</b></label>
                            <textarea class="form-control" id="mBListTelefonos" name="mBListTelefonos" rows="4"></textarea>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button id="btn-bot-command" type="button" class="btn btn-success" title="Crear">Crear</button>
                <button type="button" class="btn btn-danger" title="Cerrar" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>