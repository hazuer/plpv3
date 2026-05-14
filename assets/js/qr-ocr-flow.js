$(document).ready(function () {
    let html5QrcodeScanner = null;
    let qrDetected = '';
    let currentStream = null;

    // =====================================================
    // OPEN FLOW
    // =====================================================
    $('#btn-test').click(function (e) {
        e.preventDefault();
        openQrModal();

    });

    // =====================================================
    // OPEN QR MODAL
    // =====================================================

    function openQrModal() {
        $('#modal-scan-qr-ocr').modal({
            backdrop: 'static',
            keyboard: false
        });
        html5QrcodeScanner = new Html5QrcodeScanner(
            "qr-reader-ocr", {
            fps: 15,
            qrbox: 250,
            aspectRatio: 1
        }
        );
        html5QrcodeScanner.render(onScanSuccess);
    }

    // =====================================================
    // QR SUCCESS
    // =====================================================

    function onScanSuccess(decodedText) {
        qrDetected = decodedText;
        $('#ocr-qr').html(decodedText);
        stopQrScanner();
        $('#modal-scan-qr-ocr').modal('hide');
        setTimeout(() => {
            openOcrCamera();
        }, 400);
    }

    // =====================================================
    // STOP QR
    // =====================================================

    function stopQrScanner() {
        if (!html5QrcodeScanner) return;
        html5QrcodeScanner.clear()
            .catch(err => console.warn(err));
    }

    // =====================================================
    // OPEN OCR CAMERA
    // =====================================================
    async function openOcrCamera() {
        $('#modal-ocr-camera').modal({
            backdrop: 'static',
            keyboard: false
        });
        const video = document.getElementById('video-ocr-camera');
        // =========================================
        // VIDEO STYLE
        // =========================================
        video.style.width = '100%';
        video.style.height = '100%';
        video.style.objectFit = 'cover';

        try {
            currentStream = await navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode: { ideal: 'environment' },
                    width: { ideal: 1920 },
                    height: { ideal: 1080 }
                },
                audio: false
            });
            video.srcObject = currentStream;
            await video.play();
        } catch (error) {
            console.error(error);
            swal("Error", "No se pudo abrir la cámara", "error");

        }
    }
    // =====================================================
    // CAPTURE OCR
    // =====================================================
    $('#btn-capture-ocr').off('click');
    $('#btn-capture-ocr').on('click', function () {
        captureOcr();
    });

    // =====================================================
    // CAPTURE FUNCTION
    // =====================================================
    function captureOcr() {
        $('audio#sound-snap')[0].play();
        const video = document.getElementById('video-ocr-camera');
        const canvas = document.getElementById('canvas-ocr-camera');
        const ctx = canvas.getContext('2d');
        const overlay = document.getElementById('ocr-overlay');
        const videoWidth = video.videoWidth;
        const videoHeight = video.videoHeight;
        const overlayRect = overlay.getBoundingClientRect();
        const videoRect = video.getBoundingClientRect();
        // =========================================
        // FIX OBJECT FIT COVER
        // =========================================
        const containerWidth = video.offsetWidth;
        const containerHeight = video.offsetHeight;
        const videoRatio = videoWidth / videoHeight;
        const containerRatio = containerWidth / containerHeight;
        let renderedWidth;
        let renderedHeight;
        let offsetX = 0;
        let offsetY = 0;
        if (videoRatio > containerRatio) {
            renderedHeight = containerHeight;
            renderedWidth = videoWidth * (
                containerHeight / videoHeight
            );
            offsetX = (
                renderedWidth - containerWidth
            ) / 2;
        } else {
            renderedWidth = containerWidth;
            renderedHeight = videoHeight * (
                containerWidth / videoWidth
            );

            offsetY = (
                renderedHeight - containerHeight
            ) / 2;
        }

        const relativeX = overlayRect.left - videoRect.left;
        const relativeY = overlayRect.top - videoRect.top;
        const scaleX = videoWidth / renderedWidth;
        const scaleY = videoHeight / renderedHeight;
        const cropX = (relativeX + offsetX) * scaleX;
        const cropY = (relativeY + offsetY) * scaleY;
        const cropWidth = overlayRect.width * scaleX;
        const cropHeight = overlayRect.height * scaleY;

        // =========================================
        // CANVAS
        // =========================================
        canvas.width = cropWidth;
        canvas.height = cropHeight;
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        ctx.drawImage(video, cropX, cropY, cropWidth, cropHeight, 0, 0, canvas.width, canvas.height);
        const imageBase64 = canvas.toDataURL('image/png');
        sendImageToOcr(imageBase64);
    }

    // =====================================================
    // SEND OCR
    // =====================================================
    function sendImageToOcr(imageBase64) {
        let formData = new FormData();
        formData.append('image', imageBase64);
        formData.append('qr', qrDetected);
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
                    swal("Error", response.message, "error");
                    return;
                }
                $('#ocr-name').html(response.name || '-');
                $('#ocr-phone').html(response.phone || '-');
                $('#ocr-address').html(response.address || '-');

            },
            error: function (xhr) {
                swal.close();
                console.error(xhr.responseText);
                swal("Error", "Error al procesar OCR", "error");
            }
        });

    }

    // =====================================================
    // CLOSE OCR
    // =====================================================
    $('#btn-close-ocr-modal').click(function () {
        stopCamera();
        $('#modal-ocr-camera').modal('hide');
    });

    // =====================================================
    // CLOSE QR
    // =====================================================
    $('#btn-close-qr-modal').click(function () {
        stopQrScanner();
        $('#modal-scan-qr-ocr').modal('hide');
    });

    // =====================================================
    // STOP CAMERA
    // =====================================================
    function stopCamera() {
        if (!currentStream) return;
        currentStream.getTracks().forEach(track => {
            track.stop();
        });
        currentStream = null;
    }
    // =====================================================
    // CLEAN MODALS
    // =====================================================
    $('#modal-ocr-camera').on('hidden.bs.modal', function () {
        stopCamera();
    });
    $('#modal-scan-qr-ocr').on('hidden.bs.modal', function () {
        stopQrScanner();
    });

    // =====================================================
    // RESET FLOW
    // =====================================================

    function resetQrOcrFlow() {
        // limpiar qr
        qrDetected = '';

        // limpiar labels
        $('#ocr-qr').html('-');
        $('#ocr-name').html('-');
        $('#ocr-phone').html('-');
        $('#ocr-address').html('-');
        // limpiar canvas
        const canvas = document.getElementById('canvas-ocr-camera');
        const ctx = canvas.getContext('2d');
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        // detener cámara
        stopCamera();
        // detener scanner
        stopQrScanner();
        // cerrar modales
        $('#modal-ocr-camera').modal('hide');
        $('#modal-scan-qr-ocr').modal('hide');

        // volver a iniciar flujo
        setTimeout(() => {
            openQrModal();
        }, 500);
    }

    $('#btn-save-ocr').click(function () {
        // aquí tu lógica de guardar...
        resetQrOcrFlow();

    });
});