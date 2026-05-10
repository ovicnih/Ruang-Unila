<?php
/**
 * REST API - Ruang Unila
 * 
 * API endpoints untuk mobile app dengan JWT authentication.
 * 
 * @package RuangUnila
 * @subpackage API
 * @version 1.0.0
 */

// Define APP_ROOT jika belum didefinisikan
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

// Cegah akses langsung
if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

// Include konfigurasi
require_once APP_ROOT . '/config/constants.php';
require_once APP_ROOT . '/config/database.php';
require_once APP_ROOT . '/includes/functions.php';

// Include classes
require_once APP_ROOT . '/classes/User.php';
require_once APP_ROOT . '/classes/News.php';
require_once APP_ROOT . '/classes/Event.php';

// Set header JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Inisialisasi
$db = Database::getInstance();

// Fungsi helper untuk response JSON
function jsonResponse($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

// Fungsi helper untuk error response
function jsonError($message, $code = 400) {
    jsonResponse(['error' => true, 'message' => $message], $code);
}

// Fungsi helper untuk success response
function jsonSuccess($data, $message = 'Success') {
    jsonResponse(['error' => false, 'message' => $message, 'data' => $data]);
}

// JWT Authentication (Simple Implementation)
function generateJWT($userId, $secret = 'ruangunila_secret_key_2026') {
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    $payload = json_encode([
        'user_id' => $userId,
        'iat' => time(),
        'exp' => time() + (24 * 60 * 60) // 24 jam
    ]);
    
    $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
    $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
    
    $signature = hash_hmac('sha256', $base64Header . '.' . $base64Payload, $secret, true);
    $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
    
    return $base64Header . '.' . $base64Payload . '.' . $base64Signature;
}

function verifyJWT($token, $secret = 'ruangunila_secret_key_2026') {
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        return null;
    }
    
    [$base64Header, $base64Payload, $base64Signature] = $parts;
    
    $signature = base64_decode(str_replace(['-', '_'], ['+', '/'], $base64Signature));
    $expectedSignature = hash_hmac('sha256', $base64Header . '.' . $base64Payload, $secret, true);
    
    if (!hash_equals($signature, $expectedSignature)) {
        return null;
    }
    
    $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $base64Payload)), true);
    
    if ($payload['exp'] < time()) {
        return null;
    }
    
    return $payload;
}

// Get Authorization header
function getAuthHeader() {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? '';
    
    if (empty($authHeader) || !str_starts_with($authHeader, 'Bearer ')) {
        return null;
    }
    
    return substr($authHeader, 7);
}

// Verify authentication
function authenticate() {
    $token = getAuthHeader();
    
    if (!$token) {
        jsonError('Authorization token required', 401);
    }
    
    $payload = verifyJWT($token);
    
    if (!$payload) {
        jsonError('Invalid or expired token', 401);
    }
    
    return $payload['user_id'];
}

// Get request method and path
$method = $_SERVER['REQUEST_METHOD'];
$path = $_GET['route'] ?? '';
$pathParts = explode('/', trim($path, '/'));

// ============================================
// API ROUTES
// ============================================

// POST /api/login - Login
if ($method === 'POST' && $path === 'login') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $email = $input['email'] ?? '';
    $password = $input['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        jsonError('Email and password are required');
    }
    
    // Validasi email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonError('Invalid email format');
    }
    
    // Cek user
    $sql = "SELECT user_id, username, email, password, full_name, role, profile_photo, status 
            FROM users WHERE email = :email LIMIT 1";
    $user = $db->fetchOne($sql, [':email' => $email]);
    
    if (!$user) {
        jsonError('Invalid email or password', 401);
    }
    
    // Verifikasi password
    if (!password_verify($password, $user['password'])) {
        jsonError('Invalid email or password', 401);
    }
    
    // Cek status
    if ($user['status'] !== 'active') {
        jsonError('Account is inactive', 403);
    }
    
    // Generate token
    $token = generateJWT($user['user_id']);
    
    jsonSuccess([
        'token' => $token,
        'user' => [
            'user_id' => $user['user_id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'full_name' => $user['full_name'],
            'role' => $user['role'],
            'profile_photo' => $user['profile_photo']
        ]
    ], 'Login successful');
}

// POST /api/register - Register
if ($method === 'POST' && $path === 'register') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $username = trim($input['username'] ?? '');
    $email = trim($input['email'] ?? '');
    $password = $input['password'] ?? '';
    $fullName = trim($input['full_name'] ?? '');
    $role = $input['role'] ?? 'student';
    
    // Validasi
    if (empty($username) || empty($email) || empty($password) || empty($fullName)) {
        jsonError('All fields are required');
    }
    
    // Validasi email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonError('Invalid email format');
    }
    
    // Validasi username
    if (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username)) {
        jsonError('Username must be 3-50 characters (letters, numbers, underscore)');
    }
    
    // Validasi password
    if (strlen($password) < 8) {
        jsonError('Password must be at least 8 characters');
    }
    
    // Validasi role
    $allowedRoles = ['student', 'organisation'];
    if (!in_array($role, $allowedRoles)) {
        $role = 'student';
    }
    
    // Cek email exists
    $sql = "SELECT COUNT(*) as count FROM users WHERE email = :email";
    $result = $db->fetchOne($sql, [':email' => $email]);
    if ($result['count'] > 0) {
        jsonError('Email already registered');
    }
    
    // Cek username exists
    $sql = "SELECT COUNT(*) as count FROM users WHERE username = :username";
    $result = $db->fetchOne($sql, [':username' => $username]);
    if ($result['count'] > 0) {
        jsonError('Username already taken');
    }
    
    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    
    // Insert user
    $sql = "INSERT INTO users (username, email, password, full_name, role, status, created_at) 
            VALUES (:username, :email, :password, :full_name, :role, 'active', NOW())";
    
    $stmt = $db->executeQuery($sql, [
        ':username' => $username,
        ':email' => $email,
        ':password' => $hashedPassword,
        ':full_name' => $fullName,
        ':role' => $role
    ]);
    
    if (!$stmt) {
        jsonError('Registration failed', 500);
    }
    
    $userId = $db->lastInsertId();
    $token = generateJWT($userId);
    
    jsonSuccess([
        'token' => $token,
        'user' => [
            'user_id' => $userId,
            'username' => $username,
            'email' => $email,
            'full_name' => $fullName,
            'role' => $role
        ]
    ], 'Registration successful');
}

// GET /api/news - Get all news
if ($method === 'GET' && $path === 'news') {
    $page = max(1, (int)($_GET['page'] ?? 1));
    $perPage = min(50, max(1, (int)($_GET['per_page'] ?? 10)));
    $category = $_GET['category'] ?? null;
    $search = $_GET['search'] ?? null;
    
    $news = new News();
    
    $sql = "SELECT n.news_id, n.title, n.content, n.category, n.image, n.views, n.created_at,
                   u.full_name as author_name
            FROM news n
            LEFT JOIN users u ON n.author_id = u.user_id
            WHERE n.status = 'published'";
    
    $params = [];
    
    if ($category) {
        $sql .= " AND n.category = :category";
        $params[':category'] = $category;
    }
    
    if ($search) {
        $sql .= " AND (n.title LIKE :search OR n.content LIKE :search)";
        $params[':search'] = '%' . $search . '%';
    }
    
    // Count total
    $countSql = str_replace(
        "SELECT n.news_id, n.title, n.content, n.category, n.image, n.views, n.created_at, u.full_name as author_name",
        "SELECT COUNT(*) as total",
        $sql
    );
    $totalResult = $db->fetchOne($countSql, $params);
    $total = $totalResult ? (int) $totalResult['total'] : 0;
    
    // Get paginated data
    $sql .= " ORDER BY n.created_at DESC LIMIT :limit OFFSET :offset";
    $params[':limit'] = $perPage;
    $params[':offset'] = ($page - 1) * $perPage;
    
    $newsList = $db->fetchAll($sql, $params);
    
    jsonSuccess([
        'news' => $newsList,
        'pagination' => [
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => ceil($total / $perPage)
        ]
    ]);
}

// GET /api/news/{id} - Get news detail
if ($method === 'GET' && count($pathParts) === 2 && $pathParts[0] === 'news') {
    $newsId = (int) $pathParts[1];
    
    $news = new News();
    $newsItem = $news->getById($newsId);
    
    if (!$newsItem || $newsItem['status'] !== 'published') {
        jsonError('News not found', 404);
    }
    
    // Increment views
    $news->incrementViews($newsId);
    
    // Get related news
    $relatedNews = $news->getRelated($newsId, $newsItem['category'], 3);
    
    jsonSuccess([
        'news' => $newsItem,
        'related_news' => $relatedNews
    ]);
}

// GET /api/events - Get all events
if ($method === 'GET' && $path === 'events') {
    $page = max(1, (int)($_GET['page'] ?? 1));
    $perPage = min(50, max(1, (int)($_GET['per_page'] ?? 10)));
    $status = $_GET['status'] ?? 'upcoming';
    
    $event = new Event();
    
    $sql = "SELECT e.event_id, e.title, e.description, e.location, e.event_date, 
                   e.registration_deadline, e.max_participants, e.fee, e.image, e.status,
                   u.full_name as organizer_name
            FROM events e
            LEFT JOIN users u ON e.organizer_id = u.user_id
            WHERE 1=1";
    
    $params = [];
    
    if ($status === 'upcoming') {
        $sql .= " AND e.event_date >= NOW() AND e.status IN ('upcoming', 'ongoing')";
    } elseif ($status === 'completed') {
        $sql .= " AND e.status = 'completed'";
    } elseif ($status === 'all') {
        // No filter
    } else {
        $sql .= " AND e.status = :status";
        $params[':status'] = $status;
    }
    
    // Count total
    $countSql = str_replace(
        "SELECT e.event_id, e.title, e.description, e.location, e.event_date, e.registration_deadline, e.max_participants, e.fee, e.image, e.status, u.full_name as organizer_name",
        "SELECT COUNT(*) as total",
        $sql
    );
    $totalResult = $db->fetchOne($countSql, $params);
    $total = $totalResult ? (int) $totalResult['total'] : 0;
    
    // Get paginated data
    $sql .= " ORDER BY e.event_date ASC LIMIT :limit OFFSET :offset";
    $params[':limit'] = $perPage;
    $params[':offset'] = ($page - 1) * $perPage;
    
    $eventsList = $db->fetchAll($sql, $params);
    
    // Add registrations count
    foreach ($eventsList as &$eventItem) {
        $regSql = "SELECT COUNT(*) as count FROM event_registrations WHERE event_id = :event_id";
        $regResult = $db->fetchOne($regSql, [':event_id' => $eventItem['event_id']]);
        $eventItem['registrations_count'] = $regResult ? (int) $regResult['count'] : 0;
        $eventItem['remaining_quota'] = $eventItem['max_participants'] - $eventItem['registrations_count'];
    }
    
    jsonSuccess([
        'events' => $eventsList,
        'pagination' => [
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => ceil($total / $perPage)
        ]
    ]);
}

// GET /api/events/{id} - Get event detail
if ($method === 'GET' && count($pathParts) === 2 && $pathParts[0] === 'events') {
    $eventId = (int) $pathParts[1];
    
    $event = new Event();
    $eventItem = $event->getById($eventId);
    
    if (!$eventItem) {
        jsonError('Event not found', 404);
    }
    
    // Get registrations count
    $regSql = "SELECT COUNT(*) as count FROM event_registrations WHERE event_id = :event_id";
    $regResult = $db->fetchOne($regSql, [':event_id' => $eventId]);
    $eventItem['registrations_count'] = $regResult ? (int) $regResult['count'] : 0;
    $eventItem['remaining_quota'] = $eventItem['max_participants'] - $eventItem['registrations_count'];
    
    jsonSuccess([
        'event' => $eventItem
    ]);
}

// POST /api/events/{id}/register - Register for event
if ($method === 'POST' && count($pathParts) === 3 && $pathParts[0] === 'events' && $pathParts[2] === 'register') {
    $userId = authenticate();
    $eventId = (int) $pathParts[1];
    
    $event = new Event();
    $eventItem = $event->getById($eventId);
    
    if (!$eventItem) {
        jsonError('Event not found', 404);
    }
    
    // Check if already registered
    if ($event->isUserRegistered($eventId, $userId)) {
        jsonError('Already registered for this event');
    }
    
    // Check quota
    $remainingQuota = $event->getRemainingQuota($eventId);
    if ($remainingQuota <= 0) {
        jsonError('Event is full');
    }
    
    // Check deadline
    $now = new DateTime();
    $deadline = new DateTime($eventItem['registration_deadline']);
    if ($deadline < $now) {
        jsonError('Registration deadline has passed');
    }
    
    // Generate registration number
    $registrationNumber = 'REG-' . date('Ymd') . '-' . str_pad($eventId, 4, '0', STR_PAD_LEFT) . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
    
    // Insert registration
    $sql = "INSERT INTO event_registrations (event_id, user_id, registration_number, status, payment_status, registered_at) 
            VALUES (:event_id, :user_id, :registration_number, 'pending', :payment_status, NOW())";
    
    $paymentStatus = $eventItem['fee'] > 0 ? 'unpaid' : 'paid';
    
    $stmt = $db->executeQuery($sql, [
        ':event_id' => $eventId,
        ':user_id' => $userId,
        ':registration_number' => $registrationNumber,
        ':payment_status' => $paymentStatus
    ]);
    
    if (!$stmt) {
        jsonError('Registration failed', 500);
    }
    
    // If free event, auto-confirm
    if ($eventItem['fee'] == 0) {
        $regId = $db->lastInsertId();
        $updateSql = "UPDATE event_registrations SET status = 'confirmed', payment_status = 'paid' WHERE registration_id = :id";
        $db->executeQuery($updateSql, [':id' => $regId]);
    }
    
    jsonSuccess([
        'registration_number' => $registrationNumber,
        'event_title' => $eventItem['title'],
        'fee' => $eventItem['fee'],
        'status' => $eventItem['fee'] == 0 ? 'confirmed' : 'pending_payment'
    ], 'Registration successful');
}

// GET /api/user/profile - Get user profile
if ($method === 'GET' && $path === 'user/profile') {
    $userId = authenticate();
    
    $sql = "SELECT user_id, username, email, full_name, role, profile_photo, phone, bio, created_at 
            FROM users WHERE user_id = :user_id";
    $user = $db->fetchOne($sql, [':user_id' => $userId]);
    
    if (!$user) {
        jsonError('User not found', 404);
    }
    
    jsonSuccess([
        'user' => $user
    ]);
}

// PUT /api/user/profile - Update user profile
if ($method === 'PUT' && $path === 'user/profile') {
    $userId = authenticate();
    $input = json_decode(file_get_contents('php://input'), true);
    
    $fullName = trim($input['full_name'] ?? '');
    $phone = trim($input['phone'] ?? '');
    $bio = trim($input['bio'] ?? '');
    
    $sql = "UPDATE users SET full_name = :full_name, phone = :phone, bio = :bio, updated_at = NOW() 
            WHERE user_id = :user_id";
    
    $stmt = $db->executeQuery($sql, [
        ':full_name' => $fullName,
        ':phone' => $phone,
        ':bio' => $bio,
        ':user_id' => $userId
    ]);
    
    if (!$stmt) {
        jsonError('Update failed', 500);
    }
    
    jsonSuccess([], 'Profile updated successfully');
}

// GET /api/user/registrations - Get user event registrations
if ($method === 'GET' && $path === 'user/registrations') {
    $userId = authenticate();
    
    $sql = "SELECT er.registration_id, er.registration_number, er.payment_status, er.registered_at,
                   e.title as event_title, e.event_date, e.location, e.fee
            FROM event_registrations er
            JOIN events e ON er.event_id = e.event_id
            WHERE er.user_id = :user_id
            ORDER BY er.registered_at DESC";
    
    $registrations = $db->fetchAll($sql, [':user_id' => $userId]);
    
    jsonSuccess([
        'registrations' => $registrations
    ]);
}

// GET /api/categories - Get news categories
if ($method === 'GET' && $path === 'categories') {
    jsonSuccess([
        'categories' => NEWS_CATEGORIES
    ]);
}

// Default route - 404
jsonError('Endpoint not found', 404);
?>