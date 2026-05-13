$(document).ready(function() {
	let baseController = 'controllers/waba.php';
    let idLocationSelected = $('#option-location');

    let table = $('#tbl-msj-whats').DataTable({
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
			{title: `Teléfono`,               name:`sender_phone`,       data:`sender_phone`},      //0
			{title: `Destinatario`,        name:`contact_name`,    data:`contact_name`},   //1
			{title: `Último mensaje`,       name:`last_message`,      data:`last_message`},     //2
			{title: `Fecha mensaje`,   name:`last_date`,   data:`last_date`},  //3
            {title: `Opc.`,   name:`opc`,   data:`opc`},  //3
		],
        "columnDefs": [
			// { "width": "40%", "targets": [1,2] }
		],
        'order': [[3, 'asc']]
	});

    let currentPhone = null;
    let indexes = table.rows({ search: 'applied', order: 'applied' }).indexes().toArray();
    let chatInterval = null; // 👉 Variable global para controlar el intervalo

	//funcion para borrar campo de busqueda
	let clearButton = $(`<span id="clear-search" style="cursor: pointer;">&nbsp;<i class="fa fa-eraser fa-lg" aria-hidden="true"></i></span>`);
	clearButton.click(function() {
		$("#tbl-msj-whats_filter input[type='search']").val("");
		setTimeout(function() {
			$("#tbl-msj-whats_filter input[type='search']").trigger('mouseup').focus();
		}, 100);
	});
	$("#tbl-msj-whats_filter label").append(clearButton);

       /*function loadInfoTracking(tophone) {
        //console.log(tophone);
       }*/

    // 👉 Función que carga los mensajes
    function cargarMensajes(tophone, phoneWaba) {
        console.log('tophone',tophone);
        let id_location = idLocationSelected.val();
        // console.log(tophone, phoneWaba);
        $.ajax({
            url: `${base_url}/${baseController}`,
            type: 'POST',
            data: { 
                phone: tophone, 
                phoneWaba: phoneWaba,
                id_location:id_location,
                option: 'getAllMessagesToRead' 
            },
            dataType: 'json',
            /*beforeSend: function() {
                // showSwal('Cargando mensajes', 'Espere por favor...');
                // $('.swal-button-container').hide();
            },*/
            success: function(mensajes) {
                let html = '';
                if (mensajes.length > 0) {
                    mensajes.reverse().forEach(msg => {
                        //console.log(msg);
                        const myNumber = phoneWaba;
                        let tipo = (msg.sender_phone === myNumber) ? 'sent' : 'received';

                        let who_sent = (msg.message_type === 'outgoing') ? `(${msg.who_sent === 'bot' ? '🤖' : msg.who_sent}) `: "";
                        let fechaHora = new Date(msg.datelog.replace(' ', 'T')).toLocaleString([], {
                            weekday: 'short',
                            day: '2-digit',
                            month: 'short',
                            hour: '2-digit',
                            minute: '2-digit'
                        });

                        // 👇 Aquí transformamos el texto en HTML (imagen, audio, doc o texto plano)
                        let content = renderMessageContent(msg.message_text);

                        html += `<div class="chat-bubble ${tipo}">
                                    ${content}
                                    <span class="time">${who_sent}${fechaHora}</span>
                                </div>`;
                    })
                } else {
                    html = "<p style='text-align:center;color:#777;'>No hay mensajes.</p>";
                }

                $('#chat-container').html(html);
               /*setTimeout(() => {
                    $('#chat-container').scrollTop($('#chat-container')[0].scrollHeight);
                    $('#chat-input').focus();
                }, 300);*/
            },
            error: function(xhr, status, error) {
                console.error("Error al cargar mensajes:", error);
            }
        });
    }

    function renderMessageContent(messageText) {
        // Detectar si contiene el tag de guardado
        if (messageText.startsWith("[IMAGE SAVED]")) {
            let filePath = messageText.replace("[IMAGE SAVED]", "").trim();
            //let fileName = filePath.split("/").pop();
            return `
                <a href="${base_url}/${filePath}" target="_blank">
                    <img src="${base_url}/${filePath}" 
                        class="chat-media" 
                        style="max-width:200px; border-radius:8px; cursor:pointer;" />
                </a>`;
        }

        if (messageText.startsWith("[AUDIO SAVED]")) {
            let filePath = messageText.replace("[AUDIO SAVED]", "").trim();
            return `<audio controls style="max-width:250px;">
                        <source src="${base_url}/${filePath}" type="audio/ogg">
                        Tu navegador no soporta audio.
                    </audio>`;
        }

        if (messageText.startsWith("[DOCUMENT SAVED]")) {
            let filePath = messageText.replace("[DOCUMENT SAVED]", "").trim();
            let fileName = filePath.split("/").pop();
            return `<a href="${base_url}/${filePath}" download="${fileName}" class="chat-doc">
                        📄 ${fileName}
                    </a>`;
        }

        if (messageText.startsWith("[REACTION]")) {
            return `<span class="chat-reaction">${messageText}</span>`;
        }

        // Si no es multimedia, devolver como texto normal
        return messageText;
    }


    $('#modal-chat-w').on('shown.bs.modal', function () {
        /*let chat = $('#chat-container');
        chat.scrollTop(chat[0].scrollHeight);
        $('#chat-input').focus();*/
        scrollTopChat();
    });

function scrollTopChat() {
    setTimeout(() => {
        $('#chat-container').scrollTop($('#chat-container')[0].scrollHeight);
    }, 500);
}

// Abrir modal de chat
$(`#tbl-msj-whats tbody`).on('click', '#btn-read-w', function () {
    let row = table.row($(this).closest('tr'));
    let data = row.data();

    currentRowIndex = indexes.indexOf(row.index());
    currentPhone = data.sender_phone; // ✅ AQUÍ
    // $("#tophone").val(currentPhone);
    let phoneWaba = $("#phone_waba").val();
    cargarMensajes(currentPhone, phoneWaba);
    // loadInfoTracking(currentPhone);
    $('#modal-chat-w-title').html(`${data.sender_phone} - ${data.contact_name}`);
    $('#modal-chat-w').modal({ backdrop: 'static', keyboard: false });

    if (chatInterval) clearInterval(chatInterval);
    chatInterval = setInterval(function () {
        cargarMensajes(currentPhone, phoneWaba); // ✅ SIEMPRE el actual
    }, 10000);
});

$('#btn-prev-chat').on('click', function () {
    let rows = table.rows({ search: 'applied', order: 'applied' });
    if (currentRowIndex > 0) {
        currentRowIndex--;
        let rowIndex = rows.indexes()[currentRowIndex];
        let data = table.row(rowIndex).data();
        currentPhone = data.sender_phone;
        //$("#tophone").val(currentPhone);

        cargarMensajes(currentPhone, $("#phone_waba").val());
        // loadInfoTracking(currentPhone);
        $('#modal-chat-w-title').html(`${currentPhone} - ${data.contact_name}`);
        scrollTopChat();
    }
});

$('#btn-next-chat').on('click', function () {
    let rows = table.rows({ search: 'applied', order: 'applied' });
    if (currentRowIndex < rows.count() - 1) {
        currentRowIndex++;
        let rowIndex = rows.indexes()[currentRowIndex];
        let data = table.row(rowIndex).data();
        currentPhone = data.sender_phone;
        // $("#tophone").val(currentPhone);
        cargarMensajes(currentPhone, $("#phone_waba").val());
        // loadInfoTracking(currentPhone);
        $('#modal-chat-w-title').html(`${currentPhone} - ${data.contact_name}`);
        scrollTopChat();
    }
});


// Cerrar modal
$('#btn-close-chatw, #btn-close-chatw-1').click(function(){
    if (chatInterval) {
        clearInterval(chatInterval); // 👉 Detener recarga automática
        chatInterval = null;
    }
    //TODO:window.location.reload();
});

// Enviar mensaje
$('#btn-send').on('click', function() {
    sendWhats();
});

function sendWhats(){
    let id_location = idLocationSelected.val();
    let tophone = currentPhone;
    let tokenWaba = $("#tokenWaba").val();
    let phoneWaba = $("#phone_waba").val();
    let phoneNumberId = $("#phone_number_id").val();
    let mensaje = $("#chat-input").val();

    if(mensaje.trim() !== '') {
        $.ajax({
            url: `${base_url}/${baseController}`,
            type: 'POST',
            data: { 
                id_location:id_location,
                tophone: tophone, 
                tokenWaba: tokenWaba,
                phoneWaba: phoneWaba,
                msj: mensaje,
                phoneNumberId: phoneNumberId,
                option: 'sendMessage' 
            },
            success: function(response) {
                // console.log(response);
                $("#chat-input").val('');
                cargarMensajes(tophone, phoneWaba);
            },
            error: function(xhr, status, error) {
                console.error("Error al enviar mensaje:", error);
            }
        });
    }
}

// Enviar con Enter
$('#chat-input').on('keydown', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault(); 
        sendWhats();
    }
});

$("#mBListTelefonos").on("input", function () {
const texto = $(this).val().trim();
    showDetailNumbers(texto);
});

function showDetailNumbers(texto){
    // Si no hay salto de línea, no mostrar nada
    if (!texto.includes("\n")) {
        $("#infp").html("");
        return;
    }
    const numeros = texto.split("\n").filter(n => n.trim() !== "");
    const total = numeros.length;
    if (total < 2) {
        $("#infp").html("");
        return;
    }
    const primerNumero = numeros[0];
    const ultimoNumero  = numeros[total - 1];

    $("#infp").html(
        `Total:${total}, I:${primerNumero} : F:${ultimoNumero}`
    );
}

//---------------------------------------
    $('#new-template').click(function(){
		loadAdminTemplate();
	});
    async function loadAdminTemplate() {
		//$('#mTIdLocation').val(idLocationSelected.val());
		$('#modal-admin-template-title').html('Nueva Plantilla Meta');
		$('#modal-admin-template').modal({backdrop: 'static', keyboard: false}, 'show');
        $('#mAdtName').val('');
        $('#mAdtParcel').val('99');
        $('#mAdtPlantilla').val('');
		setTimeout(function(){
			$('#mAdtName').focus();
		}, 600);
	}

    $('#btn-guardar-plantilla').click(function(){
        let mAdtName = $('#mAdtName').val();
        let mAdtParcel = $('#mAdtParcel').val();
        let mAdtPlantilla = $('#mAdtPlantilla').val();
		if (mAdtName === '' || mAdtPlantilla === '') {
            swal("Atención!", "* Campos requeridos", "error");
			return;
        }
        console.log('continue');
        let formData = new FormData();
		formData.append('mAdtName', mAdtName);
        formData.append('mAdtParcel', mAdtParcel);
		formData.append('mAdtPlantilla', mAdtPlantilla);
		formData.append('option', 'saveTempMeta');
		try {
			$.ajax({
				url        : `${base_url}/${baseController}`,
				type       : 'POST',
				data       : formData,
				cache      : false,
				contentType: false,
				processData: false,
				beforeSend : function() {
					showSwal('Guardando plantilla','Espere por favor...');
					$('.swal-button-container').hide();
				}
			})
			.done(function(response) {
				swal.close();
				$('#modal-admin-template').modal('hide');
				swal(`Éxito`,`${response.message}`, "success");
				$('.swal-button-container').hide();
				setTimeout(function(){
					swal.close();
				}, 3500);
			});
		} catch (error) {
			console.log("Opps algo salio mal",error);
		}
	});

$('#mTselectTemplate').prop('disabled', true);
$('#mBEstatus').prop('disabled', true);

// 1️⃣ Al seleccionar paquetería
$('#mbIdCatParcel').on('change', function () {
    let parcel = $(this).val();

    // Resetear dependientes
    $('#mTselectTemplate').val('99').prop('disabled', true);
    $('#mBEstatus').val('99').prop('disabled', true);
    $('#mBListTelefonos').val('');

    // Ocultar todas primero
    $('#mTselectTemplate option').hide();
    $('#mTselectTemplate option[value="99"]').show(); // "Selecciona"

    // 👉 Caso: Selecciona (0)
    if (parcel === '0') {
        return;
    }

    // 👉 Caso: TODAS
    if (parcel === '99') {
        $('#mTselectTemplate option').show();
        $('#mTselectTemplate').prop('disabled', false);
        return;
    }

    // 👉 Caso: Paquetería específica
    $('#mTselectTemplate option').each(function () {
        if ($(this).data('parcel') == parcel) {
            $(this).show();
        }
    });

    $('#mTselectTemplate').prop('disabled', false);
});

// 2️⃣ Al seleccionar plantilla
$('#mTselectTemplate').on('change', function () {
    let template = $(this).val();

    $('#mBEstatus').val('99').prop('disabled', true);
    $('#mBListTelefonos').val('');

    if (template !== '99') {
        $('#mBEstatus').prop('disabled', false);
    }
});

$('#mTMode').on('change', function () {
    let mode = $(this).val();
$('#mBListTelefonos').val('');
    if (mode === '2') { // Automático
        $('#mBListTelefonos')
            .prop('disabled', true)
            .val(''); // opcional: limpiar
    } else { // Manual
        $('#mBListTelefonos')
            .prop('disabled', false)
            .focus();
    }
});


$('#mBEstatus').on('change', function () {
    let mTMode           = $('#mTMode').val();
    let mbIdCatParcel    = $('#mbIdCatParcel').val();
    let mBEstatus        = $('#mBEstatus').val();
    
    if(mTMode === '2') { // Automático
        console.log(mTMode,mbIdCatParcel,mBEstatus);
        let formData =  new FormData();
        formData.append('id_location', idLocationSelected.val());
        formData.append('mbIdCatParcel', mbIdCatParcel);
        formData.append('mBEstatus', mBEstatus);
        formData.append('option', 'loadPhonesAuto');
        try {
            $.ajax({
                url        : `${base_url}/${baseController}`,
                type       : 'POST',
                data       : formData,
                cache      : false,
                contentType: false,
                processData: false,
                beforeSend : function() {
                   showSwal('Filtrando números','Espere por favor...');
                    $('.swal-button-container').hide();
                }
            })
            .done(function(response) {
                swal.close();
                $('#mBListTelefonos').val('');
                if (response.success === true) {
                    // Extraer solo los teléfonos
                    let phones = response.data.map(item => item.phone);
                    // Unir con salto de línea
                    let texto = phones.join("\n");
                    // Asignar al textarea
                    $('#mBListTelefonos').val(texto);
                    showDetailNumbers(texto);
                } else {
                    swal(`Sin números para enviar`, "", "info");
                    showDetailNumbers('');
                }
            })
            .fail(function(e) {
                console.log("Error algo salió mal", e);
            });
        } catch (error) {
            console.error(error);
        }
    }
});

//---------------------------------------


//--------------
let originalTemplate = ""; // variable global

	$('#waba-template').click(function(){
		loadModalTemplate();
	});
	async function loadModalTemplate() {
		$('#mTIdLocation').val(idLocationSelected.val());
		$('#modal-template-title').html('Envío de mensajes meta');
		$('#modal-template').modal({backdrop: 'static', keyboard: false}, 'show');
        $('#titleParams').hide();
        $('#camposDinamicos').hide();
        $('#div-prev').hide();
        $('#mTselectTemplate').val('99');
        $('#mBEstatus').val('99');
        $('#mBListTelefonos').val('');
        $("#infp").html("");
		setTimeout(function(){
			$('#mTTemplate').focus();
		}, 600);
	}
	$('#mTselectTemplate').on('change', function() {
		let idTemplate = $('#mTselectTemplate').val();
        if(idTemplate!='99'){
            loadTemplate(idTemplate);
        }else{
            $('#titleParams').hide();
            $('#camposDinamicos').hide();
            $('#div-prev').hide();
        }
	});
    async function loadTemplate(idTemplate) {
        let txtTemplatePrev = await getTemplateMeta(idTemplate);
        originalTemplate = txtTemplatePrev; // <- guardar plantilla original
        $("#preview-template").text(txtTemplatePrev);
        const matches = txtTemplatePrev.match(/{{(\d+)}}/g) || [];
        const uniqueFields = [...new Set(matches)];
        $('#camposDinamicos').empty().addClass('row');
        uniqueFields.forEach(field => {
            const num = field.replace(/[{}]/g, "");

            const inputHtml = `
                <div class="col-md-2 mb-2">
                    <div class="form-group">
                        <input type="text" class="form-control dynamic-field" data-field="${num}" id="field${num}" placeholder="{{${num}}}">
                    </div>
                </div>`;
            $('#camposDinamicos').append(inputHtml);
        });
        $('#div-prev').show();
        $('#titleParams').show();
        $('#camposDinamicos').show();

        // activar listeners para actualizar preview
        activateListeners();
    }
    function activateListeners() {
        $('.dynamic-field').on('input', function() {
            updatePreview();
        });
    }
    function updatePreview() {
        let preview = originalTemplate;
        $('.dynamic-field').each(function() {
            let num = $(this).data('field');
            let val = $(this).val();
            // regex robusto (tolera espacios)
            let regex = new RegExp(`\\{\\{\\s*${num}\\s*\\}\\}`, "g");
            preview = preview.replace(regex, val !== "" ? val : `{{${num}}}`);
        });
        $("#preview-template").html(
            preview.replace(/\r?\n/g, "<br>")
        );
    }
   // ---------------------------
// DRAG & DROP ROBUSTO
// ---------------------------
// Inicio de arrastre: aceptamos cualquier elemento draggable que tenga el atributo "valor"
document.addEventListener("dragstart", function(e) {
    // buscar el badge arrastrado (por si el click fue en el icono <i> o texto)
    const badge = e.target.closest && e.target.closest('[draggable="true"][valor]');
    if (badge) {
        const val = badge.getAttribute('valor') || badge.dataset.valor || '';
        // setear el valor para el drop
        e.dataTransfer.setData("text/plain", val);
        // opcional: tipo
        e.dataTransfer.effectAllowed = "copy";
        // debug
        // console.log('dragstart ->', val);
    }
});

// Permitir soltar si lo que se detecta está dentro de un input .dynamic-field
document.addEventListener("dragover", function(e) {
    const input = e.target.closest && e.target.closest('.dynamic-field');
    if (input) {
        e.preventDefault(); // necesario para permitir drop
        e.dataTransfer.dropEffect = "copy";
        input.classList.add("dragover");
    }
});
// quitar estilo cuando se sale del área
document.addEventListener("dragleave", function(e) {
    const input = e.target.closest && e.target.closest('.dynamic-field');
    if (input) {
        input.classList.remove("dragover");
    }
});
// Soltar: buscamos el input más cercano (por si cae en el label o el contenedor)
document.addEventListener("drop", function(e) {
    const input = e.target.closest && e.target.closest('.dynamic-field');
    if (input) {
        e.preventDefault();
        input.classList.remove("dragover");
        const valor = e.dataTransfer.getData("text/plain");
        // debug
        // console.log('drop on ->', input.id, 'valor ->', valor);
        // insertar el valor
        input.value = valor;

        // disparar evento input para que tu listener lo detecte y actualice preview
        input.dispatchEvent(new Event('input', { bubbles: true }));
    }
});

    async function getTemplateMeta(idTemplate) {
        let txtTemplate = 0;
        let formData = new FormData();
        formData.append('idTemplate', idTemplate);
        formData.append('option', 'getTemplateMeta');
        try {
            const response = await $.ajax({
                url: `${base_url}/${baseController}`,
                type: 'POST',
                data: formData,
                cache: false,
                contentType: false,
                processData: false,
                beforeSend: function() {
                    showSwal('Cargando plantilla', 'Espere por favor...');
                    $('.swal-button-container').hide();
                }
            });
            swal.close();
            txtTemplate = response.dataJson;

        } catch (error) {
            console.error("Opps algo salió mal", error);
        }
        return txtTemplate;
    }

    $("#btn-send-template").on("click", function () {
        const numeros = $("#mBListTelefonos").val().trim().split("\n").filter(n => n !== "");
        const total = numeros.length;

        let sltTemplate = $('#mTselectTemplate').val();
        if (sltTemplate === '99') {
            swal("Atención!", "Selecciona la plantilla", "error");
            return;
        }

        let mBEstatus = $('#mBEstatus').val();
        if (mBEstatus === '99') {
            swal("Atención!", "Selecciona el estatus", "error");
            return;
        }

         // Validar campos dinámicos
        let camposDinamicos = {};
        let faltan = false;
        $(".dynamic-field").each(function () {
            const num = $(this).data("field");
            const val = $(this).val().trim();
            if (val === "") {
                $(this).addClass("is-invalid");
                faltan = true;
            } else {
                $(this).removeClass("is-invalid");
            }
            camposDinamicos[num] = val;
        });

        if (faltan) {
            swal("Atención!", "Completa todos los campos de la plantilla.", "error");
            return;
        }

        if (total === 0) {
            swal("Atención!", "Lista de teléfonos vacía", "error");
            return;
        }

        // SweetAlert con barra de progreso dentro
        swal({
            title: "Enviando mensajes...",
            content: {
                element: "div",
                attributes: {
                    innerHTML: `
                        <div style="width:100%;border:1px solid #ccc;border-radius:5px;">
                            <div id="progress-bar" style="width:0%;background:#28a745;height:20px;border-radius:5px;"></div>
                        </div>
                        <p id="progress-text" style="margin-top:10px;">0 de ${total}</p>
                        <div id="send-log" style="
                            max-height:50px;
                            overflow-y:auto;
                            font-size:13px;
                            text-align:left;"></div>
                    `
                }
            },
            buttons: false,
            closeOnClickOutside: false,
            closeOnEsc: false
        });

        let index = 0;
        let successCount = 0;
        let errorCount = 0;

        function addLog(icon, text) {
            const log = $("#send-log");
            log.prepend(`<div>${icon} - ${text}</div>`);
            log.scrollTop(0); // se queda arriba
        }

        function enviarSiguiente() {
            if (index >= total) {
                swal("¡Proceso completado!", 
                    `${total} mensajes procesados.\n✅ Éxitosos: ${successCount}\n❌ Errores: ${errorCount}`, 
                    "success");
                return;
            }
            const numero = numeros[index];
            let formData = new FormData();
            formData.append('id_location', idLocationSelected.val());
            formData.append('mBEstatus', $("#mBEstatus").val());
            formData.append('mbIdCatParcel', $("#mbIdCatParcel").val());
            formData.append('nameTemplate',$("#mTselectTemplate option:selected").text().trim());
            formData.append('location_lnk', $("#location_lnk").val());
            let camposDinamicos = {};

            $('.dynamic-field').each(function() {
                let num = $(this).data('field');
                camposDinamicos[num] = $(this).val();
            });
            formData.append("campos_plantilla", JSON.stringify(camposDinamicos));

            formData.append('number', numero);
            let texto = $("#preview-template")
             .html()
             .replace(/<br\s*\/?>/gi, "\n");
            formData.append('txtTemplate', texto);
            formData.append('tokenWaba', $("#tokenWaba").val());
            formData.append('phoneWaba', $("#phone_waba").val());
            formData.append('phoneNumberId', $("#phone_number_id").val());
            formData.append('option', 'sendTemplate');

            $.ajax({
                url: `${base_url}/${baseController}`,
                type: 'POST',
                data: formData,
                cache: false,
                contentType: false,
                processData: false,
                success: function (response) {
                    try {
                        if (response.success === "true" || response.success === true) {
                            successCount++;
                            addLog("✅", response.message);
                        } else {
                            errorCount++;
                            addLog("❌", response.message);
                        }
                    } catch (e) {
                        errorCount++; // si no se puede parsear el JSON, lo contamos como error
                        addLog("❌", response.message);
                    }

                    index++;
                    let porcentaje = Math.round((index / total) * 100);
                    $("#progress-bar").css("width", porcentaje + "%");
                    $("#progress-text").text(`${index} de ${total}`);
                    enviarSiguiente(); // Llamar al siguiente
                },
                error: function () {
                    errorCount++;
                    index++;
                    $("#progress-text").text(`${index} de ${total} (Error)`);
                    enviarSiguiente();
                }
            });
        }

        enviarSiguiente();
    });

    $('#btn-read').click(function(){
        markasread();
    });

    function markasread() {
		let tophone = currentPhone;
        $.ajax({
            url: baseController,
            method: "POST",
            data: {
                tophone: tophone,
                option:'markAsRead'
            },
            dataType: 'json',
            success: function(response) {
                swal("Éxito", "Mensajes leidos", "success");
                /*setTimeout(function(){
						swal.close();
						window.location.reload();
					}, 1500);*/
            },
            error: function(xhr) {
                swal("Error", "Error al actualizar lectura.", "error");
            }
        });
	}

    $('#btn-cln').click(function(){
        $('#chPhone').val('');
    });

    $('#info-guias').on('click', function() {
        let tophone = currentPhone;
        $.ajax({
            url: baseController,
            method: "POST",
            data: {
                tophone: tophone,
                option:'infoGuias'
            },
            dataType: 'json',
            beforeSend : function() {
					showSwal('Revisando guías','Espere por favor...');
					$('.swal-button-container').hide();
			},
            success: function(response) {
                console.log(response);
                swal.close();
                if (response.success) {
                    let html = '';
                    response.data.forEach(function(item) {
                    html += `
                        <div style="margin-bottom:8px; font-size:13px">
                            <b>${item.parcel}</b> — ${item.status_desc}<br>
                            ${item.folio} | ${item.tracking}
                        </div>`;
                    });
                    swal({
                        title: "Guías encontradas",
                        content: {
                            element: "div",
                            attributes: {
                                innerHTML: html
                            }
                        },
                        icon: "success"
                    });

                } else {
                    swal("Sin datos", "No se encontraron guías para este número", "warning");
                }
            },
            error: function(xhr) {
                swal("Error", "Error al actualizar lectura.", "error");
            }
        });
    });

    $('#tbl-list-templates').on('click', '.btn-delete-template', function () {
        let id   = $(this).data('id');
        let name = $(this).data('name');

        deleteTemplateWaba(id, name);
    });

    function deleteTemplateWaba(id_template, name) {
        console.log('id_template',id_template,name);
        swal({
                title: `Eliminar plantilla`,
                text: `Desea eliminar la plantilla ${name}?`,
                icon: "info",
                buttons: true,
                dangerMode: false,
            }).then((weContinue) => {
            if (weContinue) {
                    console.log('continue delete');
                let formData =  new FormData();
                    formData.append('id_template', id_template);
                    formData.append('option', 'deleteTemplateWaba');
                    try {
                        $.ajax({
                            url        : `${base_url}/${baseController}`,
                            type       : 'POST',
                            data       : formData,
                            cache      : false,
                            contentType: false,
                            processData: false,
                            beforeSend : function() {
                                showSwal('Eliminando','Espere por favor...');
                                $('.swal-button-container').hide();
                            }
                        })
                        .done(function(response) {
                            swal.close();
                            if(response.success==true){
                                swal(`Plantilla eliminada`, "", "success");
                                $('.swal-button-container').hide();
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
                } else {
                    return false;
                }
        });
    }

});
