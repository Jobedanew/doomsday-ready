<?php

function sendJson($data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function sendError(int $status, string $code, string $message): void
{
    sendJson(['error' => $code, 'message' => $message], $status);
}

function readJsonBody(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') {
        return [];
    }
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        sendError(400, 'INVALID_JSON', 'Request body must be a valid JSON object');
    }
    return $data;
}