<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/session.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($method) {
        case 'GET':
            handleGetRequest($action);
            break;
        case 'POST':
            handlePostRequest($action);
            break;
        default:
            throw new Exception('Method not allowed');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function handleGetRequest($action) {
    switch ($action) {
        case 'search':
            searchUsers($_GET['q'] ?? '');
            break;
        case 'profile':
            getUserProfile($_GET['username'] ?? '');
            break;
        case 'suggestions':
            getUserSuggestions();
            break;
        default:
            throw new Exception('Invalid action');
    }
}

function handlePostRequest($action) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'update':
            updateProfile($data);
            break;
        default:
            throw new Exception('Invalid action');
    }
}

function searchUsers($query) {
    $query = trim($query);
    
    if (empty($query) || strlen($query) < 2) {
        echo json_encode(['success' => true, 'users' => []]);
        return;
    }
    
    $users = loadUsers();
    $currentUser = getCurrentUser();
    
    $results = [];
    foreach ($users as $user) {
        if ($user['username'] === $currentUser['username']) {
            continue; // Skip current user
        }
        
        if (stripos($user['username'], $query) !== false || 
            stripos($user['fullName'], $query) !== false) {
            $results[] = [
                'username' => $user['username'],
                'fullName' => $user['fullName'],
                'bio' => $user['bio'] ?? '',
                'isFollowing' => in_array($user['username'], $currentUser['following'] ?? [])
            ];
        }
        
        if (count($results) >= 10) {
            break; // Limit results
        }
    }
    
    echo json_encode(['success' => true, 'users' => $results]);
}

function getUserProfile($username) {
    if (empty($username)) {
        throw new Exception('Username is required');
    }
    
    $users = loadUsers();
    $currentUser = getCurrentUser();
    $posts = loadPosts();
    
    $user = null;
    foreach ($users as $u) {
        if ($u['username'] === $username) {
            $user = $u;
            break;
        }
    }
    
    if (!$user) {
        throw new Exception('User not found');
    }
    
    // Count user's posts
    $userPosts = array_filter($posts, function($post) use ($username) {
        return $post['username'] === $username;
    });
    
    $profile = [
        'username' => $user['username'],
        'fullName' => $user['fullName'],
        'bio' => $user['bio'] ?? '',
        'postsCount' => count($userPosts),
        'followersCount' => count($user['followers'] ?? []),
        'followingCount' => count($user['following'] ?? []),
        'isFollowing' => in_array($user['username'], $currentUser['following'] ?? []),
        'isOwnProfile' => $user['username'] === $currentUser['username']
    ];
    
    echo json_encode(['success' => true, 'profile' => $profile]);
}

function getUserSuggestions() {
    $users = loadUsers();
    $currentUser = getCurrentUser();
    $following = $currentUser['following'] ?? [];
    
    $suggestions = [];
    foreach ($users as $user) {
        if ($user['username'] === $currentUser['username'] || 
            in_array($user['username'], $following)) {
            continue; // Skip current user and already following
        }
        
        $suggestions[] = [
            'username' => $user['username'],
            'fullName' => $user['fullName'],
            'bio' => $user['bio'] ?? '',
            'followersCount' => count($user['followers'] ?? [])
        ];
        
        if (count($suggestions) >= 5) {
            break; // Limit suggestions
        }
    }
    
    // Sort by followers count (descending)
    usort($suggestions, function($a, $b) {
        return $b['followersCount'] - $a['followersCount'];
    });
    
    echo json_encode(['success' => true, 'users' => $suggestions]);
}

function updateProfile($data) {
    $fullName = trim($data['fullName'] ?? '');
    $bio = trim($data['bio'] ?? '');
    
    if (empty($fullName)) {
        throw new Exception('Full name is required');
    }
    
    if (strlen($bio) > 160) {
        throw new Exception('Bio cannot exceed 160 characters');
    }
    
    $users = loadUsers();
    $currentUser = getCurrentUser();
    
    // Find and update user
    foreach ($users as &$user) {
        if ($user['username'] === $currentUser['username']) {
            $user['fullName'] = $fullName;
            $user['bio'] = $bio;
            break;
        }
    }
    
    saveUsers($users);
    
    // Update session
    $_SESSION['fullName'] = $fullName;
    
    echo json_encode([
        'success' => true,
        'message' => 'Profile updated successfully',
        'user' => [
            'fullName' => $fullName,
            'bio' => $bio
        ]
    ]);
}
?>
