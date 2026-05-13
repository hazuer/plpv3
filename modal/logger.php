<div class="modal fade" id="modal-logger" tabindex="-1" role="dialog" aria-labelledby="modal-logger-title" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title"><span id="modal-logger-title"> </span></h3>
                <button id="close-logger" type="button" class="close" data-dismiss="modal" aria-label="Close" title="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="form-modal-logger" name="form-modal-logger" class="form" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-12" style="overflow: auto; max-height: 250px; width: 100%;">
                        <table class="table table-striped table-bordered nowrap table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Fecha</th>
                                <th>Usuario</th>
                                <th>Estatus</th>
                                <th>Estatus Anterior</th>
                                <th>Descripción del Moviemiento</th>
                            </tr>
                        </thead>
                        <tbody id="tbl-logger">
                        </tbody>
                        </table>
                        </div>
                    </div>
                </form>
                <input type="hidden" name="id_package_db" id="id_package_db" value="" >
                <input type="hidden" name="txt_mv_location" id="txt_mv_location" value="" >
                <input type="hidden" name="newLocation" id="newLocation" value="" >
            </div>
            <div class="modal-footer">
                <button id="btn-change-location" type="button" class="btn btn-success" title="Cambiar ubicación" data-dismiss="modal"></button>
                <button id="btn-revert-status" type="button" class="btn btn-success" title="Cambiar a Contactado" data-dismiss="modal">Cambiar a Contactado</button>
                <button id="close-btn-logger" type="button" class="btn btn-danger" title="Cerrar" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>