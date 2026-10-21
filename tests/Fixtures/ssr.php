<?php

$page = json_decode((string) file_get_contents('php://input'), true);
header('Content-Type: application/json');

if (! is_array($page) || $page['component'] === 'Broken') {
    http_response_code(500);
    echo json_encode(['error' => 'render failed', 'hint' => '', 'timestamp' => 0]);

    return;
}

echo json_encode([
    'head' => ['<title>'.$page['component'].'</title>', '<meta name="ssr" content="1">'],
    'body' => '<div data-server-rendered="true" id="app">'.$page['component'].'</div>',
]);
