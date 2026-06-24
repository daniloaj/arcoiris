<?php
// ============================================================
//  enviar.php - Procesa formularios usando PHPMailer con SMTP
//  Lee configuración desde .env (seguro para GitHub)
// ============================================================

// --- Cargar variables de entorno desde .env ---
function loadEnv($path) {
    if (!file_exists($path)) {
        throw new Exception("Archivo .env no encontrado");
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        list($key, $value) = explode('=', $line, 2);
        putenv(trim($key) . '=' . trim($value));
    }
}

// Cargar .env
try {
    loadEnv(__DIR__ . '/.env');
} catch (Exception $e) {
    die('Error: Archivo .env no encontrado. Por favor, configura tus credenciales SMTP.');
}

// --- Obtener variables de entorno ---
define('SMTP_HOST',   getenv('SMTP_HOST') ?: 'smtp.gmail.com');
define('SMTP_USER',   getenv('SMTP_USER') ?: '');
define('SMTP_PASS',   getenv('SMTP_PASS') ?: '');
define('SMTP_PORT',   getenv('SMTP_PORT') ?: 587);
define('SMTP_SECURE', getenv('SMTP_SECURE') ?: 'tls');

define('DESTINO_EMAIL', getenv('DESTINO_EMAIL') ?: '');
define('DESTINO_NOMBRE', getenv('DESTINO_NOMBRE') ?: 'Farmacia Acoiris');

// Verificar configuración
if (empty(SMTP_USER) || empty(SMTP_PASS) || empty(DESTINO_EMAIL)) {
    die('Error: Configuración SMTP incompleta. Revisa tu archivo .env');
}

// --- Incluir PHPMailer (con Composer) ---
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// --- FUNCIÓN PARA ENVIAR CORREO ---
function enviarCorreo($asunto, $cuerpoHtml, $cuerpoTexto = '') {
    $mail = new PHPMailer(true);

    try {
        // Configuración del servidor SMTP
        $mail->SMTPDebug = SMTP::DEBUG_OFF;
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port       = SMTP_PORT;

        // Remitente y destinatario
        $mail->setFrom(SMTP_USER, 'Farmacia Acoiris - Web');
        $mail->addAddress(DESTINO_EMAIL, DESTINO_NOMBRE);
        $mail->addReplyTo(SMTP_USER, 'Farmacia Acoiris');

        // Contenido
        $mail->isHTML(true);
        $mail->Subject = $asunto;
        $mail->Body    = $cuerpoHtml;
        $mail->AltBody = $cuerpoTexto ?: strip_tags($cuerpoHtml);

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Error al enviar correo: " . $mail->ErrorInfo);
        return false;
    }
}

// --- PROCESAR FORMULARIO ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $tipo = $_POST['tipo'] ?? '';

    // Variables comunes
    $nombre   = trim($_POST['nombre'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');

    $exito = false;
    $mensaje = '';

    if ($tipo === 'cita') {
        // --- FORMULARIO DE CITA ---
        $servicio     = trim($_POST['servicio'] ?? '');
        $fecha        = trim($_POST['fecha'] ?? '');
        $hora         = trim($_POST['hora'] ?? 'No especificada');
        $observaciones = trim($_POST['observaciones'] ?? '');

        if (empty($nombre) || empty($telefono) || empty($servicio) || empty($fecha)) {
            $mensaje = '⚠️ Faltan campos obligatorios. Por favor, completa todos los datos.';
            $exito = false;
        } else {
            $asunto = '📋 NUEVA CITA - Farmacia Acoiris';
            $cuerpo = "
                <h2>📋 Nueva solicitud de cita</h2>
                <p><strong>👤 Nombre:</strong> " . htmlspecialchars($nombre) . "</p>
                <p><strong>📞 Teléfono:</strong> " . htmlspecialchars($telefono) . "</p>
                <p><strong>📧 Email:</strong> " . htmlspecialchars($email ?: 'No indicado') . "</p>
                <p><strong>💊 Servicio:</strong> " . htmlspecialchars($servicio) . "</p>
                <p><strong>📅 Fecha:</strong> " . htmlspecialchars($fecha) . "</p>
                <p><strong>🕐 Hora:</strong> " . htmlspecialchars($hora) . "</p>
                <p><strong>📝 Observaciones:</strong> " . nl2br(htmlspecialchars($observaciones ?: 'Ninguna')) . "</p>
                <hr>
                <p style='color: #1a5a6e;'>✉️ Este mensaje fue enviado desde el formulario de citas de Farmacia Acoiris.</p>
            ";

            if (enviarCorreo($asunto, $cuerpo)) {
                $exito = true;
                $mensaje = '✅ ¡Cita solicitada con éxito! En breve recibirás la confirmación.';
            } else {
                $mensaje = '❌ Error al enviar la solicitud. Por favor, intenta de nuevo.';
            }
        }

    } elseif ($tipo === 'contacto') {
        // --- FORMULARIO DE CONTACTO ---
        $mensajeContacto = trim($_POST['mensaje'] ?? '');

        if (empty($nombre) || empty($email) || empty($mensajeContacto)) {
            $mensaje = '⚠️ Por favor, completa todos los campos del formulario de contacto.';
            $exito = false;
        } else {
            $asunto = '📩 NUEVO MENSAJE - Farmacia Acoiris';
            $cuerpo = "
                <h2>📩 Nuevo mensaje de contacto</h2>
                <p><strong>👤 Nombre:</strong> " . htmlspecialchars($nombre) . "</p>
                <p><strong>📧 Email:</strong> " . htmlspecialchars($email) . "</p>
                <p><strong>📝 Mensaje:</strong></p>
                <p style='background: #f4fafd; padding: 15px; border-radius: 12px;'>" . nl2br(htmlspecialchars($mensajeContacto)) . "</p>
                <hr>
                <p style='color: #1a5a6e;'>✉️ Este mensaje fue enviado desde el formulario de contacto de Farmacia Acoiris.</p>
            ";

            if (enviarCorreo($asunto, $cuerpo)) {
                $exito = true;
                $mensaje = '✅ ¡Mensaje enviado con éxito! Te responderemos en menos de 24 horas.';
            } else {
                $mensaje = '❌ Error al enviar el mensaje. Por favor, intenta de nuevo.';
            }
        }

    } else {
        $mensaje = '⚠️ Tipo de formulario no válido.';
        $exito = false;
    }

    // --- REDIRIGIR CON MENSAJE ---
    $status = $exito ? 'ok' : 'error';
    $msgEncoded = urlencode($mensaje);
    header("Location: index.html?status=$status&msg=$msgEncoded");
    exit;
}