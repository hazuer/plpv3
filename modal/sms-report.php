<div class="modal fade" id="modal-sms-report" tabindex="-1" role="dialog" aria-labelledby="modal-sms-report-title" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title"><span id="modal-sms-report-title"> </span></h3>
                <button id="close-sms" type="button" class="close" data-dismiss="modal" aria-label="Close" title="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="form-modal-sms-report" name="form-modal-sms-report" class="form" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-12" style="overflow: auto; max-height: 300px; width: 100%;">
                        <table class="table table-striped table-bordered nowrap table-hover" style="width: 100%;">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Fecha Envío</th>
                                <th>Télefono</th>
                                <th>Destinatario</th>
                                <th>Envió</th>
                                <th>Mensaje</th>
                                <th>Cta.</th>
                                <th>SID</th>
                            </tr>
                        </thead>
                        <tbody id="tbl-sms-sent">
                        </tbody>
                        </table>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button id="close-rep-sms" type="button" class="btn btn-danger" title="Cerrar" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>