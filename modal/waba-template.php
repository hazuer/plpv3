<div class="modal fade" id="modal-template" tabindex="-1" role="dialog" aria-labelledby="modal-template-title" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title"><span id="modal-template-title"></span></h3>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" title="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="mTMode"><b>Modo:</b></label>
                            <select name="mTMode" id="mTMode" class="form-control">
                                <option value="1">Manual</option>
                                <option value="2">Automático</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="mTIdLocation"><b>Ubicación:</b></label>
                            <select name="mTIdLocation" id="mTIdLocation" class="form-control" disabled>
                                <option value="1">Tlaquiltenango</option>
                                <option value="2">Zacatepec</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="mbIdCatParcel"><b>Paquetería:</b></label>
                            <select name="mbIdCatParcel" id="mbIdCatParcel" class="form-control">
                                <option value="0">Selecciona</option>
                                <option value="99">TODAS</option>
                                <option value="1">J&T</option>
                                <option value="2">IMILE</option>
                                <option value="3">CNMEX</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="mTselectTemplate"><b>* Plantilla:</b></label>
                            <select name="mTselectTemplate" id="mTselectTemplate" class="form-control" disabled>
                                <option value="99">Selecciona</option>
                                <?php foreach ($template as $row): ?>
                                    <option value="<?= $row['id_template']; ?>" data-parcel="<?= $row['id_cat_parcel']; ?>">
                                        <?= htmlspecialchars($row['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="mBEstatus"><b>* Estatus del Paquete:</b></label>
                            <select name="mBEstatus" id="mBEstatus" class="form-control" disabled>
                                <option value="99">Selecciona</option>
                                <option value="1">Nuevo</option>
                                <option value="2">Mensaje Enviado (Recordatorio)</option>
                                <option value="5">Confirmados (Recordatorio)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-9">
                       <div class="row" id="titleParams">
                            <input type="hidden" class="form-control" id="location_lnk" value="<?php echo $infoLocation[0]['address'].', '.$infoLocation[0]['address_share'];?>">
                            <div class="col-md-12">
                                <hr>
                                <div class="col-md-12">
                                    <div class="row" style="text-align: center;">
                                        <div class="col-md-2"><label>Parametros:</label></div>
                                        <div class="col-md-2">
                                            <span draggable="true" class="badge badge-pill badge-info" style="cursor: pointer;" data-toggle="tooltip" data-placement="top" valor="usuario_db" title="Asigar el nombre de usuario desde base de datos">
                                                <i class="fa fa-user"></i> usuario
                                            </span>
                                        </div>
                                        <div class="col-md-2">
                                            <span draggable="true" class="badge badge-pill badge-info" style="cursor: pointer;" data-toggle="tooltip" data-placement="top" valor="<?php echo $infoLocation[0]['location_desc'];?>" title="<?php echo $infoLocation[0]['location_desc'];?>">
                                                <i class="fa fa-map-marker"></i> <?php echo $infoLocation[0]['location_desc'];?>
                                            </span>
                                        </div>
                                        <div class="col-md-2">
                                            <span draggable="true" class="badge badge-pill badge-info" style="cursor: pointer;" data-toggle="tooltip" data-placement="top" valor="<?php echo $infoLocation[0]['address'].', '.$infoLocation[0]['address_share'];?>" title="<?php echo $infoLocation[0]['address'].', '.$infoLocation[0]['address_share'];?>">
                                                <i class="fa fa-map-marker"></i> dirección
                                            </span>
                                        </div>
                                        <div class="col-md-2">
                                            <span draggable="true" class="badge badge-pill badge-info" style="cursor: pointer;" data-toggle="tooltip" data-placement="top" valor="09:00 am" title="09:00 am">
                                                <i class="fa fa-clock-o"></i> 09:00 am
                                            </span>
                                        </div>
                                        <div class="col-md-2">
                                            <span draggable="true" class="badge badge-pill badge-info" style="cursor: pointer;" data-toggle="tooltip" data-placement="top" valor="05:00 pm" title="05:00 pm">
                                                <i class="fa fa-clock-o"></i> 05:00 pm
                                            </span>
                                        </div>
                                        <div class="col-md-2">
                                            <span draggable="true" class="badge badge-pill badge-info" style="cursor: pointer;" data-toggle="tooltip" data-placement="top" valor="<?php echo $hoy;?>" title="Hoy: <?php echo $hoy;?>">
                                                <i class="fa fa-calendar"></i> hoy
                                            </span>
                                        </div>
                                         <div class="col-md-2">
                                            <span draggable="true" class="badge badge-pill badge-info" style="cursor: pointer;" data-toggle="tooltip" data-placement="top" valor="<?php echo $tomorrow;?>" title="mañana: <?php echo $tomorrow;?>">
                                                <i class="fa fa-calendar"></i> mañana
                                            </span>
                                        </div>
                                        <div class="col-md-2">
                                            <span draggable="true" class="badge badge-pill badge-info" style="cursor: pointer;" data-toggle="tooltip" data-placement="top" valor="<?php echo $fechaDev;?>" title="Fecha devolución: <?php echo $fechaDev;?>">
                                                <i class="fa fa-calendar"></i> f. dev.
                                            </span>
                                        </div>
                                        <div class="col-md-2">
                                            <span draggable="true" class="badge badge-pill badge-info" style="cursor: pointer;" data-toggle="tooltip" data-placement="top" valor="08:00 am" title="08:00 am">
                                                <i class="fa fa-clock-o"></i> 08:00 am
                                            </span>
                                        </div>
                                        <div class="col-md-2">
                                            <span draggable="true" class="badge badge-pill badge-info" style="cursor: pointer;" data-toggle="tooltip" data-placement="top" valor="folios_db" title="Asignar folios desde base de datos">
                                                <i class="fa fa-cubes"></i> folios
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <hr>
                                <div id="camposDinamicos" class="row"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="mBListTelefonos"><b>* Telefonos (Excel):</b></label>
                            <textarea class="form-control" id="mBListTelefonos" name="mBListTelefonos" rows="8"></textarea>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-9"></div>
                <div class="col-md-3"><span id="infp"></span></div>
            </div>

            <div class="col-md-12 mt-3" id="div-prev" style="display: none;">
                <h5 style="text-align:center;"><b>Vista Previa:</b></h5>
                <div id="preview-template" style="white-space: pre-line; border:1px solid #ccc; padding:10px; border-radius:5px; background-color:#f7f7f7;"></div>
            </div>
            <div class="modal-footer">
                <button id="btn-send-template" type="button" class="btn btn-success" title="Guardar">Enviar</button>
                <button type="button" class="btn btn-danger" title="Cerrar" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>