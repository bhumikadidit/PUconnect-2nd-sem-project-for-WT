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
$profileUsername = $_GET['user'] ?? $currentUser['username'];
$profileUser = getUserByUsername($profileUsername);

if (!$profileUser) {
    header('Location: index.php');
    exit();
}

$isOwnProfile = $profileUser['username'] === $currentUser['username'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SocialConnect - <?php echo htmlspecialchars($profileUser['fullName']); ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-brand">
                <h2><a href="index.php"><i class="fas fa-share-alt"></i> SocialConnect</a></h2>
            </div>
            <div class="nav-menu">
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
            <!-- Profile Header -->
            <div class="profile-header">
                <div class="profile-cover">
                    <div class="profile-info">
                        <img src="https://via.placeholder.com/120" alt="Profile Picture" class="profile-avatar">
                        <div class="profile-details">
                            <h1><?php echo htmlspecialchars($profileUser['fullName']); ?></h1>
                            <p class="username">@<?php echo htmlspecialchars($profileUser['username']); ?></p>
                            <p class="bio"><?php echo htmlspecialchars($profileUser['bio'] ?? 'No bio available'); ?></p>
                            <div class="profile-stats">
                                <span><strong id="postsCount">0</strong> Posts</span>
                                <span><strong id="followersCount">0</strong> Followers</span>
                                <span><strong id="followingCount">0</strong> Following</span>
                            </div>
                        </div>
                        <div class="profile-actions">
                            <?php if (!$isOwnProfile): ?>
                                <button id="followBtn" class="btn btn-primary" data-username="<?php echo $profileUser['username']; ?>">
                                    <i class="fas fa-user-plus"></i> Follow
                                </button>
                            <?php else: ?>
                                <button id="editProfileBtn" class="btn btn-secondary">
                                    <i class="fas fa-edit"></i> Edit Profile
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Profile Navigation -->
            <div class="profile-nav">
                <button class="nav-tab active" data-tab="posts">Posts</button>
                <button class="nav-tab" data-tab="media">Media</button>
                <button class="nav-tab" data-tab="likes">Likes</button>
            </div>

            <!-- Profile Content -->
            <div class="profile-content">
                <div id="profilePosts" class="posts-feed">
                    <div class="loading">
                        <i class="fas fa-spinner fa-spin"></i> Loading posts...
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Edit Profile Modal -->
    <div id="editProfileModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Profile</h3>
                <span class="close">&times;</span>
            </div>
            <form id="editProfileForm">
                <div class="form-group">
                    <label for="editFullName">Full Name</label>
                    <input type="text" id="editFullName" name="fullName" value="<?php echo htmlspecialchars($profileUser['fullName']); ?>">
                </div>
                <div class="form-group">
                    <label for="editBio">Bio</label>
                    <textarea id="editBio" name="bio" rows="3"><?php echo htmlspecialchars($profileUser['bio'] ?? ''); ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </form>
        </div>
    </div>

    <script src="js/main.js"></script>
    <script src="js/auth.js"></script>
    <script>
        // Profile-specific JavaScript
        const profileUsername = '<?php echo $profileUser['username']; ?>';
        const isOwnProfile = <?php echo $isOwnProfile ? 'true' : 'false'; ?>;
        
        document.addEventListener('DOMContentLoaded', function() {
            loadProfileData();
            setupProfileTabs();
            if (isOwnProfile) {
                setupEditProfile();
            } else {
                setupFollowButton();
            }
        });
    </script>
</body>
</html>
