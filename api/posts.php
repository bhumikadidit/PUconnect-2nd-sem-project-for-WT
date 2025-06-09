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
        case 'GET':
            handleGetRequest($action);
            break;
        case 'DELETE':
            handleDeleteRequest($action);
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
        case 'create':
            createPost($data);
            break;
        case 'like':
            toggleLike($data);
            break;
        case 'comment':
            addComment($data);
            break;
        default:
            throw new Exception('Invalid action');
    }
}

function handleGetRequest($action) {
    switch ($action) {
        case 'feed':
            getFeed();
            break;
        case 'user':
            getUserPosts($_GET['username'] ?? '');
            break;
        default:
            throw new Exception('Invalid action');
    }
}

function handleDeleteRequest($action) {
    switch ($action) {
        case 'delete':
            deletePost($_GET['id'] ?? '');
            break;
        default:
            throw new Exception('Invalid action');
    }
}

function createPost($data) {
    $content = trim($data['content'] ?? '');
    
    if (empty($content)) {
        throw new Exception('Post content cannot be empty');
    }
    
    if (strlen($content) > 500) {
        throw new Exception('Post content cannot exceed 500 characters');
    }
    
    $currentUser = getCurrentUser();
    $posts = loadPosts();
    
    $newPost = [
        'id' => generateId(),
        'userId' => $currentUser['id'],
        'username' => $currentUser['username'],
        'fullName' => $currentUser['fullName'],
        'content' => $content,
        'createdAt' => date('Y-m-d H:i:s'),
        'likes' => [],
        'comments' => []
    ];
    
    array_unshift($posts, $newPost); // Add to beginning of array
    savePosts($posts);
    
    echo json_encode([
        'success' => true,
        'message' => 'Post created successfully',
        'post' => $newPost
    ]);
}

function getFeed() {
    $currentUser = getCurrentUser();
    $posts = loadPosts();
    $users = loadUsers();
    
    // Get posts from followed users and own posts
    $following = $currentUser['following'] ?? [];
    $following[] = $currentUser['username']; // Include own posts
    
    $feedPosts = array_filter($posts, function($post) use ($following) {
        return in_array($post['username'], $following);
    });
    
    // Sort by creation date (newest first)
    usort($feedPosts, function($a, $b) {
        return strtotime($b['createdAt']) - strtotime($a['createdAt']);
    });
    
    // Add like status and comment count
    foreach ($feedPosts as &$post) {
        $post['isLiked'] = in_array($currentUser['username'], $post['likes']);
        $post['likeCount'] = count($post['likes']);
        $post['commentCount'] = count($post['comments']);
    }
    
    echo json_encode([
        'success' => true,
        'posts' => array_slice($feedPosts, 0, 50) // Limit to 50 posts
    ]);
}

function getUserPosts($username) {
    if (empty($username)) {
        throw new Exception('Username is required');
    }
    
    $currentUser = getCurrentUser();
    $posts = loadPosts();
    
    $userPosts = array_filter($posts, function($post) use ($username) {
        return $post['username'] === $username;
    });
    
    // Sort by creation date (newest first)
    usort($userPosts, function($a, $b) {
        return strtotime($b['createdAt']) - strtotime($a['createdAt']);
    });
    
    // Add like status and comment count
    foreach ($userPosts as &$post) {
        $post['isLiked'] = in_array($currentUser['username'], $post['likes']);
        $post['likeCount'] = count($post['likes']);
        $post['commentCount'] = count($post['comments']);
    }
    
    echo json_encode([
        'success' => true,
        'posts' => array_values($userPosts)
    ]);
}

function toggleLike($data) {
    $postId = $data['postId'] ?? '';
    
    if (empty($postId)) {
        throw new Exception('Post ID is required');
    }
    
    $currentUser = getCurrentUser();
    $posts = loadPosts();
    
    $postIndex = array_search($postId, array_column($posts, 'id'));
    
    if ($postIndex === false) {
        throw new Exception('Post not found');
    }
    
    $post = &$posts[$postIndex];
    $likes = $post['likes'];
    $userIndex = array_search($currentUser['username'], $likes);
    
    if ($userIndex !== false) {
        // Unlike
        array_splice($likes, $userIndex, 1);
        $isLiked = false;
    } else {
        // Like
        $likes[] = $currentUser['username'];
        $isLiked = true;
    }
    
    $post['likes'] = $likes;
    savePosts($posts);
    
    echo json_encode([
        'success' => true,
        'isLiked' => $isLiked,
        'likeCount' => count($likes)
    ]);
}

function addComment($data) {
    $postId = $data['postId'] ?? '';
    $content = trim($data['content'] ?? '');
    
    if (empty($postId) || empty($content)) {
        throw new Exception('Post ID and comment content are required');
    }
    
    if (strlen($content) > 200) {
        throw new Exception('Comment cannot exceed 200 characters');
    }
    
    $currentUser = getCurrentUser();
    $posts = loadPosts();
    
    $postIndex = array_search($postId, array_column($posts, 'id'));
    
    if ($postIndex === false) {
        throw new Exception('Post not found');
    }
    
    $comment = [
        'id' => generateId(),
        'userId' => $currentUser['id'],
        'username' => $currentUser['username'],
        'fullName' => $currentUser['fullName'],
        'content' => $content,
        'createdAt' => date('Y-m-d H:i:s')
    ];
    
    $posts[$postIndex]['comments'][] = $comment;
    savePosts($posts);
    
    echo json_encode([
        'success' => true,
        'comment' => $comment,
        'commentCount' => count($posts[$postIndex]['comments'])
    ]);
}

function deletePost($postId) {
    if (empty($postId)) {
        throw new Exception('Post ID is required');
    }
    
    $currentUser = getCurrentUser();
    $posts = loadPosts();
    
    $postIndex = array_search($postId, array_column($posts, 'id'));
    
    if ($postIndex === false) {
        throw new Exception('Post not found');
    }
    
    if ($posts[$postIndex]['userId'] !== $currentUser['id']) {
        throw new Exception('You can only delete your own posts');
    }
    
    array_splice($posts, $postIndex, 1);
    savePosts($posts);
    
    echo json_encode([
        'success' => true,
        'message' => 'Post deleted successfully'
    ]);
}
?>
