<?php
require_once __DIR__ . '/core.php';

function attendance_api_respond(int $statusCode, array $payload): void
{
    header('Content-Type: application/json; charset=utf-8');
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit();
}

$user = current_user();
if (!$user) {
    attendance_api_respond(401, ['ok' => false, 'message' => 'Please login first.', 'entries' => []]);
}
if (!AttendanceService::canScan($user)) {
    attendance_api_respond(403, ['ok' => false, 'message' => 'Access denied. Admin only.', 'entries' => []]);
}

$action = trim((string)($_GET['action'] ?? ''));

if ($action === 'list') {
    $sessionId = (int)($_GET['session_id'] ?? 0);
    if ($sessionId <= 0) {
        attendance_api_respond(422, ['ok' => false, 'message' => 'A valid session is required.', 'entries' => []]);
    }

    $session = AttendanceService::sessionById($conn, $sessionId);
    if (!$session) {
        attendance_api_respond(404, ['ok' => false, 'message' => 'Attendance session not found.', 'entries' => []]);
    }

    attendance_api_respond(200, [
        'ok' => true,
        'session' => $session,
        'entries' => AttendanceService::recentEntries($conn, $sessionId),
    ]);
}

if ($action === 'scan') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        attendance_api_respond(405, ['ok' => false, 'message' => 'POST is required for scan requests.', 'entries' => []]);
    }

    $payload = json_decode(file_get_contents('php://input'), true);
    if (!is_array($payload)) {
        attendance_api_respond(422, ['ok' => false, 'message' => 'Invalid scan payload.', 'entries' => []]);
    }

    $sessionId = (int)($payload['session_id'] ?? 0);
    $qrText = trim((string)($payload['qr_text'] ?? ''));
    if ($sessionId <= 0 || $qrText === '') {
        attendance_api_respond(422, ['ok' => false, 'message' => 'Session and QR code are required.', 'entries' => []]);
    }

    $session = AttendanceService::sessionById($conn, $sessionId);
    if (!$session) {
        attendance_api_respond(404, ['ok' => false, 'message' => 'Attendance session not found.', 'entries' => []]);
    }
    if (($session['status'] ?? '') !== 'open') {
        attendance_api_respond(409, ['ok' => false, 'message' => 'This attendance session is already closed.', 'entries' => AttendanceService::recentEntries($conn, $sessionId)]);
    }

    $verified = AttendanceService::verifyQrToken($conn, $qrText);
    if (!$verified['ok']) {
        attendance_api_respond(422, ['ok' => false, 'message' => $verified['message'] ?? 'QR validation failed.', 'entries' => AttendanceService::recentEntries($conn, $sessionId)]);
    }

    $result = AttendanceService::recordScan($conn, $sessionId, $verified['user'], (int)$user['id']);
    if (!$result['ok']) {
        attendance_api_respond(422, ['ok' => false, 'message' => $result['message'] ?? 'Attendance could not be recorded.', 'entries' => AttendanceService::recentEntries($conn, $sessionId)]);
    }

    attendance_api_respond(200, $result);
}

attendance_api_respond(404, ['ok' => false, 'message' => 'Unknown attendance API action.', 'entries' => []]);
