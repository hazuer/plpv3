$(document).ready(function () {

    $('#modal-ocr-camera').on('hidden.bs.modal', function () {

        $('#btn-capture-ocr').off('click');

        $('#btn-stop-ocr-camera').off('click');

    });

    $('#btn-scan-ocr').click(function () {

        openOcrModal({
            title: 'Cloud Vision API',
            onSave: function (image) {

                console.log('Imagen capturada');

            }
        });

    });

    function openOcrModal(config) {

        $('#modal-ocr-title').html(config.title);

        $('#modal-ocr-camera').modal({
            backdrop: 'static',
            keyboard: false
        });

        const videoContainer = document.getElementById('video-container-ocr-camera');

        const video = document.getElementById('video-ocr-camera');

        const canvas = document.getElementById('canvas-ocr-camera');

        const context = canvas.getContext('2d');

        let stream = null;

        let capturedImageData = null;

        context.clearRect(0, 0, canvas.width, canvas.height);

        // =========================
        // VIDEO
        // =========================

        video.style.width = '100%';

        video.style.height = '100%';

        video.style.objectFit = 'cover';

        // =========================
        // ABRIR CAMARA
        // =========================

        navigator.mediaDevices.getUserMedia({

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

        })
            .then((mediaStream) => {

                stream = mediaStream;

                video.srcObject = stream;

                return video.play();

            })
            .catch((err) => {

                console.error(err);

                alert('No se pudo abrir la cámara');

            });

        // =========================
        // LIMPIAR EVENTOS
        // =========================

        $('#btn-capture-ocr').off('click');

        $('#btn-stop-ocr-camera').off('click');

        // =========================
        // CAPTURAR
        // =========================

        $('#btn-capture-ocr').on('click', function () {

            $('audio#sound-snap')[0].play();

            const videoWidth = video.videoWidth;

            const videoHeight = video.videoHeight;

            const overlay = document.getElementById('ocr-overlay');

            const overlayRect = overlay.getBoundingClientRect();

            const videoRect = video.getBoundingClientRect();

            // =========================
            // COVER FIX
            // =========================

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

                renderedWidth = videoWidth * (containerHeight / videoHeight);

                offsetX = (renderedWidth - containerWidth) / 2;

            } else {

                renderedWidth = containerWidth;

                renderedHeight = videoHeight * (containerWidth / videoWidth);

                offsetY = (renderedHeight - containerHeight) / 2;

            }

            const relativeX = overlayRect.left - videoRect.left;

            const relativeY = overlayRect.top - videoRect.top;

            const scaleX = videoWidth / renderedWidth;

            const scaleY = videoHeight / renderedHeight;

            const cropX = (relativeX + offsetX) * scaleX;

            const cropY = (relativeY + offsetY) * scaleY;

            const cropWidth = overlayRect.width * scaleX;

            const cropHeight = overlayRect.height * scaleY;

            // =========================
            // CANVAS
            // =========================

            canvas.width = cropWidth;

            canvas.height = cropHeight;

            context.clearRect(0, 0, canvas.width, canvas.height);

            context.drawImage(
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

            capturedImageData = canvas.toDataURL('image/jpeg', 1.0);

            console.log('OCR capturado');

            // =========================
            // ENVIAR A GOOGLE CLOUD VISION
            // =========================

            $.ajax({
                url: 'gcv.php',
                method: 'POST',
                data: {
                    image: capturedImageData
                },
                dataType: 'json',

                beforeSend: function () {

                    $('#ocr-qr').html('Procesando...');
                    $('#ocr-name').html('Procesando...');
                    $('#ocr-phone').html('Procesando...');
                    $('#ocr-address').html('Procesando...');

                },

                success: function (response) {

                    console.log(response);

                    if (!response.success) {

                        alert(response.message);

                        return;

                    }

                    // ====================================
                    // AUTOCOMPLETE
                    // ====================================

                    $('#ocr-qr').html(response.qr || '-');

                    $('#ocr-name').html(response.name || '-');

                    $('#ocr-phone').html(response.phone || '-');

                    $('#ocr-address').html(response.address || '-');

                },

                error: function () {

                    alert('Error al procesar OCR');

                }

            });

        });

        // =========================
        // CERRAR
        // =========================

        $('#btn-stop-ocr-camera').on('click', function () {

            if (stream) {

                stream.getTracks().forEach(track => track.stop());

                video.srcObject = null;

            }

        });

    }

});