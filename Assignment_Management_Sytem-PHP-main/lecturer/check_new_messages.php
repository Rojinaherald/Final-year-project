// Check for new messages every 10 seconds - only if we're on a chat page
if (window.location.search.includes('user=')) {
    setInterval(function() {
        var selectedUser = '<?php echo $selected_user ?? ""; ?>';
        if (!selectedUser) return;
        
        // Get the timestamp of the last message
        var lastMessageTime = $('.message').last().find('.message-time').text() || '0';
        
        $.ajax({
            type: 'GET',
            url: 'check_new_messages.php', // Use the new dedicated file
            data: { 
                user_id: selectedUser,
                last_timestamp: lastMessageTime
            },
            dataType: 'json',
            success: function(response) {
                console.log("Check messages response:", response);
                
                if (response.status === 'success' && response.messages && response.messages.length > 0) {
                    // Append new messages
                    for (var i = 0; i < response.messages.length; i++) {
                        var msg = response.messages[i];
                        var messageClass = (msg.sender_id === '<?php echo $current_user_id; ?>') ? 'sent' : 'received';
                        
                        var messageHtml = 
                            '<div class="message ' + messageClass + '">' +
                            '  <div class="message-content">' + msg.message_content + '</div>' +
                            '  <div class="message-time">' + msg.formatted_time + '</div>' +
                            '</div>';
                        
                        $('#chatContainer').append(messageHtml);
                    }
                    
                    scrollToBottom();
                }
            },
            error: function(xhr, status, error) {
                console.log("Error checking for new messages:", error);
                if (xhr.responseText) {
                    console.log("Response:", xhr.responseText);
                }
            }
        });
    }, 10000); // Check every 10 seconds
}