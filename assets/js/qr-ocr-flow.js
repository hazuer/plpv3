$(document).ready(function () {
    let ocrSaved = false;

    let html5QrCode = null;
    let qrDetected = '';
    let currentStream = null;

    // =====================================================
    // OPEN FLOW
    // =====================================================

    $('#btn-test').off('click').on('click', function (e) {

        e.preventDefault();

        resetLabels();

        openQrModal();

    });

    // =====================================================
    // OPEN QR MODAL
    // =====================================================

    async function openQrModal() {

        await stopQrScanner();

        $('#qr-reader-ocr').html('');

        $('#modal-scan-qr-ocr').modal({
            backdrop: 'static',
            keyboard: false
        });

        html5QrCode = new Html5Qrcode(
            "qr-reader-ocr"
        );

        try {

            await html5QrCode.start(
                {
                    facingMode: "environment"
                },
                {
                    fps: 15,
                    qrbox: 250,
                    aspectRatio: 1
                },
                onScanSuccess
            );

        } catch (error) {

            console.error(error);

            swal(
                "Error",
                "No se pudo abrir la cámara QR",
                "error"
            );

        }

    }

    // =====================================================
    // QR SUCCESS
    // =====================================================

    async function onScanSuccess(decodedText) {

        // evitar múltiples lecturas
        if (qrDetected !== '') {
            return;
        }

        qrDetected = decodedText;

        $('#ocr-qr').html(decodedText);

        await stopQrScanner();

        $('#modal-scan-qr-ocr').one(
            'hidden.bs.modal',
            function () {

                openOcrCamera();

            }
        );

        $('#modal-scan-qr-ocr').modal('hide');

    }

    // =====================================================
    // STOP QR
    // =====================================================

    async function stopQrScanner() {

        try {

            if (html5QrCode) {

                try {

                    await html5QrCode.stop();

                } catch (e) {

                    console.warn(e);

                }

                try {

                    await html5QrCode.clear();

                } catch (e) {

                    console.warn(e);

                }

                html5QrCode = null;

            }

        } catch (err) {

            console.warn(err);

        }

        $('#qr-reader-ocr').html('');

    }

    // =====================================================
    // OPEN OCR CAMERA
    // =====================================================

    async function openOcrCamera() {

        stopCamera();

        $('#modal-ocr-camera').modal({
            backdrop: 'static',
            keyboard: false
        });

        const video = document.getElementById(
            'video-ocr-camera'
        );

        // =========================================
        // VIDEO STYLE
        // =========================================

        video.style.width = '100%';

        video.style.height = '100%';

        video.style.objectFit = 'cover';

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

            // =========================================
            // RESET EVENT
            // =========================================

            $('#btn-capture-ocr').off('click');

            $('#btn-capture-ocr').on(
                'click',
                function () {

                    captureOcr();

                }
            );

        } catch (error) {

            console.error(error);

            swal(
                "Error",
                "No se pudo abrir la cámara",
                "error"
            );

        }

    }

    // =====================================================
    // CAPTURE FUNCTION
    // =====================================================

    function captureOcr() {

        $('audio#sound-snap')[0].play();

        const video = document.getElementById(
            'video-ocr-camera'
        );

        if (!video || !video.videoWidth) {

            swal(
                "Error",
                "La cámara aún no está lista",
                "error"
            );

            return;

        }

        const canvas = document.getElementById(
            'canvas-ocr-camera'
        );

        const ctx = canvas.getContext('2d');

        const overlay = document.getElementById(
            'ocr-overlay'
        );

        const videoWidth = video.videoWidth;

        const videoHeight = video.videoHeight;

        const overlayRect =
            overlay.getBoundingClientRect();

        const videoRect =
            video.getBoundingClientRect();

        // =========================================
        // FIX OBJECT FIT COVER
        // =========================================

        const containerWidth = video.offsetWidth;

        const containerHeight = video.offsetHeight;

        const videoRatio =
            videoWidth / videoHeight;

        const containerRatio =
            containerWidth / containerHeight;

        let renderedWidth;

        let renderedHeight;

        let offsetX = 0;

        let offsetY = 0;

        if (videoRatio > containerRatio) {

            renderedHeight = containerHeight;

            renderedWidth =
                videoWidth * (
                    containerHeight / videoHeight
                );

            offsetX =
                (
                    renderedWidth - containerWidth
                ) / 2;

        } else {

            renderedWidth = containerWidth;

            renderedHeight =
                videoHeight * (
                    containerWidth / videoWidth
                );

            offsetY =
                (
                    renderedHeight - containerHeight
                ) / 2;

        }

        const relativeX =
            overlayRect.left - videoRect.left;

        const relativeY =
            overlayRect.top - videoRect.top;

        const scaleX =
            videoWidth / renderedWidth;

        const scaleY =
            videoHeight / renderedHeight;

        const cropX =
            (relativeX + offsetX) * scaleX;

        const cropY =
            (relativeY + offsetY) * scaleY;

        const cropWidth =
            overlayRect.width * scaleX;

        const cropHeight =
            overlayRect.height * scaleY;

        // =========================================
        // CANVAS
        // =========================================

        canvas.width = cropWidth;

        canvas.height = cropHeight;

        ctx.clearRect(
            0,
            0,
            canvas.width,
            canvas.height
        );

        ctx.drawImage(
            video,
            cropX,
            cropY,
            cropWidth,
            cropHeight,
            0,
            0,
            canvas.width,
            canvas.height
        );
        const imageData = ctx.getImageData(
    0,
    0,
    canvas.width,
    canvas.height
);

const data = imageData.data;

// grayscale + contrast
for(let i = 0; i < data.length; i += 4){

    const avg =
        (
            data[i] +
            data[i + 1] +
            data[i + 2]
        ) / 3;

    const contrast = avg > 140 ? 255 : 0;

    data[i]     = contrast;
    data[i + 1] = contrast;
    data[i + 2] = contrast;
}

ctx.putImageData(imageData, 0, 0);

        const imageBase64 =
            canvas.toDataURL('image/png');

        sendImageToOcr(imageBase64);

    }

    // =====================================================
    // SEND OCR
    // =====================================================

    function sendImageToOcr(imageBase64) {

        let formData = new FormData();

        formData.append('image', imageBase64);

        formData.append('qr', qrDetected);
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

                swal({
                    title: 'Procesando OCR',
                    text: 'Espere por favor',
                    buttons: false
                });

            },

            success: function (response) {

                swal.close();

                if (!response.success) {

                    swal(
                        "Error",
                        response.message,
                        "error"
                    );

                    return;

                }

                $('#ocr-name').html(
                    response.name || '-'
                );

                $('#ocr-phone').html(
                    response.phone || '-'
                );

                $('#ocr-address').html(
                    response.address || '-'
                );

                 $('#ocr-full-text').html(
                    response.fullText || '-'
                );

            },

            error: function (xhr) {

                swal.close();

                console.error(xhr.responseText);

                swal(
                    "Error",
                    "Error al procesar OCR",
                    "error"
                );

            }

        });

    }

    // =====================================================
    // CLOSE OCR
    // =====================================================

    $('#btn-close-ocr-modal')
        .off('click')
        .on('click', function () {
        document.activeElement.blur();

            stopCamera();

            $('#modal-ocr-camera').modal('hide');

        });

    // =====================================================
    // CLOSE QR
    // =====================================================

    $('#btn-close-qr-modal')
        .off('click')
        .on('click', async function () {
        document.activeElement.blur();

            await stopQrScanner();

            $('#modal-scan-qr-ocr').modal('hide');

        });

    // =====================================================
    // STOP CAMERA
    // =====================================================

    function stopCamera() {

        try {

            if (currentStream) {

                currentStream.getTracks().forEach(
                    track => {

                        track.stop();

                    }
                );

            }

            const video = document.getElementById(
                'video-ocr-camera'
            );

            if (video) {

                video.pause();

                video.srcObject = null;

            }

        } catch (e) {

            console.warn(e);

        }

        currentStream = null;

    }

    // =====================================================
    // CLEAN MODALS
    // =====================================================

    $('#modal-ocr-camera').on(
        'hidden.bs.modal',
        function () {

            stopCamera();

        }
    );

    $('#modal-scan-qr-ocr').on(
        'hidden.bs.modal',
        async function () {

            await stopQrScanner();

        }
    );

    // =====================================================
    // RESET LABELS
    // =====================================================

    function resetLabels() {

        qrDetected = '';

        $('#ocr-qr').html('-');

        $('#ocr-name').html('-');

        $('#ocr-phone').html('-');

        $('#ocr-address').html('-');
        $('#ocr-full-text').html('-');

        $('#ocr-initial').html('');
        $('#ocr-folio').html('');
        $('#ocr-save-result').hide();

        const canvas = document.getElementById(
            'canvas-ocr-camera'
        );

        if (canvas) {
            const ctx = canvas.getContext('2d');
            ctx.clearRect(
                0,
                0,
                canvas.width,
                canvas.height
            );
        }
    }

    // =====================================================
    // RESET FLOW
    // =====================================================

    async function resetQrOcrFlow() {
ocrSaved = false;
$('#btn-save-ocr').prop('disabled', false);

$('#btn-capture-ocr').prop('disabled', false);
        resetLabels();
        stopCamera();
        $('#btn-save-ocr').prop('disabled', false);
        $('#btn-capture-ocr').prop('disabled', false);

        await stopQrScanner();
        $('#modal-ocr-camera').modal('hide');
        $('#modal-scan-qr-ocr').modal('hide');
        $('#ocr-save-result').hide();

        setTimeout(function () {

            openQrModal();

        }, 500);

    }

async function saveDataOcr(){

    if(ocrSaved){
    return;
}

    const qr      = $('#ocr-qr').text().trim();
    const name    = $('#ocr-name').text().trim();
    const phone   = $('#ocr-phone').text().trim();
    const address = $('#ocr-address').text().trim();

    
        let formData = new FormData();

        formData.append('qr', qr);
        formData.append('name', name);
        formData.append('phone', phone);
        formData.append('address', address);

        formData.append('action', 'saveDataOcr');

    $.ajax({

        url: `${base_url}/controllers/ocrRecipient.php`,
        type: 'POST',
        dataType: 'json',

        data: formData,
        processData: false,
        contentType: false,

        beforeSend: function(){
            

            $('#btn-save-ocr').prop('disabled', true);

        },

       success:function(response){

    console.log(response);

    if(response.success){

        $('#ocr-initial').text(response.initial);
        $('#ocr-folio').text(response.folio);

        $('#ocr-save-result').show();

        $('#btn-capture-ocr').prop('disabled', true);

    }

},
        error: function(xhr){

            console.error(xhr);

            

            $('#btn-save-ocr').prop('disabled', false);

        }

    });

}

    // =====================================================
    // SAVE OCR
    // =====================================================

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

        
});