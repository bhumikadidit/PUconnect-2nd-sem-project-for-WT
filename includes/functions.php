<?php
// Utility functions for the application

function generateId() {
    return uniqid('', true);
}

function loadUsers() {
    $data = file_get_contents(USERS_FILE);
    return json_decode($data, true) ?: [];
}

function saveUsers($users) {
    file_put_contents(USERS_FILE, json_encode($users, JSON_PRETTY_PRINT));
}

function loadPosts() {
    $data = file_get_contents(POSTS_FILE);
    return json_decode($data, true) ?: [];
}

function savePosts($posts) {
    file_put_contents(POSTS_FILE, json_encode($posts, JSON_PRETTY_PRINT));
}

function getUserById($id) {
    $users = loadUsers();
    foreach ($users as $user) {
        if ($user['id'] === $id) {
            return $user;
        }
    }
    return null;
}

function getUserByUsername($username) {
    $users = loadUsers();
    foreach ($users as $user) {
        if ($user['username'] === $username) {
            return $user;
        }
    }
    return null;
}

function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function formatTimeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) {
        return 'just now';
    } elseif ($time < 3600) {
        $minutes = floor($time / 60);
        return $minutes . 'm ago';
    } elseif ($time < 86400) {
        $hours = floor($time / 3600);
        return $hours . 'h ago';
    } elseif ($time < 2592000) {
        $days = floor($time / 86400);
        return $days . 'd ago';
    } else {
        return date('M j, Y', strtotime($datetime));
    }
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validateUsername($username) {
    return preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username);
}
?>
