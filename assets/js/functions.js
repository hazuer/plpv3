$(document).ready(function () {
	let keepReading = false;
	$("#logoff").click(function () {
		swal({
			title: "Cerrar sesión",
			text: "¿Está seguro?",
			icon: "warning",
			buttons: true,
			dangerMode: true,
		})
			.then((willDelete) => {
				if (willDelete) {
					window.location.href = `${base_url}/controllers/loginController.php?option=logoff`;
				} else {
					return false;
				}
			});
	});

	$('.onclikload').on('click', function (e) {
		loading();
		$('.swal-button-container').hide();
	});

	$("#home").click(function () {
		window.location.href = `${base_url}/views/packages.php`;
	});

	$('#btn-grouped').click(function () {
		window.location.href = `${base_url}/views/grouped.php`;
	});

	$('#option-location').on('change', function () {
		let formData = new FormData();
		formData.append('id_location', $('#option-location').val());
		formData.append('option', 'changeLocation');
		$.ajax({
			url: `${base_url}/controllers/packageController.php`,
			type: 'POST',
			data: formData,
			cache: false,
			contentType: false,
			processData: false,
		})
			.done(function (response) {
				window.location.reload();
			})
	});

	$('#option-location-1,#option-location-2').click(function () {
		let formData = new FormData();
		formData.append('id_location', $(this).data('slocation'));
		let sdesc = $(this).data('slocationd');
		swal(`Nueva Ubicación ${sdesc}`, "", "success");
		formData.append('option', 'changeLocation');
		$.ajax({
			url: `${base_url}/controllers/packageController.php`,
			type: 'POST',
			data: formData,
			cache: false,
			contentType: false,
			processData: false,
		})
			.done(function (response) {
				setTimeout(function () {
					window.location.reload();
				}, 1500);
			})
	});

	$('#btn-report').click(function () {
		window.location.href = `${base_url}/views/reports.php`;
	});

	$('#btn-handler').click(function () {
		window.location.href = `${base_url}/views/handler.php`;
	});

	$('#btn-list-contact').click(function () {
		window.location.href = `${base_url}/views/contacts.php`;
	});

	$('#btn-chart').click(function () {
		window.location.href = `${base_url}/views/chart.php`;
	});

	$('#btn-map').click(function () {
		window.location.href = `${base_url}/views/map.php`;
	});

	$(function () {
		$('[data-toggle="tooltip"]').tooltip()
	})

	let html5QrcodeScanner;
	let scanning = false; // bandera de bloqueo

	$('#vGuia').on('keydown', function (event) {
		if (event.key === 'Enter' || event.keyCode === 13) {
			event.preventDefault(); // Evita que se dispare un submit si está en un formulario
			const vGuia = $(this).val();

			if (!vGuia) {
				// Está vacío o solo tenía espacios
				swal("Error!", 'Ingresa numero de guía', "error");
				return; // Salir o detener ejecución
			}
			let formData = new FormData();
			formData.append('vGuia', vGuia);
			formData.append('id_location', $('#option-location').val());
			formData.append('option', 'checkGuia');
			$.ajax({
				url: `${base_url}/controllers/packageController.php`,
				type: 'POST',
				data: formData,
				cache: false,
				contentType: false,
				processData: false,
				beforeSend: function () {
					showSwal('Buscando Guía', 'Espere por favor...');
					$('.swal-button-container').hide();
				}
			})
				.done(function (response) {
					$('#vGuia').val('');
					swal.close();
					if (response.success == 'true') {
						speakText(`Folio: ${response.dataJson.folio}`);
						setTimeout(function () {
							speakText(`Letra, ${response.dataJson.initial}`);
						}, 600);
						setTimeout(function () {
							speakText(`${response.dataJson.contact_name}`);
						}, 600);

						$('#mif-folio')
							.html(`${response.dataJson.folio}`)
							.css('color', response.dataJson.marker)
							.css('font-size', '70px');

						$('#mif-letra')
							.html(`${response.dataJson.initial}`)
							.css('color', response.dataJson.marker)
							.css('font-size', '70px');;

						$('#mif-nombre').html(`${response.dataJson.contact_name}`);
						let rawPhone = response.dataJson.phone;
						let formattedPhone = `${rawPhone.substring(0, 3)}-${rawPhone.substring(3, 6)}-${rawPhone.substring(6, 8)}-${rawPhone.substring(8)}`;
						$('#mif-telefono').html(formattedPhone);

						$('#modal-info-guia-title').html(`${response.dataJson.tracking}`);
						$('#modal-info-guia').modal({ backdrop: 'static', keyboard: false }, 'show');

						setTimeout(function () {
							$('#modal-info-guia').modal('hide');
							$('#vGuia').focus();
							if (keepReading) {
								$('#btn-scan-qr').click();
							}
						}, 7000);
					} else {
						swal("Error!", 'Guía no encontrada', "error");
						setTimeout(function () {
							swal.close();
							$('#vGuia').focus();
							if (keepReading) {
								$('#btn-scan-qr').click();
							}
						}, 2500);
					}
				})
		}
	});

	$('#btn-scan-qr').click(function () {
		scanning = false; // reiniciar la bandera
		keepReading = true;
		console.log('init', keepReading);
		$('#vGuia').val('');
		let titleModal = 'Verificador Guía';
		html5QrcodeScanner = new Html5QrcodeScanner("qr-reader", {
			fps: 15,
			qrbox: function (viewfinderWidth, viewfinderHeight) {
				// Calcular el lado del cuadro como el 60% del ancho disponible o el más pequeño entre ancho y alto
				const minEdge = Math.min(viewfinderWidth, viewfinderHeight);
				const side = Math.floor(minEdge * 0.7); // por ejemplo, 60% del área visible
				return { width: side, height: side };
			}
		});
		html5QrcodeScanner.render(onScanSuccess);

		$('#modal-scan-qr-title').html(titleModal);
		$('#modal-scan-qr').modal({ backdrop: 'static', keyboard: false }, 'show');
	});

	function onScanSuccess(decodedText, decodedResult) {
		if (scanning) return; // evitar múltiples ejecuciones
		scanning = true;

		console.log('leyo codigo', keepReading);
		console.log(`Scan result: ${decodedText}`, decodedResult);
		// Establecer el valor escaneado y simular Enter
		$('#vGuia').val(decodedText).trigger('input');
		$('#vGuia').trigger(jQuery.Event('keydown', { keyCode: 13, which: 13 }));

		// cerrar el modal y limpiar el escáner inmediatamente
		$('#modal-scan-qr').modal('hide');
		html5QrcodeScanner.clear().catch(err => {
			console.warn('Error al limpiar el escáner:', err);
		});
	}

	$('#close-qr-b,#close-qr-x').click(function () {
		keepReading = false;
		console.log('se detuvo', keepReading);
		if (html5QrcodeScanner) {
			html5QrcodeScanner.clear().catch(error => {
				console.warn('Error al detener el escáner:', error);
			});
		}
	});


});
function speakText(txt, rate = 1) {
	const utterance = new SpeechSynthesisUtterance(txt);
	utterance.lang = 'es-ES'; // Español
	utterance.rate = rate; // Velocidad normal
	utterance.pitch = 1; // Tono normal
	window.speechSynthesis.speak(utterance);
}

const showSwal = (title = 'Procesando...', textDesc = 'Espere por favor') => {
	swal({
		title: title,
		text: textDesc,
		icon: `${base_url}/assets/img/ajax-loader.gif`,
		showConfirmButton: false,
		closeOnClickOutside: false
	});
}

const loading = () => {
	swal({
		title: '',
		text: '',
		icon: `${base_url}/assets/img/ajax-loader.gif`,
		showConfirmButton: false,
		closeOnClickOutside: false
	});
}