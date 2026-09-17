# Training Point

Sistema web de gestión de clases para gimnasio de entrenamiento funcional.

**En producción:** [newlogin.alwaysdata.net](https://newlogin.alwaysdata.net)

---

## Qué es

Training Point resuelve la administración diaria de un gimnasio de entrenamiento funcional que hasta ahora llevaba todo en papel: quién pagó, cuántas clases le quedan a cada alumno, quién viene hoy y quién faltó.

Los alumnos reservan sus clases, manejan sus créditos y dan presente con un código QR desde el celular. El profesor administra alumnos, turnos, pagos y asistencia desde un panel propio.

**Cliente:** Damián De Miguel — gimnasio de entrenamiento funcional.

---

## Funcionalidades

### Alumno
- Registro propio o ingreso con cuenta de Google
- Recuperación de contraseña por correo
- Compra de créditos por transferencia bancaria o efectivo
- Reserva de clases dentro de una ventana de 30 días
- Cancelación con devolución del crédito si todavía no dio presente
- Código QR único por cada clase reservada
- Carga del apto médico (imagen o PDF), que queda guardado en el perfil
- Historial de abonos y de asistencias

### Administrador
- Panel con métricas: alumnos, clases de la semana, alumnos sin créditos, asistencias del mes
- Ficha completa por alumno: datos, créditos, apto médico e historial
- Carga y descuento de créditos con fecha de vencimiento configurable
- Creación de clases sueltas o recurrentes por día de la semana y rango de fechas
- Edición y borrado individual o múltiple de clases
- Lector QR con cámara para registrar asistencia
- Control de asistencia por clase
- Envío de avisos a todos los alumnos, a uno solo, o a los inscriptos en una clase puntual

---

## Tecnologías

| Capa | Herramienta |
|---|---|
| Backend | PHP 8 |
| Base de datos | MySQL / MariaDB |
| Frontend | HTML y CSS, sin frameworks |
| Correo | PHPMailer |
| Autenticación externa | Google OAuth 2.0 |
| Lectura de QR | html5-qrcode |
| Generación de QR | api.qrserver.com |
| Hosting | AlwaysData |

---

## Instalación

### 1. Clonar el repositorio

```bash
git clone https://github.com/juanjoybeto/training-point.git
cd training-point
```

### 2. Instalar las dependencias

El directorio `vendor/` no está versionado. Se regenera con Composer:

```bash
composer install --no-dev
```

> En un hosting compartido, esto se ejecuta por SSH. Subir `vendor/` por FTP no es viable por la cantidad de archivos.

### 3. Crear la base de datos

Importar `trainingpoint.sql` desde phpMyAdmin o por consola:

```bash
mysql -u usuario -p nombre_base < trainingpoint.sql
```

### 4. Configurar la conexión

En `dbconn.php`, completar los datos del servidor de base de datos.

### 5. Configurar las variables de entorno

```bash
cp .env.example .env
```

Y completar `.env` con las credenciales de correo y de Google OAuth.

### 6. Permisos de la carpeta de subidas

```bash
mkdir -p uploads/aptos
chmod 755 uploads/aptos
```

---

## Estructura de la base de datos

| Tabla | Contenido |
|---|---|
| `registronuevo` | Usuarios. Incluye rol (`alumno` / `administrador`), créditos, vencimiento y apto médico |
| `clases` | Turnos con fecha, horario y capacidad |
| `inscripciones` | Relación alumno ↔ clase |
| `asistencias` | Presentes registrados al escanear el QR |
| `abonos` | Historial de packs de créditos comprados |
| `notificaciones` | Avisos, con destinatario individual |
| `recuperar` | Tokens temporales de recuperación de contraseña |

---

## Decisiones de diseño

**El crédito se descuenta al reservar, no al escanear el QR.** Si el alumno cancela antes de dar presente, se le devuelve. Así nadie ocupa un lugar sin costo.

**El código QR identifica alumno y clase.** Su contenido es `alumno_id:clase_id:token`. Un QR único por alumno provocaba que se registraran asistencias cruzadas cuando alguien tenía más de una reserva.

**El vencimiento de los créditos lo define el administrador.** Por defecto toma el último día del mes en curso, pero puede cambiarse por alumno.

**Los alumnos ven una ventana de 30 días.** El administrador puede cargar todo el año; los alumnos solo ven y reservan lo próximo.

**Las notificaciones de pago llegan únicamente al administrador.** Ningún alumno ve los pagos de los demás.

---

## Manuales

- [Manual del alumno](https://newlogin.alwaysdata.net/manual_alumno.html)
- [Manual del administrador](https://newlogin.alwaysdata.net/manual_administrador.html)

---

## Seguridad

Este repositorio **no incluye**:

- `.env` — credenciales de correo y de Google OAuth
- `uploads/` — aptos médicos de los alumnos, que son datos personales de salud

Ambos están excluidos en `.gitignore` y no deben subirse en ningún caso.

---

## Autores

Proyecto de 7° año — Técnico en Informática Personal y Profesional
Instituto Leonardo Murialdo · 2026

- **Juan José Bosio** — [@juanjoybeto](https://github.com/juanjoybeto)
- **Joaquín Goyechea**

Materia: Proyecto, Diseño e Implementación de Sistemas Computacionales
