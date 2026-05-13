$(document).ready(function() {
	let baseController = 'controllers/packageController.php';

  	let table = $('#tbl-reports').DataTable({
		"language": {
            processing: "Procesando...",
            search: "Buscar:",
            lengthMenu: "Mostrar _MENU_ registros",
            info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
            infoEmpty: "Mostrando 0 a 0 de 0 registros",
            infoFiltered: "(filtrado de _MAX_ registros totales)",
            loadingRecords: "Cargando...",
            zeroRecords: "No se encontraron resultados",
            emptyTable: "No hay datos disponibles en la tabla",
            paginate: {
                first: "Primero",
                previous: "Anterior",
                next: "Siguiente",
                last: "Último"
            },
            aria: {
                sortAscending: ": Activar para ordenar la columna de forma ascendente",
                sortDescending: ": Activar para ordenar la columna de forma descendente"
            }
        },
		"bPaginate": true,
		"lengthMenu": [[10, 50, 100, -1], [10, 50, 100, "All"]], // Definir las opciones de longitud del menú
        "pageLength": 500, // Establecer el número de registros por página predeterminado
        "bInfo" : true,
		scrollCollapse: true,
		scroller: true,
		scrollY: 450,
		scrollX: true,
		dom: 'Bfrtip',
		buttons: [
			'excel'
		],
		"columns" : [
			{title: `Id`,               name:`id_package`,       data:`id_package`},      //0
			{title: `Ubicación`,        name:`location_desc`,    data:`location_desc`},   //1
			{title: `Paquetería`,       name:`parcel_desc`,      data:`parcel_desc`},     //2
			{title: `Fecha Registro`,   name:`fecha_registro`,   data:`fecha_registro`},  //3
			{title: `Registró`,         name:`registrado_por`,   data:`registrado_por`},  //4
			{title: `Guía`,             name:`guia`,             data:`guia`},            //5
			{title: `Folio`,            name:`folio`,            data:`folio`},           //6
			{title: `T. Contacto`,      name:`contact_type`,     data:`contact_type`},    //7
			{title: `Télefono`,         name:`phone`,            data:`phone`},           //8
			{title: `Destinatario`,     name:`receiver`,         data:`receiver`},        //9
			{title: `Estatus`,          name:`status_desc`,      data:`status_desc`},     //10
			{title: `Fecha Mensaje`,    name:`fecha_envio_sms`,  data:`fecha_envio_sms`}, //11
			{title: `Envió Mensaje`,    name:`sms_enviado_por`,  data:`sms_enviado_por`}, //12
			{title: `Total Mensaje`,    name:`total_sms`,        data:`total_sms`},       //13
			{title: `Fecha Entrega`,    name:`fecha_liberacion`, data:`fecha_liberacion`},//14
			{title: `Entregó`,          name:`libero`,           data:`libero`},          //15
			{title: `Nota`,             name:`note`,             data:`note`},            //16
			{title: `Evidencia(s)`,     name:`evidence`,         data:`evidence`},        //17
			{title: `T.P. Entregados`,  name:`t_pk_delivery`,    data:`t_pk_delivery`},   //18
			{title: `Modo Registro`,    name:`tipo_modo`,        data:`tipo_modo`},       //19
			{title: `Fecha Rotulación`, name:`v_date`,           data:`v_date`},          //20
			{title: `Usuario que Rotuló`, name:`user_rotulo`,    data:`user_rotulo`},     //21
			{title: `Dirección`,        name:`address`,          data:`address`}          //22
		],
        'order': [[14, 'desc']]
	});

	//funcion para borrar campo de busqueda
	let clearButton = $(`<span id="clear-search" style="cursor: pointer;">&nbsp;<i class="fa fa-eraser fa-lg" aria-hidden="true"></i></span>`);
	clearButton.click(function() {
		$("#tbl-reports_filter input[type='search']").val("");
		setTimeout(function() {
			$("#tbl-reports_filter input[type='search']").trigger('mouseup').focus();
		}, 100);
	});
	$("#tbl-reports_filter label").append(clearButton);

	$(`#tbl-reports tbody`).on( `click`, `#btn-details`, function () {
		let row = table.row( $(this).closest('tr') ).data();
		loadSmsDetail(row.id_package);
	});

	$(`#tbl-reports tbody`).on( `click`, `#id-logger`, function () {
		let row = table.row( $(this).closest('tr') ).data();
		$('#btn-revert-status').hide();
		$('#btn-change-location').hide();
		$('#id_package_db').val(0);
		$('#txt_mv_location').val('');
		$('#newLocation').val('');
		loadHistory(row.id_package,row.guia);
		if(row.status_desc==='Devuelto'){
			$('#btn-revert-status').show();
			$('#id_package_db').val(row.id_package);
		}

		if(row.status_desc==='Nuevo' || row.status_desc==='Devuelto'){
			const btnTxtLocation = (row.location_desc==='Tlaquiltenago') ? 'Zacatepec':'Tlaquiltenango';
			const newIdLocation = (row.location_desc==='Tlaquiltenago') ? 2:1;
			$('#btn-change-location').html(`Mover a ${btnTxtLocation}`);
			$('#txt_mv_location').val(`Paquete actualizado: de ${row.location_desc} a ${btnTxtLocation}`);
			$('#newLocation').val(newIdLocation)
			$('#btn-change-location').show();
			$('#id_package_db').val(row.id_package);
		}
	});

	$('#btn-revert-status').click(function(){
		revertEstatus();
	});

	function revertEstatus() {
		let id_package = $('#id_package_db').val();

		let formData =  new FormData();
		formData.append('id_package', id_package);
		formData.append('option','revertStatus');
		try {
			$.ajax({
				url: `${base_url}/${baseController}`,
				type: 'POST',
				data: formData,
				cache: false,
				contentType: false,
				processData: false,
				beforeSend : function() {
					showSwal();
					$('.swal-button-container').hide();
				}
			}).done(function(response) {
				swal.close();
				if(response.success=='true'){
					swal("Éxito!", `${response.message}`, "success");
					setTimeout(function(){
						swal.close();
						window.location.reload();
					}, 1500);
				}
			}).fail(function(e) {
				console.log("Opps algo salio mal",e);
			});

		} catch (error) {
			console.error(error);
		}
	}

	$('#btn-change-location').click(function(){
		movePakageLocation();
	});
	function movePakageLocation() {
		let id_package  = $('#id_package_db').val();
		let txtLocation = $('#txt_mv_location').val();
		let newLocation = $('#newLocation').val();
		let formData    = new FormData();
		formData.append('id_package', id_package);
		formData.append('txtLocation', txtLocation);
		formData.append('newLocation', newLocation);
		formData.append('option','movePakageLocation');
		try {
			$.ajax({
				url: `${base_url}/${baseController}`,
				type: 'POST',
				data: formData,
				cache: false,
				contentType: false,
				processData: false,
				beforeSend : function() {
					showSwal('Actualizando ubicación');
					$('.swal-button-container').hide();
				}
			}).done(function(response) {
				swal.close();
				if(response.success=='true'){
					swal("Éxito!", `${response.message}`, "success");
					setTimeout(function(){
						swal.close();
						window.location.reload();
					}, 1500);
				}
			}).fail(function(e) {
				console.log("Opps algo salio mal",e);
			});

		} catch (error) {
			console.error(error);
		}
	}

	async function loadHistory(id_package,guia) {
		let listLogs = await getRecordsHistory(id_package,guia);
		createTableLog(listLogs,guia);

		$('#modal-logger').modal({backdrop: 'static', keyboard: false}, 'show');
	}

	function createTableLog(data,guia) {
		$('#tbl-logger').empty();
		let c=1;
		let phoneTitle = guia;
		$.each(data.dataJson, function(index, item) {
			let row = `<tr>
				<td><b>${c}</b></td>
				<td>${item.datelog}</td>
				<td>${item.name_user}</td>
				<td>${item.new_status}</td>
				<td>${item.old_status}</td>
				<td>${item.desc_mov}</td>
			</tr>`;
			$('#tbl-logger').append(row);
			c++;
		});
		$('#modal-logger-title').html(`Historial de Movientos Guía ${phoneTitle}`);
	}

	async function getRecordsHistory(id_package) {
		let list = [];
		let formData =  new FormData();
		formData.append('id_package', id_package);
		formData.append('option','getRecordsHistory');
		try {
			const response = await $.ajax({
				url: `${base_url}/${baseController}`,
				type: 'POST',
				data: formData,
				cache: false,
				contentType: false,
				processData: false
			});
			if(response.success=='true'){
				list = response;
			}
		} catch (error) {
			console.error(error);
		}
		return list;
	}

	async function loadSmsDetail(id_package) {
		let listSms = await getRecordsSms(id_package);
		createTableSmsSent(listSms);

		$('#modal-sms-report').modal({backdrop: 'static', keyboard: false}, 'show');
	}

	async function getRecordsSms(id_package) {
		let list = [];
		let formData =  new FormData();
		formData.append('id_package', id_package);
		formData.append('option','getRecordsSms');
		try {
			const response = await $.ajax({
				url: `${base_url}/${baseController}`,
				type: 'POST',
				data: formData,
				cache: false,
				contentType: false,
				processData: false
			});
			if(response.success=='true'){
				list = response;
			}
		} catch (error) {
			console.error(error);
		}
		return list;
	}

	function createTableSmsSent(data) {
		$('#tbl-sms-sent').empty();
		let c=1;
		let phoneTitle='';
		$.each(data.dataJson, function(index, item) {
			phoneTitle = item.phone;
			let row = `<tr>
				<td><b>${c}</b></td>
				<td>${item.n_date}</td>
				<td>${item.phone}</td>
				<td>${item.contact_name}</td>
				<td>${item.user}</td>
				<td>${item.message}</td>
				<td>${item.t_cta}</td>
				<td>${item.sid}</td>
			</tr>`;
			$('#tbl-sms-sent').append(row);
			c++;
		});
		$('#modal-sms-report-title').html(`Mensajes Enviados ${phoneTitle}`);
	}

	$('#btn-f-erase').click(function(){
		$('#rFstatus').val(99);
		$('#rFIni').val('');
		$('#rFFin').val('');
		$('#rGuia').val('');
		$('#rFolio').val('');
		$('#rTelefono').val('');
		$('#rFIniLib').val('');
		$('#rFFinLib').val('');
		$('#rGuia').focus();
		$('#rParcel').val(99);
	});

	$('#rTelefono').on('input', function() {
		let input = $(this).val();
		input = input.replace(/\D/g, '').slice(0, 10); // Elimina caracteres no numéricos y limita a 10 dígitos
		$(this).val(input);
	});

	$('#rFolio').on('input', function() {
        let input = $(this).val();
        input = input.replace(/\D/g, '').slice(0, 5); // Elimina caracteres no numéricos y limita a 10 dígitos
        $(this).val(input);
    });

	$(`#tbl-reports tbody`).on( `click`, `#btn-evidence`, function () {
		let row = table.row( $(this).closest('tr') ).data();
		loadEvidences(row.id_package);
	});

	async function loadEvidences(id_package) {
		let listEvidence = await getRecordsEvidence(id_package);
		createTableEvidence(listEvidence);
		$('#modal-evidence').modal({backdrop: 'static', keyboard: false}, 'show');
	}

	async function getRecordsEvidence(id_package) {
		let list = [];
		let formData =  new FormData();
		formData.append('id_package', id_package);
		formData.append('option','getRecordsEvidence');
		try {
			const response = await $.ajax({
				url: `${base_url}/${baseController}`,
				type: 'POST',
				data: formData,
				cache: false,
				contentType: false,
				processData: false
			});
			if(response.success=='true'){
				list = response;
			}
		} catch (error) {
			console.error(error);
		}
		return list;
	}

	function createTableEvidence(data) {
		$('#tbl-evidence').empty();
		let c=1;
		let titleGuia='';
		
		$.each(data.dataJson, function(index, item) {
			titleGuia    = item.tracking;
			let item_path = item.path;

			let clean_path   = item_path.replace(/^\.\.\//, '');
			let encoded_path = encodeURI(clean_path);
			let full_url     = `${base_url}/${encoded_path}`;
			let preview = showPreview(full_url);

			let row = `<tr>
				<td><b>${c}</b></td>
				<td>${item.date_e}</td>
				<td>${item.user}</td>
				<td style="text-align:center;">${preview}</td>
			</tr>`;
			$('#tbl-evidence').append(row);
			c++;
		});
		$('#modal-evidence-title').html(`Evidencia(s) Guía ${titleGuia}`);
	}

	function showPreview(fileUrl) {
        const fileExtension = fileUrl.split('.').pop().toLowerCase(); // Obtener la extensión del archivo

        let previewHtml = '';
		const txtFullARef =`<br><a href="${fileUrl}" target="_blank" data-toggle="tooltip" data-placement="top" title="Click para ver en pantalla completa">[Pantalla Completa `;

        if (['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'].includes(fileExtension)) {
            // Si es imagen, mostrar en un <img>
			previewHtml = `<img src="${fileUrl}" width="150" height="150">${txtFullARef} IMG]</a>`;
        } else if (fileExtension === 'pdf') {
            // Si es PDF, mostrar <embed>
            previewHtml = `<embed src="${fileUrl}" width="150" height="150" type="application/pdf">${txtFullARef} PDF]</a>`;
        } else {
            previewHtml = `<p>Archivo no compatible para vista previa</p>`;
        }

        return previewHtml;
    }

	$("#btn-admin").click(function(){
		window.location.href = `${base_url}/views/adminReport.php`;
	});

	$("#btn-pre").click(function(){
		window.location.href = `${base_url}/views/adminReport.php`;
	});
});