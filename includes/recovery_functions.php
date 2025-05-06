<?php
/**
 * Funciones para el sistema de recuperación de contraseña
 */

/**
 * Genera un salt aleatorio para aumentar la seguridad del hash
 * @return string Salt aleatorio
 */
function generateSalt() {
    return bin2hex(random_bytes(32));
}

/**
 * Genera un hash seguro para almacenar la respuesta secreta
 * @param string $respuesta La respuesta en texto plano
 * @param string $salt El salt aleatorio
 * @return string El hash resultante
 */
function hashRespuestaSecreta($respuesta, $salt) {
    // Normaliza la respuesta (minúsculas y sin espacios al inicio/final)
    $respuesta = mb_strtolower(trim($respuesta));
    
    // Combina la respuesta con el salt y genera hash con algoritmo seguro
    return password_hash($respuesta . $salt, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verifica si la respuesta proporcionada coincide con la almacenada
 * @param string $respuesta_proporcionada La respuesta proporcionada por el usuario
 * @param string $respuesta_hash El hash almacenado de la respuesta correcta
 * @param string $salt El salt utilizado para generar el hash
 * @return bool True si la respuesta es correcta, False en caso contrario
 */
function verificarRespuestaSecreta($respuesta_proporcionada, $respuesta_hash, $salt) {
    // Normaliza la respuesta proporcionada
    $respuesta_proporcionada = mb_strtolower(trim($respuesta_proporcionada));
    
    // Verifica si la respuesta coincide con el hash almacenado
    return password_verify($respuesta_proporcionada . $salt, $respuesta_hash);
}

/**
 * Registra una pregunta y respuesta secreta para un usuario
 * @param mysqli $conn Conexión a la base de datos
 * @param int $usuario_id ID del usuario
 * @param string $pregunta Pregunta secreta
 * @param string $respuesta Respuesta secreta
 * @return bool|string True si se registró correctamente, mensaje de error en caso contrario
 */
function registrarPreguntaSecreta($conn, $usuario_id, $pregunta, $respuesta) {
    try {
        // Genera un salt aleatorio
        $salt = generateSalt();
        
        // Hashea la respuesta con el salt
        $respuesta_hash = hashRespuestaSecreta($respuesta, $salt);
        
        // Fecha actual
        $fecha_actual = date('Y-m-d H:i:s');
        
        // Verifica si ya existe un registro para este usuario
        $query = "SELECT id FROM recovery_keys WHERE usuario_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Actualiza el registro existente
            $query = "UPDATE recovery_keys SET 
                      pregunta = ?, 
                      respuesta_hash = ?, 
                      salt = ?, 
                      intentos_fallidos = 0, 
                      bloqueado_hasta = NULL, 
                      fecha_actualizacion = ? 
                      WHERE usuario_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ssssi", $pregunta, $respuesta_hash, $salt, $fecha_actual, $usuario_id);
        } else {
            // Crea un nuevo registro
            $query = "INSERT INTO recovery_keys 
                      (usuario_id, pregunta, respuesta_hash, salt, fecha_creacion) 
                      VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("issss", $usuario_id, $pregunta, $respuesta_hash, $salt, $fecha_actual);
        }
        
        if ($stmt->execute()) {
            return true;
        } else {
            return "Error al guardar la información de recuperación: " . $conn->error;
        }
    } catch (Exception $e) {
        return "Error: " . $e->getMessage();
    }
}

/**
 * Verifica si un usuario tiene configurada una pregunta secreta
 * @param mysqli $conn Conexión a la base de datos
 * @param int $usuario_id ID del usuario
 * @return bool True si tiene pregunta secreta, False en caso contrario
 */
function usuarioTienePreguntaSecreta($conn, $usuario_id) {
    $query = "SELECT id FROM recovery_keys WHERE usuario_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->num_rows > 0;
}

/**
 * Obtiene la pregunta secreta de un usuario por su email
 * @param mysqli $conn Conexión a la base de datos
 * @param string $email Email del usuario
 * @return array|false Información de la pregunta o false si no existe
 */
function obtenerPreguntaSecretaPorEmail($conn, $email) {
    $query = "SELECT u.id AS usuario_id, r.id AS recovery_id, r.pregunta, r.intentos_fallidos, r.bloqueado_hasta 
              FROM usuarios u 
              JOIN recovery_keys r ON u.id = r.usuario_id 
              WHERE u.email = ? AND u.estado = 1";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        return $result->fetch_assoc();
    }
    
    return false;
}

/**
 * Registra un intento de recuperación de contraseña
 * @param mysqli $conn Conexión a la base de datos
 * @param int|null $usuario_id ID del usuario (null si no se encontró)
 * @param string $email Email usado en el intento
 * @param bool $exitoso Si el intento fue exitoso
 * @param string $mensaje_error Mensaje de error (si aplica)
 */
function registrarIntentoRecuperacion($conn, $usuario_id, $email, $exitoso, $mensaje_error = '') {
    $ip = $_SERVER['REMOTE_ADDR'];
    $fecha = date('Y-m-d H:i:s');
    $exitoso_int = $exitoso ? 1 : 0;
    
    // Verificar si la tabla existe
    $tableExists = false;
    $checkTable = $conn->query("SHOW TABLES LIKE 'recovery_attempts'");
    if ($checkTable && $checkTable->num_rows > 0) {
        $tableExists = true;
    }
    
    if (!$tableExists) {
        return;
    }
    
    if ($usuario_id === null) {
        $query = "INSERT INTO recovery_attempts (email, ip_address, intento_fecha, exitoso, mensaje_error) 
                  VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssss", $email, $ip, $fecha, $exitoso_int, $mensaje_error);
    } else {
        $query = "INSERT INTO recovery_attempts (usuario_id, email, ip_address, intento_fecha, exitoso, mensaje_error) 
                  VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("isssss", $usuario_id, $email, $ip, $fecha, $exitoso_int, $mensaje_error);
    }
    
    $stmt->execute();
}

/**
 * Verifica si hay demasiados intentos fallidos desde una IP
 * @param mysqli $conn Conexión a la base de datos
 * @param string $ip Dirección IP
 * @param int $limite Límite de intentos permitidos
 * @param int $minutos Período de tiempo en minutos
 * @return bool True si hay demasiados intentos, False en caso contrario
 */
function demasiadosIntentosPorIP($conn, $ip, $limite = 5, $minutos = 30) {
    // Verificar si la tabla existe
    $tableExists = false;
    $checkTable = $conn->query("SHOW TABLES LIKE 'recovery_attempts'");
    if ($checkTable && $checkTable->num_rows > 0) {
        $tableExists = true;
    }
    
    if (!$tableExists) {
        return false;
    }
    
    $query = "SELECT COUNT(*) as intentos FROM recovery_attempts 
              WHERE ip_address = ? 
              AND exitoso = 0 
              AND intento_fecha > DATE_SUB(NOW(), INTERVAL ? MINUTE)";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("si", $ip, $minutos);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    
    return $data['intentos'] >= $limite;
}

/**
 * Incrementa el contador de intentos fallidos para un usuario
 * @param mysqli $conn Conexión a la base de datos
 * @param int $recovery_id ID del registro de recuperación
 * @return bool|string True si se actualizó correctamente, mensaje de error en caso contrario
 */
function incrementarIntentosFallidos($conn, $recovery_id) {
    try {
        $fecha_actual = date('Y-m-d H:i:s');
        
        // Incrementa el contador y establece la fecha del último intento
        $query = "UPDATE recovery_keys SET 
                  intentos_fallidos = intentos_fallidos + 1,
                  ultimo_intento = ?
                  WHERE id = ?";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("si", $fecha_actual, $recovery_id);
        
        if ($stmt->execute()) {
            // Verifica si se ha alcanzado el límite de intentos fallidos
            $query = "SELECT intentos_fallidos FROM recovery_keys WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $recovery_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $data = $result->fetch_assoc();
            
            // Si hay más de 3 intentos fallidos, bloquea por 30 minutos
            if ($data['intentos_fallidos'] >= 3) {
                $tiempo_bloqueo = date('Y-m-d H:i:s', strtotime('+30 minutes'));
                $query = "UPDATE recovery_keys SET bloqueado_hasta = ? WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("si", $tiempo_bloqueo, $recovery_id);
                $stmt->execute();
            }
            
            return true;
        } else {
            return "Error al actualizar intentos fallidos: " . $conn->error;
        }
    } catch (Exception $e) {
        return "Error: " . $e->getMessage();
    }
}

/**
 * Restablece el contador de intentos fallidos
 * @param mysqli $conn Conexión a la base de datos
 * @param int $recovery_id ID del registro de recuperación
 */
function resetearIntentosFallidos($conn, $recovery_id) {
    $query = "UPDATE recovery_keys SET 
              intentos_fallidos = 0,
              bloqueado_hasta = NULL
              WHERE id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $recovery_id);
    $stmt->execute();
}

/**
 * Genera un token de restablecimiento temporal
 * @param mysqli $conn Conexión a la base de datos
 * @param int $usuario_id ID del usuario
 * @return string|false El token generado o false en caso de error
 */
function generarTokenRestablecimiento($conn, $usuario_id) {
    try {
        // Genera un token aleatorio
        $token = bin2hex(random_bytes(32));
        $hash_token = password_hash($token, PASSWORD_DEFAULT);
        
        // Establece la fecha de expiración (1 hora)
        $expiracion = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Guarda el token en la base de datos
        $query = "INSERT INTO reset_tokens (usuario_id, token, expiracion) 
                  VALUES (?, ?, ?)";
        
        // Primero verificamos si la tabla existe
        $tableExists = false;
        $checkTable = $conn->query("SHOW TABLES LIKE 'reset_tokens'");
        if ($checkTable && $checkTable->num_rows > 0) {
            $tableExists = true;
        }
        
        // Si la tabla no existe, la creamos
        if (!$tableExists) {
            $createTable = "CREATE TABLE IF NOT EXISTS `reset_tokens` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `usuario_id` int(11) NOT NULL,
                `token` varchar(255) NOT NULL,
                `expiracion` datetime NOT NULL,
                `usado` tinyint(1) DEFAULT 0,
                PRIMARY KEY (`id`),
                KEY `usuario_id` (`usuario_id`),
                CONSTRAINT `fk_reset_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
            
            $conn->query($createTable);
        }
        
        // Elimina tokens anteriores para este usuario
        $query_delete = "DELETE FROM reset_tokens WHERE usuario_id = ?";
        $stmt_delete = $conn->prepare($query_delete);
        $stmt_delete->bind_param("i", $usuario_id);
        $stmt_delete->execute();
        
        // Inserta el nuevo token
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iss", $usuario_id, $hash_token, $expiracion);
        
        if ($stmt->execute()) {
            return $token;
        } else {
            return false;
        }
    } catch (Exception $e) {
        error_log("Error generando token: " . $e->getMessage());
        return false;
    }
}

/**
 * Cambia la contraseña de un usuario
 * @param mysqli $conn Conexión a la base de datos
 * @param int $usuario_id ID del usuario
 * @param string $nueva_password Nueva contraseña
 * @return bool|string True si se cambió correctamente, mensaje de error en caso contrario
 */
function cambiarPassword($conn, $usuario_id, $nueva_password) {
    try {
        // Hashea la nueva contraseña
        $hashed_password = password_hash($nueva_password, PASSWORD_BCRYPT, ['cost' => 12]);
        
        // Actualiza la contraseña en la base de datos
        $query = "UPDATE usuarios SET password = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("si", $hashed_password, $usuario_id);
        
        if ($stmt->execute()) {
            return true;
        } else {
            return "Error al cambiar la contraseña: " . $conn->error;
        }
    } catch (Exception $e) {
        return "Error: " . $e->getMessage();
    }
}
?>
