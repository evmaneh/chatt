<?php
// Load chat logs
$chatlogs_file = 'secret/logs/chatlogs.txt';
if (file_exists($chatlogs_file)) {
    $chatlogs = file($chatlogs_file, FILE_IGNORE_NEW_LINES);
} else {
    $chatlogs = [];
}

foreach ($chatlogs as $log_entry) {
    // Extract the username and message from the log entry
    preg_match('/^(\d{1,2}:\d{2} [APM]{2}) - (.*?): (.*)$/', $log_entry, $matches);
    
    if (count($matches) === 4) {
        $timestamp = $matches[1];
        $user = htmlspecialchars($matches[2]);
        $message = $matches[3];
        
        // Get the user's image and details
        $users_file = 'secret/logs/users.json';
        if (file_exists($users_file)) {
            $users = json_decode(file_get_contents($users_file), true);
            $user_data = isset($users[$user]) ? $users[$user] : [
                'image' => 'public/images/default.png',
                'rank' => 'Unknown',
                'description' => 'No description available'
            ];
            $user_image = htmlspecialchars($user_data['image']);
            $user_rank = htmlspecialchars($user_data['rank']);
            $user_description = htmlspecialchars($user_data['description']);
        } else {
            $user_image = 'public/images/default.png';
            $user_rank = 'Unknown';
            $user_description = 'No description available';
        }

        // Output the message with image
        echo "<div class='chat-message'>";
        echo "<img src='" . $user_image . "' alt='User Image' class='user-image' data-username='$user' data-rank='$user_rank' data-description='$user_description'>";
        echo "<div class='message-content'>";
        echo "<span class='timestamp'>$timestamp</span>";
        echo "<span class='username'>$user:</span>";
        echo "<span class='message'>$message</span>";
        echo "</div></div>";
    }
}
?>
