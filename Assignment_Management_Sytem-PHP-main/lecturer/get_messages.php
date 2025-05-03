<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Start session
session_start();

// Include database connection
include "../db_handler.php";

// Create log function
function logError($message) {
    // Log to PHP error log
    error_log($message);
    // Also save to a custom file for easier access
    $logFile = 'messaging_debug.log';
    if (is_writable($logFile) || (!file_exists($logFile) && is_writable(dirname($logFile)))) {
        file_put_contents($logFile, date('[Y-m-d H:i:s] ') . $message . PHP_EOL, FILE_APPEND);
    } else {
        error_log("Permission denied: Unable to write to log file $logFile");
    }
}

// Log session information
logError("Session data: " . print_r($_SESSION, true));
logError("POST data: " . print_r($_POST, true));

// Check login status
if (!isset($_SESSION['id']) || !isset($_SESSION['rank'])) {
    logError("User not logged in or session data missing");
    header("Location: ../login.php");
    exit;
}

$current_user_id = $_SESSION['id'];
$current_user_rank = $_SESSION['rank'];
logError("Current user ID: $current_user_id, Rank: $current_user_rank");

// Get user data
try {
    $sql = "SELECT id, name, surname, `rank` FROM users WHERE id != ?";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        logError("Prepare failed for user query: " . $conn->error);
        die("Database error: " . $conn->error);
    }
    
    $stmt->bind_param("s", $current_user_id);
    $stmt->execute();
    $users_result = $stmt->get_result();
    $stmt->close();
    logError("User query successful, found " . $users_result->num_rows . " other users");
} catch (Exception $e) {
    logError("Exception in user query: " . $e->getMessage());
    die("Database error: " . $e->getMessage());
}

// Handle message sending
if(isset($_POST['send_message']) && isset($_POST['receiver_id']) && isset($_POST['message'])) {
    $receiver_id = $_POST['receiver_id'];
    $message_content = $_POST['message'];
    
    logError("Attempting to send message: From: $current_user_id, To: $receiver_id, Content: $message_content");
    
    // Validate inputs
    if (empty($receiver_id) || empty($message_content)) {
        logError("Error: Empty receiver ID or message content");
        $error_message = "Message or receiver cannot be empty!";
    } else {
        try {
            // First, verify the receiver exists
            $check_sql = "SELECT id FROM users WHERE id = ?";
            $check_stmt = $conn->prepare($check_sql);
            
            if (!$check_stmt) {
                logError("Prepare failed for receiver check: " . $conn->error);
                $error_message = "Database error during validation.";
            } else {
                $check_stmt->bind_param("s", $receiver_id);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                
                if ($check_result->num_rows === 0) {
                    logError("Error: Receiver ID $receiver_id does not exist");
                    $error_message = "Invalid receiver.";
                } else {
                    // Now proceed with the insert
                    $insert_sql = "INSERT INTO messages (sender_id, receiver_id, message_content, timestamp, is_read) 
                               VALUES (?, ?, ?, NOW(), 0)";
                    $insert_stmt = $conn->prepare($insert_sql);
                    
                    if (!$insert_stmt) {
                        logError("Prepare failed for message insert: " . $conn->error);
                        $error_message = "Failed to prepare message insert.";
                    } else {
                        // Make sure IDs are strings
                        $current_user_id = (string)$current_user_id;
                        $receiver_id = (string)$receiver_id;
                        
                        $insert_stmt->bind_param("sss", $current_user_id, $receiver_id, $message_content);
                        
                        $success = $insert_stmt->execute();
                        
                        if (!$success) {
                            logError("Execute failed for message insert: " . $insert_stmt->error);
                            $error_message = "Failed to send message: " . $insert_stmt->error;
                        } else {
                            logError("Message inserted successfully with ID: " . $conn->insert_id);
                            $success_message = "Message sent successfully!";
                            
                            // Set selected user to ensure conversation remains visible after message sent
                            $selected_user = $receiver_id;
                            
                            // Redirect to refresh page and show new message
                            // This ensures the sent message appears immediately
                            header("Location: messaging.php?user=" . $receiver_id);
                            exit();
                        }
                        
                        $insert_stmt->close();
                    }
                    
                    $check_stmt->close();
                }
            }
        } catch (Exception $e) {
            logError("Exception during message insert: " . $e->getMessage());
            $error_message = "Error: " . $e->getMessage();
        }
    }
}

// Get selected user's messages
$selected_user = isset($_GET['user']) ? $_GET['user'] : (isset($_POST['receiver_id']) ? $_POST['receiver_id'] : null);
$messages = array();

if($selected_user) {
    logError("Selected user for conversation: $selected_user");
    
    try {
        // Mark messages as read
        $sql = "UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ?";
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            logError("Prepare failed for marking messages read: " . $conn->error);
        } else {
            $stmt->bind_param("ss", $selected_user, $current_user_id);
            $stmt->execute();
            logError("Marked " . $stmt->affected_rows . " messages as read");
            $stmt->close();
        }
        
        // Get conversation
        $sql = "SELECT m.*, u_sender.name AS sender_name, u_sender.surname AS sender_surname, 
                u_receiver.name AS receiver_name, u_receiver.surname AS receiver_surname 
                FROM messages m
                JOIN users u_sender ON m.sender_id = u_sender.id
                JOIN users u_receiver ON m.receiver_id = u_receiver.id
                WHERE (m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?)
                ORDER BY m.timestamp ASC";
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            logError("Prepare failed for conversation query: " . $conn->error);
        } else {
            $stmt->bind_param("ssss", $current_user_id, $selected_user, $selected_user, $current_user_id);
            $stmt->execute();
            $messages_result = $stmt->get_result();
            logError("Found " . $messages_result->num_rows . " messages in conversation");
            
            while($row = $messages_result->fetch_assoc()) {
                $messages[] = $row;
            }
            
            $stmt->close();
        }
    } catch (Exception $e) {
        logError("Exception during conversation query: " . $e->getMessage());
    }
}

// Function to get unread message count
function getUnreadMessageCount($user_id, $conn) {
    try {
        $sql = "SELECT COUNT(*) as unread FROM messages WHERE receiver_id = ? AND is_read = 0";
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            logError("Prepare failed for unread count: " . $conn->error);
            return 0;
        }
        
        $stmt->bind_param("s", $user_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result['unread'];
    } catch (Exception $e) {
        logError("Exception in unread count: " . $e->getMessage());
        return 0;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messaging System</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <style>
        body {
            background-color: #f0f8ff;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .chat-container {
            height: 400px;
            overflow-y: auto;
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 15px;
            background-color: #f9f9f9;
            border-radius: 8px;
        }
        .message {
            margin-bottom: 10px;
            padding: 10px;
            border-radius: 12px;
            max-width: 70%;
            position: relative;
            clear: both;
            display: inline-block;
        }
        .sent {
            background-color: #dcf8c6;
            float: right;
            border-bottom-right-radius: 2px;
            box-shadow: 0 1px 1px rgba(0,0,0,0.1);
        }
        .received {
            background-color: #ffffff;
            float: left;
            border-bottom-left-radius: 2px;
            box-shadow: 0 1px 1px rgba(0,0,0,0.1);
        }
        .message-time {
            font-size: 0.75rem;
            color: #888;
            text-align: right;
            margin-top: 5px;
        }
        .user-list {
            height: 400px;
            overflow-y: auto;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: #fff;
        }
        .user-item {
            padding: 10px 15px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .user-item:hover {
            background-color: #f5f5f5;
        }
        .user-item.active {
            background-color: #007bff;
            color: white;
        }
        .unread-badge {
            background-color: #dc3545;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 0.75rem;
            margin-left: 5px;
        }
        .debug-info {
            margin-top: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 0.9rem;
        }
        #wrapper {
            padding-left: 0;
            transition: all 0.5s ease;
        }
        #page-wrapper {
            width: 100%;
            padding: 15px;
        }
        .message-form {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 0 0 8px 8px;
        }
        .message-input {
            border-radius: 20px;
            padding: 10px 15px;
            resize: none;
        }
        .send-btn {
            border-radius: 20px;
            padding: 8px 20px;
        }
        .panel-title {
            font-weight: 600;
        }
        .chat-header {
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .user-status {
            font-size: 0.8rem;
            color: #28a745;
        }
        .no-messages {
            text-align: center;
            color: #6c757d;
            margin-top: 150px;
        }
        
        /* Clear float after messages */
        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }
        
        /* For message bubbles */
        .message-wrapper {
            width: 100%;
            overflow: hidden;
            margin-bottom: 10px;
        }
        
        /* Mobile responsiveness */
        @media (max-width: 768px) {
            .chat-container {
                height: 300px;
            }
            .user-list {
                height: 200px;
                margin-bottom: 20px;
            }
        }
    </style>
</head>
<body>
    <?php 
        include "../includes/header.php";
        
        // Include appropriate navbar based on user rank
        if ($current_user_rank == 'admin') {
            include "../includes/admin-navbar.php";
        } elseif ($current_user_rank == 'lecturer') {
            include "../includes/lecturer-navbar.php";
        } elseif ($current_user_rank == 'student') {
            include "../includes/student-navbar.php";
        } elseif ($current_user_rank == 'supervisor') {
            include "../includes/supervisor-navbar.php";
        }
    ?>
    
    <div id="wrapper">
        <div id="page-wrapper">
            <div class="container-fluid">
                <div class="container">
                    <h1 class="my-4"><i class="fas fa-comments text-primary mr-2"></i>Messaging System</h1>
                    <hr>
                    
                    <?php if(isset($error_message)): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                            <i class="fas fa-exclamation-circle mr-1"></i> <?php echo $error_message; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if(isset($success_message)): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                            <i class="fas fa-check-circle mr-1"></i> <?php echo $success_message; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="card mb-4">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="card-title mb-0"><i class="fas fa-users mr-2"></i>Users</h5>
                                </div>
                                <div class="card-body p-0">
                                    <div class="user-list">
                                        <?php
                                        try {
                                            // Get unread message counts
                                            $sql = "SELECT sender_id, COUNT(*) as unread 
                                                   FROM messages 
                                                   WHERE receiver_id = ? AND is_read = 0 
                                                   GROUP BY sender_id";
                                            $stmt = $conn->prepare($sql);
                                            
                                            if (!$stmt) {
                                                logError("Prepare failed for unread counts: " . $conn->error);
                                            } else {
                                                $stmt->bind_param("s", $current_user_id);
                                                $stmt->execute();
                                                $unread_result = $stmt->get_result();
                                                $stmt->close();
                                                
                                                $unread_counts = array();
                                                while($row = $unread_result->fetch_assoc()) {
                                                    $unread_counts[$row['sender_id']] = $row['unread'];
                                                }
                                                
                                                // Display user list
                                                if ($users_result->num_rows > 0) {
                                                    $users_result->data_seek(0); // Reset result pointer
                                                    while($user = $users_result->fetch_assoc()) {
                                                        $active_class = ($selected_user == $user['id']) ? 'active' : '';
                                                        $unread_count = isset($unread_counts[$user['id']]) ? $unread_counts[$user['id']] : 0;
                                                        $unread_badge = $unread_count > 0 ? "<span class='unread-badge float-right'>{$unread_count}</span>" : "";
                                                        
                                                        echo "<a href='?user={$user['id']}' class='text-decoration-none'>";
                                                        echo "<div class='user-item {$active_class}'>";
                                                        echo "<i class='fas fa-user-circle mr-2'></i>";
                                                        echo "{$user['name']} {$user['surname']} ";
                                                        echo "<small class='text-muted'>({$user['rank']})</small> {$unread_badge}";
                                                        echo "</div>";
                                                        echo "</a>";
                                                    }
                                                } else {
                                                    echo "<div class='p-3'>No other users found.</div>";
                                                }
                                            }
                                        } catch (Exception $e) {
                                            logError("Exception in displaying users: " . $e->getMessage());
                                            echo "<div class='p-3 text-danger'>Error loading users.</div>";
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="card mb-4">
                                <div class="card-header bg-primary text-white">
                                    <?php
                                    if($selected_user) {
                                        try {
                                            $sql = "SELECT name, surname, `rank` FROM users WHERE id = ?";
                                            $stmt = $conn->prepare($sql);
                                            
                                            if (!$stmt) {
                                                logError("Prepare failed for user info: " . $conn->error);
                                                echo "<h5 class='card-title mb-0'><i class='fas fa-comment-dots mr-2'></i>Chat</h5>";
                                            } else {
                                                $stmt->bind_param("s", $selected_user);
                                                $stmt->execute();
                                                $user_info = $stmt->get_result()->fetch_assoc();
                                                $stmt->close();
                                                
                                                if ($user_info) {
                                                    echo "<h5 class='card-title mb-0'>";
                                                    echo "<i class='fas fa-comment-dots mr-2'></i>Chat with {$user_info['name']} {$user_info['surname']}";
                                                    echo " <small>({$user_info['rank']})</small>";
                                                    echo "</h5>";
                                                } else {
                                                    echo "<h5 class='card-title mb-0'><i class='fas fa-comment-dots mr-2'></i>Chat with Unknown User</h5>";
                                                }
                                            }
                                        } catch (Exception $e) {
                                            logError("Exception in displaying user info: " . $e->getMessage());
                                            echo "<h5 class='card-title mb-0'><i class='fas fa-comment-dots mr-2'></i>Chat</h5>";
                                        }
                                    } else {
                                        echo "<h5 class='card-title mb-0'><i class='fas fa-comment-dots mr-2'></i>Select a user to start chatting</h5>";
                                    }
                                    ?>
                                </div>
                                <div class="card-body p-0">
                                    <?php if($selected_user): ?>
                                        <div class="chat-container" id="chatContainer">
                                            <?php if(count($messages) > 0): ?>
                                                <?php foreach($messages as $msg): ?>
                                                    <div class="message-wrapper clearfix">
                                                        <?php 
                                                        $message_class = ($msg['sender_id'] == $current_user_id) ? 'sent' : 'received';
                                                        $formatted_time = date('M d, Y g:i A', strtotime($msg['timestamp']));
                                                        ?>
                                                        <div class="message <?php echo $message_class; ?>">
                                                            <div class="message-content"><?php echo htmlspecialchars($msg['message_content']); ?></div>
                                                            <div class="message-time"><?php echo $formatted_time; ?></div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <p class="no-messages">No messages yet. Start a conversation!</p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="message-form">
                                            <form method="post" action="messaging.php" id="messageForm">
                                                <input type="hidden" name="receiver_id" value="<?php echo $selected_user; ?>">
                                                <div class="form-group">
                                                    <textarea class="form-control message-input" name="message" placeholder="Type your message..." required rows="2"></textarea>
                                                </div>
                                                <button type="submit" name="send_message" class="btn btn-primary send-btn">
                                                    <i class="fas fa-paper-plane mr-1"></i> Send
                                                </button>
                                            </form>
                                        </div>
                                    <?php else: ?>
                                        <div class="p-5 text-center">
                                            <i class="fas fa-comments text-muted mb-3" style="font-size: 3rem;"></i>
                                            <p class="lead">Please select a user from the list to start a conversation.</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <?php if (isset($_SESSION['rank']) && $_SESSION['rank'] == 'admin'): ?>
                            <!-- Debug information - only visible to admins -->
                            <div class="card debug-info">
                                <div class="card-header bg-light">
                                    <h5 class="card-title mb-0"><i class="fas fa-bug mr-2"></i>Debug Information</h5>
                                </div>
                                <div class="card-body">
                                    <p><strong>Session ID:</strong> <?php echo session_id(); ?></p>
                                    <p><strong>Current User ID:</strong> <?php echo htmlspecialchars($current_user_id); ?></p>
                                    <p><strong>Current User Rank:</strong> <?php echo htmlspecialchars($current_user_rank); ?></p>
                                    <p><strong>Selected User:</strong> <?php echo htmlspecialchars($selected_user ?? 'None'); ?></p>
                                    <p><strong>PHP Version:</strong> <?php echo phpversion(); ?></p>
                                    <p><strong>MySQL Version:</strong> <?php echo $conn->server_info; ?></p>
                                    <p><strong>Database Name:</strong> <?php echo $conn->database ?? 'Unknown'; ?></p>
                                    <p>
                                        <button class="btn btn-sm btn-outline-secondary" onclick="window.open('messaging_debug.log', '_blank')">
                                            <i class="fas fa-file-alt mr-1"></i> View Debug Log
                                        </button>
                                    </p>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto-scroll to the bottom of the chat container
        function scrollToBottom() {
            const chatContainer = document.getElementById('chatContainer');
            if (chatContainer) {
                chatContainer.scrollTop = chatContainer.scrollHeight;
            }
        }

        // Scroll to bottom on page load
        $(document).ready(function() {
            scrollToBottom();
            
            // Add polling mechanism to refresh messages every 10 seconds
            <?php if($selected_user): ?>
            function refreshMessages() {
                const selectedUser = '<?php echo $selected_user; ?>';
                
                $.ajax({
                    url: 'get_messages.php',
                    type: 'GET',
                    data: {
                        user: selectedUser
                    },
                    success: function(data) {
                        $('#chatContainer').html(data);
                        scrollToBottom();
                    }
                });
            }
            
            // Refresh every 10 seconds
            setInterval(refreshMessages, 10000);
            
            // Prevent default form behavior for immediate visual feedback
            $('#messageForm').submit(function(e) {
                const messageText = $(this).find('textarea[name="message"]').val().trim();
                
                if (messageText !== '') {
                    // Already showing immediate visual feedback
                    // But we'll let the form submit naturally to ensure proper database insertion
                    return true;
                }
                e.preventDefault();
                return false;
            });
            <?php endif; ?>
        });
    </script>
</body>
</html>