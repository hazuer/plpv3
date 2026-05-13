<div class="modal fade" id="modal-sync-package" tabindex="-1" role="dialog" aria-labelledby="modal-sync-package-title" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title"><span id="modal-sync-package-title"> </span></h3>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" title="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="form-modal-sync-package" name="form-modal-sync-package" class="form" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="msyncp-id_location"><b>Ubicación:</b></label>
                                <select name="msyncp-id_location" id="msyncp-id_location" class="form-control" disabled>
                                <option value="1">Tlaquiltenango</option>
                                <option value="2">Zacatepec</option>
                            </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12" style="overflow: auto; max-height: 250px; width: 100%;">
                        <table class="table table-striped table-bordered nowrap table-hover" id="tbl-sync">
                        <thead>
                            <tr>
                                <th>Paquetería</th>
                                <th>Guía</th>
                                <th>Télefono</th>
                                <th>Destinatario</th>
                                <th>Folio</th>
                                <th>Estatus/Descripción</th>
                                <th>Hora</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Los datos se agregarán aquí mediante jQuery -->
                        </tbody>
                        </table>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" title="Cerrar" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>