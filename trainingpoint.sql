-- =============================================
-- TRAINING POINT - Base de Datos
-- Importar en AlwaysData via phpMyAdmin
-- =============================================

-- Tabla de usuarios (alumnos y admin)
CREATE TABLE IF NOT EXISTS registronuevo (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  apellido VARCHAR(100) NOT NULL,
  email VARCHAR(150) UNIQUE,
  user VARCHAR(80) UNIQUE NOT NULL,
  pass VARCHAR(255) NOT NULL,
  telefono VARCHAR(30),
  genero VARCHAR(20),
  rol ENUM('alumno','administrador') DEFAULT 'alumno',
  creditos INT DEFAULT 0,
  vencimiento_creditos DATE,
  apto_medico TINYINT(1) DEFAULT 0,
  fecha_registro DATETIME DEFAULT NOW()
);

-- Tabla de clases
CREATE TABLE IF NOT EXISTS clases (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL DEFAULT 'FUNCIONAL',
  fecha DATE NOT NULL,
  hora_inicio TIME NOT NULL,
  hora_fin TIME NOT NULL,
  capacidad INT DEFAULT 20,
  activa TINYINT(1) DEFAULT 1
);

-- Inscripciones (alumno se anota a una clase)
CREATE TABLE IF NOT EXISTS inscripciones (
  id INT AUTO_INCREMENT PRIMARY KEY,
  alumno_id INT NOT NULL,
  clase_id INT NOT NULL,
  fecha_inscripcion DATETIME DEFAULT NOW(),
  UNIQUE KEY unica (alumno_id, clase_id),
  FOREIGN KEY (alumno_id) REFERENCES registronuevo(id) ON DELETE CASCADE,
  FOREIGN KEY (clase_id) REFERENCES clases(id) ON DELETE CASCADE
);

-- Asistencias (registro QR)
CREATE TABLE IF NOT EXISTS asistencias (
  id INT AUTO_INCREMENT PRIMARY KEY,
  alumno_id INT NOT NULL,
  clase_id INT NOT NULL,
  presente TINYINT(1) DEFAULT 1,
  fecha DATETIME DEFAULT NOW(),
  UNIQUE KEY unica (alumno_id, clase_id),
  FOREIGN KEY (alumno_id) REFERENCES registronuevo(id) ON DELETE CASCADE,
  FOREIGN KEY (clase_id) REFERENCES clases(id) ON DELETE CASCADE
);

-- Historial de abonos
CREATE TABLE IF NOT EXISTS abonos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  alumno_id INT NOT NULL,
  creditos_total INT NOT NULL,
  creditos_usados INT DEFAULT 0,
  fecha_inicio DATE NOT NULL,
  fecha_fin DATE NOT NULL,
  FOREIGN KEY (alumno_id) REFERENCES registronuevo(id) ON DELETE CASCADE
);

-- Notificaciones
CREATE TABLE IF NOT EXISTS notificaciones (
  id INT AUTO_INCREMENT PRIMARY KEY,
  alumno_id INT DEFAULT NULL,
  mensaje TEXT NOT NULL,
  fecha DATETIME DEFAULT NOW(),
  leida TINYINT(1) DEFAULT 0
);

-- Recupero de contraseña
CREATE TABLE IF NOT EXISTS recuperar (
  email VARCHAR(150) PRIMARY KEY,
  TOKEN VARCHAR(64),
  FECHA_ALTA DATETIME,
  CLAVE_NUEVA VARCHAR(20)
);

-- =============================================
-- DATOS DE EJEMPLO
-- =============================================

-- Admin inicial (contraseña: admin123)
INSERT IGNORE INTO registronuevo (nombre, apellido, email, user, pass, rol) VALUES
('Dami', 'Admin', 'admin@trainingpoint.com', 'admin',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uSc-L5jU-', 'administrador');

-- Clases de ejemplo
INSERT IGNORE INTO clases (nombre, fecha, hora_inicio, hora_fin, capacidad) VALUES
('FUNCIONAL', DATE_ADD(CURDATE(), INTERVAL 1 DAY), '18:30:00', '19:30:00', 20),
('FUNCIONAL', DATE_ADD(CURDATE(), INTERVAL 1 DAY), '19:30:00', '20:30:00', 20),
('FUNCIONAL', DATE_ADD(CURDATE(), INTERVAL 3 DAY), '18:30:00', '19:30:00', 20),
('FUNCIONAL', DATE_ADD(CURDATE(), INTERVAL 3 DAY), '19:30:00', '20:30:00', 20),
('FUNCIONAL', DATE_ADD(CURDATE(), INTERVAL 5 DAY), '18:30:00', '19:30:00', 20);
