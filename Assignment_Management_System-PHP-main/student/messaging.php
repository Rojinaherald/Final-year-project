<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Start session
session_start();

// Include database connection
include "../db_handler.php";

// inlcude navbar



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

// Get all users data
try {
    $sql = "SELECT id, name, surname, `rank` FROM users";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        logError("Prepare failed for user query: " . $conn->error);
        die("Database error: " . $conn->error);
    }
    
    $stmt->execute();
    $users_result = $stmt->get_result();
    $users = array();
    while($row = $users_result->fetch_assoc()) {
        $users[$row['id']] = $row;
    }
    $stmt->close();
    logError("User query successful, found " . count($users) . " users");
} catch (Exception $e) {
    logError("Exception in user query: " . $e->getMessage());
    die("Database error: " . $e->getMessage());
}

// Handle message sending - now just a generic message to everyone (group chat)
if(isset($_POST['send_message']) && isset($_POST['message'])) {
    $message_content = $_POST['message'];
    
    logError("Attempting to send group message: From: $current_user_id, Content: $message_content");
    
    // Validate inputs
    if (empty($message_content)) {
        logError("Error: Empty message content");
        $error_message = "Message cannot be empty!";
    } else {
        try {
            // Insert the message with NULL receiver (indicating group message)
            $insert_sql = "INSERT INTO messages (sender_id, receiver_id, message_content, timestamp, is_read) 
                           VALUES (?, 'admin', ?, NOW(), 1)";
            $insert_stmt = $conn->prepare($insert_sql);
            
            if (!$insert_stmt) {
                logError("Prepare failed for message insert: " . $conn->error);
                $error_message = "Failed to prepare message insert.";
            } else {
                logError("Insert statement prepared successfully");
                
                // Convert user ID to integer to ensure it fits in the database field
                $current_user_id = (int)$current_user_id;
                
                // Dump the data types and values for debugging
                logError("sender_id: " . gettype($current_user_id) . " = $current_user_id");
                logError("message_content: " . gettype($message_content) . " = $message_content");
                
                // Use the correct bind_param types - 'i' for integer sender_id
                $insert_stmt->bind_param("is", $current_user_id, $message_content);
                
                // Try to execute and get any errors
                $success = $insert_stmt->execute();
                
                if (!$success) {
                    logError("Execute failed for message insert: " . $insert_stmt->error);
                    logError("MySQL Error #: " . $conn->errno);
                    $error_message = "Failed to send message: " . $insert_stmt->error;
                } else {
                    logError("Message inserted successfully with ID: " . $conn->insert_id);
                    $success_message = "Message sent to group chat!";
                    
                    // Redirect to refresh the page
                    header("Location: messaging.php");
                    exit;
                }
                
                $insert_stmt->close();
            }
        } catch (Exception $e) {
            logError("Exception during message insert: " . $e->getMessage());
            $error_message = "Error: " . $e->getMessage();
        }
    }
}

// Get ALL messages from all users (group chat style) with sender information
try {
    $sql = "SELECT m.*, u.name as sender_name, u.surname as sender_surname, u.rank as sender_rank
           FROM messages m
           LEFT JOIN users u ON m.sender_id = u.id
           ORDER BY m.timestamp ASC";
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        logError("Prepare failed for all messages query: " . $conn->error);
        $messages = array();
    } else {
        $stmt->execute();
        $messages_result = $stmt->get_result();
        $message_count = $messages_result->num_rows;
        logError("Found " . $message_count . " total messages in the system");
        
        $messages = array();
        while($row = $messages_result->fetch_assoc()) {
            $messages[] = $row;
        }
        
        $stmt->close();
    }
} catch (Exception $e) {
    logError("Exception during all messages query: " . $e->getMessage());
    $messages = array();
}

// Get active users count
$active_users = count($users);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">



    <title>Group Chat</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
    <style>
        body {
            background-color: lightblue;
        }
        .chat-container {
            height: 600px;
            overflow-y: auto;
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 15px;
            background-color: #f9f9f9;
        }
        .message {
            margin-bottom: 15px;
            padding: 10px;
            border-radius: 5px;
            max-width: 90%;
            position: relative;
        }
        .current-user {
            background-color: #DCF8C6;
            margin-left: auto;
        }
        .other-user {
            background-color: #FFFFFF;
        }
        .message-time {
            font-size: 0.75rem;
            color: #888;
            text-align: right;
        }
        .message-header {
            font-size: 0.9rem;
            color: #337ab7;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .user-role {
            font-size: 0.8rem;
            color: #777;
            font-style: italic;
            margin-left: 5px;
        }
        .user-list {
            height: 300px;
            overflow-y: auto;
            border: 1px solid #ddd;
        }
        .user-item {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        .active-now {
            color: #5cb85c;
            font-size: 0.8rem;
            font-weight: bold;
        }
        .debug-info {
            margin-top: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        #wrapper {
            padding-left: 0;
            -webkit-transition: all 0.5s ease;
            -moz-transition: all 0.5s ease;
            -o-transition: all 0.5s ease;
            transition: all 0.5s ease;
        }
        #page-wrapper {
            width: 100%;
            padding: 15px;
        }
        .msg-date-header {
            text-align: center;
            margin: 15px 0;
            font-size: 0.9rem;
            color: #777;
            position: relative;
        }
        .msg-date-header:before {
            content: "";
            position: absolute;
            height: 1px;
            background-color: #ddd;
            width: 40%;
            top: 50%;
            left: 0;
        }
        .msg-date-header:after {
            content: "";
            position: absolute;
            height: 1px;
            background-color: #ddd;
            width: 40%;
            top: 50%;
            right: 0;
        }
        .send-message-form {
            margin-top: 15px;
            padding: 15px;
            background-color: #f5f5f5;
            border-radius: 5px;
            border: 1px solid #ddd;
        }
        .chat-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .online-indicator {
            font-size: 0.85rem;
            color: #5cb85c;
        }
    </style>
</head>
<body>
    <?php 
        include "../includes/header.php";
        
        // Include appropriate navbar based on user rank
       
            include "../includes/admin-navbar.php";
     
    ?>
    
    <div id="wrapper">
        <div id="page-wrapper">
            <div class="container-fluid">
                <div class="container">
                    <h1>Group Chat</h1>
                    <hr>
                    
                    <?php if(isset($error_message)): ?>
                        <div class="alert alert-danger"><?php echo $error_message; ?></div>
                    <?php endif; ?>
                    
                    <?php if(isset($success_message)): ?>
                        <div class="alert alert-success"><?php echo $success_message; ?></div>
                    <?php endif; ?>
                    
                    <div class="row">
                        <div class="col-md-9">
                            <div class="panel panel-primary">
                                <div class="panel-heading">
                                    <div class="chat-title">
                                        <h3 class="panel-title">Group Chat</h3>
                                        <span class="online-indicator">
                                            <i class="glyphicon glyphicon-user"></i> <?php echo $active_users; ?> Users Online
                                        </span>
                                    </div>
                                </div>
                                <div class="panel-body">
                                    <div class="chat-container" id="chatContainer">
                                        <?php
                                        try {
                                            if (empty($messages)) {
                                                echo '<div class="alert alert-info">No messages yet. Start a conversation!</div>';
                                            } else {
                                                $current_date = '';
                                                
                                                foreach($messages as $msg) {
                                                    // Add date header if date changes
                                                    $msg_date = date('Y-m-d', strtotime($msg['timestamp']));
                                                    if ($msg_date != $current_date) {
                                                        $display_date = date('F d, Y', strtotime($msg['timestamp']));
                                                        echo "<div class='msg-date-header'>{$display_date}</div>";
                                                        $current_date = $msg_date;
                                                    }
                                                    
                                                    $message_class = ($msg['sender_id'] == $current_user_id) ? 'current-user' : 'other-user';
                                                    $formatted_time = date('g:i A', strtotime($msg['timestamp']));
                                                    
                                                    // Get sender name and role
                                                    $sender_name = "{$msg['sender_name']} {$msg['sender_surname']}";
                                                    $sender_role = $msg['sender_rank'];
                                                    ?>
                                                    <div class="message <?php echo $message_class; ?>">
                                                        <div class="message-header">
                                                            <?php echo $sender_name; ?> 
                                                            <span class="user-role"><?php echo ucfirst($sender_role); ?></span>
                                                        </div>
                                                        <div class="message-content"><?php echo htmlspecialchars($msg['message_content']); ?></div>
                                                        <div class="message-time"><?php echo $formatted_time; ?></div>
                                                    </div>
                                                <?php 
                                                }
                                            }
                                        } catch (Exception $e) {
                                            logError("Error displaying messages: " . $e->getMessage());
                                            echo '<div class="alert alert-danger">Error displaying messages. Please try again later.</div>';
                                        }
                                        ?>
                                    </div>
                                    
                                    <form method="post" action="messaging.php" class="send-message-form">
                                        <div class="form-group">
                                            <div class="input-group">
                                                <textarea class="form-control" name="message" id="message" placeholder="Type your message..." required rows="2"></textarea>
                                                <span class="input-group-btn">
                                                    <button type="submit" name="send_message" class="btn btn-primary" style="height:100%;">
                                                        <i class="glyphicon glyphicon-send"></i> Send
                                                    </button>
                                                </span>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="panel panel-primary">
                                <div class="panel-heading">
                                    <h3 class="panel-title">Active Users</h3>
                                </div>
                                <div class="panel-body p-0">
                                    <div class="user-list">
                                        <?php
                                        foreach($users as $user) {
                                            $is_current = ($user['id'] == $current_user_id);
                                            $active_class = $is_current ? 'active-now' : '';
                                            $user_status = $is_current ? ' (You)' : '';
                                            
                                            echo "<div class='user-item'>";
                                            echo "<div class='{$active_class}'>{$user['name']} {$user['surname']}{$user_status}</div>";
                                            echo "<div class='user-role'>".ucfirst($user['rank'])."</div>";
                                            echo "</div>";
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if (isset($_SESSION['rank']) && $_SESSION['rank'] == 'admin'): ?>
                            <!-- Debug information - only visible to admins -->
                            <div class="debug-info">
                                <h5>Debug Information</h5>
                                <p><strong>Session ID:</strong> <?php echo session_id(); ?></p>
                                <p><strong>Current User ID:</strong> <?php echo htmlspecialchars($current_user_id); ?></p>
                                <p><strong>Current User Rank:</strong> <?php echo htmlspecialchars($current_user_rank); ?></p>
                                <p><strong>PHP Version:</strong> <?php echo phpversion(); ?></p>
                                <p><strong>MySQL Version:</strong> <?php echo $conn->server_info; ?></p>
                                <p><strong>Messages Count:</strong> <?php echo count($messages); ?></p>
                                <p>
                                    <button class="btn btn-sm btn-default" onclick="window.open('messaging_debug.log', '_blank')">View Debug Log</button>
                                </p>
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
            var chatContainer = document.getElementById('chatContainer');
            if (chatContainer) {
                chatContainer.scrollTop = chatContainer.scrollHeight;
            }
        }

        // Scroll to bottom on page load
        $(document).ready(function() {
            scrollToBottom();
            
            // Set a timer to refresh the page every 30 seconds
            setTimeout(function() {
                window.location.reload();
            }, 30000);
            
            // Focus on message input
            $('#message').focus();
        });
    </script>
</body>
</html>