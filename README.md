# Pulso

Sistema web de gestión para gimnasios: reservas, cobros con MercadoPago, asistencia por QR, aptos médicos y avisos automáticos por WhatsApp.

**En producción:** [newlogin.alwaysdata.net](https://newlogin.alwaysdata.net) · **Landing:** [juanjoybeto.github.io/training-point-landing](https://juanjoybeto.github.io/training-point-landing/) · **Documentación:** [juanjoybeto.github.io/training-point-documentacion](https://juanjoybeto.github.io/training-point-documentacion/)

---

## Qué es

Pulso resuelve la administración diaria de un gimnasio que todavía lleva todo en papel: quién pagó, cuántas clases le quedan a cada alumno, quién viene hoy y quién faltó.

Los alumnos reservan sus clases, pagan con MercadoPago, dan presente con un código QR desde el celular y reciben los avisos por WhatsApp. El gimnasio administra alumnos, clases, pagos, aptos médicos y asistencia desde un panel propio, y configura su nombre, su logo, sus packs y sus precios sin tocar código.

Sirve para cualquier actividad: funcional, boxeo, yoga, spinning o lo que dé cada gimnasio.

---

## Funcionalidades

### Alumno
- Registro propio o ingreso con cuenta de Google, y recuperación de contraseña por correo
- Compra de packs con **MercadoPago**: tarjeta, débito, dinero en cuenta o efectivo en Pago Fácil y Rapipago. Los créditos se cargan solos
- Créditos que **vencen solos** a los 31 días de la compra (configurable)
- Reserva de clases dentro de una ventana de 30 días, y cancelación con devolución del crédito si todavía no dio presente
- Código QR único por cada clase reservada
- Carga del apto médico (imagen o PDF), con estado pendiente, aprobado o rechazado
- **Avisos automáticos por WhatsApp** y en la app: recordatorio de clase, pocos créditos, sin créditos, vencimiento próximo, pago aprobado y resultado del apto
- Tarjeta "Tu gimnasio" con WhatsApp, teléfono, correo y cómo llegar

### Administrador
- Panel con métricas: alumnos, clases de la semana, alumnos sin créditos, asistencias del mes
- **Pagos:** total cobrado en el mes e historial
- **Aptos:** validación con aprobación o rechazo con motivo
- Ficha completa por alumno: datos, créditos, apto médico e historial
- Carga manual de créditos para pagos en efectivo en el gimnasio
- Clases sueltas o recurrentes por día de la semana y rango de fechas; edición y borrado múltiple
- Lector QR para el celular: activación por botón, escaneo continuo y lectura por foto
- Avisos a todos los alumnos, a uno solo o a los inscriptos a una clase (app, WhatsApp y correo)
- **Configuración:** nombre, logo, contacto, packs, precios, vencimiento y avisos, con un **simulador de avisos** para demostraciones

---

## Tecnologías

| Capa | Herramienta |
|---|---|
| Backend | PHP 8 |
| Base de datos | MySQL / MariaDB |
| Frontend | HTML y CSS, sin frameworks |
| Pagos | MercadoPago Checkout Pro + webhook |
| WhatsApp | Green-API (también admite Twilio y la API oficial de Meta) |
| Correo | PHPMailer |
| Autenticación externa | Google OAuth 2.0 |
| Lectura de QR | html5-qrcode (alojado en el servidor) |
| Generación de QR | api.qrserver.com |
| Hosting | AlwaysData |

---

## Instalación

### 1. Clonar el repositorio

```bash
git clone https://github.com/juanjoybeto/training-point.git pulso
cd pulso
```

### 2. Instalar las dependencias

El directorio `vendor/` no está versionado. Se regenera con Composer:

```bash
composer install --no-dev
```

> En un hosting compartido, esto se ejecuta por SSH. Subir `vendor/` por FTP no es viable por la cantidad de archivos.

### 3. Crear la base de datos

Importar `pulso.sql` (estructura completa + datos iniciales) desde phpMyAdmin o por consola:

```bash
mysql -u usuario -p nombre_base < pulso.sql
```

Crea un administrador inicial: usuario `admin`, contraseña `admin123`. **Cambiala después del primer ingreso.**

### 4. Configurar la conexión

```bash
cp dbconn.example.php dbconn.php
```

Y completar los datos del servidor de base de datos.

### 5. Configurar las variables de entorno

```bash
cp .env.example .env
```

Completar `.env` con las credenciales de correo, Google OAuth, MercadoPago (`MP_ACCESS_TOKEN`) y el servicio de WhatsApp elegido.

### 6. Avisos diarios

Los recordatorios de clase y los avisos de vencimiento se mandan solos una vez por día, con la primera visita después de las 9:00. Para que salgan a horario fijo, programar una tarea diaria en el hosting:

```bash
php /ruta/al/sistema/tareas_diarias.php
```

### 7. Configurar el gimnasio

Entrar como administrador → **Configuración**: nombre, logo, contacto, packs, precios y activación de WhatsApp.

---

## Estructura de la base de datos

| Tabla | Contenido |
|---|---|
| `registronuevo` | Usuarios. Rol, créditos, vencimiento, apto médico con su estado, celular y preferencia de avisos |
| `clases` | Clases de cualquier actividad, con fecha, horario y capacidad |
| `inscripciones` | Relación alumno ↔ clase, con marca de recordatorio enviado |
| `asistencias` | Presentes registrados al escanear el QR |
| `abonos` | Historial de packs acreditados |
| `pagos` | Pagos con MercadoPago: monto, estado e identificador |
| `packs` | Packs y precios del gimnasio |
| `configuracion` | Datos del gimnasio y ajustes de avisos |
| `notificaciones` | Avisos de la campanita, con destinatario individual |
| `whatsapp_log` | Registro de cada WhatsApp enviado y su resultado |
| `recuperar` | Tokens temporales de recuperación de contraseña |

---

## Decisiones de diseño

**Los pagos se verifican con MercadoPago, nunca con el navegador.** El sistema consulta cada pago con el token del gimnasio, controla el monto y acredita una sola vez.

**El crédito se descuenta al reservar, no al escanear el QR.** Si el alumno cancela antes de dar presente, se le devuelve. Así nadie ocupa un lugar sin costo.

**El código QR identifica alumno y clase.** Su contenido es `alumno_id:clase_id:token`. Un QR único por alumno provocaba asistencias cruzadas cuando alguien tenía más de una reserva.

**Los avisos automáticos no se repiten.** Cada recordatorio y cada aviso de vencimiento se envía una sola vez.

**Los alumnos ven una ventana de 30 días.** El administrador puede cargar todo el año; los alumnos solo ven y reservan lo próximo.

**Todo lo propio de cada gimnasio se configura desde el panel.** Nombre, logo, contacto, packs, precios y avisos, sin tocar código.

---

## Documentación

- [Manual del alumno](https://newlogin.alwaysdata.net/manual_alumno.html)
- [Manual del administrador](https://newlogin.alwaysdata.net/manual_administrador.html)
- [Preguntas frecuentes](https://newlogin.alwaysdata.net/faq.html)
- [Documentación del proyecto](https://juanjoybeto.github.io/training-point-documentacion/)

---

## Seguridad

Este repositorio **no incluye**:

- `.env` — credenciales de correo, Google, MercadoPago y WhatsApp
- `dbconn.php` — datos de conexión a la base
- `uploads/` — aptos médicos de los alumnos, que son datos personales de salud

Están excluidos en `.gitignore` y no deben subirse en ningún caso.

---

## Autores

Proyecto de 7° año — Técnico en Informática Personal y Profesional
Instituto Leonardo Murialdo · 2026

- **Juan José Bosio** — [@juanjoybeto](https://github.com/juanjoybeto)
- **Joaquín Goyechea**

Materia: Proyecto, Diseño e Implementación de Sistemas Computacionales
