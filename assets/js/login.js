$(document).ready(function() {
	let btn_login = $('#btn-login');
	let username  = $('#username');
	let password  = $('#password');

	btn_login.click(function(){

		if(username.val()=='' || password.val()==''){
			swal("Atención!", "Campos requeridos (*)", "warning");
			return false;
		}

		let formData = new FormData();
		formData.append('username',username.val());
		formData.append('password',password.val());
		formData.append('device_id', localStorage.getItem('device_id'));
		formData.append('option','login');
		$.ajax( {
		url        : `${base_url}/controllers/loginController.php`,
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
			setTimeout(function(){
				swal.close();

				if(response.device === 'invalid'){
					swal(
						"Error",
						"No fue posible identificar el dispositivo.",
						"error"
					);
					return;
				}
				
				if(response.device === 'pending'){
					swal(
						"Dispositivo pendiente",
						"Tu dispositivo fue registrado y está pendiente de autorización.",
						"warning"
					);
					return;
				}

				if(response.device === 'blocked'){
					swal(
						"Dispositivo bloqueado",
						"Contacta al administrador.",
						"error"
					);
					return;
				}
				if(response.success==='true'){
					window.location.href = `${base_url}/dashboard.php`;
				}else{
					swal("Error!", "Usuario/Contraseña incorrecto", "warning");
				return false;
				}
			}, 1000);
		}).fail(function(e) {
			console.log("Algo salió  mal",e);
		})
	});

});