$(document).ready(function() {
	let baseController = 'controllers/packageController.php';
	let idLocationSelected = $('#option-location');
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
			// {title: `Folio`,            name:`folio`,            data:`folio`},           //6
			{title: `T. Contacto`,      name:`contact_type`,     data:`contact_type`},    //7
			{title: `Télefono`,         name:`phone`,            data:`phone`},           //8
			{title: `Destinatario`,     name:`receiver`,         data:`receiver`},        //9
			// {title: `Estatus`,          name:`status_desc`,      data:`status_desc`},     //10
			{title: `Nota`,             name:`note`,             data:`note`},            //16
			// {title: `Modo Registro`,    name:`tipo_modo`,        data:`tipo_modo`},       //19
			{title: `Dirección`,        name:`address`,          data:`address`}          //22
		],
        'order': [[3, 'desc']]
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

	$('#btn-create-cb-pre').click(function(){
var locat = $('.name_user').text().trim();
		swal({
			title: `Crear Códigos de Barras - Pre-Registro ${locat}`,
			text: "¿Que Opción Deseas Generar?",
			content: createSelect(),
			icon: "info",
			dangerMode: false,
			buttons: {
				confirm: {
					text: "Generar",
					value: "generar"
				}
			}
		})
		.then((value) => {
			if(value==='generar'){
				let idParcel = $('#optionSelect').val();
				let formData = new FormData();
				formData.append('id_location', idLocationSelected.val());
				formData.append('idParcel', idParcel);
				formData.append('option', 'createcbpre');

				try {
					$.ajax({
						url        : `${base_url}/${baseController}`,
						type       : 'POST',
						data       : formData,
						cache      : false,
						contentType: false,
						processData: false,
						beforeSend : function() {
							showSwal('Creando códigos','Espere por favor...');
							$('.swal-button-container').hide();
						}
					})
					.done(function(response) {
						swal.close();
						if (response.success=='true') {
							// Crear un enlace temporal
							let link = document.createElement('a');
							link.href =`${base_url}/controllers/${response.zip}`,
							link.download = response.zip; // Nombre del archivo ZIP
							document.body.appendChild(link);
							// Simular el clic en el enlace para iniciar la descarga
							link.click();
							// Eliminar el enlace temporal del DOM
							document.body.removeChild(link);
							swal("Éxito!", `Descarga finalizada`, "success");
						$('.swal-button-container').hide();
						setTimeout(function(){
							swal.close();
							let formData = new FormData();
							formData.append('zipFile',`${response.zip}`);
							formData.append('option','deleteZip');
							$.ajax({
								url        : `${base_url}/${baseController}`,
								type       : 'POST',
								data       : formData,
								cache      : false,
								contentType: false,
								processData: false,
							})
						}, 2500);
						} else {
							//console.error('Error al generar el archivo ZIP:', response.message);
							swal('Atención', response.message, "warning");
						}
					}).fail(function(e) {
						console.log("Opps algo salio mal",e);
					});
				} catch (error) {
					console.error(error);
				}
			}
		});
	});

	function createSelect(){
		let selectDiv = document.createElement('div');
		// Crear el select option
		let select = document.createElement('select');
		select.setAttribute('id', 'optionSelect');

		// Agregar opciones al select
		let option1 = document.createElement('option');
		option1.value = '1';
		option1.text = 'J&T';

		let option2 = document.createElement('option');
		option2.value = '2';
		option2.text = 'IMILE';

		let option3 = document.createElement('option');
		option3.value = '3';
		option3.text = 'CNMEX';

		let option4 = document.createElement('option');
		option4.value = '99';
		option4.text = 'TODAS';

		// Añadir las opciones al select
		select.appendChild(option1);
		select.appendChild(option2);
		select.appendChild(option3);
		select.appendChild(option4);
		selectDiv.appendChild(select);
		return selectDiv;
	}


	$('#btn-borrar-pre').click(function(){
		var locat = $('.name_user').text().trim();
		swal({
			title: "Borrado de Pre-Registros",
			text: `¿Desea borrar TODOS los datos de ${locat}?`,
			icon: "warning",
			buttons: {
				btnconfirm: {
					text: "Sí, borrar todo",
					value: "ok",
				}
			},
			dangerMode: false,
		})
		.then((value) => {
			let formData = new FormData();
			formData.append('id_location', idLocationSelected.val());
			formData.append('option', 'deletePre');
			if(value==='ok'){
				$.ajax({
				url        : `${base_url}/${baseController}`,
				type       : 'POST',
				data       : formData,
				cache      : false,
				contentType: false,
				processData: false,
				beforeSend : function() {
					showSwal('Borrando información','Espere por favor...');
					$('.swal-button-container').hide();
				}
				})
				.done(function(response) {
					swal.close();
					if(response.success==='true'){
						swal('Éxito', response.message, "success");
						setTimeout(function(){
							swal.close();
							window.location.reload();
						}, 2500);
					}
					$('.swal-button-container').hide();
				});
			}
		});
	});

});