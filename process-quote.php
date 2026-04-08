<?php
// =====================================
// Intercept East - Quote Form Handler (simple mail())
// - Saves optional upload into /site/uploads/
// - Emails HTML + plain text fallback
// - Redirects to thankyou.html
// =====================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: quote.html');
  exit;
}

function clean($v) { return trim((string)$v); }
function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

// ---------- Form fields ----------
$name        = clean($_POST['name'] ?? '');
$company     = clean($_POST['company'] ?? '');
$email       = clean($_POST['email'] ?? '');
$phone       = clean($_POST['phone'] ?? '');

$street      = clean($_POST['street'] ?? '');
$city        = clean($_POST['city'] ?? '');
$state       = clean($_POST['state'] ?? '');
$postcode    = clean($_POST['postcode'] ?? '');
$country     = clean($_POST['country'] ?? '');

$quoteType   = clean($_POST['quoteType'] ?? '');

$length      = clean($_POST['length'] ?? '');
$width       = clean($_POST['width'] ?? '');
$height      = clean($_POST['height'] ?? '');
$weight      = clean($_POST['weight'] ?? '');

$productCode = clean($_POST['product-code'] ?? '');
$quantity    = clean($_POST['quantity'] ?? '');

$notes       = clean($_POST['notes'] ?? '');

// ---------- Where to send ----------
$to      = "sales@intercepteast.com.au"; //
$subject = "New Quote Request from Website";

// ---------- Optional upload ----------
$uploadSaved = false;
$uploadNote  = "No file uploaded.";
$fileUrl     = "";

if (isset($_FILES['photo']) && is_array($_FILES['photo'])) {
  $err = $_FILES['photo']['error'] ?? UPLOAD_ERR_NO_FILE;

  if ($err !== UPLOAD_ERR_NO_FILE) {
    if ($err === UPLOAD_ERR_OK) {
      $originalName = $_FILES['photo']['name'] ?? '';
      $tmpPath      = $_FILES['photo']['tmp_name'] ?? '';
      $size         = (int)($_FILES['photo']['size'] ?? 0);

      // Limit 10MB
      if ($size > 10 * 1024 * 1024) {
        $uploadNote = "Upload too large (max 10MB).";
      } else {
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','gif','webp','mp4','mov','webm'];

        if (!in_array($ext, $allowed, true)) {
          $uploadNote = "Upload blocked: file type not allowed.";
        } else {

          // Save into /public_html/site/uploads/
          $uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR;

          if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0755, true);
          }

          $safeBase = preg_replace('/[^a-zA-Z0-9_-]+/', '-', pathinfo($originalName, PATHINFO_FILENAME));
          $safeBase = trim($safeBase, '-');
          if ($safeBase === '') $safeBase = 'upload';

          $newName  = $safeBase . '-' . date('Ymd-His') . '-' . substr(bin2hex(random_bytes(4)), 0, 8) . '.' . $ext;
          $destPath = $uploadDir . $newName;

          if (is_uploaded_file($tmpPath) && is_writable($uploadDir) && move_uploaded_file($tmpPath, $destPath)) {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host   = $_SERVER['HTTP_HOST'] ?? 'intercepteast.com.au';

            // Site is under /site/
            $fileUrl = $scheme . '://' . $host . '/site/uploads/' . rawurlencode($newName);

            $uploadSaved = true;
            $uploadNote  = "Upload saved: " . $newName;
          } else {
            $uploadNote = "Upload failed: could not save file (permissions/path).";
          }
        }
      }
    } else {
      $uploadNote = "Upload failed: PHP error code " . $err;
    }
  }
}

// ---------- Friendly quote type ----------
$quoteTypeLabel = ($quoteType === 'wrap')
  ? 'Wrap / Freight'
  : (($quoteType === 'product') ? 'Product' : ($quoteType !== '' ? $quoteType : 'Not specified'));

// ---------- Build HTML email ----------
$uploadRowHtml = $uploadSaved
  ? '<tr><td style="padding:6px 0;width:180px;"><strong>Upload:</strong></td><td style="padding:6px 0;"><a href="'.h($fileUrl).'" style="color:#2563eb;">View uploaded file</a><div style="color:#6b7280;font-size:12px;margin-top:4px;">'.h($uploadNote).'</div></td></tr>'
  : '<tr><td style="padding:6px 0;width:180px;"><strong>Upload:</strong></td><td style="padding:6px 0;">'.h($uploadNote).'</td></tr>';

$html = '
<!doctype html>
<html>
  <body style="margin:0;padding:0;background:#f6f7fb;font-family:Arial,Helvetica,sans-serif;">
    <div style="max-width:720px;margin:0 auto;padding:20px;">
      <div style="background:#111827;color:#fff;border-radius:14px;padding:18px 20px;">
        <div style="font-size:18px;font-weight:700;">New Quote Request</div>
        <div style="margin-top:6px;font-size:13px;opacity:0.9;">Submitted via intercepteast.com.au</div>
      </div>

      <div style="background:#ffffff;border-radius:14px;margin-top:12px;padding:18px 20px;border:1px solid #e8eaf1;">
        <div style="font-size:15px;font-weight:700;color:#111827;margin-bottom:10px;">Contact details</div>
        <table style="width:100%;border-collapse:collapse;font-size:14px;">
          <tr><td style="padding:6px 0;width:180px;"><strong>Name:</strong></td><td style="padding:6px 0;">'.h($name).'</td></tr>
          <tr><td style="padding:6px 0;"><strong>Company:</strong></td><td style="padding:6px 0;">'.h($company).'</td></tr>
          <tr><td style="padding:6px 0;"><strong>Email:</strong></td><td style="padding:6px 0;"><a href="mailto:'.h($email).'" style="color:#2563eb;">'.h($email).'</a></td></tr>
          <tr><td style="padding:6px 0;"><strong>Phone:</strong></td><td style="padding:6px 0;">'.h($phone).'</td></tr>
        </table>
      </div>

      <div style="background:#ffffff;border-radius:14px;margin-top:12px;padding:18px 20px;border:1px solid #e8eaf1;">
        <div style="font-size:15px;font-weight:700;color:#111827;margin-bottom:10px;">Shipping address</div>
        <table style="width:100%;border-collapse:collapse;font-size:14px;">
          <tr><td style="padding:6px 0;width:180px;"><strong>Street:</strong></td><td style="padding:6px 0;">'.h($street).'</td></tr>
          <tr><td style="padding:6px 0;"><strong>Suburb / City:</strong></td><td style="padding:6px 0;">'.h($city).'</td></tr>
          <tr><td style="padding:6px 0;"><strong>State:</strong></td><td style="padding:6px 0;">'.h($state).'</td></tr>
          <tr><td style="padding:6px 0;"><strong>Postcode:</strong></td><td style="padding:6px 0;">'.h($postcode).'</td></tr>
          <tr><td style="padding:6px 0;"><strong>Country:</strong></td><td style="padding:6px 0;">'.h($country).'</td></tr>
        </table>
      </div>

      <div style="background:#ffffff;border-radius:14px;margin-top:12px;padding:18px 20px;border:1px solid #e8eaf1;">
        <div style="font-size:15px;font-weight:700;color:#111827;margin-bottom:10px;">Quote details</div>
        <table style="width:100%;border-collapse:collapse;font-size:14px;">
          <tr><td style="padding:6px 0;width:180px;"><strong>Type:</strong></td><td style="padding:6px 0;">'.h($quoteTypeLabel).'</td></tr>';

if ($quoteType === 'wrap') {
  $html .= '
          <tr><td style="padding:6px 0;"><strong>Length (cm):</strong></td><td style="padding:6px 0;">'.h($length).'</td></tr>
          <tr><td style="padding:6px 0;"><strong>Width (cm):</strong></td><td style="padding:6px 0;">'.h($width).'</td></tr>
          <tr><td style="padding:6px 0;"><strong>Height (cm):</strong></td><td style="padding:6px 0;">'.h($height).'</td></tr>
          <tr><td style="padding:6px 0;"><strong>Weight (kg):</strong></td><td style="padding:6px 0;">'.h($weight).'</td></tr>';
}

if ($quoteType === 'product') {
  $html .= '
          <tr><td style="padding:6px 0;"><strong>Product code:</strong></td><td style="padding:6px 0;">'.h($productCode).'</td></tr>
          <tr><td style="padding:6px 0;"><strong>Quantity:</strong></td><td style="padding:6px 0;">'.h($quantity).'</td></tr>';
}

$html .= '
          '.$uploadRowHtml.'
        </table>

        <div style="margin-top:12px;padding-top:12px;border-top:1px solid #eef0f6;">
          <div style="font-weight:700;margin-bottom:6px;">Additional information</div>
          <div style="white-space:pre-wrap;line-height:1.45;color:#111827;">'.h($notes !== '' ? $notes : '(none)').'</div>
        </div>
      </div>

      <div style="color:#6b7280;font-size:12px;margin-top:12px;text-align:center;">
        Sent from intercepteast.com.au
      </div>
    </div>
  </body>
</html>';

// ---------- Plain text fallback ----------
$text  = "New Quote Request\r\n\r\n";
$text .= "CONTACT DETAILS\r\n";
$text .= "Name: {$name}\r\nCompany: {$company}\r\nEmail: {$email}\r\nPhone: {$phone}\r\n\r\n";
$text .= "SHIPPING ADDRESS\r\n";
$text .= "Street: {$street}\r\nSuburb/City: {$city}\r\nState: {$state}\r\nPostcode: {$postcode}\r\nCountry: {$country}\r\n\r\n";
$text .= "QUOTE DETAILS\r\n";
$text .= "Type: {$quoteTypeLabel}\r\n";

if ($quoteType === 'wrap') {
  $text .= "Length (cm): {$length}\r\nWidth (cm): {$width}\r\nHeight (cm): {$height}\r\nWeight (kg): {$weight}\r\n";
}
if ($quoteType === 'product') {
  $text .= "Product Code: {$productCode}\r\nQuantity: {$quantity}\r\n";
}

$text .= "\r\nUPLOAD\r\n";
$text .= ($uploadSaved ? "Link: {$fileUrl}\r\n" : "") . $uploadNote . "\r\n\r\n";
$text .= "ADDITIONAL INFORMATION\r\n" . ($notes !== '' ? $notes : '(none)') . "\r\n";

// ---------- Headers for HTML email ----------
$fromEmail = "no-reply@intercepteast.com.au"; // may still show server "on behalf of" – but will send
$headers  = "From: Intercept East Website <{$fromEmail}>\r\n";
if ($email !== '') {
  $headers .= "Reply-To: {$email}\r\n";
}
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: multipart/alternative; boundary=\"IEBOUNDARY\"\r\n";

// Multipart body
$message  = "--IEBOUNDARY\r\n";
$message .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n";
$message .= $text . "\r\n";
$message .= "--IEBOUNDARY\r\n";
$message .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
$message .= $html . "\r\n";
$message .= "--IEBOUNDARY--\r\n";

// Send
@mail($to, $subject, $message, $headers);

// Redirect
header("Location: thankyou.html");
exit;
