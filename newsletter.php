<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
function reply($status, $ok) {
    http_response_code($status);
    echo json_encode(array('ok' => $ok));
    exit;
}
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') reply(405, false);
$value = $_POST['email'] ?? null;
if (!is_string($value)) reply(400, false);
$email = trim($value);
if (strlen($email) > 254 || !filter_var($email, FILTER_VALIDATE_EMAIL)) reply(400, false);

$root = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
if (!$root || dirname($root) === $root) reply(503, false);
$directory = dirname($root) . '/.dta-newsletter';
try {
    if (!is_dir($directory) && !@mkdir($directory, 0700) && !is_dir($directory)) reply(503, false);
    $db = new PDO('sqlite:' . $directory . '/subscriptions.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec('PRAGMA busy_timeout=3000');
    $db->exec('CREATE TABLE IF NOT EXISTS subscribers (email TEXT PRIMARY KEY COLLATE NOCASE, created_at TEXT DEFAULT CURRENT_TIMESTAMP)');
    $statement = $db->prepare('INSERT OR IGNORE INTO subscribers (email) VALUES (?)');
    $statement->execute(array($email));
    reply(200, true);
} catch (Throwable $error) {
    reply(503, false);
}
