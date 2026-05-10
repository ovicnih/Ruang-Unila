<?php
// Define APP_ROOT
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

require_once APP_ROOT . '/config/constants.php';
require_once APP_ROOT . '/config/database.php';

$db = Database::getInstance();
$events = $db->fetchAll('SELECT event_id, title, image, status FROM events WHERE status = "upcoming"');

echo "<pre>";
print_r($events);
echo "</pre>";
?>