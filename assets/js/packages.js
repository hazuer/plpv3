$(document).ready(function () {
	let baseController = 'controllers/packageController.php';

	let idLocationSelected = $('#option-location');
	let id_location = $('#id_location');
	let id_package = $('#id_package');
	let folio = $('#folio');
	let action = $('#action');
	let c_date = $('#c_date');
	let phone = $('#phone');
	let receiver = $('#receiver');
	let tracking = $('#tracking');
	let id_status = $('#id_status');
	let divStatus = $('#div-status');

	let table = $('#tbl-packages').DataTable({
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
		//"bFilter": false,
		"bInfo": true,
		scrollCollapse: true,
		scroller: true,
		scrollY: 450,
		scrollX: true,
		dom: 'Bfrtip',
		buttons: [
			'excel'
		],
		"columns": [
			{ title: `id_package`, name: `id_package`, data: `id_package` },  //0
			{ title: `Guía`, name: `tracking`, data: `tracking` },    //1
			{ title: `Télefono`, name: `phone`, data: `phone` },       //2
			{ title: `id_location`, name: `id_location`, data: `id_location` }, //3
			{ title: `Fecha Registro`, name: `c_date`, data: `c_date` },      //4
			{ title: `Folio`, name: `folio`, data: `folio` },       //5
			{ title: `Destinatario`, name: `receiver`, data: `receiver` },    //6
			{ title: `id_status`, name: `id_status`, data: `id_status` },   //7
			{ title: `Estatus`, name: `status_desc`, data: `status_desc` }, //8
			{ title: `note`, name: `note`, data: `note` },        //9
			{ title: `id_contact`, name: `id_contact`, data: `id_contact` },   //10
			{ title: `id_cat_parcel`, name: `id_cat_parcel`, data: `id_cat_parcel` },   //11
			{ title: `Paq.`, name: `parcel`, data: `parcel` },   //12
			{ title: `TM`, name: `messages`, data: `messages` },   //13
			{ title: `TD`, name: `tdt`, data: `tdt` }   //14 + 1 last
		],
		"columnDefs": [
			{ "orderable": false, 'targets': 0, 'checkboxes': { 'selectRow': true } },
			{ "targets": [3, 7, 9, 10, 11], "visible": false, "searchable": false, "orderable": false },
			{ "orderable": false, "targets": 15 }, // last
			// { "width": "40%", "targets": [1,2] }
		],
		'select': {
			'style': 'multi'
		},
		'order': [[4, 'desc']]
	});

	//funcion para borrar campo de busqueda
	let clearButton = $(`<span id="clear-search" style="cursor: pointer;">&nbsp;<i class="fa fa-eraser fa-lg" aria-hidden="true"></i></span>`);
	clearButton.click(function () {
		$("#tbl-packages_filter input[type='search']").val("");
		setTimeout(function () {
			$("#tbl-packages_filter input[type='search']").trigger('mouseup').focus();
		}, 100);
	});
	$("#tbl-packages_filter label").append(clearButton);

	$("#btn-first-package, #btn-add-package,#btn-add-package-1").click(function (e) {
		let fechaFormateada = getCurrentDate();
		let row = {
			id_package: 0,
			phone: '',
			id_location: idLocationSelected.val(),
			c_date: fechaFormateada,
			id_status: 1,
			tracking: '',
			id_status: 1,
			note: '',
			id_contact: 0,
			id_cat_parcel: uIdCatParcel
		}
		loadPackageForm(row);
	});

	function getCurrentDate() {
		let fechaActual = new Date();
		// Obteniendo cada parte de la fecha y hora
		let year = fechaActual.getFullYear();
		let mes = String(fechaActual.getMonth() + 1).padStart(2, '0'); // Agrega un cero al mes si es menor que 10
		let dia = String(fechaActual.getDate()).padStart(2, '0'); // Agrega un cero al día si es menor que 10
		let horas = String(fechaActual.getHours()).padStart(2, '0'); // Agrega un cero a las horas si es menor que 10
		let minutos = String(fechaActual.getMinutes()).padStart(2, '0'); // Agrega un cero a los minutos si es menor que 10
		let segundos = String(fechaActual.getSeconds()).padStart(2, '0'); // Agrega un cero a los segundos si es menor que 10
		// Formateando la fecha en el formato deseado
		let dtCurrent = `${year}-${mes}-${dia} ${horas}:${minutos}:${segundos}`;
		return dtCurrent;
	}

	$(`#tbl-packages tbody`).on(`click`, `#btn-records`, function () {
		let row = table.row($(this).closest('tr')).data();
		loadPackageForm(row);
	});

	function takeEvidence(row) {
		openEvidenceModal({
			title: `Evidencia de Entrega ${row.tracking}`,
			onSave: function (image) {
				ajaxRealese(row, image);
			}
		});
	}

	function ajaxRealese(row, imgEvidence) {
		let listPackageRelease = [];
		let guia = row.tracking;
		listPackageRelease.push(`'${guia}'`);
		let formData = new FormData();
		formData.append('id_location', idLocationSelected.val());
		formData.append('tracking', guia);
		formData.append('listPackageRelease', JSON.stringify(listPackageRelease));
		formData.append('option', 'releasePackage');
		formData.append('imgEvidence', imgEvidence);
		formData.append('desc_mov', 'Liberación de Paquete Manual');
		$.ajax({
			url: `${base_url}/${baseController}`,
			type: 'POST',
			data: formData,
			cache: false,
			contentType: false,
			processData: false,
			beforeSend: function () {
				showSwal('Liberación de Paquete Manual', 'Espere por favor...');
				$('.swal-button-container').hide();
			}
		}).done(function (response) {
			swal.close();
			if (response.success === 'true') {

				if (response.dataJson && response.dataJson.length > 0) {
					// Hay paquetes pendientes por liberar
					let detalle = '';
					response.dataJson.forEach(item => {
						detalle += `${item.tracking} - ${item.receiver} (Folio: ${item.folio})\n`;
					});
					swal(
						"Paquetes pendientes por liberar",
						detalle,
						"warning"
					);
					$('.swal-button-container').hide();
					setTimeout(function () {
						swal.close();
						window.location.reload();
					}, 5500);
				} else {
					// Mensaje normal
					swal(guia, response.message, "success");
					$('.swal-button-container').hide();
					setTimeout(function () {
						swal.close();
						window.location.reload();
					}, 3500);
				}
			}
			else {
				swal(guia, response.message, "warning");
				$('.swal-button-container').hide();
				setTimeout(function () {
					swal.close();
					window.location.reload();
				}, 3500);
			}

		}).fail(function (e) {
			console.log("Opps algo salio mal", e);
		});
	}

	$(`#tbl-packages tbody`).on(`click`, `#btn-tbl-liberar`, function () {
		let row = table.row($(this).closest('tr')).data();
		swal({
			title: `Folio:${row.folio} - ${row.receiver}`,
			text: `Desea liberar la guía ${row.tracking}?`,
			icon: "info",
			buttons: true,
			dangerMode: false,
		}).then((weContinue) => {
			if (weContinue) {
				if (row.id_status == 5) {
					takeEvidence(row);
				} else {
					ajaxRealese(row, '');
				}
			} else {
				return false;
			}
		});
	});

	async function loadPackageForm(row) {
		let titleModal = '';
		$('#form-modal-package')[0].reset();
		divStatus.hide();

		let coincidenciasDiv = $('#coincidencias');
		coincidenciasDiv.empty();
		coincidenciasDiv.hide();

		id_package.val(row.id_package);
		$('#id_contact').val(row.id_contact);
		phone.val(row.phone);
		id_location.val(row.id_location);
		c_date.val(row.c_date);
		receiver.val(row.receiver);
		tracking.val(row.tracking);
		id_status.val(row.id_status);
		$('#id_cat_parcel').val(row.id_cat_parcel);//session
		$('#note').val(row.note);
		action.val('new');
		$('#btn-erase').show();
		$('#phone').prop('disabled', false);
		$('#receiver').prop('disabled', false);
		$('#tracking').prop('disabled', false);
		$('#id_cat_parcel').prop('disabled', false);

		if (row.id_package != 0) {
			$('#div-keep-modal').hide();
			divStatus.show();
			folio.val(row.folio);
			titleModal = `Editar Folio ${row.folio}`;
			action.val('update');
			$('#tracking').prop('disabled', true);
			$('#id_cat_parcel').val(row.id_cat_parcel);
			$('#id_cat_parcel').prop('disabled', true);

			if (row.id_status != 1) {
				$('#phone').prop('disabled', true);
				$('#receiver').prop('disabled', true);
			}
			$('#btn-erase').hide();
		} else {
			updateColors(uMarker);
			cleanForm();
			$('#opcMA').prop('checked', true);
			$('#div-keep-modal').show();
			let newFolio = await getFolio('new');
			folio.val(newFolio);
			titleModal = `Folio Número ${newFolio}`;
			if (rVoice == '1') {
				speakText(titleModal);
			}
		}

		$('#modal-package-title').html(titleModal);
		$('#modal-package').modal({ backdrop: 'static', keyboard: false }, 'show');
	}

	async function getFolio(type) {
		let folio = 0;
		let formData = new FormData();
		formData.append('id_location', idLocationSelected.val());
		formData.append('type', type);
		formData.append('option', 'getFolio');
		try {
			const response = await $.ajax({
				url: `${base_url}/${baseController}`,
				type: 'POST',
				data: formData,
				cache: false,
				contentType: false,
				processData: false,
				beforeSend: function () {
					showSwal('Obteniendo Folio', 'Espere por favor...');
					$('.swal-button-container').hide();
				},
				complete: function () {
					if (type == 'new') {
						setTimeout(function () {
							phone.focus();
						}, 600);
					}
					swal.close();
				}
			});
			folio = response.folio;
		} catch (error) {
			console.error(error);
		}
		return folio;
	}

	$('#btn-save').click(function () {
		savePackage();
	});

	$('#btn-erase').click(function () {
		cleanForm();
	});

	function cleanForm() {
		$('#id_contact').val(0);
		$('#phone').val('');
		$('#receiver').val('');
		$('#tracking').val('');
		$('#note').val('');
		$('#phone').focus();
		let coincidenciasDiv = $('#coincidencias');
		coincidenciasDiv.empty();
		coincidenciasDiv.hide();
	}

	$('#phone').on('keydown', function (e) {
		if (e.key === "Backspace" || e.key === "Delete") {
			$('#id_contact').val(0);
			$('#receiver').val('');
		}
	});

	//-----------------------
	let scanTimeout; // Variable para el temporizador
	$('#tracking').on('input', function () {
		let input = $(this).val().trim();
		if ($('#id_cat_parcel').val() == 1) {
			if (input.length === 15 && input.substr(0, 3).toUpperCase() === "JMX") {
				$('#btn-save').click();
			}
		} else if ($('#id_cat_parcel').val() == 2) {
			clearTimeout(scanTimeout); // Reinicia el temporizador si hay más entradas rápidas

			let inputImile = $(this).val().replace(/[\r\n\s]/g, ''); // Elimina saltos de línea y espacios
			$(this).val(inputImile); // Actualiza el input con los datos limpios

			// Espera 500ms antes de validar para permitir que el escáner termine de escribir
			scanTimeout = setTimeout(() => {
				if (
					(/^\d{13}$/.test(inputImile)) ||       // Solo números, 13 dígitos
					(/^\d{14}$/.test(inputImile)) ||       // Solo números, 14 dígitos
					(/^im\d{14}$/i.test(inputImile))      // "im" o "IM" + 14 dígitos
				) {
					$('#btn-save').click();
					$(this).val(''); // Limpia el input después de procesarlo
				}
			}, 500);
		} else if ($('#id_cat_parcel').val() == 3) {
			if (input.length === 15 && input.substr(0, 5).toUpperCase() === "CNMEX") {
				$('#btn-save').click();
			}
		}
	});

	function savePackage() {
		if (phone.val() == '' || receiver.val() == '' || tracking.val() == '') {
			swal("Atención!", "* Campos requeridos", "error");
			return;
		}

		let p = phone.val().trim(); // Eliminar espacios en blanco al inicio y al final
		if (p.length != 10) {
			swal("Atención!", "* El número de télefono no es válido", "error");
			return;
		}

		if (phone.val().trim() === '0000000000') {
			swal("Atención!", "* El número de télefono (0000000000) no es válido", "error");
			return;
		}

		let validarG = $('#checkG').is(':checked') ? 1 : 0;
		let guia = '';
		if (validarG == 1) {

			if ($('#id_cat_parcel').val() == 1) {
				let jtTracking = tracking.val().trim(); // Eliminar espacios en blanco al inicio y al final
				let regex = /^JMX\d{12}$/;
				if (jtTracking.length !== 15 || !regex.test(jtTracking.toUpperCase())) {
					let mensajeError = "* Código de barras no válido:";
					if (jtTracking.length !== 15) {
						mensajeError += " Debe tener 15 caracteres";
					} else {
						mensajeError += " Formato no válido";
					}
					swal("Atención!", mensajeError, "error");
					return;
				}
				let decodedText = $('#tracking').val();
				guia = decodedText.substring(0, 3).toUpperCase() + decodedText.substring(3);
			} else if ($('#id_cat_parcel').val() == 2) {
				let imTracking = tracking.val().trim(); // Eliminar espacios al inicio y al final
				/* ── Normalizar a mayúsculas si empieza con im ── */
				if (/^im\d{14}$/i.test(imTracking)) {     // "im" o "IM" + 14 dígitos
					imTracking = imTracking.toUpperCase(); // lo convertimos a "IM...."
				}
				// Expresiones regulares para validar
				const regexNumerico = /^\d{13,14}$/;       // Solo números, 13 o 14 dígitos
				const regexIm = /^IM\d{14}$/;              // Empieza con IM seguido de 14 dígitos

				// Validar el formato
				if (!regexNumerico.test(imTracking) && !regexIm.test(imTracking)) {
					let mensajeError = "* Código de barras no válido:";

					if (imTracking.startsWith("IM")) {
						if (imTracking.length !== 16) {
							mensajeError += " Los códigos 'IM' deben tener exactamente 16 caracteres.";
						} else {
							mensajeError += " El formato debe ser 'IM' seguido de 14 dígitos.";
						}
					} else if (/^\d+$/.test(imTracking)) {
						mensajeError += " Debe tener 13 o 14 dígitos numéricos.";
					} else {
						mensajeError += " Solo se permiten números o formato 'IM' con 14 dígitos.";
					}

					swal("Atención!", mensajeError, "error");
					return;
				}

				guia = imTracking;
			} else if ($('#id_cat_parcel').val() == 3) {
				let cnTracking = tracking.val().trim(); // Eliminar espacios en blanco al inicio y al final
				let regex = /^CNMEX\d{10}$/;
				if (cnTracking.length !== 15 || !regex.test(cnTracking.toUpperCase())) {
					let mensajeError = "* Código de barras no válido:";
					if (cnTracking.length !== 15) {
						mensajeError += " Debe tener 15 caracteres";
					} else {
						mensajeError += " Formato no válido";
					}
					swal("Atención!", mensajeError, "error");
					return;
				}
				let decodedText = $('#tracking').val();
				guia = decodedText.substring(0, 5).toUpperCase() + decodedText.substring(5);
			}
		} else {
			guia = tracking.val().trim();
		}

		let file = null;
		const evidenceElement = document.getElementById('evidence');
		if (evidenceElement) {
			file = evidenceElement.files[0] ?? null;
		}
		if (id_status.val() == '4' && file === null) {
			swal("Atención!", 'Por favor, proporciona la evidencia (Imagen) de la devolución del paquete', "error");
			return;
		}

		let formData = new FormData();
		formData.append('id_package', id_package.val());
		formData.append('id_location', idLocationSelected.val());
		formData.append('folio', folio.val());
		formData.append('c_date', c_date.val());
		formData.append('phone', phone.val());
		formData.append('receiver', receiver.val());
		formData.append('id_contact', $('#id_contact').val());
		formData.append('tracking', guia);
		formData.append('id_status', id_status.val());
		formData.append('id_marcador', $('#id_marcador').val());
		formData.append('action', action.val());
		formData.append('option', 'savePackage');
		formData.append('note', $('#note').val());
		formData.append('id_cat_parcel', $('#id_cat_parcel').val());
		formData.append('evidence', file);  // Añade el archivo al FormData

		$.ajax({
			url: `${base_url}/${baseController}`,
			type: 'POST',
			data: formData,
			cache: false,
			contentType: false,
			processData: false,
			beforeSend: function () {
				$('#modal-package').modal('hide');
				showSwal('El paquete se está guardando', 'Espere por favor...');
				$('.swal-button-container').hide();
			}
		})
			.done(function (response) {
				swal.close();
				if (response.success == 'true') {
					uMarker = $('#id_marcador').val();
					uIdCatParcel = $('#id_cat_parcel').val();
					let timex = 1500;
					if (response.message == 'Paquete listo para Agrupar') {
						$('audio#togroup')[0].play();
						swal(`${response.message}`, `${response.dataJson}`, "success");
						timex = 2000;
					} else {
						swal(`${response.message}`, "", "success");
					}
					$('.swal-button-container').hide();

					if (action.val() == "update") {
						setTimeout(function () {
							swal.close();
							window.location.reload();
						}, 1500);
						return;
					}

					if (action.val() == "new") {
						cleanForm();
						if ($('#opcMA').prop('checked')) {
							setTimeout(function () {
								swal.close();
								setTimeout(function () {
									$('#btn-add-package').click();
									setTimeout(function () {
										phone.focus();
									}, 100);
								}, 300);
							}, timex);
							return;
						} else {
							let timez = 1500;
							if (response.message == 'Paquete listo para Agrupar') { timez = 2000; }
							setTimeout(function () {
								swal.close();
								window.location.reload();
							}, timez);
							return;
						}
					}
				}
				if (response.success == 'false') {
					$('audio#wrong')[0].play();
					swal("Atención!", `${response.message}`, "info");
					$('.swal-button-container').hide();
					setTimeout(function () {
						swal.close();
						$('#modal-package').modal('show');
					}, 3500);
					return;
				}
			}).fail(function (e) {
				console.log("Opps algo salio mal", e);
			});
	}

	let typingTimer; // Timer global
	let doneTypingInterval = 800; // Tiempo en ms para detectar que el usuario terminó de escribir

	phone.on('input', function () {
		let input = $(this).val().replace(/\D/g, '').slice(0, 10); // solo números, máx 10 dígitos
		$(this).val(input);

		clearTimeout(typingTimer); // Cancela el temporizador anterior si sigue escribiendo

		typingTimer = setTimeout(() => {

			let phoneNumber = input;
			let id_location = idLocationSelected.val();
			let coincidenciasDiv = $('#coincidencias');

			if (input.length === 10) {
				coincidenciasDiv.hide();
				receiver.focus();
				speakText('Nuevo usuario');
				return;
			}

			let idParcel = $('#id_cat_parcel').val();
			let limitDigit = (idParcel == 1 || idParcel == 3) ? 5 : 3;

			if (input.length <= limitDigit) {
				coincidenciasDiv.hide();
				return;
			}

			$.ajax({
				url: `${base_url}/${baseController}`, // URL ficticia de la API
				method: 'POST',
				data: { phone: phoneNumber, id_location: id_location, option: 'getContact', idParcel: idParcel },
				beforeSend: function () {
					$('#phone-loading').show();
					$('#id_contact').val(0);
					$('#receiver').val('');
				},
				success: function (data) {
					let coincidencias = data.dataJson; // Supongamos que la respuesta contiene una lista de coincidencias
					// Limpiar el contenido del div de coincidencias
					coincidenciasDiv.empty();
					$('#id_contact').val(0);
					$('#receiver').val('');
					if (phoneNumber.length == 10) {
						coincidenciasDiv.hide();
						return;
					}
					// Mostrar el div de coincidencias si hay coincidencias
					if (phoneNumber.length > 0 && coincidencias.length > 0) {
						coincidenciasDiv.show();
						let coincidenciasArray = Object.values(coincidencias);

						// Agregar cada coincidencia como un elemento <p> al div
						coincidenciasArray.forEach(function (coincidencia) {
							coincidenciasDiv.append(`<p class="coincidencia-item" tabindex="0" data-phone="${coincidencia.phone}" data-name="${coincidencia.contact_name}" data-idcontact="${coincidencia.id_contact}">${coincidencia.phone} - ${coincidencia.contact_name}</p>`);
						});

						let items = $(".coincidencia-item");
						let selectedIndex = -1;
						$(document).off("keydown").on("keydown", function (e) {
							if (items.length === 0) return;

							if (e.key === "ArrowDown") {
								e.preventDefault();
								selectedIndex = (selectedIndex + 1) % items.length;
							} else if (e.key === "ArrowUp") {
								e.preventDefault();
								selectedIndex = (selectedIndex - 1 + items.length) % items.length;
							} else if (e.key === "Enter" && selectedIndex !== -1) {
								let selected = items.eq(selectedIndex);
								let name = selected.data('name');
								let phoneNumber = selected.data('phone');
								let id_contact = selected.data('idcontact');
								seleccionarCoincidencia(name, phoneNumber, id_contact, 'Enter');
								coincidenciasDiv.hide();
							}

							items.removeClass("selected");
							if (selectedIndex !== -1) {
								items.eq(selectedIndex).addClass("selected");
							}
						});
						$("<style>")
							.prop("type", "text/css")
							.html(`
							.coincidencia-item {
								padding: 5px;
								cursor: pointer;
							}
							.coincidencia-item.selected {
								background-color: #D4EDDA;
							}
						`)
							.appendTo("head");

					} else {
						coincidenciasDiv.hide();
					}
				},
				error: function (xhr, status, error) {
					console.error(error); // Manejo de errores
				},
				complete: function () {
					$('#phone-loading').hide();
				}
			});
		}, doneTypingInterval); // Ejecuta después de que el usuario deja de escribir
	});

	$('#coincidencias').on('click', 'p', function () {
		let name = $(this).data('name');
		let phoneNumber = $(this).data('phone');
		let id_contact = $(this).data('idcontact');
		seleccionarCoincidencia(name, phoneNumber, id_contact, 'clic');
	});

	function seleccionarCoincidencia(name, phoneNumber, id_contact, mode) {
		$('#receiver').val(name);
		$('#phone').val(phoneNumber);
		$('#id_contact').val(id_contact);
		$('#coincidencias').hide();
		$('#tracking').focus();
		if ($('#action').val() === 'new') {
			speakText(name);
		}
	}

	$('#mfNumFolio').on('input', function () {
		let input = $(this).val();
		input = input.replace(/\D/g, '').slice(0, 5); // Elimina caracteres no numéricos y limita a 10 dígitos
		$(this).val(input);
	});

	// ----------------------------------------------------

	$('#btn-folio,#btn-folio-1').click(function () {
		loadModalFolio();
	});

	$('#mfModo').on('change', function () {
		let id_mode = $('#mfModo').val();
		if (id_mode == 1) {
			$('#mfNumFolio').val(0);
			$('#mfNumFolio').prop('disabled', true);
		} else {
			$('#mfNumFolio').val('');
			$('#mfNumFolio').prop('disabled', false);
			setTimeout(function () {
				$('#mfNumFolio').focus();
			}, 250);
		}
	});

	async function loadModalFolio() {
		let foliActual = await getFolio('current');
		$('#mfFolioActual').val(foliActual);
		$('#mfIdLocation').val(idLocationSelected.val());
		$('#mfModo').val(1);
		$('#mfNumFolio').val(0);
		$('#mfNumFolio').prop('disabled', true);
		$('#modal-folio-title').html('Control de Folios');
		$('#modal-folio').modal({ backdrop: 'static', keyboard: false }, 'show');
	}

	$(`#btn-save-folio`).click(function () {
		if ($('#mfNumFolio').val() == '') {
			swal("Atención!", "* Campos requeridos", "error");
			return;
		}

		let formData = new FormData();
		formData.append('id_location', idLocationSelected.val());
		formData.append('mfNumFolio', $('#mfNumFolio').val());
		formData.append('mfVoice', $('#mfVoice').val());
		formData.append('option', 'saveFolio');
		try {
			$.ajax({
				url: `${base_url}/${baseController}`,
				type: 'POST',
				data: formData,
				cache: false,
				contentType: false,
				processData: false,
				beforeSend: function () {
					showSwal('Guardando Folio', 'Espere por favor...');
					$('.swal-button-container').hide();
				}
			})
				.done(function (response) {
					swal.close();
					if (response.success == 'true') {
						swal(`${response.message}`, "", "success");
						$('.swal-button-container').hide();
						$('#modal-folio').modal('hide');
						setTimeout(function () {
							swal.close();
							window.location.reload();
						}, 1500);
					}
				}).fail(function (e) {
					console.log("Opps algo salio mal", e);
				});
		} catch (error) {
			console.error(error);
		}
	});

	//--------------
	$('#btn-template,#btn-template-1').click(function () {
		loadModalTemplate();
	});
	async function loadModalTemplate() {
		$('#mTTemplate').val(templateMsj);
		$('#mTIdLocation').val(idLocationSelected.val());
		$('#modal-template-title').html('Plantilla de Mensajes');
		$('#modal-template').modal({ backdrop: 'static', keyboard: false }, 'show');
		setTimeout(function () {
			$('#mTTemplate').focus();
		}, 600);
	}

	$('#btn-save-template').click(function () {
		if ($('#mTTemplate').val() == '') {
			swal("Atención!", "* Campos requeridos", "error");
			return;
		}

		let formData = new FormData();
		formData.append('idTemplateMsj', idTemplateMsj);
		formData.append('mTTemplate', $('#mTTemplate').val());
		formData.append('option', 'saveTemplate');
		try {
			$.ajax({
				url: `${base_url}/${baseController}`,
				type: 'POST',
				data: formData,
				cache: false,
				contentType: false,
				processData: false,
				beforeSend: function () {
					showSwal('Guardando plantilla', 'Espere por favor...');
					$('.swal-button-container').hide();
				}
			})
				.done(function (response) {
					swal.close();
					if (response.success == 'true') {
						swal(`${response.message}`, "", "success");
						$('.swal-button-container').hide();
						$('#modal-template').modal('hide');
						setTimeout(function () {
							swal.close();
							window.location.reload();
						}, 1500);
					}
				}).fail(function (e) {
					console.log("Opps algo salio mal", e);
				});
		} catch (error) {
			console.error(error);
		}
	});


	$('#mBListTelefonos').on('keypress', function (event) {
		var tecla = event.which;
		// Permitir solo números y comas (código ASCII: 44 para la coma y del 48 al 57 para los números)
		if ((tecla != 44 && tecla < 48) || (tecla > 57)) {
			event.preventDefault();
		}
	});

	$('#btn-bot').click(function () {
		$('#mBListTelefonos').val('');
		$('#modal-bot-title').html('Chatbot Envío de Mensajes 🤖');
		$('#mBEstatus').val(99);
		$('#mbIdCatParcel').val(99);
		$('#mBIdLocation').val(idLocationSelected.val());
		$('#modal-bot').modal({ backdrop: 'static', keyboard: false }, 'show');
		let msj = `${templateMsj}`;
		$('#mBMessage').val(msj);
		setTimeout(function () {
			$('#mBListTelefonos').focus();
		}, 600);
	});

	$('#btn-bot-command').click(function () {

		if ($('#mBListTelefonos').val() == '' || $('#mBEstatus').val() == '99') {
			swal("Atención!", "* Campos requeridos", "error");
			return;
		}

		let formData = new FormData();
		formData.append('id_location', idLocationSelected.val());
		formData.append('idContactType', 2);
		formData.append('idEstatus', $('#mBEstatus').val());
		formData.append('messagebot', $('#mBMessage').val());
		formData.append('phonelistbot', $('#mBListTelefonos').val());
		formData.append('mbIdCatParcel', $('#mbIdCatParcel').val());
		formData.append('option', 'bot');
		try {
			$.ajax({
				url: `${base_url}/${baseController}`,
				type: 'POST',
				data: formData,
				cache: false,
				contentType: false,
				processData: false,
				beforeSend: function () {
					showSwal('Creando BOT', 'Espere por favor...');
					$('.swal-button-container').hide();
				}
			})
				.done(function (response) {
					swal.close();
					$('#modal-bot').modal('hide');
					swal(`🤖`, `${response.message}`, "success");
					$('.swal-button-container').hide();
					setTimeout(function () {
						swal.close();
					}, 3500);
				});
		} catch (error) {
			console.log("Opps algo salio mal", error);

		}
	});

	$(`#tbl-packages tbody`).on(`click`, `#btn-details-p`, function () {
		let row = table.row($(this).closest('tr')).data();
		loadSmsDetail(row.id_package);
	});

	$(`#tbl-packages tbody`).on(`click`, `#btn-evidence`, function () {
		let row = table.row($(this).closest('tr')).data();
		loadEvidences(row.id_package);
	});

	async function loadEvidences(id_package) {
		let listEvidence = await getRecordsEvidence(id_package);
		createTableEvidence(listEvidence);
		$('#modal-evidence').modal({ backdrop: 'static', keyboard: false }, 'show');
	}

	async function getRecordsEvidence(id_package) {
		let list = [];
		let formData = new FormData();
		formData.append('id_package', id_package);
		formData.append('option', 'getRecordsEvidence');
		try {
			const response = await $.ajax({
				url: `${base_url}/${baseController}`,
				type: 'POST',
				data: formData,
				cache: false,
				contentType: false,
				processData: false,
				beforeSend: function () {
					showSwal('Cargando evidencia(s)', 'Espere por favor...');
					$('.swal-button-container').hide();
				},
				complete: function () {
					swal.close();
				}
			});
			if (response.success == 'true') {
				list = response;
			}
		} catch (error) {
			console.error(error);
		}
		return list;
	}

	function createTableEvidence(data) {
		$('#tbl-evidence').empty();
		let c = 1;
		let titleGuia = '';

		$.each(data.dataJson, function (index, item) {
			titleGuia = item.tracking;
			let item_path = item.path;

			let clean_path = item_path.replace(/^\.\.\//, '');
			let encoded_path = encodeURI(clean_path);
			let full_url = `${base_url}/${encoded_path}`;
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
		const txtFullARef = `<br><a href="${fileUrl}" target="_blank" data-toggle="tooltip" data-placement="top" title="Click para ver en pantalla completa">[Pantalla Completa `;

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

	async function loadSmsDetail(id_package) {
		let listSms = await getRecordsSms(id_package);
		createTableSmsSent(listSms);

		$('#modal-sms-report').modal({ backdrop: 'static', keyboard: false }, 'show');
	}

	async function getRecordsSms(id_package) {
		let list = [];
		let formData = new FormData();
		formData.append('id_package', id_package);
		formData.append('option', 'getRecordsSms');
		try {
			const response = await $.ajax({
				url: `${base_url}/${baseController}`,
				type: 'POST',
				data: formData,
				cache: false,
				contentType: false,
				processData: false,
				beforeSend: function () {
					showSwal('Cargando mensajes', 'Espere por favor...');
					$('.swal-button-container').hide();
				},
				complete: function () {
					swal.close();
				}
			});
			if (response.success == 'true') {
				list = response;
			}
		} catch (error) {
			console.error(error);
		}
		return list;
	}

	function createTableSmsSent(data) {
		$('#tbl-sms-sent').empty();
		let c = 1;
		let phoneTitle = '';
		$.each(data.dataJson, function (index, item) {
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


	$('#btn-sync,#btn-sync-1').click(async function () {
		$('#tbl-sync tbody').empty();  // Vacía el contenido del cuerpo de la tabla
		swal({
			title: "Verificación de Guías Liberadas",
			text: "¿De qué paquetería desea comprobar las guías liberadas?",
			content: createSelectSync(),
			icon: "info",
			buttons: {
				btnconfirm: {
					text: "Iniciar",
					value: "ok",
				}
			},
			dangerMode: false,
		})
			.then((value) => {
				let idParcel = $('#optionSelectSync').val();
				if (value === 'ok') {
					continueSync(idParcel);
				}
			});
	});

	async function continueSync(idParcel) {
		showSwal('Validando guías', 'Espere por favor...');
		$('.swal-button-container').hide();
		let result = await chekout(idParcel);

		// Iterar sobre el trackingList y agregar las filas correspondientes
		var trackingList = result.trackingList;
		let t = 0;
		for (var guia in trackingList) {
			if (trackingList.hasOwnProperty(guia)) {
				var data = trackingList[guia];
				if (data.status === 'Verificar') {
					t++;
					addRowToTable(data.guia, data.phone, data.receiver, data.folio, data.desc_status, data.parcel, data.scanTime);
				}
			}
		}
		if (t == 0) {
			swal("Éxito!", `Estás al día`, "success");
			$('.swal-button-container').hide();
			setTimeout(function () {
				swal.close();
			}, 3500);
			return;
		}

		swal.close();
		$('#form-modal-sync-package')[0].reset();
		$('#msyncp-id_location').val(idLocationSelected.val());

		$('#modal-sync-package-title').html('Verificación de Guías Liberadas');
		$('#modal-sync-package').modal({ backdrop: 'static', keyboard: false }, 'show');
	}

	function createSelectSync() {
		let selectDivSync = document.createElement('div');
		// Crear el select option
		let select = document.createElement('select');
		select.setAttribute('id', 'optionSelectSync');

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
		selectDivSync.appendChild(select);
		return selectDivSync;
	}

	function addRowToTable(guia, telefono, destinatario, folio, desc_action, parcel, timeh) {
		var table = document.getElementById("tbl-sync").getElementsByTagName('tbody')[0];
		var newRow = table.insertRow(table.rows.length);
		var cellParcel = newRow.insertCell(0);
		var cellGuia = newRow.insertCell(1);
		var cellTelefono = newRow.insertCell(2);
		var cellDestinatario = newRow.insertCell(3);
		var cellFolio = newRow.insertCell(4);
		var cellDescAtion = newRow.insertCell(5);
		var celltime = newRow.insertCell(6);
		cellParcel.innerHTML = parcel;
		cellGuia.innerHTML = guia;
		cellTelefono.innerHTML = telefono;
		cellDestinatario.innerHTML = destinatario;
		cellFolio.innerHTML = folio;
		cellDescAtion.innerHTML = desc_action;
		celltime.innerHTML = timeh;
	}

	async function chekout(idParcel) {
		let result = '';
		let formData = new FormData();
		formData.append('id_location', idLocationSelected.val());
		formData.append('option', 'chekout');
		formData.append('idParcel', idParcel);
		idParcel
		try {
			const response = await $.ajax({
				url: `${base_url}/${baseController}`,
				type: 'POST',
				data: formData,
				cache: false,
				contentType: false,
				processData: false
			});
			result = response;
		} catch (error) {
			console.error(error);
		}
		return result;
	}

	updateColors(uMarker);
	updatePaqueteria(uIdCatParcel);

	document.getElementById("id_marcador").addEventListener("change", function () {
		let selectedColor = this.value;
		updateColors(selectedColor);
	});

	document.getElementById("id_cat_parcel").addEventListener("change", function () {
		let selectedId = this.value;
		updatePaqueteria(selectedId);
	});

	$('#btn-ocurre,#btn-ocurre-1').click(function () {
		swal({
			title: "Crear Códigos de Barras",
			text: "¿Que Opción Deseas Generar?",
			content: createSelect(),
			icon: "info",
			buttons: {
				opcion1: {
					text: "Autoservicio",
					value: "opcion1",
				},
				opcion2: {
					text: "Ocurre",
					value: "opcion2",
				},
				opcion3: {
					text: "Anomalia",
					value: "opcion3",
				},
				opcion4: {
					text: "Manual",
					value: "opcion4",
				}
			},
			dangerMode: false,
		})
			.then((value) => {
				let idParcel = $('#optionSelect').val();
				switch (value) {
					case "opcion1":
						swal({
							title: "Selecciona Fecha del Autoservicio",
							content: createDatePicker(),  // Función para crear el calendario
							buttons: {
								confirm: {
									text: "Aceptar",
									value: "confirmar"
								}
							}
						}).then((dateValue) => {
							if (dateValue === 'confirmar') {
								let fechaAuto = $('#datepicker').val();
								createBarCode('auto', idParcel, fechaAuto);
							}
						});
						break;
					case "opcion2":
						createBarCode('ocurre', idParcel, '');
						break;
					case "opcion3":
						createBarCode('anomalia', idParcel, '');
						break;
					case "opcion4":
						const lblLocation = $("#option-location option:selected").text();
						const lblParcel = $('#optionSelect option:selected').text();
						swal({
							title: `Ingresa Código(s) Barra(s) \n ${lblLocation} - ${lblParcel}`,
							content: createTextArea(),  // Función para crear el calendario
							buttons: {
								confirm: {
									text: "Generar",
									value: "generar"
								}
							}
						}).then((dateValue) => {
							if (dateValue === 'generar' && $('#txtBarcode').val() != "") {
								let txtBarcode = $('#txtBarcode').val();
								createBarCode('manual', idParcel, '', txtBarcode);
							}
						});
						break;
				}
			});
	});

	function createSelect() {
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

	function createDatePicker() {
		let calendarDiv = document.createElement('div');

		// Crear el input para el datepicker
		let input = document.createElement('input');
		input.setAttribute('id', 'datepicker');
		input.setAttribute('readonly', true); // Evitar edición manual
		calendarDiv.appendChild(input);

		// Inicializar el datepicker de jQuery UI
		setTimeout(function () {
			$('#datepicker').datetimepicker({
				dateFormat: 'yy-mm-dd',  // Formato de la fecha
				timeFormat: 'HH:mm', // Formato de la hora
				controlType: 'select',  // Selectores para hora y minutos
				oneLine: true,          // Mostrar en una sola línea
				defaultDate: null,      // Establecer nulo para evitar conflicto
				hour: 7,                // Hora predeterminada
				minute: 0,              // Minuto predeterminado
				second: 0               // Segundo predeterminado
			});
			const defaultDateTime = new Date();
			defaultDateTime.setHours(7, 0, 0); // Configurar la hora: 07:00:00
			$('#datepicker').datetimepicker('setDate', defaultDateTime);  // Establecer la fecha actual por defecto
		}, 100);
		return calendarDiv;
	}

	function createTextArea() {
		let textAreaDiv = document.createElement('div');

		let texta = document.createElement('textarea');
		texta.id = 'txtBarcode';
		texta.style.width = '200px';  // Ancho del textarea
		texta.style.height = '300px'; // Alto del textarea
		textAreaDiv.appendChild(texta);
		setTimeout(function () {
			texta.focus();
		}, 100);

		return textAreaDiv;
	}

	function createBarCode(mode, idParcel, fechaAuto, textAreatracking = null) {
		let formData = new FormData();
		formData.append('id_location', idLocationSelected.val());
		formData.append('type_mode', mode);
		formData.append('option', 'createBarcode');
		formData.append('idParcel', idParcel);
		formData.append('fechaAuto', fechaAuto);
		formData.append('textAreatracking', textAreatracking);
		try {
			$.ajax({
				url: `${base_url}/${baseController}`,
				type: 'POST',
				data: formData,
				cache: false,
				contentType: false,
				processData: false,
				beforeSend: function () {
					showSwal('Creando códigos', 'Espere por favor...');
					$('.swal-button-container').hide();
				}
			})
				.done(function (response) {
					swal.close();
					if (response.success == 'true') {
						// Crear un enlace temporal
						let link = document.createElement('a');
						link.href = `${base_url}/controllers/${response.zip}`,
							link.download = response.zip; // Nombre del archivo ZIP
						document.body.appendChild(link);
						// Simular el clic en el enlace para iniciar la descarga
						link.click();
						// Eliminar el enlace temporal del DOM
						document.body.removeChild(link);
						swal("Éxito!", `Descarga finalizada`, "success");
						$('.swal-button-container').hide();
						setTimeout(function () {
							swal.close();
							let formData = new FormData();
							formData.append('zipFile', `${response.zip}`);
							formData.append('option', 'deleteZip');
							$.ajax({
								url: `${base_url}/${baseController}`,
								type: 'POST',
								data: formData,
								cache: false,
								contentType: false,
								processData: false,
							})
						}, 2500);
					} else {
						//console.error('Error al generar el archivo ZIP:', response.message);
						swal('Atención', response.message, "warning");
					}
				}).fail(function (e) {
					console.log("Opps algo salio mal", e);
				});
		} catch (error) {
			console.error(error);
		}
	}

	$("#confirmg").click(function (e) {
		//let rowsConfirm     = '';
		let rows_selected = table.column(0).checkboxes.selected();
		let tRows = 0;
		let isValid = true;
		let noValidTracking = [];
		let phoneUser = [];
		let userName = [];
		let folios = [];

		$.each(rows_selected, function (index, rowId) {
			tRows++;
			// Obtener el índice de la fila
			let rowIndex = table.row('#row_id_' + rowId).index();
			// Obtener el valor de la columna 7 para la fila actual usando el índice
			let rowData = table.row(rowIndex).data(); // Obtener los datos de la fila
			let status = rowData.id_status; // Obtener el valor de la columna 7
			// Verificar si el estatus es 2 o 7
			if (status !== '2' && status !== '7') {
				isValid = false; // Marcar como inválido si no cumple con el criterio 
				noValidTracking.push(rowData.tracking); // Agregar el tracking no válido al array
			}
			phoneUser.push(rowData.phone);
			userName.push(rowData.receiver);
			folios.push(rowData.folio);
		});

		if (tRows === 0) {
			swal("Error al confirmar!", "Debes seleccionar las guías para confirmar", "error");
			return false;
		}

		if (!isValid) {
			let noValidTrackingList = noValidTracking.join(',');
			swal("Error al confirmar!", "Solo se permite confirmar paquetes con estatus:\nMensaje Enviado\nContactado\n\nGuías no válidas para confirmar:\n" + noValidTrackingList, "error");
			return false;
		}

		// same phone 7341287415
		if (!allPhonesEqual(phoneUser)) {
			swal("Error al confirmar!", "Todos los paquetes deben tener el mismo número de teléfono para confirmar", "error");
			return false;
		}

		let rowsConfirm = rows_selected.join(",");
		let tpaquetes = tRows;
		let tphone = phoneUser[0];
		let tname = userName[0];
		let tids = rowsConfirm;
		// Ordenar el arreglo de forma ascendente
		folios.sort(function (a, b) {
			return a - b;
		});
		let lsFolios = folios.join(',');
		swal({
			title: `Confirmar Paquetes 👍`,
			text: `Total:${tpaquetes} Paquetes\nTélefono:${tphone}\nDesinatario:${tname}\nFolios:${lsFolios}\n\nEstá seguro ?`,
			icon: "info",
			buttons: true,
			dangerMode: false,
		})
			.then((weContinue) => {
				if (weContinue) {

					let formData = new FormData();
					formData.append('id_location', idLocationSelected.val());
					formData.append('idsx', tids);
					formData.append('option', 'pullConfirm');
					try {
						$.ajax({
							url: `${base_url}/${baseController}`,
							type: 'POST',
							data: formData,
							cache: false,
							contentType: false,
							processData: false,
							beforeSend: function () {
								showSwal('Confirmando guías', 'Espere por favor...');
								$('.swal-button-container').hide();
							}
						})
							.done(function (response) {
								swal.close();
								if (response.success === 'true') {
									swal('Éxito', response.message, "success");
									setTimeout(function () {
										swal.close();
										window.location.reload();
									}, 3500);
								} else {
									swal('Atención', response.message, "warning");
								}
								$('.swal-button-container').hide();
							});
					} catch (error) {
						console.log("Opps algo salio mal", error);
					}
				} else {
					return false;
				}
			});

		e.preventDefault();
	});

	function allPhonesEqual(phoneArray) {
		if (phoneArray.length === 0) {
			return true; // Si el array está vacío, consideramos que todos son iguales (o podrías manejar esto como un caso especial)
		}
		let firstPhone = phoneArray[0]; // Obtener el primer número de teléfono
		// Comparar cada número de teléfono con el primero
		return phoneArray.every(phone => phone === firstPhone);
	}

	$("#releaseg").click(function (e) {
		let rows_selected = table.column(0).checkboxes.selected();
		let tRows = 0;
		let isValid = true;
		let noValidTracking = [];
		let phoneUser = [];
		let userName = [];
		let folios = [];
		let arrayStatus = [];

		$.each(rows_selected, function (index, rowId) {
			tRows++;
			let rowIndex = table.row('#row_id_' + rowId).index();
			let rowData = table.row(rowIndex).data(); // Obtener los datos de la fila
			let status = rowData.id_status;
			// Verificar si el estatus es 2 o 7
			if (status !== '2' && status !== '5' && status !== '7') {
				isValid = false; // Marcar como inválido si no cumple con el criterio 
				noValidTracking.push(rowData.tracking); // Agregar el tracking no válido al array
			}
			phoneUser.push(rowData.phone);
			userName.push(rowData.receiver);
			folios.push(rowData.folio);
			arrayStatus.push(rowData.id_status);
		});

		if (tRows === 0) {
			swal("Error al liberar!", "Debes seleccionar las guías para liberar", "error");
			return false;
		}

		if (!isValid) {
			let noValidTrackingList = noValidTracking.join(',');
			swal("Error al liberar!", "Solo se permite liberar paquetes con estatus:\nMensaje Enviado\nContactado\nConfirmado\n\nGuías no válidas para liberar:\n" + noValidTrackingList, "error");
			return false;
		}

		// same phone 7341287415
		if (!allPhonesEqual(phoneUser)) {
			swal("Error al liberar!", "Todos los paquetes deben tener el mismo número de teléfono para liberar", "error");
			return false;
		}

		let rowsRelease = rows_selected.join(",");
		let tpaquetes = tRows;
		let tphone = phoneUser[0];
		let tname = userName[0];
		let tids = rowsRelease;
		// Ordenar el arreglo de forma ascendente
		folios.sort(function (a, b) {
			return a - b;
		});
		let lsFolios = folios.join(',');
		swal({
			title: `Liberar Paquetes 📦`,
			text: `Total:${tpaquetes} Paquetes\nTélefono:${tphone}\nDesinatario:${tname}\nFolios:${lsFolios}\n\nEstá seguro ?`,
			icon: "info",
			buttons: true,
			dangerMode: false,
		})
			.then((weContinue) => {
				if (weContinue) {
					if (arrayStatus.includes('5')) {
						tkEvi(tids, tphone);
					} else {
						releasePullPhoto(tids, '');
					}
				} else {
					return false;
				}
			});
		e.preventDefault();
	});

	$('#modal-evidence-camera').on('hidden.bs.modal', function () {
		$('#video-camera').off('click');
		$('#btn-save-camera').off('click');
		$('#btn-stop-camera').off('click');
	});

	function openEvidenceModal(config) {
		$('#modal-evidence-title').html(config.title);
		$('#modal-evidence-camera').modal({
			backdrop: 'static',
			keyboard: false
		});

		const videoContainer = document.getElementById('video-container-camera');
		const video = document.getElementById('video-camera');
		const canvas = document.getElementById('canvas-camera');

		//const btnSave = document.getElementById('btn-save-camera');
		//const btnStop = document.getElementById('btn-stop-camera');
		let stream = null;
		let capturedImageData = null;
		$('#btn-save-camera').hide();
		const highResWidth = largo;
		const highResHeight = alto;
		canvas.width = highResWidth;
		canvas.height = highResHeight;

		video.width = highResWidth;
		video.height = highResHeight;

		const context = canvas.getContext('2d');
		context.clearRect(0, 0, canvas.width, canvas.height);

		// estilos
		videoContainer.style.width = "50%";
		videoContainer.style.height = "50%";
		videoContainer.style.maxWidth = "320px";
		videoContainer.style.maxHeight = "320px";
		videoContainer.style.border = "2px solid green";
		videoContainer.style.display = "flex";
		videoContainer.style.alignItems = "center";
		videoContainer.style.justifyContent = "center";
		videoContainer.style.margin = "0 auto";

		video.style.width = "100%";
		video.style.height = "100%";
		video.style.objectFit = "cover";

		navigator.mediaDevices.enumerateDevices()
			.then((devices) => {
				const videoDevices = devices.filter(device => device.kind === 'videoinput');
				const rearCamera = videoDevices.find(device =>
					device.label.toLowerCase().includes('back') ||
					device.label.toLowerCase().includes('rear')
				);
				return navigator.mediaDevices.getUserMedia({
					video: {
						deviceId: rearCamera
							? rearCamera.deviceId
							: videoDevices[0].deviceId
					}
				});

			})
			.then((mediaStream) => {
				stream = mediaStream;
				video.srcObject = stream;
			})
			.catch((err) => {
				console.error(err);
			});

		// IMPORTANTE:
		// remover listeners anteriores
		$('#video-camera').off('click');
		$('#btn-save-camera').off('click');
		$('#btn-stop-camera').off('click');

		// capturar
		$('#video-camera').on('click', function () {
			$('audio#sound-snap')[0].play();
			$('#btn-save-camera').show();
			context.drawImage(video, 0, 0, video.videoWidth, video.videoHeight, 0, 0, canvas.width, canvas.height);

			capturedImageData = canvas.toDataURL('image/png');
		});

		// cerrar
		$('#btn-stop-camera').on('click', function () {
			if (stream) {
				stream.getTracks().forEach(track => track.stop());
				video.srcObject = null;
				videoContainer.style.border = "none";
			}
		});

		// guardar
		$('#btn-save-camera').on('click', function () {
			if (stream) {
				stream.getTracks().forEach(track => track.stop());
				video.srcObject = null;
				videoContainer.style.border = "none";
			}
			if (capturedImageData) {
				$('#modal-evidence-camera').modal('hide');
				config.onSave(capturedImageData);
			}
		});
	}

	function tkEvi(tids, tphone) {
		openEvidenceModal({
			title: `Evidencia de Entrega ${tphone}`,
			onSave: function (image) {
				releasePullPhoto(tids, image);
			}
		});
	}

	function releasePullPhoto(tids, imgEvidence) {
		let formData = new FormData();
		formData.append('id_location', idLocationSelected.val());
		formData.append('idsx', tids);
		formData.append('imgEvidence', imgEvidence);
		formData.append('option', 'pullRealise');
		formData.append('desc_mov', 'Liberación de Paquete por Selección');
		try {
			$.ajax({
				url: `${base_url}/${baseController}`,
				type: 'POST',
				data: formData,
				cache: false,
				contentType: false,
				processData: false,
				beforeSend: function () {
					showSwal('Liberación de Paquete por Selección', 'Espere por favor...');
					$('.swal-button-container').hide();
				}
			})
				.done(function (response) {
					swal.close();
					if (response.success === 'true') {
						swal('Éxito', response.message, "success");
						setTimeout(function () {
							swal.close();
							window.location.reload();
						}, 3500);
					} else {
						swal('Atención', response.message, "warning");
					}
					$('.swal-button-container').hide();
				});
		} catch (error) {
			console.log("Opps algo salio mal", error);
		}
	}

	$('#tbl-packages').on('click', 'td:nth-child(3)', function () {
		var phoneNumber = $(this).text().trim();
		var searchInput = $('input[type="search"]');
		searchInput.val(phoneNumber).trigger('input').trigger('keyup');
	});

});

function updateColors(selectedColor) {
	let select = document.getElementById("id_marcador");

	// Establecer el color seleccionado como seleccionado en el select
	for (let i = 0; i < select.options.length; i++) {
		if (select.options[i].value === selectedColor) {
			select.selectedIndex = i;
			break;
		}
	}

	// Actualizar los colores de las opciones
	for (let i = 0; i < select.options.length; i++) {
		let option = select.options[i];
		option.style.backgroundColor = option.value;
		option.style.color = option.value === selectedColor ? 'black' : 'white';
	}

	// Establecer el color de fondo del select
	select.style.backgroundColor = selectedColor;
	select.style.color = 'white'; // Cambiar el color del texto para que sea visible
}

function updatePaqueteria(selectedId) {
	let select = document.getElementById("id_cat_parcel");
	for (let i = 0; i < select.options.length; i++) {
		if (select.options[i].value === selectedId) {
			select.selectedIndex = i;
			break;
		}
	}
}