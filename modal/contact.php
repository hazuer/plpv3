<div class="modal fade" id="modal-contacto" tabindex="-1" role="dialog" aria-labelledby="modal-contacto-title" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title"><span id="modal-contacto-title"> </span></h3>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" title="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
            <form id="form-modal-contact" name="form-modal-contact" class="form" enctype="multipart/form-data">
                <div class="form-group">
                        <input type="hidden" name="mCid_contact" id="mCid_contact" value="" >
                        <input type="hidden" name="mCaction" id="mCaction" value="" >
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="mCIdLocation"><b>Ubicación:</b></label>
                            <select name="mCIdLocation" id="mCIdLocation" class="form-control" disabled>
                                <option value="1">Tlaquiltenango</option>
                                <option value="2">Zacatepec</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <div class="form-group">
                                <label for="mFRegContact"><b>Fecha Registro:</b></label>
                                <input type="text" class="form-control" name="mFRegContact" id="mFRegContact" value="<?php echo date('Y-m-d H:i:s');?>" autocomplete="off" disabled>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <div class="form-group">
                                <label for="mCPhone"><b>* Télefono:</b></label>
                                <input type="text" class="form-control" name="mCPhone" id="mCPhone" value="" autocomplete="off" >
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                <div class="col-md-6">
                        <div class="form-group">
                            <label for="mCName"><b>* Nombre:</b></label>
                            <input type="text" class="form-control" name="mCName" id="mCName" value="" autocomplete="off" >
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="mCContactType"><b>Tipo Contacto:</b></label>
                            <select name="mCContactType" id="mCContactType" class="form-control" >
                                <option value="1">SMS</option>
                                <option value="2">WhatsApp</option>
                                <option value="3">Casa</option>
                                <option value="4">Domicilio</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="mCEstatus"><b>Estatus:</b></label>
                            <select name="mCEstatus" id="mCEstatus" class="form-control" >
                                <option value="1">Activo</option>
                                <option value="2">Inactivo</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="mRegContact"><b>Modo Registro:</b></label>
                            <select name="mRegContact" id="mRegContact" class="form-control" disabled>
                                <option value="1">Manual</option>
                                <option value="2">Automático</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            </form>
            <div class="modal-footer">
                <button id="btn-save-contacto" type="button" class="btn btn-success" title="Guardar">Guardar</button>
                <button type="button" class="btn btn-danger" title="Cerrar" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
