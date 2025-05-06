-- Estructura de tabla para almacenar información de recuperación de contraseñas
CREATE TABLE IF NOT EXISTS `recovery_keys` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `pregunta` varchar(255) NOT NULL,
  `respuesta_hash` varchar(255) NOT NULL,
  `salt` varchar(64) NOT NULL,
  `intentos_fallidos` int(11) DEFAULT 0,
  `bloqueado_hasta` datetime DEFAULT NULL,
  `ultimo_intento` datetime DEFAULT NULL,
  `fecha_creacion` datetime NOT NULL,
  `fecha_actualizacion` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `fk_recovery_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Modificar la tabla usuarios para incluir método preferido de recuperación
ALTER TABLE `usuarios` 
ADD COLUMN `metodo_recuperacion` ENUM('palabra_clave', 'sms', '2fa') DEFAULT 'palabra_clave' AFTER `estado`,
ADD COLUMN `telefono_verificado` TINYINT(1) DEFAULT 0 AFTER `metodo_recuperacion`;

-- Estructura de tabla para registrar los intentos de recuperación de contraseña
CREATE TABLE IF NOT EXISTS `recovery_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `intento_fecha` datetime NOT NULL,
  `exitoso` tinyint(1) NOT NULL DEFAULT 0,
  `mensaje_error` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `ip_address` (`ip_address`),
  KEY `intento_fecha` (`intento_fecha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
