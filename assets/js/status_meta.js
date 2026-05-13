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
			{title: `Guías`,               name:`guias`,       data:`guias`},      //0
			{title: `Télefono`,        name:`phone`,    data:`phone`},   //1
			{title: `Fecha Notificación`,       name:`n_date`,      data:`n_date`},     //2
			{title: `Destinatario`,  contact_name:`fecha_registro`,   contact_name:`fecha_registro`},  //3
			{title: `Ultimo Estatus`,         name:`statusName`,   data:`statusName`},  //4
			{title: `Fecha Estatus`,            name:`statusDate`,            data:`statusDate`},
			{title: `Wamid`,             name:`message_id`,             data:`message_id`}            //5
		],
        'order': [[5, 'desc']]
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

	$('#btn-borrar').click(function(){
		$('#smGuia').val('');
		$('#smPhone').val('');
	});
});