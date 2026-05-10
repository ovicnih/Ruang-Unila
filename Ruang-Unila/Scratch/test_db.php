<?php
// Define APP_ROOT
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(dirname(__FILE__)));
}

// Test database connection
require_once APP_ROOT . '/config/database.php';

try {
    $db = Database::getInstance();
    $user = $db->fetchOne("SELECT user_id, username, email, password, role, status FROM users WHERE username = 'ovic' LIMIT 1");
    
    if ($user) {
        echo "<h2>User Found!</h2>";
        echo "<pre>";
        print_r($user);
        echo "</pre>";
        
        // Test password verification
        $testPassword = 'password123';
        $verify = password_verify($testPassword, $user['password']);
        echo "<p>Password verification: " . ($verify ? 'SUCCESS' : 'FAILED') . "</p>";
        
        echo "<p>Password hash preview: " . substr($user['password'], 0, 30) . "...</p>";
    } else {
        echo "<h2>User not found!</h2>";
    }
} catch (Exception $e) {
    echo "<h2>Error:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>
