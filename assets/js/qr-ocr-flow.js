$(document).ready(function () {
    let currentStream = null;
    let ocrStartTime  = 0;
    let evidencePath  = '';
    let selectedColor = '';
    let selectedCourierType = '';
    const $btnTest    = $('#btn-test');

    $('#btn-save-ocr')
    .off('click')
    .on('click', function () {
        saveDataOcr();
    });

    $('#btn-next-ocr')
    .off('click')
    .on('click', function () {
        resetQrOcrFlow();
    });

    $btnTest.off('click').on('click', async function (e) {
        e.preventDefault();
        // si falta alguno mostrar modal único
        if (!selectedColor || !selectedCourierType) {
            const config = await selectPackageConfig();
            if (!config) {
                return;
            }
            selectedColor       = config.color;
            selectedCourierType = config.courierType;
            applyPackageColor();
        }
        resetLabels();
        openOcrCamera();
    });

    function selectPackageConfig() {
        return new Promise((resolve) => {
            swal({
                title: 'Configuración del paquete',
                content: {
                    element: "div",
                    attributes: {
                        innerHTML: `
                            <div style="text-align:left">
                            <label>
                                    Tipo paquetería
                                </label>
                                <select id="sw_id_cat_parcel" class="form-control">
                                    <option value="">Seleccione tipo</option>
                                    <option value="1">J&T</option>
                                    <option value="2">IMILE</option>
                                    <option value="3">CNMEX</option>
                                    <option value="99">Otro</option>
                                </select>
                                <br>
                                <label>
                                    Color
                                </label>
                                <select id="sw_marker_color"
                                        class="form-control"
                                        style="margin-bottom:15px;">
                                    <option value="">
                                        Seleccione color
                                    </option>
                                    <option value="red">🔴 Rojo</option>
                                    <option value="blue">🔵 Azul</option>
                                    <option value="green">🟢 Verde</option>
                                    <option value="black">⚫ Negro</option>
                                </select>
                            </div>
                        `
                    }
                },

                buttons: {
                    cancel: true,
                    confirm: {text: 'Continuar',closeModal: false}
                }

            }).then(() => {
                const color =
                $('#sw_marker_color').val();
                const courierType =
                $('#sw_id_cat_parcel').val();

                if (!color || !courierType) {
                    swal('Error','Debe seleccionar color y tipo','error');
                    resolve(null);
                    return;
                }
                swal.close();
                resolve({color,courierType});
            });
        });
    }

    function validateTrackingByParcel(tracking) {
        tracking = tracking.trim();
        const parcelType = parseInt(selectedCourierType);

        // J&T
        if (parcelType === 1) {
            let regex = /^JMX\d{12}$/i;
            if ( tracking.length !== 15 || !regex.test(tracking)) {
                return {
                    success: false,
                    message: 'Guía J&T inválida. Debe tener formato JMX + 12 dígitos'
                };
            }
            tracking = tracking.substring(0, 3).toUpperCase() + tracking.substring(3);
            return {
                success: true,
                tracking
            };
        }

        // IMILE
        if (parcelType === 2) {
            if (/^im\d{14}$/i.test(tracking)) {
                tracking = tracking.toUpperCase();
            }
            const regexNumerico = /^\d{13,14}$/;
            const regexIm       = /^IM\d{14}$/;
            if ( !regexNumerico.test(tracking) && !regexIm.test(tracking) ) {
                return {
                    success: false,
                    message: 'Guía IMILE inválida'
                };
            }
            return {
                success: true,
                tracking
            };
        }

        // CNMEX
        if (parcelType === 3) {
            let regex = /^CNMEX\d{10}$/i;
            if ( tracking.length !== 15 || !regex.test(tracking)) {
                return {
                    success: 
                    false,message: 'Guía CNMEX inválida'
                };
            }
            tracking = tracking.substring(0, 5).toUpperCase() + tracking.substring(5);
            return {
                success: 
                true,tracking
            };
        }

        // OTRO
        return {success: true,tracking};
    }

    // [1] Open OCR Camera
    async function openOcrCamera() {
        stopCamera();
        $('#modal-ocr-camera').modal({ backdrop: 'static', keyboard: false });
        const video = document.getElementById(
            'video-ocr-camera'
        );
        // Video Style
        video.style.width     = '100%';
        video.style.height    = '100%';
        video.style.objectFit = 'cover';
        $('#btn-save-ocr').hide();

        try {
            currentStream =
                await navigator.mediaDevices.getUserMedia({
                    video: {
                        facingMode: {
                            ideal: 'environment'
                        },
                        width: {
                            ideal: 1920
                        },
                        height: {
                            ideal: 1080
                        }
                    },
                    audio: false
                });
            video.srcObject = currentStream;
            await video.play();

            $('#btn-capture-ocr').off('click');
            $('#btn-capture-ocr').on('click',function () {
                captureOcr();
            });
        } catch (error) {
            console.error(error);
            swal("Error","No se pudo abrir la cámara","error");
        }
    }

    function dataURLtoFile(dataurl, filename) {
        const arr   = dataurl.split(',');
        const mime  = arr[0].match(/:(.*?);/)[1];
        const bstr  = atob(arr[1]);
        let n       = bstr.length;
        const u8arr = new Uint8Array(n);
        while (n--) {
            u8arr[n] = bstr.charCodeAt(n);
        }
        return new File(
            [u8arr],
            filename,
            { type: mime }
        );
    }

    async function readBarcodeFromImage(imageBase64) {
        const container         = document.createElement('div');
        container.id            = 'barcode-reader-' + Date.now();
        container.style.display = 'none';
        document.body.appendChild(container);
        try {
            const html5QrCode       = new Html5Qrcode(container.id);
            const file        = dataURLtoFile(imageBase64,'capture.png');
            const result      = await html5QrCode.scanFile( file, true );
            await html5QrCode.clear();
            container.remove();
            return result;
        } catch(e){
            console.error(e);
            //alert('ERROR REAL:\n\n' +(e.message ||JSON.stringify(e)));
            container.remove();
            return '';
        }
    }

    // CAPTURE FUNCTION
    function captureOcr() {

        $('audio#sound-snap')[0].play();
        const video = document.getElementById('video-ocr-camera');

        if (!video || !video.videoWidth) {
            swal("Error","La cámara aún no está lista","error");
            return;
        }

        const canvas      = document.getElementById('canvas-ocr-camera');
        const ctx         = canvas.getContext('2d');
        const overlay     = document.getElementById('ocr-overlay');
        const videoWidth  = video.videoWidth;
        const videoHeight = video.videoHeight;
        const overlayRect = overlay.getBoundingClientRect();
        const videoRect   = video.getBoundingClientRect();

        // FIX OBJECT FIT COVER
        const containerWidth  = video.offsetWidth;
        const containerHeight = video.offsetHeight;
        const videoRatio      = videoWidth / videoHeight;
        const containerRatio  = containerWidth / containerHeight;

        let renderedWidth;
        let renderedHeight;
        let offsetX = 0;
        let offsetY = 0;
        if (videoRatio > containerRatio) {
            renderedHeight = containerHeight;
            renderedWidth  = videoWidth * ( containerHeight / videoHeight );
            offsetX        = ( renderedWidth - containerWidth ) / 2;
        } else {
            renderedWidth  = containerWidth;
            renderedHeight = videoHeight * ( containerWidth / videoWidth );
            offsetY        = ( renderedHeight - containerHeight ) / 2;
        }

        const relativeX  = overlayRect.left - videoRect.left;
        const relativeY  = overlayRect.top - videoRect.top;
        const scaleX     = videoWidth / renderedWidth;
        const scaleY     = videoHeight / renderedHeight;
        const cropX      = (relativeX + offsetX) * scaleX;
        const cropY      = (relativeY + offsetY) * scaleY;
        const cropWidth  = overlayRect.width * scaleX;
        const cropHeight = overlayRect.height * scaleY;

        const barcodeCanvas  = document.createElement('canvas');
        barcodeCanvas.width  = videoWidth;
        barcodeCanvas.height = videoHeight;
        const barcodeCtx     = barcodeCanvas.getContext('2d');
        barcodeCtx.drawImage(video,0,0,videoWidth,videoHeight);
        const barcodeImage   = barcodeCanvas.toDataURL('image/png');

        // CANVAS
        // mejorar resolución OCR
        canvas.width = cropWidth * 2.5;
        canvas.height = cropHeight * 2.5;
        ctx.clearRect( 0, 0, canvas.width, canvas.height);

        // mejor calidad escalado
        ctx.imageSmoothingEnabled = true;
        ctx.imageSmoothingQuality = 'high';

        // filtros OCR
        ctx.filter = `
            grayscale(100%)
            contrast(185%)
            brightness(128%)
            saturate(0%)
            `.replace(/\s+/g, ' ');

        // dibujar imagen
        ctx.drawImage(video,cropX,cropY,cropWidth,cropHeight,0,0,canvas.width,canvas.height);
        ctx.globalCompositeOperation ='screen';
        ctx.fillStyle ='rgba(255,255,255,0.14)';
        ctx.fillRect(0,0,canvas.width,canvas.height);
        ctx.globalCompositeOperation ='source-over';

        // reset filtros
        ctx.filter = 'none';
/*
        // new code
        const imageData = ctx.getImageData(0,0,canvas.width,canvas.height);
        const data = imageData.data;
        // grayscale + contrast
        for(let i = 0; i < data.length; i += 4){
            const avg =
                (
                    data[i] +
                    data[i + 1] +
                    data[i + 2]
                ) / 3;
            const contrast = avg > 130 ? 255 : 0; //130?255
            data[i]     = contrast;
            data[i + 1] = contrast;
            data[i + 2] = contrast;
        }
        ctx.putImageData(imageData, 0, 0);
        // end new code
        */

        // BASE64
        const imageBase64 = canvas.toDataURL('image/png');
        // INICIAR CRONOMETRO OCR
        ocrStartTime = performance.now();
        //sendImageToOcr(imageBase64);

        (async () => {
            let trackingDetected = '';
            try {
                trackingDetected = await readBarcodeFromImage(barcodeImage);
                //alert('trackingDetected: ' +trackingDetected);
                //return;
            } catch(e) {
                //console.warn(e);
            }

            if (trackingDetected == '') {
                swal('Atención','No es posible identificar la guía','warning');
                return;
            }

            const validation = validateTrackingByParcel(trackingDetected);
            if (!validation.success) {
                swal('Atención',validation.message,'error');
                return;
            }
            sendImageToOcr(imageBase64, trackingDetected
            );
        })();
    }

    // [2] Send Image to OCR
    function sendImageToOcr(imageBase64,trackingDetected = '') {

        const idLocation     = $('#option-location').val();
        // prefijo ubicación
        const locationPrefix = $('#option-location option:selected').text().trim().toLowerCase().replace(/[^a-z0-9]/g,'_');
        // random corto
        const random         = Math.random().toString(36).substring(2,10);
        // nombre final
        const imageName      = `${locationPrefix}_${random}.png`;

        let formData = new FormData();
        formData.append('image', imageBase64);
        formData.append('trackingDetected',trackingDetected);
        formData.append('idLocation', idLocation);
        formData.append('imageName', imageName);
        formData.append('courierType',selectedCourierType);
        formData.append('action', 'processImage');
        $.ajax({
            url: `${base_url}/controllers/ocrRecipient.php`,
            type: 'POST',
            data: formData,
            cache: false,
            contentType: false,
            processData: false,
            dataType: 'json',
            beforeSend: function () {
                $('#btn-save-ocr').hide();
                $('#btn-capture-ocr').hide();
                $('#btn-use-suggested-name').hide();
                loading();
                $('.swal-button-container').hide();
            },
            success: function (response) {
                swal.close();
                // TIEMPO OCR
                const ocrEndTime = performance.now();
                const totalTime = ( (ocrEndTime - ocrStartTime) / 1000 ).toFixed(2);

                if (!response.success) {
                    swal("Error",response.message,"error");
                    $('#btn-capture-ocr').show();
                    return;
                }
                $('#ocr-qr').html(response.tracking);
                $('#ocr-name').html(response.name);
                $('#ocr-phone').html(response.phone);
                $('#ocr-address').html(response.address);
                $('#ocr-postal-code').html(response.postalCode);
                evidencePath = response.evidencePath;
                renderValidationStatus(response.ocrDb);

                // AUTO REGISTRO
                /*if(
                    response.ocrDb &&
                    response.ocrDb.status === 'auto_register'
                ){
                    setTimeout(function(){
                        //#########TRIGER AUTO SAVE#########
                        $('#btn-save-ocr').trigger('click');
                        //######### call to saveDataOcr()#########
                    }, 400);
                    $('#btn-save-ocr').hide();
                    $('#btn-capture-ocr').hide();
                }else{
                    //TODO: Editar nombre o telefono para mejorar coincidencia
                    //$('#btn-save-ocr').show();
                    $('#btn-capture-ocr').show();
                    swal({
                        title: 'No fue posible el registro automático',
                        text: `No se detectton coincidencias suficientes para auto registrar`,
                        icon: 'warning',
                        timer: 1500,
                        buttons: false
                    });
                }*/
               switch(response.ocrDb.status){
                case 'auto_register':
                      setTimeout(function(){
                        //#########TRIGER AUTO SAVE#########
                        $('#btn-save-ocr').trigger('click');
                        //######### call to saveDataOcr()#########
                    }, 400);
                    $('#btn-save-ocr').hide();
                    $('#btn-capture-ocr').hide();
                break;
                case 'suggest_name':
                    $('#btn-save-ocr').show();
                    swal({
                        title: 'Variación detectada',
                        text: 'Se encontró un contacto similar: ' + response.ocrDb.suggestedName,
                        icon: 'warning',
                        timer: 2500,
                    });
                break;
                case 'new_variant':
                    $('#btn-save-ocr').show();
                    swal({
                        title: 'Nombre diferente',
                        text: 'El teléfono existe pero el nombre es distinto.',
                        icon: 'warning',
                        timer: 2500,
                    });
                break;
                case 'new_contact':
                    $('#btn-save-ocr').show();
                    swal({
                        title: 'Nuevo contacto',
                        text: 'El teléfono no existe en la base de datos.',
                        icon: 'info',
                        timer: 2500,
                    });
                break;
            }
            },error: function (xhr) {
                swal.close();
                console.error(xhr.responseText);
                $('#btn-save-ocr').hide();
                $('#btn-capture-ocr').show();
                swal("Error","Error al procesar OCR","error");
            }
        });
    }

    $('#btn-use-suggested-name')
    .off('click')
    .on('click', function(){
        const suggested = $(this).data('name');
        $('#ocr-name').text(suggested);
        $('#btn-save-ocr').show();
        $(this).hide();
    });

    function renderValidationStatus(validation){
        let phoneIcon = '🔴';
        let nameIcon  = '🔴';

        // TELEFONO
        if(validation.phoneStatus === 'found'){
            phoneIcon = '🟢';
        }

        // NOMBRE
        switch(validation.nameStatus){
            case 'exact':
                nameIcon = '🟢';
                break;

            case 'similar':
                nameIcon = '🟡';
                $('#btn-use-suggested-name').data('name',validation.suggestedName).show();
                $('#btn-save-ocr').show();
                break;

            case 'variant':
                nameIcon = '🟠';
                break;

            default:
                nameIcon = '🔴';
                break;
        }

        $('#lbl-phone').html(`${phoneIcon} Teléfono:`);
        $('#lbl-name').html(`${nameIcon} Nombre:`);
    }

    // CLOSE OCR
    $('#btn-close-ocr-modal')
        .off('click')
        .on('click', function () {
            document.activeElement.blur();
            stopCamera();
            $('#modal-ocr-camera').modal('hide');
            setTimeout(function(){
                location.reload();
            }, 300);
        });

    // STOP CAMERA
    function stopCamera() {
        try {
            if (currentStream) {
                currentStream.getTracks().forEach(
                    track => {
                        track.stop();
                    }
                );
            }

            const video = document.getElementById('video-ocr-camera');
            if (video) {
                video.pause();
                video.srcObject = null;
            }
        } catch (e) {
            console.warn(e);
        }
        currentStream = null;
    }

    // CLEAN MODALS
    $('#modal-ocr-camera').on(
        'hidden.bs.modal',
        function () {
            stopCamera();
        }
    );

    // RESET LABELS
    function resetLabels() {
        $('#ocr-qr').html('');
        $('#ocr-name').html('');
        $('#ocr-phone').html('');
        $('#ocr-address').html('');
        $('#ocr-postal-code').html('');
        $('#ocr-initial').html('');
        $('#ocr-folio').html('');
        $('#btn-use-suggested-name').hide();
        $('#ocr-save-result').hide();
        const canvas = document.getElementById('canvas-ocr-camera');

        if (canvas) {
            const ctx = canvas.getContext('2d');
            ctx.clearRect(0,0,canvas.width,canvas.height);
        }
    }

    // RESET FLOW
    async function resetQrOcrFlow() {
        $('#btn-save-ocr').prop('disabled', false);
        $('#btn-capture-ocr').prop('disabled', false);
        $('#btn-save-ocr').show();
        $('#btn-capture-ocr').show();
        resetLabels();
        stopCamera();
        $('#modal-ocr-camera').modal('hide');
        $('#ocr-save-result').hide();
        setTimeout(function () {
            openOcrCamera();
        }, 500);
    }

    // [3] Save OCR Data
    async function saveDataOcr(){

        const qr      = $('#ocr-qr').text().trim();
        const name    = $('#ocr-name').text().trim();
        const phone   = $('#ocr-phone').text().trim();
        const address = $('#ocr-address').text().trim();
        const postalCode = $('#ocr-postal-code').text().trim();
        const idLocation = $('#option-location').val();

        if (qr == '' || name == '' || phone == '') {
			swal("Atención!", "* Campos requeridos", "error");
			return;
		}

        let formData = new FormData();
        formData.append('qr', qr);
        formData.append('name', name);
        formData.append('phone', phone);
        formData.append('address', address);
        formData.append('postalCode', postalCode);
        formData.append('idLocation', idLocation);
        formData.append('packageColor', selectedColor);
        formData.append('courierType', selectedCourierType);
        formData.append('evidencePath',evidencePath);
        formData.append('action', 'saveDataOcr');

        $.ajax({
            url: `${base_url}/controllers/ocrRecipient.php`,
            type: 'POST',
            dataType: 'json',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function(){
                /*swal({
                    title: 'Guardando datos',
                    text: 'Espere por favor',incon: 'info',buttons: false
                });*/
                $('#btn-save-ocr').prop('disabled', true);
            },
            success:function(response){
            if(response.success){
                $('#ocr-initial').text(response.initial);
                $('#ocr-folio').text(response.folio);
                $('#ocr-save-result').show();
                speakPackageData(response);
                $('#btn-capture-ocr').prop('disabled',true);

                // YA EXISTE
                if(response.alreadyExists){
                    swal({
                        title: 'Guía Registrada',
                        text: '  ',//response.message,
                        icon: 'success',
                        timer: 500,
                        buttons: false
                    });
                }else{
                    swal({
                        title: 'Registrado',
                        text: '  ',//response.message,
                        icon: 'success',
                        timer: 500,
                        buttons: false
                    });
                }

                setTimeout(function () {
                    //#########TRIGER AUTO NEXT#########
                    $('#btn-next-ocr').trigger('click');
                }, 5000);
            }
        },
            error: function(xhr){
                console.error(xhr);
                $('#btn-save-ocr').prop('disabled', false);
            }
        });
    }

    function applyPackageColor(){
        const color = selectedColor;
        $('#ocr-initial').removeClass().addClass('badge');
        $('#ocr-folio').removeClass().addClass('badge');

        switch(color){

            case 'red':
                $('#ocr-initial').css({background:'#dc3545',color:'#fff'});
                $('#ocr-folio').css({background:'#dc3545',color:'#fff'});
                break;

            case 'blue':
                $('#ocr-initial').css({background:'#007bff',color:'#fff'});
                $('#ocr-folio').css({background:'#007bff',color:'#fff'});
                break;

            case 'green':
                $('#ocr-initial').css({background:'#28a745',color:'#fff'});
                $('#ocr-folio').css({background:'#28a745',color:'#fff'});
                break;

            case 'black':
                $('#ocr-initial').css({background:'#212529',color:'#fff'});
                $('#ocr-folio').css({background:'#212529',color:'#fff'});
                break;
        }
    }

    function speakPackageData(response){
        window.speechSynthesis.cancel();
        speakText(`Folio ${response.folio}`);

        setTimeout(function(){
            speakText(`Letra ${response.initial}`);
        }, 600);

        setTimeout(function(){
            speakText(response.contact_name);
        }, 600);
    }
});