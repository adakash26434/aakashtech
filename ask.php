<?php
require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(array('ok' => false, 'error' => 'Send a question from the public page.'));
    exit;
}

if (!csrf_is_valid()) {
    http_response_code(403);
    echo json_encode(array('ok' => false, 'error' => 'The page expired. Refresh it and ask again.'));
    exit;
}

$question = isset($_POST['question']) ? (string) $_POST['question'] : '';
$result = site_ai_answer($conn, $question);
if (empty($result['ok'])) {
    http_response_code(422);
    echo json_encode(array('ok' => false, 'error' => $result['error']));
    exit;
}

echo json_encode(array('ok' => true, 'answer' => $result['answer']));
