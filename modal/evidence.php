<div class="modal fade" id="modal-evidence" tabindex="-1" role="dialog" aria-labelledby="modal-evidence-title" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title"><span id="modal-evidence-title"> </span></h3>
                <button id="close-evidence" type="button" class="close" data-dismiss="modal" aria-label="Close" title="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="form-modal-evidence" name="form-modal-evidence" class="form" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-12" style="overflow: auto; max-height: 250px; width: 100%;">
                        <table class="table table-striped table-bordered nowrap table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Fecha</th>
                                <th>Usuario</th>
                                <th>Preview</th>
                            </tr>
                        </thead>
                        <tbody id="tbl-evidence">
                        </tbody>
                        </table>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button id="close-evidence" type="button" class="btn btn-danger" title="Cerrar" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>