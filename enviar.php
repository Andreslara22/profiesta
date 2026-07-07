<?php
/**
 * enviar.php — Procesa el formulario de contacto de Pro Fiesta.
 * Envía las solicitudes de distribuidores por correo y responde en JSON.
 */

header('Content-Type: application/json; charset=utf-8');

// ---- Destino ----
$DESTINO = 'alara@cidproyectos.com.mx';
// Remitente del dominio (evita bloqueos de SPF/DMARC). El correo del cliente va en Reply-To.
$FROM    = 'no-reply@profiesta.com.mx';

function salir($success, $message, $code = 200) {
  http_response_code($code);
  echo json_encode(['success' => $success, 'message' => $message], JSON_UNESCAPED_UNICODE);
  exit;
}

// ---- Solo POST ----
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  salir(false, 'Método no permitido.', 405);
}

// ---- Anti-spam: honeypot (el campo "website" debe venir vacío) ----
if (!empty($_POST['website'])) {
  // Fingimos éxito para no darle pistas al bot.
  salir(true, 'Solicitud enviada.');
}

// ---- Recoger y limpiar datos ----
$limpiar = function ($v) {
  return trim(str_replace(["\r", "\n"], ' ', (string)$v)); // evita header injection
};

$nombre   = $limpiar($_POST['nombre']   ?? '');
$empresa  = $limpiar($_POST['empresa']  ?? '');
$email    = $limpiar($_POST['email']    ?? '');
$telefono = $limpiar($_POST['telefono'] ?? '');
$linea    = $limpiar($_POST['linea']    ?? '');
$mensaje  = trim($_POST['mensaje'] ?? '');

// ---- Validación ----
$errores = [];
if ($nombre === '')                                   $errores[] = 'nombre';
if (!filter_var($email, FILTER_VALIDATE_EMAIL))       $errores[] = 'email';
if (strlen(preg_replace('/\D/', '', $telefono)) !== 10) $errores[] = 'teléfono';
if ($linea === '')                                    $errores[] = 'línea de interés';

if ($errores) {
  salir(false, 'Revisa estos campos: ' . implode(', ', $errores) . '.', 422);
}

// ---- Armar el correo ----
$asunto = 'Nueva solicitud de distribuidor — ' . $nombre;

$cuerpo  = "Nueva solicitud desde profiesta.com.mx\n";
$cuerpo .= "----------------------------------------\n\n";
$cuerpo .= "Nombre:    $nombre\n";
$cuerpo .= "Empresa:   " . ($empresa !== '' ? $empresa : '(no indicada)') . "\n";
$cuerpo .= "Email:     $email\n";
$cuerpo .= "Teléfono:  $telefono\n";
$cuerpo .= "Línea:     $linea\n\n";
$cuerpo .= "Mensaje:\n" . ($mensaje !== '' ? $mensaje : '(sin mensaje)') . "\n\n";
$cuerpo .= "----------------------------------------\n";
$cuerpo .= "Fecha: " . date('d/m/Y H:i') . " (hora del servidor)\n";
$cuerpo .= "IP:    " . ($_SERVER['REMOTE_ADDR'] ?? 'desconocida') . "\n";

$headers  = "From: Pro Fiesta Web <$FROM>\r\n";
$headers .= "Reply-To: $nombre <$email>\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
$headers .= "Content-Transfer-Encoding: 8bit\r\n";

$asuntoEnc = '=?UTF-8?B?' . base64_encode($asunto) . '?=';

// -f fija el envelope-sender para mejorar la entrega en SiteGround
$ok = @mail($DESTINO, $asuntoEnc, $cuerpo, $headers, "-f $FROM");

if ($ok) {
  salir(true, 'Solicitud enviada. Te contactaremos pronto.');
} else {
  salir(false, 'No se pudo enviar en este momento. Intenta más tarde o escríbenos directo.', 500);
}
