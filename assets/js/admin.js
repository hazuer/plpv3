$(document).ready(function() {
	let baseController = 'controllers/packageController.php';

	const checkboxes = document.querySelectorAll(".chk-package");
	let idLocationSelected = $('#option-location');

	checkboxes.forEach(chk => {
		chk.addEventListener("change", function() {
			const tracking = this.getAttribute("data-tracking");
			const isVerified = this.checked ? 1 : 0; // ✅ 1 si está marcado, 0 si no
			console.log("Tracking seleccionado:", tracking, "Estado:", isVerified);

			let formData = new FormData();
			formData.append('tracking', tracking);
			formData.append('is_verified', isVerified); // enviar el valor a la BD
			formData.append('option', 'verifiedPackage');

			$.ajax({
				url: `${base_url}/${baseController}`,
				type: 'POST',
				data: formData,
				cache: false,
				contentType: false,
				processData: false,
			}).done(function(response) {
				console.log("Actualizado:", response);
			}).fail(function(e) {
				console.log("Opps algo salió mal", e);
			});
		});
	});

	$("#onOffBot").on("click", function (e) {
		e.preventDefault(); // evita que navegue

		let enable = $(this).data("enable");
		let txtBot="";
		enable = (enable == 1) ? 0 : 1;

		if (enable == 1) {
			txtBot= "Bot activo";
		} else {
			txtBot= "Bot inactivo";
		}
		let formData = new FormData();
		formData.append('enable', enable);
		formData.append('id_location', idLocationSelected.val());
		formData.append('option', 'onOffBot');

		$.ajax({
			url: `${base_url}/${baseController}`,
			type: 'POST',
			data: formData,
			cache: false,
			contentType: false,
			processData: false,
		}).done(function(response) {
			swal(`${txtBot}`, "", "success");
			$('.swal-button-container').hide();
			setTimeout(function(){
				swal.close();
				window.location.reload();
			}, 1500);
		}).fail(function(e) {
			console.log("Opps algo salió mal", e);
		});
	});

});