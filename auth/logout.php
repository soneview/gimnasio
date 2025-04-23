<?php
session_start();

// Destruir todas las variables de sesión
$_SESSION = array();

// Destruir la sesión
session_destroy();

// Redireccionar a la página principal
header("Location: ../index.php");
exit();
?>
