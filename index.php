<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/session.php';

// Redirect to login if not authenticated
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$currentUser = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SocialConnect - Home</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-brand">
                <h2><i class="fas fa-share-alt"></i> SocialConnect</h2>
            </div>
            <div class="nav-menu">
                <div class="search-container">
                    <input type="text" id="searchInput" placeholder="Search users..." class="search-input">
                    <div id="searchResults" class="search-results"></div>
                </div>
                <div class="nav-user">
                    <img src="https://via.placeholder.com/40" alt="Profile" class="nav-avatar">
                    <span><?php echo htmlspecialchars($currentUser['username']); ?></span>
                    <div class="dropdown">
                        <button class="dropdown-btn"><i class="fas fa-chevron-down"></i></button>
                        <div class="dropdown-content">
                            <a href="profile.php?user=<?php echo $currentUser['username']; ?>">My Profile</a>
                            <a href="#" onclick="logout()">Logout</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <main class="main-content">
        <div class="container">
            <!-- Create Post Section -->
            <div class="create-post-card">
                <div class="post-input-container">
                    <img src="https://via.placeholder.com/50" alt="Your avatar" class="post-avatar">
                    <textarea id="postContent" placeholder="What's on your mind, <?php echo htmlspecialchars($currentUser['username']); ?>?" class="post-input"></textarea>
                </div>
                <div class="post-actions">
                    <button id="createPostBtn" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Post
                    </button>
                </div>
            </div>

            <!-- Posts Feed -->
            <div id="postsFeed" class="posts-feed">
                <div class="loading" id="loadingIndicator">
                    <i class="fas fa-spinner fa-spin"></i> Loading posts...
                </div>
            </div>
        </div>
    </main>

    <!-- Sidebar for suggestions -->
    <aside class="sidebar">
        <div class="suggestions-card">
            <h3>Who to follow</h3>
            <div id="userSuggestions" class="user-suggestions">
                <!-- Dynamic content will be loaded here -->
            </div>
        </div>
    </aside>

    <script src="js/main.js"></script>
    <script src="js/auth.js"></script>
</body>
</html>
