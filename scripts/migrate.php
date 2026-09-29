<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit('Not found.');
}

require_once dirname(__DIR__) . '/config/bootstrap.php';

try {
    $pdo = db();
    $pdo->exec("CREATE TABLE IF NOT EXISTS schema_migrations (
        migration VARCHAR(190) NOT NULL PRIMARY KEY,
        applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $files = glob(BASE_PATH . '/database/migrations/*.sql') ?: [];
    sort($files, SORT_STRING);
    $applied = $pdo->query('SELECT migration FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN);
    $record = $pdo->prepare('INSERT INTO schema_migrations (migration) VALUES (?)');

    foreach ($files as $file) {
        $name = basename($file);
        if (in_array($name, $applied, true)) {
            echo "Already applied: {$name}" . PHP_EOL;
            continue;
        }
        $sql = file_get_contents($file);
        if ($sql === false || trim($sql) === '') {
            throw new RuntimeException("Migration is empty or unreadable: {$name}");
        }
        $pdo->exec($sql);
        $record->execute([$name]);
        echo "Applied: {$name}" . PHP_EOL;
    }
    echo "Migration run complete." . PHP_EOL;
} catch (Throwable $exception) {
    fwrite(STDERR, 'Migration failed: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
