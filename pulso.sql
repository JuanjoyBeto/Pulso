-- =============================================
-- PULSO — Sistema de gestión para gimnasios
-- Instalación completa de la base de datos (estructura + datos iniciales)
-- Uso: phpMyAdmin → base vacía → pestaña SQL → pegar y ejecutar
-- =============================================

CREATE TABLE IF NOT EXISTS registronuevo (
  id int(11) NOT NULL AUTO_INCREMENT,
  nombre varchar(100) NOT NULL,
  apellido varchar(100) NOT NULL,
  email varchar(150) DEFAULT NULL,
  user varchar(80) NOT NULL,
  pass varchar(255) NOT NULL,
  telefono varchar(30) DEFAULT NULL,
  genero varchar(20) DEFAULT NULL,
  rol enum('alumno','administrador') DEFAULT 'alumno',
  creditos int(11) DEFAULT 0,
  vencimiento_creditos date DEFAULT NULL,
  apto_medico tinyint(1) DEFAULT 0,
  fecha_registro datetime DEFAULT current_timestamp(),
  apto_medico_archivo varchar(255) DEFAULT NULL,
  apto_estado enum('sin_cargar','pendiente','aprobado','rechazado') NOT NULL DEFAULT 'sin_cargar',
  apto_motivo varchar(255) DEFAULT NULL,
  apto_fecha_subida datetime DEFAULT NULL,
  apto_fecha_revision datetime DEFAULT NULL,
  wa_avisos tinyint(1) NOT NULL DEFAULT 1,
  aviso_venc_enviado date DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY user (user),
  UNIQUE KEY email (email)
);
CREATE TABLE IF NOT EXISTS clases (
  id int(11) NOT NULL AUTO_INCREMENT,
  nombre varchar(100) NOT NULL,
  fecha date NOT NULL,
  hora_inicio time NOT NULL,
  hora_fin time NOT NULL,
  capacidad int(11) DEFAULT 20,
  activa tinyint(1) DEFAULT 1,
  PRIMARY KEY (id)
);
CREATE TABLE IF NOT EXISTS inscripciones (
  id int(11) NOT NULL AUTO_INCREMENT,
  alumno_id int(11) NOT NULL,
  clase_id int(11) NOT NULL,
  fecha_inscripcion datetime DEFAULT current_timestamp(),
  recordatorio_enviado tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY unica (alumno_id,clase_id),
  KEY clase_id (clase_id),
  CONSTRAINT inscripciones_ibfk_1 FOREIGN KEY (alumno_id) REFERENCES registronuevo (id) ON DELETE CASCADE,
  CONSTRAINT inscripciones_ibfk_2 FOREIGN KEY (clase_id) REFERENCES clases (id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS asistencias (
  id int(11) NOT NULL AUTO_INCREMENT,
  alumno_id int(11) NOT NULL,
  clase_id int(11) NOT NULL,
  presente tinyint(1) DEFAULT 1,
  fecha datetime DEFAULT current_timestamp(),
  PRIMARY KEY (id),
  UNIQUE KEY unica (alumno_id,clase_id),
  KEY clase_id (clase_id),
  CONSTRAINT asistencias_ibfk_1 FOREIGN KEY (alumno_id) REFERENCES registronuevo (id) ON DELETE CASCADE,
  CONSTRAINT asistencias_ibfk_2 FOREIGN KEY (clase_id) REFERENCES clases (id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS abonos (
  id int(11) NOT NULL AUTO_INCREMENT,
  alumno_id int(11) NOT NULL,
  creditos_total int(11) NOT NULL,
  creditos_usados int(11) DEFAULT 0,
  fecha_inicio date NOT NULL,
  fecha_fin date NOT NULL,
  PRIMARY KEY (id),
  KEY alumno_id (alumno_id),
  CONSTRAINT abonos_ibfk_1 FOREIGN KEY (alumno_id) REFERENCES registronuevo (id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS notificaciones (
  id int(11) NOT NULL AUTO_INCREMENT,
  alumno_id int(11) DEFAULT NULL,
  mensaje text NOT NULL,
  fecha datetime DEFAULT current_timestamp(),
  leida tinyint(1) DEFAULT 0,
  PRIMARY KEY (id)
);
CREATE TABLE IF NOT EXISTS recuperar (
  email varchar(150) NOT NULL,
  TOKEN varchar(64) DEFAULT NULL,
  FECHA_ALTA datetime DEFAULT NULL,
  CLAVE_NUEVA varchar(20) DEFAULT NULL,
  PRIMARY KEY (email)
);
CREATE TABLE IF NOT EXISTS configuracion (
  id int(11) NOT NULL DEFAULT 1,
  nombre_gimnasio varchar(100) NOT NULL DEFAULT 'Pulso',
  logo varchar(255) NOT NULL DEFAULT 'logo_pulso.png',
  email_contacto varchar(150) DEFAULT '',
  telefono varchar(30) DEFAULT '',
  direccion varchar(200) DEFAULT '',
  dias_vencimiento int(11) NOT NULL DEFAULT 31,
  whatsapp_activo tinyint(1) NOT NULL DEFAULT 0,
  dias_aviso_vencimiento int(11) NOT NULL DEFAULT 3,
  creditos_aviso int(11) NOT NULL DEFAULT 3,
  PRIMARY KEY (id)
);
CREATE TABLE IF NOT EXISTS packs (
  id int(11) NOT NULL AUTO_INCREMENT,
  nombre varchar(60) NOT NULL,
  creditos int(11) NOT NULL,
  precio decimal(10,2) NOT NULL,
  activo tinyint(1) DEFAULT 1,
  PRIMARY KEY (id)
);
CREATE TABLE IF NOT EXISTS pagos (
  id int(11) NOT NULL AUTO_INCREMENT,
  alumno_id int(11) NOT NULL,
  pack_id int(11) DEFAULT NULL,
  creditos int(11) NOT NULL,
  monto decimal(10,2) NOT NULL,
  metodo enum('mercadopago','transferencia','efectivo') NOT NULL,
  estado enum('pendiente','aprobado','rechazado') DEFAULT 'pendiente',
  mp_payment_id varchar(50) DEFAULT NULL,
  fecha_creado datetime DEFAULT current_timestamp(),
  fecha_aprobado datetime DEFAULT NULL,
  PRIMARY KEY (id),
  KEY alumno_id (alumno_id),
  CONSTRAINT pagos_ibfk_1 FOREIGN KEY (alumno_id) REFERENCES registronuevo (id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS whatsapp_log (
  id int(11) NOT NULL AUTO_INCREMENT,
  usuario_id int(11) DEFAULT NULL,
  telefono varchar(30) DEFAULT NULL,
  mensaje text DEFAULT NULL,
  ok tinyint(1) DEFAULT 0,
  detalle varchar(255) DEFAULT NULL,
  fecha datetime DEFAULT current_timestamp(),
  PRIMARY KEY (id)
);

-- =============================================
-- DATOS INICIALES
-- =============================================

-- Datos del gimnasio (se cambian desde Configuración)
INSERT IGNORE INTO configuracion (id, nombre_gimnasio, logo) VALUES (1, 'Pulso', 'logo_pulso.png');

-- Packs de ejemplo (se cambian desde Configuración)
INSERT INTO packs (nombre, creditos, precio) VALUES
('Pack 4 clases', 4, 12000),
('Pack 8 clases', 8, 20000),
('Pack 12 clases', 12, 27000);

-- Administrador inicial — usuario: admin / contraseña: admin123
-- ¡Cambiá la contraseña después del primer ingreso!
INSERT IGNORE INTO registronuevo (nombre, apellido, email, user, pass, rol) VALUES
('Admin', 'Pulso', 'admin@pulso.com', 'admin', '$2y$10$hRDnzxNUgstewqVJvNdvfuSI9QLaDFJ9g1OhYthQ6Wh3fJBJKrjPe', 'administrador');
