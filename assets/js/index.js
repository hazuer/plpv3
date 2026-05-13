(function ($) {
    "use strict";
    
    // Dropdown on mouse hover
    $(document).ready(function () {
        let baseController = 'controllers/packageController.php';
        function toggleNavbarMethod() {
            if ($(window).width() > 992) {
                $('.navbar .dropdown').on('mouseover', function () {
                    $('.dropdown-toggle', this).trigger('click');
                }).on('mouseout', function () {
                    $('.dropdown-toggle', this).trigger('click').blur();
                });
            } else {
                $('.navbar .dropdown').off('mouseover').off('mouseout');
            }
        }
        toggleNavbarMethod();
        $(window).resize(toggleNavbarMethod);

        $("#btn-search").click(function(e){
            let tracking = $('#tracking').val();

            if(tracking==''){
                swal("Atención!", "Debes ingresar el número de guía", "error");
                return;
            }

            let t = tracking.trim(); // Eliminar espacios en blanco al inicio y al final

            let regex = /^JMX\d{12}$/;
            if (t.length !== 15 || !regex.test(t.toUpperCase())) {
                let mensajeError = "Número de guía";
                if (t.length !== 15) {
                    mensajeError += " no válido"; //length 15
                } else {
                    mensajeError += " no válido"; //invalid
                }
                swal("Atención!", mensajeError, "error");
                return;
            }

			let formData = new FormData();
			formData.append('tracking',tracking);
			formData.append('option','check-tracking');
			$.ajax({
				url: `${base_url}/${baseController}`,
				type       : 'POST',
				data       : formData,
				cache      : false,
				contentType: false,
				processData: false,
                beforeSend : function() {
                    showSwal();
                    $('.swal-button-container').hide();
                }
            })
            .done(function(response) {
                if(response.success==='true'){
                    swal(tracking, response.message, "success");
                }else {
                    swal(tracking, response.message, "warning");
                }
                $('.swal-button-container').hide();
                $('#tracking').val('')
            }).fail(function(e) {
                console.log("Error",e);
            });
        });
    });

    // Back to top button
    $(window).scroll(function () {
        if ($(this).scrollTop() > 100) {
            $('.back-to-top').fadeIn('slow');
        } else {
            $('.back-to-top').fadeOut('slow');
        }
    });

})(jQuery);
