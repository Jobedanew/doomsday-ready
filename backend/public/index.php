<?php 

/*

require __DIR__ . '/../src/helpers.php';
require __DIR__ . '/../src/db.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$path = rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

try {
    if ($method === 'GET' && $path === '/api/movies') {
        $rows = db()->query('SELECT id, title, release_year, runtime, watched FROM movies ORDER BY id')->fetchAll();
        sendJson($rows);
    } else {
        sendError(404, 'NOT_FOUND', 'Route not found');
    }
} catch (Throwable $e) {
    error_log($e->getMessage());
    sendError(500, 'SERVER_ERROR', 'Something went wrong on the server');
}*/



require __DIR__ . '/../src/helpers.php';
require __DIR__ . '/../src/db.php';
require __DIR__ . '/../src/algorithms.php';
require __DIR__ . '/../src/movies.php';
require __DIR__ . '/../src/cinemas.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$path = rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

try {
    if ($method === 'POST' && $path === '/api/movies') {
        createMovie();
    } elseif ($method === 'GET' && $path === '/api/movies') {
        listMovies();
    } elseif ($method === 'PATCH' && preg_match('#^/api/movies/(\d+)$#', $path, $m)) {
        updateMovie((int)$m[1]);
    } elseif ($method === 'GET' && preg_match('#^/api/movies/(\d+)/rewatch-order$#', $path, $m)) {
        getRewatchOrder((int)$m[1]);
    } elseif ($method === 'POST' && $path === '/api/cinemas') {
        createCinema();
    } elseif ($method === 'GET' && $path === '/api/cinemas') {
        listCinemas();
    } elseif ($method === 'GET' && preg_match('#^/api/cinemas/(\d+)/booking$#', $path, $m)) {
        getBooking((int)$m[1]);
    } else {
        sendError(404, 'NOT_FOUND', 'Route not found');
    }
} catch (Throwable $e) {
    error_log($e->getMessage());
    sendError(500, 'SERVER_ERROR', 'Something went wrong on the server');
}