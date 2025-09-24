<?php
// --- CORS/Preflight (útil en Codespaces o si hay headers custom) ---
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(200);
  exit; // preflight OK
}

// --- GET: página de test para verificar que PHP corre ---
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  ?>
  <!doctype html><meta charset="utf-8">
  <h3>contact.php está activo</h3>
  <form method="post">
    <p><input name="name" placeholder="Tu nombre"></p>
    <p><input name="email" type="email" placeholder="tu@email.com"></p>
    <p><input name="subject" placeholder="Asunto de prueba"></p>
    <p><textarea name="message" placeholder="Mensaje"></textarea></p>
    <button>Enviar test</button>
  </form>
  <?php
  exit;
}

// --- Solo POST para enviar correo ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo 'Method Not Allowed';
  exit;
}

// ===== CONFIG =====
$receiving_email_address = 'jimunozacuna@gmail.com';
$LIB_PATH = '../assets/vendor/php-email-form/php-email-form.php';

// Incluye la librería de BootstrapMade (PRO)
if (!file_exists($LIB_PATH)) {
  http_response_code(500);
  echo 'Unable to load the "PHP Email Form" Library!';
  exit;
}
include $LIB_PATH;

$contact = new PHP_Email_Form;
$contact->ajax = true;

// Datos visitante
$visitor_name  = trim($_POST['name']    ?? 'Visitante');
$visitor_email = trim($_POST['email']   ?? '');
$visitor_subj  = trim($_POST['subject'] ?? 'Nuevo mensaje');
$visitor_msg   = trim($_POST['message'] ?? '');

// Remitente técnico (evita spoofing). El “reply-to” va al visitante.
$contact->to         = $receiving_email_address;
$contact->from_name  = $visitor_name;
$contact->from_email = 'no-reply@' . ($_SERVER['HTTP_HOST'] ?? 'example.com');
$contact->subject    = $visitor_subj;

if (filter_var($visitor_email, FILTER_VALIDATE_EMAIL)) {
  $contact->reply_to = $visitor_email;
}

// --- SMTP Gmail (puerto 587 TLS) ---
// OJO: pega la contraseña de aplicación SIN espacios.
$app_password = ''; // <- pegalá SIN espacios (ej: "abcd efgh..." => "abcdefghijklmnop")

$contact->smtp = array(
  'host'       => 'smtp.gmail.com',
  'username'   => 'info@gmail.com',
  'password'   => preg_replace('/\s+/', '', $app_password),
  'port'       => '587',
  // según versión de la librería, puede usar "encryption" o "secure"
  'encryption' => 'tls',
  // 'secure'   => 'tls',
);

// Cuerpo
$contact->add_message($visitor_name,  'From');
$contact->add_message($visitor_email, 'Email');
$contact->add_message($visitor_msg,   'Message', 10);

// Enviar
$result = $contact->send();
if ($result === true || $result === 'OK') {
  echo 'OK';
} else {
  http_response_code(500);
  echo is_string($result) ? $result : 'Error al enviar';
}
