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

function handlePostRequest($action) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'follow':
            toggleFollow($data);
            break;
        default:
            throw new Exception('Invalid action');
    }
}

function toggleFollow($data) {
    $targetUsername = trim($data['username'] ?? '');
    
    if (empty($targetUsername)) {
        throw new Exception('Username is required');
    }
    
    $currentUser = getCurrentUser();
    
    if ($targetUsername === $currentUser['username']) {
        throw new Exception('You cannot follow yourself');
    }
    
    $users = loadUsers();
    
    // Find current user and target user
    $currentUserIndex = -1;
    $targetUserIndex = -1;
    
    foreach ($users as $index => $user) {
        if ($user['username'] === $currentUser['username']) {
            $currentUserIndex = $index;
        }
        if ($user['username'] === $targetUsername) {
            $targetUserIndex = $index;
        }
    }
    
    if ($targetUserIndex === -1) {
        throw new Exception('User not found');
    }
    
    $currentUserData = &$users[$currentUserIndex];
    $targetUserData = &$users[$targetUserIndex];
    
    // Initialize arrays if they don't exist
    if (!isset($currentUserData['following'])) {
        $currentUserData['following'] = [];
    }
    if (!isset($targetUserData['followers'])) {
        $targetUserData['followers'] = [];
    }
    
    $isFollowing = in_array($targetUsername, $currentUserData['following']);
    
    if ($isFollowing) {
        // Unfollow
        $currentUserData['following'] = array_values(array_filter(
            $currentUserData['following'],
            function($username) use ($targetUsername) {
                return $username !== $targetUsername;
            }
        ));
        
        $targetUserData['followers'] = array_values(array_filter(
            $targetUserData['followers'],
            function($username) use ($currentUser) {
                return $username !== $currentUser['username'];
            }
        ));
        
        $newStatus = false;
        $message = 'Unfollowed successfully';
    } else {
        // Follow
        $currentUserData['following'][] = $targetUsername;
        $targetUserData['followers'][] = $currentUser['username'];
        
        $newStatus = true;
        $message = 'Followed successfully';
    }
    
    saveUsers($users);
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'isFollowing' => $newStatus,
        'followersCount' => count($targetUserData['followers']),
        'followingCount' => count($currentUserData['following'])
    ]);
}
?>
