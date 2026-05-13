$(document).ready(function() {
	let baseController = 'controllers/packageController.php';

	//funcion para borrar campo de busqueda
	let clearButton = $(`<span id="clear-search" style="cursor: pointer;">&nbsp;<i class="fa fa-eraser fa-lg" aria-hidden="true"></i></span>`);
	clearButton.click(function() {
		$("#tbl-msj-whats_filter input[type='search']").val("");
		setTimeout(function() {
			$("#tbl-msj-whats_filter input[type='search']").trigger('mouseup').focus();
		}, 100);
	});
	$("#tbl-msj-whats_filter label").append(clearButton);

});