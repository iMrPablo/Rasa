<?php
/**
 * Bootstrap - Entry Point for MVC Application
 */

// Load core classes
require_once __DIR__ . '/app/core/Database.php';
require_once __DIR__ . '/app/core/Helpers.php';
require_once __DIR__ . '/app/core/JalaliCalendar.php';
require_once __DIR__ . '/app/core/Router.php';
require_once __DIR__ . '/app/controllers/MainController.php';

// Initialize database
$db = new Database();
$db->initialize();

// Handle API requests
if (isset($_GET['api'])) {
    header('Content-Type: application/json; charset=utf-8');
    
    $action = $_GET['action'] ?? '';
    $payload = json_decode(file_get_contents('php://input'), true);
    if (!is_array($payload)) {
        $payload = [];
    }
    
    $controller = new MainController();
    $controller->handleRequest($action, $payload);
    exit;
}

// Render the view
$viewFile = __DIR__ . '/app/views/main.php';
if (file_exists($viewFile)) {
    require_once $viewFile;
} else {
    // If view doesn't exist yet, serve the original index.php content
    require_once __DIR__ . '/index.php.original';
}
