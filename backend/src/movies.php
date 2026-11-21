<?php

function formatMovie(array $row, array $prereqIds): array
{
    return [
        'id' => (int)$row['id'],
        'title' => $row['title'],
        'releaseYear' => (int)$row['release_year'],
        'runtime' => (int)$row['runtime'],
        'watched' => (bool)$row['watched'],
        'prerequisites' => $prereqIds,
    ];
}


function loadGraph(): array
{
    $graph = [];
    $rows = db()->query('SELECT movie_id, prerequisite_id FROM movie_prerequisites');
    foreach ($rows as $r) {
        $graph[(int)$r['movie_id']][] = (int)$r['prerequisite_id'];
    }
    return $graph;
}


function loadAllMovies(): array
{
    $graph = loadGraph();
    $movies = [];
    foreach (db()->query('SELECT * FROM movies ORDER BY id') as $row) {
        $id = (int)$row['id'];
        $movies[$id] = formatMovie($row, $graph[$id] ?? []);
    }
    return $movies;
}

function loadMovie(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM movies WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) {
        return null;
    }
    $p = db()->prepare('SELECT prerequisite_id FROM movie_prerequisites WHERE movie_id = ? ORDER BY prerequisite_id');
    $p->execute([$id]);
    $ids = array_map('intval', $p->fetchAll(PDO::FETCH_COLUMN));
    return formatMovie($row, $ids);
}

function titleTaken(string $title, ?int $exceptId): bool
{
    $stmt = db()->prepare('SELECT id FROM movies WHERE title = ? AND id <> ?');
    $stmt->execute([$title, $exceptId ?? 0]);
    return (bool)$stmt->fetch();
}

function allMoviesExist(array $ids): bool
{
    if (!$ids) {
        return true;
    }
    $marks = implode(',', array_fill(0, count($ids), '?'));
    $stmt = db()->prepare("SELECT COUNT(*) FROM movies WHERE id IN ($marks)");
    $stmt->execute($ids);
    return (int)$stmt->fetchColumn() === count($ids);
}

function savePrerequisites(int $movieId, array $ids): void
{
    $pdo = db();
    $pdo->prepare('DELETE FROM movie_prerequisites WHERE movie_id = ?')->execute([$movieId]);
    $ins = $pdo->prepare('INSERT INTO movie_prerequisites (movie_id, prerequisite_id) VALUES (?, ?)');
    foreach ($ids as $pid) {
        $ins->execute([$movieId, $pid]);
    }
}


function validateMovieInput(array $b, bool $isCreate): array
{
    $errors = [];
    $clean = [];

    if ($isCreate || array_key_exists('title', $b)) {
        $t = $b['title'] ?? null;
        if (!is_string($t) || trim($t) === '' || mb_strlen(trim($t)) > 255) {
            $errors[] = 'title must be a non-empty string (max 255 characters)';
        } else {
            $clean['title'] = trim($t);
        }
    }

    if ($isCreate || array_key_exists('releaseYear', $b)) {
        $y = $b['releaseYear'] ?? null;
        if (!is_int($y) || $y < 1888 || $y > 2100) {
            $errors[] = 'releaseYear must be an integer between 1888 and 2100';
        } else {
            $clean['releaseYear'] = $y;
        }
    }

    if ($isCreate || array_key_exists('runtime', $b)) {
        $r = $b['runtime'] ?? null;
        if (!is_int($r) || $r < 1 || $r > 300) {
            $errors[] = 'runtime must be an integer between 1 and 300';
        } else {
            $clean['runtime'] = $r;
        }
    }

    if (array_key_exists('watched', $b)) {
        if (!is_bool($b['watched'])) {
            $errors[] = 'watched must be true or false';
        } else {
            $clean['watched'] = $b['watched'];
        }
    }

    if (array_key_exists('prerequisites', $b)) {
        $p = $b['prerequisites'];
        if (!is_array($p) || count(array_filter($p, 'is_int')) !== count($p)) {
            $errors[] = 'prerequisites must be an array of movie ids';
        } else {
            $clean['prerequisites'] = array_values(array_unique($p));
        }
    }

    return [$clean, $errors];
}

// -------------------------------------------------

function createMovie(): void
{
    [$data, $errors] = validateMovieInput(readJsonBody(), true);
    if ($errors) {
        sendError(400, 'VALIDATION_ERROR', implode('; ', $errors));
    }
    $prereqs = $data['prerequisites'] ?? [];

    if (titleTaken($data['title'], null)) {
        sendError(409, 'DUPLICATE_TITLE', 'A movie with this title already exists');
    }
    if (!allMoviesExist($prereqs)) {
        sendError(400, 'PREREQUISITE_NOT_FOUND', 'One or more prerequisite movies do not exist');
    }
  
    $pdo = db();
    $pdo->beginTransaction();
    $stmt = $pdo->prepare('INSERT INTO movies (title, release_year, runtime, watched) VALUES (?, ?, ?, ?)');
    $stmt->execute([$data['title'], $data['releaseYear'], $data['runtime'], (int)($data['watched'] ?? false)]);
    $id = (int)$pdo->lastInsertId();
    savePrerequisites($id, $prereqs);
    $pdo->commit();

    sendJson(loadMovie($id), 201);
}

function listMovies(): void
{
    sendJson(array_values(loadAllMovies()));
}

function updateMovie(int $id): void
{
    if (!loadMovie($id)) {
        sendError(404, 'NOT_FOUND', 'Movie not found');
    }
    [$data, $errors] = validateMovieInput(readJsonBody(), false);
    if ($errors) {
        sendError(400, 'VALIDATION_ERROR', implode('; ', $errors));
    }
    if (!$data) {
        sendError(400, 'VALIDATION_ERROR', 'Nothing to update');
    }

    if (isset($data['title']) && titleTaken($data['title'], $id)) {
        sendError(409, 'DUPLICATE_TITLE', 'A movie with this title already exists');
    }

    if (array_key_exists('prerequisites', $data)) {
        if (!allMoviesExist($data['prerequisites'])) {
            sendError(400, 'PREREQUISITE_NOT_FOUND', 'One or more prerequisite movies do not exist');
        }

        $graph = loadGraph();
        $all = loadAllMovies();   // id => movie, used to look up titles

        foreach ($data['prerequisites'] as $pid) {
            if (!canAddPrerequisite($id, $pid, $graph)) {
                $movieTitle = $all[$id]['title'];
                $prereqTitle = $all[$pid]['title'];

                if ($id === $pid) {
                    $msg = "You can not set \"$movieTitle\" as a prerequisite of itself.";
                } else {
                    $msg = "You can not set \"$prereqTitle\" as a prerequisite of \"$movieTitle\" because it would create a circular dependency.";
                }
                sendError(409, 'CIRCULAR_DEPENDENCY', $msg);
            }
        }
    }

    $pdo = db();
    $pdo->beginTransaction();

    $sets = [];
    $vals = [];
    $columns = ['title' => 'title', 'releaseYear' => 'release_year', 'runtime' => 'runtime'];
    foreach ($columns as $key => $col) {
        if (array_key_exists($key, $data)) {
            $sets[] = "$col = ?";
            $vals[] = $data[$key];
        }
    }
    if (array_key_exists('watched', $data)) {
        $sets[] = 'watched = ?';
        $vals[] = (int)$data['watched'];
    }
    if ($sets) {
        $vals[] = $id;
        $pdo->prepare('UPDATE movies SET ' . implode(', ', $sets) . ' WHERE id = ?')->execute($vals);
    }
    if (array_key_exists('prerequisites', $data)) {
        savePrerequisites($id, $data['prerequisites']);
    }
    $pdo->commit();

    sendJson(loadMovie($id));
}

function getRewatchOrder(int $id): void
{
    $all = loadAllMovies();
    if (!isset($all[$id])) {
        sendError(404, 'NOT_FOUND', 'Movie not found');
    }

    $ids = rewatchOrder($id, loadGraph());

    $order = [];
    $watchedCount = 0;
    $remainingMinutes = 0;
    foreach ($ids as $mid) {
        $m = $all[$mid];
        $order[] = $m;
        if ($m['watched']) {
            $watchedCount++;
        } else {
            $remainingMinutes += $m['runtime'];
        }
    }

    sendJson([
        'movieId' => $id,
        'order' => $order,
        'totalCount' => count($order),
        'watchedCount' => $watchedCount,
        'remainingMinutes' => $remainingMinutes,
    ]);
}