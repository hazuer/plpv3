<?php
// Redirige a la página principal o muestra un mensaje de error
header('HTTP/1.0 403 Forbidden');
echo 'Acceso restringido. No tienes permiso para acceder a este directorio.';
exit;
