<?php
// Database connection helper
// Returns a PDO instance connected to our SQLite database
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dbPath = __DIR__ . '/../db/arun.db';
        try {
            $pdo = new PDO('sqlite:' . $dbPath);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            // Enable WAL mode for better concurrent read performance
            $pdo->exec('PRAGMA journal_mode=WAL');
            $pdo->exec('PRAGMA foreign_keys=ON');
        } catch (PDOException $e) {
            // Re-throw for callers to handle
            throw $e;
        }
    }
    return $pdo;
}
