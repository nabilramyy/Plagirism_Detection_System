<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

header('Content-Type: application/json; charset=utf-8');

function json_error(string $message, int $statusCode = 400): void {
    http_response_code($statusCode);
    echo json_encode([
        'success' => false,
        'message' => $message,
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Invalid request method', 405);
}

$courseId     = isset($_POST['course_id']) ? (int) $_POST['course_id'] : 0;
$name         = $_POST['name']         ?? '';
$description  = $_POST['description']  ?? '';
$instructorId = $_POST['instructor_id'] ?? '';

if ($courseId <= 0) {
    json_error('Missing or invalid course_id');
}
if ($name === '' || $instructorId === '') {
    json_error('Name and instructor are required');
}

require_once __DIR__ . '/../includes/db.php'; // gives $conn (mysqli)

$sql = 'UPDATE courses
        SET name = ?,
            description = ?,
            instructor_id = ?
        WHERE id = ?';

$stmt = $conn->prepare($sql);

if ($stmt === false) {
    json_error('Failed to prepare SQL: ' . $conn->error, 500);
}

$stmt->bind_param('ssii', $name, $description, $instructorId, $courseId);

if (!$stmt->execute()) {
    json_error('Failed to execute SQL: ' . $stmt->error, 500);
}

if ($stmt->affected_rows === 0) {
    json_error('Course not found or no changes', 404);
}

$stmt->close();

echo json_encode([
    'success' => true,
    'message' => 'Course updated successfully',
]);
exit;
