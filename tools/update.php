<?php
// tools/update.php - brings an existing database up to date WITHOUT deleting any data.
// Every change to the tables after the first setup is a file in database/migrations/ (see its README.md).
// This runs the files that were not run yet (by name order) and writes each name in the `migrations` table.
//
//   php tools/update.php             run the new files
//   php tools/update.php --mark-all  only write all file names as done (setup.bat calls this after a fresh
//                                    schema.sql import, because schema.sql already has every change in it)
//   php tools/update.php --list      show which files are done / waiting
//
// Used by update.bat (Windows, double-click) and setup.bat. Command line only: a browser can't open it.

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Command line only.');
}

$root = dirname(__DIR__);
$file = is_file($root . '/config/database.php') ? $root . '/config/database.php' : $root . '/config/database.example.php';
$config = require $file;

try {
    $db = new PDO('mysql:host=' . $config['host'] . ';port=' . (int)$config['port'] . ';dbname=' . $config['database'] . ';charset=utf8mb4',
                  $config['username'], $config['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $ex) {
    fwrite(STDERR, "[X] Database e connect hoy nai: " . $ex->getMessage() . "\n");
    exit(1);
}
$db->exec("SET time_zone = '+06:00'");
$db->exec('CREATE TABLE IF NOT EXISTS migrations (
             name   VARCHAR(120) NOT NULL,
             run_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
             PRIMARY KEY (name)
           ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

$files = glob($root . '/database/migrations/*.sql') ?: [];
sort($files, SORT_STRING);
$done = $db->query('SELECT name FROM migrations')->fetchAll(PDO::FETCH_COLUMN);
$todo = array_values(array_filter($files, function ($f) use ($done) { return !in_array(basename($f), $done, true); }));
$mode = $argv[1] ?? '';

if ($mode === '--list') {
    foreach ($files as $f) {
        echo (in_array(basename($f), $done, true) ? '[done]    ' : '[waiting] ') . basename($f) . "\n";
    }
    exit(0);
}
if ($mode === '--mark-all') {
    $st = $db->prepare('INSERT IGNORE INTO migrations (name) VALUES (?)');
    foreach ($files as $f) {
        $st->execute([basename($f)]);
    }
    echo "[OK] " . count($files) . " ta update file 'done' hisebe likha holo.\n";
    exit(0);
}
if (!$todo) {
    echo "[OK] Database already up to date. Notun kichu nai.\n";
    exit(0);
}

foreach ($todo as $f) {
    $name = basename($f);
    // split into statements: each statement ends with ; at the end of a line. -- comment lines are skipped.
    $sql = preg_replace('/^\s*--.*$/m', '', file_get_contents($f));
    $parts = array_filter(array_map('trim', preg_split('/;\s*(\r?\n|$)/', $sql)));
    echo "  -> $name (" . count($parts) . " ta kaj)\n";
    try {
        foreach ($parts as $q) {
            $db->exec($q);
        }
        $db->prepare('INSERT INTO migrations (name) VALUES (?)')->execute([$name]);
    } catch (PDOException $ex) {
        // MySQL can't undo table changes (ALTER/CREATE), so stop here and say exactly where.
        fwrite(STDERR, "[X] $name e problem: " . $ex->getMessage() . "\n");
        fwrite(STDERR, "    Baki file gulo chalano hoy nai. Backup ache backups\\ folder e.\n");
        exit(1);
    }
}
echo "[OK] " . count($todo) . " ta update chalano holo.\n";
