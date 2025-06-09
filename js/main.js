// Main JavaScript file for SocialConnect

// Global variables
let currentUser = null;
let posts = [];
let searchTimeout = null;

// Initialize the application
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

async function initializeApp() {
    try {
        // Check authentication status
        const authResponse = await fetch('/api/auth.php?action=check');
        const authData = await authResponse.json();
        
        if (authData.loggedIn) {
            setupEventListeners();
            loadFeed();
            loadUserSuggestions();
        }
    } catch (error) {
        console.error('Failed to initialize app:', error);
    }
}

function setupEventListeners() {
    // Create post button
    const createPostBtn = document.getElementById('createPostBtn');
    if (createPostBtn) {
        createPostBtn.addEventListener('click', createPost);
    }
    
    // Post content textarea
    const postContent = document.getElementById('postContent');
    if (postContent) {
        postContent.addEventListener('input', function() {
            const createBtn = document.getElementById('createPostBtn');
            createBtn.disabled = this.value.trim().length === 0;
        });
        
        postContent.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) {
                createPost();
            }
        });
    }
    
    // Search functionality
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', handleSearch);
        searchInput.addEventListener('focus', showSearchResults);
        searchInput.addEventListener('blur', hideSearchResults);
    }
    
    // Dropdown functionality
    const dropdownBtn = document.querySelector('.dropdown-btn');
    const dropdownContent = document.querySelector('.dropdown-content');
    if (dropdownBtn && dropdownContent) {
        dropdownBtn.addEventListener('click', function() {
            dropdownContent.style.display = dropdownContent.style.display === 'block' ? 'none' : 'block';
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.dropdown')) {
                dropdownContent.style.display = 'none';
            }
        });
    }
    
    // Profile tabs
    const profileTabs = document.querySelectorAll('.nav-tab');
    profileTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const tabName = this.dataset.tab;
            switchProfileTab(tabName);
        });
    });
    
    // Edit profile modal
    const editProfileBtn = document.getElementById('editProfileBtn');
    const editProfileModal = document.getElementById('editProfileModal');
    if (editProfileBtn && editProfileModal) {
        editProfileBtn.addEventListener('click', function() {
            editProfileModal.style.display = 'block';
        });
        
        const closeBtn = editProfileModal.querySelector('.close');
        closeBtn.addEventListener('click', function() {
            editProfileModal.style.display = 'none';
        });
        
        window.addEventListener('click', function(e) {
            if (e.target === editProfileModal) {
                editProfileModal.style.display = 'none';
            }
        });
        
        const editProfileForm = document.getElementById('editProfileForm');
        editProfileForm.addEventListener('submit', updateProfile);
    }
    
    // Follow button
    const followBtn = document.getElementById('followBtn');
    if (followBtn) {
        followBtn.addEventListener('click', toggleFollow);
    }
}

// Post creation
async function createPost() {
    const content = document.getElementById('postContent').value.trim();
    
    if (!content) {
        showMessage('Post content cannot be empty', 'error');
        return;
    }
    
    if (content.length > 500) {
        showMessage('Post content cannot exceed 500 characters', 'error');
        return;
    }
    
    try {
        const response = await fetch('/api/posts.php?action=create', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ content })
        });
        
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('postContent').value = '';
            showMessage('Post created successfully!', 'success');
            loadFeed(); // Reload the feed
        } else {
            showMessage(data.message, 'error');
        }
    } catch (error) {
        showMessage('Failed to create post', 'error');
        console.error('Error creating post:', error);
    }
}

// Load posts feed
async function loadFeed() {
    const feedContainer = document.getElementById('postsFeed');
    const loadingIndicator = document.getElementById('loadingIndicator');
    
    if (loadingIndicator) {
        loadingIndicator.style.display = 'block';
    }
    
    try {
        const response = await fetch('/api/posts.php?action=feed');
        const data = await response.json();
        
        if (data.success) {
            posts = data.posts;
            renderPosts(posts, feedContainer);
        } else {
            showMessage(data.message, 'error');
        }
    } catch (error) {
        showMessage('Failed to load posts', 'error');
        console.error('Error loading feed:', error);
    } finally {
        if (loadingIndicator) {
            loadingIndicator.style.display = 'none';
        }
    }
}

// Load user posts for profile page
async function loadUserPosts(username) {
    const feedContainer = document.getElementById('profilePosts');
    
    try {
        const response = await fetch(`/api/posts.php?action=user&username=${encodeURIComponent(username)}`);
        const data = await response.json();
        
        if (data.success) {
            renderPosts(data.posts, feedContainer);
        } else {
            showMessage(data.message, 'error');
        }
    } catch (error) {
        showMessage('Failed to load user posts', 'error');
        console.error('Error loading user posts:', error);
    }
}

// Render posts
function renderPosts(postsArray, container) {
    if (!container) return;
    
    if (postsArray.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-file-alt"></i>
                <h3>No posts yet</h3>
                <p>Be the first to share something!</p>
            </div>
        `;
        return;
    }
    
    container.innerHTML = postsArray.map(post => createPostHTML(post)).join('');
    
    // Add event listeners to post actions
    container.querySelectorAll('.like-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            toggleLike(this.dataset.postId);
        });
    });
    
    container.querySelectorAll('.comment-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            toggleComments(this.dataset.postId);
        });
    });
    
    container.querySelectorAll('.comment-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            addComment(this.dataset.postId);
        });
    });
    
    container.querySelectorAll('.delete-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            deletePost(this.dataset.postId);
        });
    });
}

// Create post HTML
function createPostHTML(post) {
    const timeAgo = formatTimeAgo(post.createdAt);
    const isOwner = currentUser && post.username === currentUser.username;
    
    return `
        <div class="post-card" data-post-id="${post.id}">
            <div class="post-header">
                <img src="https://via.placeholder.com/50" alt="${post.fullName}" class="post-avatar">
                <div class="post-user-info">
                    <div class="post-user-name">${escapeHtml(post.fullName)}</div>
                    <div class="post-username">@${escapeHtml(post.username)}</div>
                </div>
                <div class="post-time">${timeAgo}</div>
                ${isOwner ? `<button class="delete-btn" data-post-id="${post.id}" title="Delete post"><i class="fas fa-trash"></i></button>` : ''}
            </div>
            <div class="post-content">${escapeHtml(post.content)}</div>
            <div class="post-actions-bar">
                <button class="post-action like-btn ${post.isLiked ? 'liked' : ''}" data-post-id="${post.id}">
                    <i class="fas fa-heart"></i>
                    <span>${post.likeCount}</span>
                </button>
                <button class="post-action comment-btn" data-post-id="${post.id}">
                    <i class="fas fa-comment"></i>
                    <span>${post.commentCount}</span>
                </button>
                <button class="post-action share-btn">
                    <i class="fas fa-share"></i>
                </button>
            </div>
            <div class="comments-section" id="comments-${post.id}" style="display: none;">
                <form class="comment-form" data-post-id="${post.id}">
                    <img src="https://via.placeholder.com/30" alt="Your avatar" class="comment-avatar">
                    <input type="text" class="comment-input" placeholder="Write a comment..." required>
                    <button type="submit" class="comment-btn">Post</button>
                </form>
                <div class="comments-list" id="comments-list-${post.id}">
                    ${post.comments ? post.comments.map(comment => createCommentHTML(comment)).join('') : ''}
                </div>
            </div>
        </div>
    `;
}

// Create comment HTML
function createCommentHTML(comment) {
    const timeAgo = formatTimeAgo(comment.createdAt);
    
    return `
        <div class="comment">
            <img src="https://via.placeholder.com/30" alt="${comment.fullName}" class="comment-avatar">
            <div class="comment-content">
                <div class="comment-header">
                    <span class="comment-author">${escapeHtml(comment.fullName)}</span>
                    <span class="comment-time">${timeAgo}</span>
                </div>
                <div class="comment-text">${escapeHtml(comment.content)}</div>
            </div>
        </div>
    `;
}

// Toggle like on post
async function toggleLike(postId) {
    try {
        const response = await fetch('/api/posts.php?action=like', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ postId })
        });
        
        const data = await response.json();
        
        if (data.success) {
            const likeBtn = document.querySelector(`[data-post-id="${postId}"].like-btn`);
            const likeCount = likeBtn.querySelector('span');
            
            likeBtn.classList.toggle('liked', data.isLiked);
            likeCount.textContent = data.likeCount;
        } else {
            showMessage(data.message, 'error');
        }
    } catch (error) {
        showMessage('Failed to toggle like', 'error');
        console.error('Error toggling like:', error);
    }
}

// Toggle comments section
function toggleComments(postId) {
    const commentsSection = document.getElementById(`comments-${postId}`);
    commentsSection.style.display = commentsSection.style.display === 'none' ? 'block' : 'none';
    
    if (commentsSection.style.display === 'block') {
        const commentInput = commentsSection.querySelector('.comment-input');
        commentInput.focus();
    }
}

// Add comment to post
async function addComment(postId) {
    const commentForm = document.querySelector(`[data-post-id="${postId}"].comment-form`);
    const commentInput = commentForm.querySelector('.comment-input');
    const content = commentInput.value.trim();
    
    if (!content) {
        return;
    }
    
    try {
        const response = await fetch('/api/posts.php?action=comment', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ postId, content })
        });
        
        const data = await response.json();
        
        if (data.success) {
            commentInput.value = '';
            
            // Update comment count
            const commentBtn = document.querySelector(`[data-post-id="${postId}"].comment-btn span`);
            commentBtn.textContent = data.commentCount;
            
            // Add comment to list
            const commentsList = document.getElementById(`comments-list-${postId}`);
            commentsList.insertAdjacentHTML('beforeend', createCommentHTML(data.comment));
        } else {
            showMessage(data.message, 'error');
        }
    } catch (error) {
        showMessage('Failed to add comment', 'error');
        console.error('Error adding comment:', error);
    }
}

// Delete post
async function deletePost(postId) {
    if (!confirm('Are you sure you want to delete this post?')) {
        return;
    }
    
    try {
        const response = await fetch(`/api/posts.php?action=delete&id=${postId}`, {
            method: 'DELETE'
        });
        
        const data = await response.json();
        
        if (data.success) {
            const postCard = document.querySelector(`[data-post-id="${postId}"]`);
            postCard.remove();
            showMessage('Post deleted successfully', 'success');
        } else {
            showMessage(data.message, 'error');
        }
    } catch (error) {
        showMessage('Failed to delete post', 'error');
        console.error('Error deleting post:', error);
    }
}

// Search functionality
function handleSearch(e) {
    const query = e.target.value.trim();
    
    clearTimeout(searchTimeout);
    
    if (query.length < 2) {
        hideSearchResults();
        return;
    }
    
    searchTimeout = setTimeout(() => {
        searchUsers(query);
    }, 300);
}

async function searchUsers(query) {
    try {
        const response = await fetch(`/api/users.php?action=search&q=${encodeURIComponent(query)}`);
        const data = await response.json();
        
        if (data.success) {
            displaySearchResults(data.users);
        }
    } catch (error) {
        console.error('Error searching users:', error);
    }
}

function displaySearchResults(users) {
    const searchResults = document.getElementById('searchResults');
    
    if (users.length === 0) {
        searchResults.innerHTML = '<div class="search-result-item">No users found</div>';
    } else {
        searchResults.innerHTML = users.map(user => `
            <div class="search-result-item" onclick="goToProfile('${user.username}')">
                <img src="https://via.placeholder.com/30" alt="${user.fullName}" class="comment-avatar">
                <div>
                    <div class="comment-author">${escapeHtml(user.fullName)}</div>
                    <div class="comment-time">@${escapeHtml(user.username)}</div>
                </div>
            </div>
        `).join('');
    }
    
    searchResults.style.display = 'block';
}

function showSearchResults() {
    const searchResults = document.getElementById('searchResults');
    if (searchResults.innerHTML.trim()) {
        searchResults.style.display = 'block';
    }
}

function hideSearchResults() {
    setTimeout(() => {
        const searchResults = document.getElementById('searchResults');
        searchResults.style.display = 'none';
    }, 200);
}

function goToProfile(username) {
    window.location.href = `profile.php?user=${encodeURIComponent(username)}`;
}

// Load user suggestions
async function loadUserSuggestions() {
    const suggestionsContainer = document.getElementById('userSuggestions');
    
    if (!suggestionsContainer) return;
    
    try {
        const response = await fetch('/api/users.php?action=suggestions');
        const data = await response.json();
        
        if (data.success) {
            renderUserSuggestions(data.users, suggestionsContainer);
        }
    } catch (error) {
        console.error('Error loading user suggestions:', error);
    }
}

function renderUserSuggestions(users, container) {
    if (users.length === 0) {
        container.innerHTML = '<p class="text-muted">No suggestions available</p>';
        return;
    }
    
    container.innerHTML = users.map(user => `
        <div class="user-suggestion">
            <img src="https://via.placeholder.com/40" alt="${user.fullName}" class="suggestion-avatar">
            <div class="suggestion-info">
                <div class="suggestion-name">${escapeHtml(user.fullName)}</div>
                <div class="suggestion-username">@${escapeHtml(user.username)}</div>
            </div>
            <button class="follow-btn" onclick="followUser('${user.username}')">Follow</button>
        </div>
    `).join('');
}

// Follow user from suggestions
async function followUser(username) {
    try {
        const response = await fetch('/api/interactions.php?action=follow', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ username })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showMessage(data.message, 'success');
            loadUserSuggestions(); // Reload suggestions
        } else {
            showMessage(data.message, 'error');
        }
    } catch (error) {
        showMessage('Failed to follow user', 'error');
        console.error('Error following user:', error);
    }
}

// Profile page functions
function loadProfileData() {
    if (typeof profileUsername !== 'undefined') {
        loadUserProfile(profileUsername);
        loadUserPosts(profileUsername);
    }
}

async function loadUserProfile(username) {
    try {
        const response = await fetch(`/api/users.php?action=profile&username=${encodeURIComponent(username)}`);
        const data = await response.json();
        
        if (data.success) {
            updateProfileStats(data.profile);
            updateFollowButton(data.profile);
        }
    } catch (error) {
        console.error('Error loading profile:', error);
    }
}

function updateProfileStats(profile) {
    const postsCount = document.getElementById('postsCount');
    const followersCount = document.getElementById('followersCount');
    const followingCount = document.getElementById('followingCount');
    
    if (postsCount) postsCount.textContent = profile.postsCount;
    if (followersCount) followersCount.textContent = profile.followersCount;
    if (followingCount) followingCount.textContent = profile.followingCount;
}

function updateFollowButton(profile) {
    const followBtn = document.getElementById('followBtn');
    
    if (followBtn && !profile.isOwnProfile) {
        followBtn.innerHTML = profile.isFollowing ? 
            '<i class="fas fa-user-check"></i> Following' : 
            '<i class="fas fa-user-plus"></i> Follow';
        followBtn.classList.toggle('btn-secondary', profile.isFollowing);
        followBtn.classList.toggle('btn-primary', !profile.isFollowing);
    }
}

function switchProfileTab(tabName) {
    // Update active tab
    document.querySelectorAll('.nav-tab').forEach(tab => {
        tab.classList.remove('active');
    });
    document.querySelector(`[data-tab="${tabName}"]`).classList.add('active');
    
    // Load content based on tab
    switch (tabName) {
        case 'posts':
            loadUserPosts(profileUsername);
            break;
        case 'media':
            showEmptyState('No media posts yet');
            break;
        case 'likes':
            showEmptyState('No liked posts yet');
            break;
    }
}

function showEmptyState(message) {
    const profilePosts = document.getElementById('profilePosts');
    if (profilePosts) {
        profilePosts.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-file-alt"></i>
                <h3>${message}</h3>
            </div>
        `;
    }
}

function setupEditProfile() {
    // Edit profile functionality is handled in event listeners
}

async function updateProfile(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const data = {
        fullName: formData.get('fullName'),
        bio: formData.get('bio')
    };
    
    try {
        const response = await fetch('/api/users.php?action=update', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showMessage('Profile updated successfully!', 'success');
            document.getElementById('editProfileModal').style.display = 'none';
            location.reload(); // Reload to show updated profile
        } else {
            showMessage(result.message, 'error');
        }
    } catch (error) {
        showMessage('Failed to update profile', 'error');
        console.error('Error updating profile:', error);
    }
}

async function toggleFollow() {
    const followBtn = document.getElementById('followBtn');
    const username = followBtn.dataset.username;
    
    try {
        const response = await fetch('/api/interactions.php?action=follow', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ username })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showMessage(data.message, 'success');
            updateFollowButton({ isFollowing: data.isFollowing });
            updateProfileStats({ followersCount: data.followersCount });
        } else {
            showMessage(data.message, 'error');
        }
    } catch (error) {
        showMessage('Failed to toggle follow', 'error');
        console.error('Error toggling follow:', error);
    }
}

function setupFollowButton() {
    // Follow button functionality is handled in event listeners
}

// Utility functions
function formatTimeAgo(datetime) {
    const now = new Date();
    const past = new Date(datetime);
    const diff = Math.floor((now - past) / 1000);
    
    if (diff < 60) return 'just now';
    if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
    if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
    if (diff < 2592000) return Math.floor(diff / 86400) + 'd ago';
    
    return past.toLocaleDateString();
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showMessage(message, type = 'info') {
    // Create or update message element
    let messageEl = document.getElementById('globalMessage');
    
    if (!messageEl) {
        messageEl = document.createElement('div');
        messageEl.id = 'globalMessage';
        messageEl.style.cssText = `
            position: fixed;
            top: 80px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 8px;
            color: white;
            font-weight: 600;
            z-index: 9999;
            max-width: 300px;
            transition: all 0.3s ease;
        `;
        document.body.appendChild(messageEl);
    }
    
    messageEl.textContent = message;
    messageEl.className = `message ${type}`;
    
    // Set background color based on type
    switch (type) {
        case 'success':
            messageEl.style.backgroundColor = '#28a745';
            break;
        case 'error':
            messageEl.style.backgroundColor = '#dc3545';
            break;
        case 'warning':
            messageEl.style.backgroundColor = '#ffc107';
            messageEl.style.color = '#212529';
            break;
        default:
            messageEl.style.backgroundColor = '#17a2b8';
    }
    
    messageEl.style.display = 'block';
    messageEl.style.opacity = '1';
    
    // Auto-hide after 3 seconds
    setTimeout(() => {
        messageEl.style.opacity = '0';
        setTimeout(() => {
            messageEl.style.display = 'none';
        }, 300);
    }, 3000);
}

// Logout function (called from HTML)
function logout() {
    if (confirm('Are you sure you want to logout?')) {
        fetch('/api/auth.php?action=logout', { method: 'POST' })
            .then(() => {
                window.location.href = 'login.php';
            })
            .catch(error => {
                console.error('Logout error:', error);
                window.location.href = 'login.php';
            });
    }
}
