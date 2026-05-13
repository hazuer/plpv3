<?php
session_start();
define( '_VALID_MOS', 1 );

require_once('includes/configuration.php');
require_once('includes/DB.php');
$db = new DB(HOST,USERNAME,PASSWD,DBNAME,PORT,SOCKET);
//require_once('includes/session.php');

if(isset($_SESSION["uActive"])){
	header('Location: '.BASE_URL.'/dashboard.php');
	die();
}
?>
<!DOCTYPE html>
<html lang="es-MX">
      <head>
         <?php include_once('head.php');?>
         <script src="<?php echo BASE_URL;?>/assets/js/login.js?version=<?php echo time(); ?>"></script>
         <style>
            /* Reset and base styles for login */
            html, body {
                padding-bottom: 0px !important;
            }
            body.login-page {
                margin: 0;
                padding: 0;
                font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                background-color: #f3f4f6;
                display: flex;
                min-height: 100vh;
                width: 100%;
                overflow: hidden;
                padding-bottom: 0px !important;
            }
            
            .login-container {
                display: flex;
                width: 100%;
                height: 100vh;
            }

            /* Left side - Image / Hero */
            .login-hero {
                flex: 1;
                display: none;
                position: relative;
                overflow: hidden;
            }
            
            .login-hero-bg {
                position: absolute;
                top: 0; left: 0; right: 0; bottom: 0;
                background-size: cover;
                background-position: center center;
                animation: zoomInOut 20s infinite alternate ease-in-out;
                z-index: 0;
                opacity: 0;
                transition: opacity 1.5s ease-in-out;
            }
            .login-hero-bg.active {
                opacity: 1;
            }

            @media (min-width: 992px) {
                .login-hero {
                    display: flex;
                    flex-direction: column;
                    justify-content: flex-end;
                    padding: 4rem;
                }
            }

            .login-hero::before {
                content: '';
                position: absolute;
                top: 0; left: 0; right: 0; bottom: 0;
                background: linear-gradient(to bottom, rgba(15, 23, 42, 0.3), rgba(15, 23, 42, 0.95));
                z-index: 1;
            }

            .hero-content {
                position: relative;
                z-index: 2;
                color: #ffffff;
                animation: fadeInUp 1s ease-out;
            }

            .hero-content h1 {
                font-size: 3rem;
                font-weight: 700;
                margin-bottom: 1rem;
                line-height: 1.2;
                letter-spacing: -0.02em;
                color: #ffffff;
                text-shadow: 2px 2px 8px rgba(0,0,0,0.8);
            }

            .hero-content p {
                font-size: 1.125rem;
                opacity: 0.9;
                max-width: 400px;
                line-height: 1.6;
                text-shadow: 1px 1px 4px rgba(0,0,0,0.8);
            }

            /* Right side - Form */
            .login-form-container {
                flex: 1;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 2rem;
                background: #ffffff;
                position: relative;
            }

            @media (min-width: 992px) {
                .login-form-container {
                    flex: 0 0 500px;
                    box-shadow: -20px 0 40px rgba(0, 0, 0, 0.05);
                    z-index: 2;
                }
            }

            .login-form-wrapper {
                width: 100%;
                max-width: 380px;
                animation: fadeIn 0.6s ease-out;
            }

            .login-logo {
                text-align: center;
                margin-bottom: 2.5rem;
                display: flex;
                flex-direction: column;
                align-items: center;
            }

            .login-logo img {
                max-height: 150px;
                width: auto;
                object-fit: contain;
                transition: transform 0.3s ease;
            }
            
            .login-logo img:hover {
                transform: scale(1.05);
            }

            .mobile-logo-text {
                display: none;
                font-size: 1.5rem;
                color: #1e293b;
                font-weight: 700;
                margin-top: 0.5rem;
                letter-spacing: -0.02em;
            }

            @media (max-width: 768px) {
                .login-logo img {
                    /*width: 60px;*/ /* Recorta la imagen para ocultar las letras y dejar solo el icono */
                    object-fit: cover;
                    object-position: left;
                }
                .mobile-logo-text {
                    display: block;
                }
            }

            .login-header {
                text-align: center;
                margin-bottom: 2rem;
            }

            .login-header h2 {
                font-size: 1.75rem;
                color: #1e293b;
                font-weight: 700;
                margin-bottom: 0.5rem;
            }

            .login-header p {
                color: #64748b;
                font-size: 0.95rem;
            }

            .modern-form .input-group {
                margin-bottom: 1.5rem;
                position: relative;
            }

            .modern-form label {
                display: block;
                font-size: 0.875rem;
                font-weight: 600;
                color: #475569;
                margin-bottom: 0.5rem;
            }

            .modern-form input {
                width: 100%;
                padding: 0.875rem 1rem 0.875rem 2.5rem;
                font-size: 1rem;
                color: #1e293b;
                background-color: #f8fafc;
                border: 1px solid #e2e8f0;
                border-radius: 0.5rem;
                transition: all 0.3s ease;
                box-sizing: border-box;
            }

            .modern-form input:focus {
                outline: none;
                border-color: #3b82f6;
                background-color: #ffffff;
                box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
            }
            
            /* Icons for inputs */
            .input-group::before {
                position: absolute;
                left: 1rem;
                top: 2.3rem;
                color: #94a3b8;
                font-size: 1.1rem;
                display: flex;
                align-items: center;
                justify-content: center;
                pointer-events: none;
                transition: color 0.3s ease;
            }
            
            .input-group:focus-within::before {
                color: #3b82f6;
            }

            .input-user::before {
                content: '👤';
            }
            .input-lock::before {
                content: '🔒';
            }

            .login-btn {
                width: 100%;
                padding: 0.875rem;
                background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
                color: #ffffff;
                border: none;
                border-radius: 0.5rem;
                font-size: 1rem;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s ease;
                box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.2), 0 2px 4px -1px rgba(37, 99, 235, 0.1);
                margin-top: 1rem;
            }

            .login-btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 10px 15px -3px rgba(37, 99, 235, 0.3), 0 4px 6px -2px rgba(37, 99, 235, 0.15);
            }

            .login-btn:active {
                transform: translateY(0);
            }
            
            /* Animations */
            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(20px); }
                to { opacity: 1; transform: translateY(0); }
            }
            
            @keyframes fadeInUp {
                from { opacity: 0; transform: translateY(30px); }
                to { opacity: 1; transform: translateY(0); }
            }
            
            @keyframes zoomInOut {
                0% { transform: scale(1); }
                100% { transform: scale(1.15); }
            }
            
            /* Preloader Styles */
            #preloader {
                position: fixed;
                top: 0; left: 0; right: 0; bottom: 0;
                background: #f8fafc;
                z-index: 9999;
                display: flex;
                justify-content: center;
                align-items: center;
                flex-direction: column;
                transition: opacity 0.5s ease;
            }
            .loader-container {
                position: relative;
                width: 100px;
                height: 100px;
            }
            .circle-track {
                position: absolute;
                top: 10px; left: 10px; right: 10px; bottom: 10px;
                border: 2px dashed #cbd5e1;
                border-radius: 50%;
                animation: spin-reverse 15s linear infinite;
            }
            .airplane-spinner {
                position: absolute;
                top: 0; left: 0; right: 0; bottom: 0;
                animation: spin 2s linear infinite;
            }
            .airplane-icon {
                position: absolute;
                top: -12px;
                left: 50%;
                /* Ajuste de rotación para que apunte hacia adelante en el giro */
                transform: translateX(-50%) rotate(45deg);
                width: 32px;
                height: 32px;
                fill: #2563eb;
                filter: drop-shadow(0px 4px 6px rgba(37, 99, 235, 0.4));
            }
            .loading-text {
                margin-top: 20px;
                font-weight: 600;
                color: #475569;
                font-size: 0.9rem;
                letter-spacing: 2px;
                animation: pulse 1.5s ease-in-out infinite;
            }
            @keyframes spin { 100% { transform: rotate(360deg); } }
            @keyframes spin-reverse { 100% { transform: rotate(-360deg); } }
            @keyframes pulse { 0%, 100% { opacity: 0.5; } 50% { opacity: 1; } }
         </style>
      </head>
   <body class="login-page">
      <div id="preloader">
          <div class="loader-container">
              <div class="circle-track"></div>
              <div class="airplane-spinner">
                  <svg class="airplane-icon" viewBox="0 0 24 24">
                      <path d="M21,16V14L13,9V3.5A1.5,1.5 0 0,0 11.5,2A1.5,1.5 0 0,0 10,3.5V9L2,14V16L10,13.5V19L8,20.5V22L11.5,21L15,22V20.5L13,19V13.5L21,16Z" />
                  </svg>
              </div>
          </div>
          <div class="loading-text">CARGANDO</div>
      </div>
      <div class="login-container">
         <div class="login-hero">
             <div class="login-hero-bg active" style="background-image: url('images/paqueteria_avion.png');"></div>
             <div class="login-hero-bg" style="background-image: url('images/paqueteria_moto.png');"></div>
             <div class="login-hero-bg" style="background-image: url('images/paqueteria_barco.png');"></div>
             <div class="login-hero-bg" style="background-image: url('images/paqueteria_unidad.png');"></div>
             <div class="hero-content">
                 <h1>Paquetería Los Pinos</h1>
                 <p>Plataforma de gestión y seguimiento de paquetería diseñada para potenciar el crecimiento de tu empresa.</p>
             </div>
         </div>
         <div class="login-form-container">
             <div class="login-form-wrapper">
                 <div class="login-logo">
                     <img src="images/logo/logo_icon.png" alt="Paquetería Los Pinos" />
                     <span class="mobile-logo-text"></span>
                 </div>
                 <div class="login-header">
                     <h2>Paquetería Los Pinos</h2>
                     <br>
                     <p>Ingresa tus credenciales para acceder al sistema</p>
                 </div>
                 <form class="modern-form" onsubmit="return false;">
                     <div class="input-group input-user">
                         <label for="username">Usuario</label>
                         <input type="text" autofocus name="username" id="username" placeholder="Ingresa tu usuario" autocomplete="off" />
                     </div>
                     <div class="input-group input-lock">
                         <label for="password">Contraseña</label>
                         <input type="password" name="password" id="password" placeholder="Ingresa tu contraseña" autocomplete="off" />
                     </div>
                     <button class="login-btn" name="btn-login" id="btn-login">Iniciar Sesión</button>
                 </form>
             </div>
         </div>
      </div>
      <script>
         document.addEventListener('DOMContentLoaded', function() {
             const bgs = document.querySelectorAll('.login-hero-bg');
             if (bgs.length > 0) {
                 let currentIndex = Math.floor(Math.random() * bgs.length);
                 
                 // Hide all, show initial random
                 bgs.forEach(bg => bg.classList.remove('active'));
                 bgs[currentIndex].classList.add('active');

                 setInterval(() => {
                     bgs[currentIndex].classList.remove('active');
                     let nextIndex;
                     do {
                         nextIndex = Math.floor(Math.random() * bgs.length);
                     } while (nextIndex === currentIndex);
                     
                     currentIndex = nextIndex;
                     bgs[currentIndex].classList.add('active');
                 }, 7000); // Change image every 7 seconds
             }
         });

         // Ocultar preloader cuando todo haya cargado
         window.addEventListener('load', function() {
             const preloader = document.getElementById('preloader');
             if(preloader) {
                 preloader.style.opacity = '0';
                 setTimeout(() => {
                     preloader.style.display = 'none';
                 }, 500);
             }
         });
      </script>
   </body>
</html>