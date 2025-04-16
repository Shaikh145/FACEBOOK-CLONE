<?php
// This file contains the JavaScript code for post interactions
// Include this at the bottom of your home.php and profile.php files
?>

<script>
// Like post functionality
function likePost(postId) {
    fetch('like_post.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'post_id=' + postId
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            const likeBtn = document.querySelector(`#post-${postId} .post-action-btn:first-child`);
            const likeIcon = likeBtn.querySelector('i');
            const likeCount = document.getElementById(`like-count-${postId}`);
            const likeCountContainer = document.querySelector(`#post-${postId} .post-stats div:first-child`);
            
            if(data.liked) {
                likeBtn.classList.add('active');
                likeIcon.classList.remove('far');
                likeIcon.classList.add('fas');
            } else {
                likeBtn.classList.remove('active');
                likeIcon.classList.remove('fas');
                likeIcon.classList.add('far');
            }
            
            // Update like count
            if(data.count > 0) {
                if(likeCount) {
                    likeCount.textContent = data.count;
                } else {
                    likeCountContainer.innerHTML = `<i class="fas fa-thumbs-up" style="color: #1877f2;"></i>
                                            <span id="like-count-${postId}">${data.count}</span>`;
                }
            } else {
                if(likeCount) {
                    likeCountContainer.innerHTML = '';
                }
            }
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

// Toggle comments section
function toggleComments(postId) {
    const commentsSection = document.getElementById(`comments-section-${postId}`);
    if(commentsSection.style.display === 'block') {
        commentsSection.style.display = 'none';
    } else {
        commentsSection.style.display = 'block';
    }
}

// Handle comment key press (Enter to submit)
function handleCommentKeyPress(event, postId) {
    if(event.key === 'Enter') {
        event.preventDefault();
        addComment(postId);
    }
}

// Add comment functionality
function addComment(postId) {
    const commentInput = document.getElementById(`comment-input-${postId}`);
    const content = commentInput.value.trim();
    
    if(content) {
        fetch('comment_post.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'post_id=' + postId + '&content=' + encodeURIComponent(content)
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                // Add comment to list
                const commentsList = document.getElementById(`comments-list-${postId}`);
                commentsList.insertAdjacentHTML('beforeend', data.comment);
                
                // Clear input
                commentInput.value = '';
                
                // Update comment count
                const commentCount = document.getElementById(`comment-count-${postId}`);
                const commentCountContainer = document.querySelector(`#post-${postId} .post-stats div:last-child`);
                
                if(commentCount) {
                    commentCount.textContent = data.count + ' comments';
                } else {
                    commentCountContainer.innerHTML = `<span id="comment-count-${postId}">${data.count} comments</span>`;
                }
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
    }
}

// Share post functionality
function openShareModal(postId, authorName, content) {
    document.getElementById('originalPostId').value = postId;
    document.getElementById('originalPostContent').innerHTML = `
        <strong>${authorName}</strong>
        <p>${content}</p>
    `;
    document.getElementById('shareModal').style.display = 'flex';
}

function closeShareModal() {
    document.getElementById('shareModal').style.display = 'none';
}

function sharePost() {
    const postId = document.getElementById('originalPostId').value;
    const content = document.getElementById('shareContent').value.trim();
    
    fetch('post_actions.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=share&post_id=' + postId + '&content=' + encodeURIComponent(content)
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            alert('Post shared successfully!');
            closeShareModal();
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}
</script>
