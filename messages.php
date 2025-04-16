<?php
session_start();
require_once 'db.php';

// Check if user is logged in
if(!isLoggedIn()) {
    redirect('index.php');
}

$user_id = getCurrentUserId();
$user_name = getCurrentUserName();

// Get conversations
$sql = "SELECT 
            c.id as conversation_id, 
            IF(c.user1_id = '$user_id', c.user2_id, c.user1_id) as other_user_id,
            u.name as other_user_name,
            u.profile_picture,
            m.content as last_message,
            m.created_at as last_message_time,
            m.sender_id as last_message_sender,
            (SELECT COUNT(*) FROM messages 
             WHERE conversation_id = c.id 
             AND receiver_id = '$user_id' 
             AND is_read = 0) as unread_count
        FROM conversations c
        JOIN users u ON (c.user1_id = '$user_id' AND c.user2_id = u.id) OR (c.user2_id = '$user_id' AND c.user1_id = u.id)
        LEFT JOIN messages m ON c.last_message_id = m.id
        WHERE c.user1_id = '$user_id' OR c.user2_id = '$user_id'
        ORDER BY c.updated_at DESC";

$conversations_result = $conn->query($sql);

// Get active conversation if selected
$active_conversation = null;
$other_user = null;
$messages = null;

if (isset($_GET['conversation'])) {
    $conversation_id = $conn->real_escape_string($_GET['conversation']);
    
    // Check if conversation exists and belongs to user
    $check_sql = "SELECT 
                    c.id,
                    IF(c.user1_id = '$user_id', c.user2_id, c.user1_id) as other_user_id,
                    u.name as other_user_name,
                    u.profile_picture
                  FROM conversations c
                  JOIN users u ON (c.user1_id = '$user_id' AND c.user2_id = u.id) OR (c.user2_id = '$user_id' AND c.user1_id = u.id)
                  WHERE c.id = '$conversation_id' AND (c.user1_id = '$user_id' OR c.user2_id = '$user_id')";
    
    $check_result = $conn->query($check_sql);
    
    if ($check_result->num_rows > 0) {
        $active_conversation = $check_result->fetch_assoc();
        $other_user_id = $active_conversation['other_user_id'];
        $other_user = [
            'id' => $other_user_id,
            'name' => $active_conversation['other_user_name'],
            'profile_picture' => $active_conversation['profile_picture']
        ];
        
        // Get messages
        $messages_sql = "SELECT 
                            m.*,
                            u.name,
                            u.profile_picture
                         FROM messages m
                         JOIN users u ON m.sender_id = u.id
                         WHERE m.conversation_id = '$conversation_id'
                         ORDER BY m.created_at ASC";
        
        $messages = $conn->query($messages_sql);
        
        // Mark messages as read
        $update_sql = "UPDATE messages 
                      SET is_read = 1 
                      WHERE conversation_id = '$conversation_id' 
                      AND receiver_id = '$user_id' 
                      AND is_read = 0";
        $conn->query($update_sql);
    }
} else if (isset($_GET['user'])) {
    // Start new conversation
    $other_user_id = $conn->real_escape_string($_GET['user']);
    
    // Check if user exists
    $user_sql = "SELECT id, name, profile_picture FROM users WHERE id = '$other_user_id'";
    $user_result = $conn->query($user_sql);
    
    if ($user_result->num_rows > 0) {
        $other_user = $user_result->fetch_assoc();
        
        // Check if conversation already exists
        $check_sql = "SELECT id FROM conversations 
                     WHERE (user1_id = '$user_id' AND user2_id = '$other_user_id')
                     OR (user1_id = '$other_user_id' AND user2_id = '$user_id')";
        
        $check_result = $conn->query($check_sql);
        
        if ($check_result->num_rows > 0) {
            $conversation = $check_result->fetch_assoc();
            redirect('messages.php?conversation=' . $conversation['id']);
        } else {
            // Create new conversation
            $create_sql = "INSERT INTO conversations (user1_id, user2_id, updated_at) 
                          VALUES ('$user_id', '$other_user_id', NOW())";
            
            if ($conn->query($create_sql)) {
                $conversation_id = $conn->insert_id;
                redirect('messages.php?conversation=' . $conversation_id);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages | Facebook</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f0f2f5;
        }
        
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: white;
            padding: 0 16px;
            height: 56px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .navbar-left {
            display: flex;
            align-items: center;
        }
        
        .logo {
            color: #1877f2;
            font-size: 2rem;
            font-weight: bold;
            text-decoration: none;
        }
        
        .search-bar {
            background-color: #f0f2f5;
            border-radius: 50px;
            padding: 8px 16px;
            margin-left: 10px;
            display: flex;
            align-items: center;
        }
        
        .search-bar i {
            color: #65676b;
            margin-right: 8px;
        }
        
        .search-bar input {
            border: none;
            background-color: transparent;
            outline: none;
            font-size: 0.9rem;
            width: 240px;
        }
        
        .navbar-center {
            display: flex;
        }
        
        .nav-icon {
            color: #65676b;
            font-size: 1.5rem;
            padding: 10px 40px;
            border-radius: 8px;
            cursor: pointer;
        }
        
        .nav-icon.active {
            color: #1877f2;
            border-bottom: 3px solid #1877f2;
        }
        
        .nav-icon:hover {
            background-color: #f0f2f5;
        }
        
        .navbar-right {
            display: flex;
            align-items: center;
        }
        
        .profile-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #e4e6eb;
            display: flex;
            justify-content: center;
            align-items: center;
            margin-right: 10px;
            cursor: pointer;
            text-decoration: none;
            color: #050505;
            font-weight: bold;
            overflow: hidden;
        }
        
        .profile-icon img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .icon-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #e4e6eb;
            display: flex;
            justify-content: center;
            align-items: center;
            margin-left: 8px;
            cursor: pointer;
        }
        
        .icon-btn i {
            color: #050505;
            font-size: 1.2rem;
        }
        
        .messages-container {
            display: flex;
            max-width: 1200px;
            margin: 20px auto;
            height: calc(100vh - 96px);
        }
        
        .conversations-list {
            width: 360px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        
        .conversations-header {
            padding: 16px;
            border-bottom: 1px solid #e4e6eb;
        }
        
        .conversations-title {
            font-size: 1.5rem;
            font-weight: bold;
        }
        
        .search-messages {
            margin-top: 10px;
            background-color: #f0f2f5;
            border-radius: 50px;
            padding: 8px 16px;
            display: flex;
            align-items: center;
        }
        
        .search-messages i {
            color: #65676b;
            margin-right: 8px;
        }
        
        .search-messages input {
            border: none;
            background-color: transparent;
            outline: none;
            font-size: 0.9rem;
            width: 100%;
        }
        
        .conversations {
            flex: 1;
            overflow-y: auto;
        }
        
        .conversation-item {
            display: flex;
            padding: 12px 16px;
            cursor: pointer;
            border-bottom: 1px solid #f0f2f5;
            position: relative;
        }
        
        .conversation-item:hover {
            background-color: #f0f2f5;
        }
        
        .conversation-item.active {
            background-color: #e7f3ff;
        }
        
        .conversation-avatar {
            margin-right: 12px;
        }
        
        .conversation-info {
            flex: 1;
            overflow: hidden;
        }
        
        .conversation-name {
            font-weight: 500;
            margin-bottom: 4px;
            color: #050505;
        }
        
        .conversation-last-message {
            font-size: 0.9rem;
            color: #65676b;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .conversation-time {
            font-size: 0.75rem;
            color: #65676b;
            margin-left: 8px;
        }
        
        .unread-badge {
            background-color: #1877f2;
            color: white;
            font-size: 0.75rem;
            font-weight: bold;
            min-width: 20px;
            height: 20px;
            border-radius: 10px;
            display: flex;
            justify-content: center;
            align-items: center;
            position: absolute;
            right: 16px;
            bottom: 12px;
        }
        
        .chat-area {
            flex: 1;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
            margin-left: 16px;
            display: flex;
            flex-direction: column;
        }
        
        .chat-header {
            padding: 12px 16px;
            border-bottom: 1px solid #e4e6eb;
            display: flex;
            align-items: center;
        }
        
        .chat-title {
            font-weight: 600;
            margin-left: 12px;
        }
        
        .chat-actions {
            margin-left: auto;
            display: flex;
        }
        
        .chat-action {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background-color: #f0f2f5;
            display: flex;
            justify-content: center;
            align-items: center;
            margin-left: 8px;
            cursor: pointer;
        }
        
        .chat-action i {
            color: #65676b;
            font-size: 1rem;
        }
        
        .chat-messages {
            flex: 1;
            padding: 16px;
            overflow-y: auto;
            background-color: #f0f2f5;
        }
        
        .message {
            display: flex;
            margin-bottom: 16px;
        }
        
        .message.outgoing {
            justify-content: flex-end;
        }
        
        .message-avatar {
            margin-right: 8px;
        }
        
        .message.outgoing .message-avatar {
            display: none;
        }
        
        .message-content {
            max-width: 60%;
        }
        
        .message-bubble {
            padding: 8px 12px;
            border-radius: 18px;
            font-size: 0.9rem;
            position: relative;
        }
        
        .message.incoming .message-bubble {
            background-color: #e4e6eb;
            color: #050505;
            border-top-left-radius: 4px;
        }
        
        .message.outgoing .message-bubble {
            background-color: #0084ff;
            color: white;
            border-top-right-radius: 4px;
        }
        
        .message-time {
            font-size: 0.75rem;
            color: #65676b;
            margin-top: 4px;
            text-align: right;
        }
        
        .message.outgoing .message-time {
            color: #8a8d91;
        }
        
        .chat-input {
            padding: 12px 16px;
            border-top: 1px solid #e4e6eb;
            display: flex;
            align-items: center;
        }
        
        .chat-input-actions {
            display: flex;
        }
        
        .chat-input-action {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background-color: #f0f2f5;
            display: flex;
            justify-content: center;
            align-items: center;
            margin-right: 8px;
            cursor: pointer;
        }
        
        .chat-input-action i {
            color: #65676b;
            font-size: 1rem;
        }
        
        .chat-input-field {
            flex: 1;
            background-color: #f0f2f5;
            border-radius: 20px;
            padding: 8px 12px;
            margin: 0 8px;
            display: flex;
            align-items: center;
        }
        
        .chat-input-field input {
            flex: 1;
            border: none;
            background-color: transparent;
            outline: none;
            font-size: 0.9rem;
        }
        
        .chat-input-send {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background-color: #f0f2f5;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
        }
        
        .chat-input-send i {
            color: #65676b;
            font-size: 1rem;
        }
        
        .chat-input-send.active i {
            color: #0084ff;
        }
        
        .empty-state {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: #65676b;
            padding: 20px;
            text-align: center;
        }
        
        .empty-state i {
            font-size: 4rem;
            color: #1877f2;
            margin-bottom: 16px;
        }
        
        .empty-state-title {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 8px;
            color: #050505;
        }
        
        .empty-state-text {
            font-size: 1rem;
            max-width: 300px;
            margin-bottom: 16px;
        }
        
        .new-message-btn {
            background-color: #1877f2;
            color: white;
            border: none;
            border-radius: 6px;
            padding: 8px 16px;
            font-weight: 600;
            cursor: pointer;
        }
        
        @media (max-width: 768px) {
            .messages-container {
                flex-direction: column;
                height: auto;
            }
            
            .conversations-list {
                width: 100%;
                margin-bottom: 16px;
                height: 300px;
            }
            
            .chat-area {
                margin-left: 0;
                height: calc(100vh - 432px);
            }
            
            .navbar-center {
                display: none;
            }
            
            .search-bar {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="navbar-left">
            <a href="home.php" class="logo">f</a>
            <div class="search-bar">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Search Facebook">
            </div>
        </div>
        
        <div class="navbar-center">
            <a href="home.php" class="nav-icon">
                <i class="fas fa-home"></i>
            </a>
            <div class="nav-icon">
                <i class="fas fa-tv"></i>
            </div>
            <div class="nav-icon">
                <i class="fas fa-store"></i>
            </div>
            <div class="nav-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="nav-icon">
                <i class="fas fa-gamepad"></i>
            </div>
        </div>
        
        <div class="navbar-right">
            <a href="profile.php" class="profile-icon">
                <?php
                // Check if user has profile picture
                $sql = "SELECT profile_picture FROM users WHERE id = '$user_id'";
                $result = $conn->query($sql);
                $user = $result->fetch_assoc();
                
                if ($user && $user['profile_picture']) {
                    echo '<img src="' . $user['profile_picture'] . '" alt="Profile Picture">';
                } else {
                    echo substr($user_name, 0, 1);
                }
                ?>
            </a>
            <div class="icon-btn">
                <i class="fas fa-plus"></i>
            </div>
            <div class="icon-btn active">
                <i class="fab fa-facebook-messenger"></i>
            </div>
            <div class="icon-btn">
                <i class="fas fa-bell"></i>
            </div>
            <div class="icon-btn">
                <i class="fas fa-caret-down"></i>
            </div>
        </div>
    </div>
    
    <div class="messages-container">
        <div class="conversations-list">
            <div class="conversations-header">
                <div class="conversations-title">Chats</div>
                <div class="search-messages">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search in Messenger">
                </div>
            </div>
            
            <div class="conversations">
                <?php if($conversations_result->num_rows > 0): ?>
                    <?php while($conversation = $conversations_result->fetch_assoc()): ?>
                        <?php 
                            $is_active = isset($_GET['conversation']) && $_GET['conversation'] == $conversation['conversation_id'];
                            $profile_pic = $conversation['profile_picture'] ? 
                                '<img src="'.$conversation['profile_picture'].'" alt="Profile Picture">' : 
                                substr($conversation['other_user_name'], 0, 1);
                        ?>
                        <a href="messages.php?conversation=<?php echo $conversation['conversation_id']; ?>" 
                           class="conversation-item <?php echo $is_active ? 'active' : ''; ?>">
                            <div class="conversation-avatar">
                                <div class="profile-icon"><?php echo $profile_pic; ?></div>
                            </div>
                            <div class="conversation-info">
                                <div class="conversation-name"><?php echo $conversation['other_user_name']; ?></div>
                                <div class="conversation-last-message">
                                    <?php 
                                        if($conversation['last_message']) {
                                            if($conversation['last_message_sender'] == $user_id) {
                                                echo 'You: ';
                                            }
                                            echo $conversation['last_message'];
                                        } else {
                                            echo 'Start a conversation';
                                        }
                                    ?>
                                </div>
                            </div>
                            <div class="conversation-time">
                                <?php 
                                    if($conversation['last_message_time']) {
                                        echo timeAgo($conversation['last_message_time']);
                                    }
                                ?>
                            </div>
                            <?php if($conversation['unread_count'] > 0): ?>
                                <div class="unread-badge"><?php echo $conversation['unread_count']; ?></div>
                            <?php endif; ?>
                        </a>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state" style="padding: 20px; text-align: center; color: #65676b;">
                        <p>No conversations yet</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="chat-area">
            <?php if($active_conversation): ?>
                <div class="chat-header">
                    <div class="profile-icon">
                        <?php
                        if ($other_user['profile_picture']) {
                            echo '<img src="' . $other_user['profile_picture'] . '" alt="Profile Picture">';
                        } else {
                            echo substr($other_user['name'], 0, 1);
                        }
                        ?>
                    </div>
                    <div class="chat-title"><?php echo $other_user['name']; ?></div>
                    <div class="chat-actions">
                        <div class="chat-action">
                            <i class="fas fa-phone"></i>
                        </div>
                        <div class="chat-action">
                            <i class="fas fa-video"></i>
                        </div>
                        <div class="chat-action">
                            <i class="fas fa-info-circle"></i>
                        </div>
                    </div>
                </div>
                
                <div class="chat-messages" id="chat-messages">
                    <?php if($messages && $messages->num_rows > 0): ?>
                        <?php while($message = $messages->fetch_assoc()): ?>
                            <?php 
                                $is_outgoing = $message['sender_id'] == $user_id;
                                $message_class = $is_outgoing ? 'outgoing' : 'incoming';
                                $profile_pic = $message['profile_picture'] ? 
                                    '<img src="'.$message['profile_picture'].'" alt="Profile Picture">' : 
                                    substr($message['name'], 0, 1);
                            ?>
                            <div class="message <?php echo $message_class; ?>">
                                <div class="message-avatar">
                                    <div class="profile-icon"><?php echo $profile_pic; ?></div>
                                </div>
                                <div class="message-content">
                                    <div class="message-bubble"><?php echo nl2br($message['content']); ?></div>
                                    <div class="message-time"><?php echo date('g:i A', strtotime($message['created_at'])); ?></div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fab fa-facebook-messenger"></i>
                            <div class="empty-state-title">No messages yet</div>
                            <div class="empty-state-text">Be the first to send a message!</div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="chat-input">
                    <div class="chat-input-actions">
                        <div class="chat-input-action">
                            <i class="fas fa-plus-circle"></i>
                        </div>
                        <div class="chat-input-action">
                            <i class="fas fa-image"></i>
                        </div>
                        <div class="chat-input-action">
                            <i class="fas fa-sticky-note"></i>
                        </div>
                    </div>
                    <div class="chat-input-field">
                        <input type="text" id="message-input" placeholder="Aa" autocomplete="off">
                        <i class="far fa-smile"></i>
                    </div>
                    <div class="chat-input-send" id="send-button">
                        <i class="fas fa-paper-plane"></i>
                    </div>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fab fa-facebook-messenger"></i>
                    <div class="empty-state-title">Your Messages</div>
                    <div class="empty-state-text">Send private messages to a friend or group</div>
                    <button class="new-message-btn">Send New Message</button>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Scroll to bottom of chat
        function scrollToBottom() {
            const chatMessages = document.getElementById('chat-messages');
            if (chatMessages) {
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }
        }
        
        // Call on page load
        window.onload = function() {
            scrollToBottom();
            
            // Setup message sending
            const messageInput = document.getElementById('message-input');
            const sendButton = document.getElementById('send-button');
            
            if (messageInput && sendButton) {
                // Enable/disable send button based on input
                messageInput.addEventListener('input', function() {
                    if (this.value.trim().length > 0) {
                        sendButton.classList.add('active');
                    } else {
                        sendButton.classList.remove('active');
                    }
                });
                
                // Send message on enter
                messageInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter' && this.value.trim().length > 0) {
                        sendMessage();
                    }
                });
                
                // Send message on button click
                sendButton.addEventListener('click', function() {
                    if (messageInput.value.trim().length > 0) {
                        sendMessage();
                    }
                });
            }
        };
        
        // Send message function
        function sendMessage() {
            const messageInput = document.getElementById('message-input');
            const content = messageInput.value.trim();
            const conversationId = <?php echo isset($_GET['conversation']) ? "'".$_GET['conversation']."'" : 'null'; ?>;
            const receiverId = <?php echo isset($other_user) ? $other_user['id'] : 'null'; ?>;
            
            if (content && conversationId && receiverId) {
                // Send message via AJAX
                fetch('send_message.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'conversation_id=' + conversationId + '&receiver_id=' + receiverId + '&content=' + encodeURIComponent(content)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Add message to chat
                        const chatMessages = document.getElementById('chat-messages');
                        const messageHtml = `
                            <div class="message outgoing">
                                <div class="message-content">
                                    <div class="message-bubble">${content.replace(/\n/g, '<br>')}</div>
                                    <div class="message-time">Just now</div>
                                </div>
                            </div>
                        `;
                        
                        // Clear input
                        messageInput.value = '';
                        sendButton.classList.remove('active');
                        
                        // If empty state exists, remove it
                        const emptyState = chatMessages.querySelector('.empty-state');
                        if (emptyState) {
                            chatMessages.innerHTML = '';
                        }
                        
                        // Append message
                        chatMessages.insertAdjacentHTML('beforeend', messageHtml);
                        scrollToBottom();
                    } else {
                        alert('Error sending message: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
            }
        }
    </script>
</body>
</html>

<?php
// Helper function to format time ago
function timeAgo($datetime) {
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    
    if ($diff->y > 0) {
        return $diff->y . 'y';
    } elseif ($diff->m > 0) {
        return $diff->m . 'mo';
    } elseif ($diff->d > 0) {
        if ($diff->d == 1) {
            return 'Yesterday';
        } elseif ($diff->d < 7) {
            return $diff->d . 'd';
        } else {
            return floor($diff->d / 7) . 'w';
        }
    } elseif ($diff->h > 0) {
        return $diff->h . 'h';
    } elseif ($diff->i > 0) {
        return $diff->i . 'm';
    } else {
        return 'Just now';
    }
}
?>
