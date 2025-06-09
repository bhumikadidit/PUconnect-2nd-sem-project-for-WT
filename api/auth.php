<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/session.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($method) {
        case 'POST':
            handlePostRequest($action);
            break;
        case 'GET':
            handleGetRequest($action);
            break;
        default:
            throw new Exception('Method not allowed');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function handlePostRequest($action) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'login':
            handleLogin($data);
            break;
        case 'register':
            handleRegister($data);
            break;
        case 'logout':
            handleLogout();
            break;
        default:
            throw new Exception('Invalid action');
    }
}

function handleGetRequest($action) {
    switch ($action) {
        case 'check':
            echo json_encode(['success' => true, 'loggedIn' => isLoggedIn()]);
            break;
        default:
            throw new Exception('Invalid action');
    }
}

function handleLogin($data) {
    $username = trim($data['username'] ?? '');
    $password = $data['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        throw new Exception('Username and password are required');
    }
    
    $users = loadUsers();
    $user = null;
    
    // Find user by username or email
    foreach ($users as $u) {
        if ($u['username'] === $username || $u['email'] === $username) {
            $user = $u;
            break;
        }
    }
    
    if (!$user || !password_verify($password, $user['password'])) {
        throw new Exception('Invalid username or password');
    }
    
    // Set session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    
    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'fullName' => $user['fullName'],
            'email' => $user['email']
        ]
    ]);
}

function handleRegister($data) {
    $fullName = trim($data['fullName'] ?? '');
    $username = trim($data['username'] ?? '');
    $email = trim($data['email'] ?? '');
    $password = $data['password'] ?? '';
    $confirmPassword = $data['confirmPassword'] ?? '';
    
    // Validation
    if (empty($fullName) || empty($username) || empty($email) || empty($password)) {
        throw new Exception('All fields are required');
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }
    
    if (strlen($password) < 6) {
        throw new Exception('Password must be at least 6 characters');
    }
    
    if ($password !== $confirmPassword) {
        throw new Exception('Passwords do not match');
    }
    
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        throw new Exception('Username can only contain letters, numbers, and underscores');
    }
    
    $users = loadUsers();
    
    // Check for existing username or email
    foreach ($users as $user) {
        if ($user['username'] === $username) {
            throw new Exception('Username already exists');
        }
        if ($user['email'] === $email) {
            throw new Exception('Email already exists');
        }
    }
    
    // Create new user
    $newUser = [
        'id' => generateId(),
        'fullName' => $fullName,
        'username' => $username,
        'email' => $email,
        'password' => password_hash($password, PASSWORD_DEFAULT),
        'bio' => '',
        'createdAt' => date('Y-m-d H:i:s'),
        'followers' => [],
        'following' => []
    ];
    
    $users[] = $newUser;
    saveUsers($users);
    
    // Set session
    $_SESSION['user_id'] = $newUser['id'];
    $_SESSION['username'] = $newUser['username'];
    
    echo json_encode([
        'success' => true,
        'message' => 'Registration successful',
        'user' => [
            'id' => $newUser['id'],
            'username' => $newUser['username'],
            'fullName' => $newUser['fullName'],
            'email' => $newUser['email']
        ]
    ]);
}

function handleLogout() {
    session_destroy();
    echo json_encode(['success' => true, 'message' => 'Logged out successfully']);
}
?>
