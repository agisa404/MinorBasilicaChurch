<?php
require_once __DIR__ . '/core.php';
$user = require_login();

$requestId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($requestId <= 0) {
    http_response_code(400);
    exit('Invalid request reference.');
}

$stmt = $conn->prepare('SELECT r.*, u.full_name, u.email FROM service_requests r JOIN users u ON u.id = r.user_id WHERE r.id = ? LIMIT 1');
$stmt->bind_param('i', $requestId);
$stmt->execute();
$request = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$request) {
    http_response_code(404);
    exit('Request not found.');
}

if ((int)$request['user_id'] !== (int)$user['id'] && !is_admin_or_staff($user)) {
    http_response_code(403);
    exit('Access denied.');
}

$details = json_decode((string)$request['details'], true);
if (!is_array($details)) {
    $details = ['details' => (string)$request['details']];
}

function pdf_escape(string $text): string
{
    $text = str_replace('\\', '\\\\', $text);
    $text = str_replace('(', '\\(', $text);
    $text = str_replace(')', '\\)', $text);
    $text = preg_replace('/\r\n|\r|\n/', ' ', $text) ?? $text;
    return $text;
}

function format_pdf_value(mixed $value): string
{
    if (is_array($value)) {
        if (!$value) {
            return '-';
        }
        $parts = [];
        foreach ($value as $item) {
            $parts[] = is_array($item) ? json_encode($item, JSON_UNESCAPED_UNICODE) : (string)$item;
        }
        return implode(', ', $parts);
    }
    $text = trim((string)$value);
    return $text !== '' ? $text : '-';
}

$lines = [];
$lines[] = 'MINOR BASILICA INFORMATION MANAGEMENT SYSTEM';
$lines[] = 'Filled Form PDF';
$lines[] = '';
$lines[] = 'Reference ID: #' . $request['id'];
$lines[] = 'Form Type: ' . $request['form_type'];
$lines[] = 'Title: ' . $request['title'];
$lines[] = 'Status: ' . $request['status'];
$lines[] = 'Submitted By: ' . ($request['full_name'] ?: $request['email']);
$lines[] = 'Submitted At: ' . $request['created_at'];
$lines[] = 'Requested Date: ' . ($request['requested_date'] ?: 'N/A');
$lines[] = 'Requested Time: ' . ($request['requested_time'] ? date('h:i A', strtotime($request['requested_time'])) : 'N/A');
$lines[] = '';
$lines[] = 'Form Fields:';

foreach ($details as $key => $value) {
    $label = ucwords(str_replace('_', ' ', (string)$key));
    $line = $label . ': ' . format_pdf_value($value);
    $wrapped = wordwrap($line, 100, "\n", true);
    foreach (explode("\n", $wrapped) as $part) {
        $lines[] = $part;
    }
}

$maxLines = 52;
if (count($lines) > $maxLines) {
    $lines = array_slice($lines, 0, $maxLines - 1);
    $lines[] = '... (truncated, view full details online)';
}

$stream = "BT\n/F1 11 Tf\n13 TL\n50 800 Td\n";
foreach ($lines as $line) {
    $stream .= '(' . pdf_escape($line) . ") Tj\nT*\n";
}
$stream .= "ET";

$objects = [];
$objects[] = "<< /Type /Catalog /Pages 2 0 R >>";
$objects[] = "<< /Type /Pages /Kids [3 0 R] /Count 1 >>";
$objects[] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>";
$objects[] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>";
$objects[] = "<< /Length " . strlen($stream) . " >>\nstream\n" . $stream . "\nendstream";

$pdf = "%PDF-1.4\n";
$offsets = [0];
for ($i = 0; $i < count($objects); $i++) {
    $objNum = $i + 1;
    $offsets[$objNum] = strlen($pdf);
    $pdf .= $objNum . " 0 obj\n" . $objects[$i] . "\nendobj\n";
}

$xrefPos = strlen($pdf);
$pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
$pdf .= "0000000000 65535 f \n";
for ($i = 1; $i <= count($objects); $i++) {
    $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
}
$pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\n";
$pdf .= "startxref\n" . $xrefPos . "\n%%EOF";

$fileName = 'form_request_' . $request['id'] . '.pdf';
$isInlineView = isset($_GET['view']) && $_GET['view'] === '1';
header('Content-Type: application/pdf');
header('Content-Disposition: ' . ($isInlineView ? 'inline' : 'attachment') . '; filename="' . $fileName . '"');
header('Content-Length: ' . strlen($pdf));
echo $pdf;
exit();
?>
