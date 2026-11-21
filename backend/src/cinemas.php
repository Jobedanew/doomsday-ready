<?php

function bundleSizesOf(int $cinemaId): array
{
    $stmt = db()->prepare('SELECT size FROM cinema_bundles WHERE cinema_id = ? ORDER BY size');
    $stmt->execute([$cinemaId]);
    return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

function loadCinema(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM cinemas WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) {
        return null;
    }
    return ['id' => (int)$row['id'], 'name' => $row['name'], 'bundleSizes' => bundleSizesOf((int)$row['id'])];
}

function createCinema(): void
{
    $b = readJsonBody();
    $errors = [];

    $name = $b['name'] ?? null;
    if (!is_string($name) || trim($name) === '' || mb_strlen(trim($name)) > 255) {
        $errors[] = 'name must be a non-empty string (max 255 characters)';
    }

    $sizes = $b['bundleSizes'] ?? null;
    if (!is_array($sizes) || count(array_filter($sizes, 'is_int')) !== count($sizes)) {
        $errors[] = 'bundleSizes must be an array of integers';
    } else {
        $sizes = array_values(array_unique($sizes)); // duplicate sizes are merged
        if (count($sizes) < 1 || count($sizes) > 10) {
            $errors[] = 'a cinema needs between 1 and 10 different bundle sizes';
        }
        foreach ($sizes as $s) {
            if ($s < 1 || $s > 500) {
                $errors[] = 'each bundle size must be between 1 and 500';
                break;
            }
        }
    }

    if ($errors) {
        sendError(400, 'VALIDATION_ERROR', implode('; ', $errors));
    }

    $name = trim($name);
    $pdo = db();
    $dup = $pdo->prepare('SELECT id FROM cinemas WHERE name = ?');
    $dup->execute([$name]);
    if ($dup->fetch()) {
        sendError(409, 'DUPLICATE_NAME', 'A cinema with this name already exists');
    }

    $pdo->beginTransaction();
    $pdo->prepare('INSERT INTO cinemas (name) VALUES (?)')->execute([$name]);
    $id = (int)$pdo->lastInsertId();
    $ins = $pdo->prepare('INSERT INTO cinema_bundles (cinema_id, size) VALUES (?, ?)');
    foreach ($sizes as $s) {
        $ins->execute([$id, $s]);
    }
    $pdo->commit();

    sendJson(loadCinema($id), 201);
}

function listCinemas(): void
{
    $out = [];
    foreach (db()->query('SELECT id FROM cinemas ORDER BY id') as $row) {
        $out[] = loadCinema((int)$row['id']);
    }
    sendJson($out);
}

function getBooking(int $id): void
{
    $cinema = loadCinema($id);
    if (!$cinema) {
        sendError(404, 'NOT_FOUND', 'Cinema not found');
    }

    $groupSize = filter_var($_GET['groupSize'] ?? null, FILTER_VALIDATE_INT);
    if ($groupSize === false || $groupSize < 1 || $groupSize > 10000) {
        sendError(400, 'VALIDATION_ERROR', 'groupSize must be an integer between 1 and 10000');
    }

    $result = minBundles($cinema['bundleSizes'], $groupSize);

    if ($result === null) {
        sendJson([
            'cinemaId' => $id,
            'groupSize' => $groupSize,
            'possible' => false,
            'message' => 'No exact combination of bundles exists for this group size',
        ]);
    }

    sendJson([
        'cinemaId' => $id,
        'groupSize' => $groupSize,
        'possible' => true,
        'totalBundles' => $result['totalBundles'],
        'bundles' => $result['bundles'],
    ]);
}