<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}

$username = $_SESSION['username'];
$user_json_file = 'secret/logs/users.json';

// Load users from JSON
if (file_exists($user_json_file)) {
    $users = json_decode(file_get_contents($user_json_file), true);
} else {
    $users = [];
}
// Add user to users.json with default values if not already there
if (!isset($users[$username])) {
    $users[$username] = [
        'password' => '',        // Default empty password
        'image' => '',           // Default empty image
        'description' => '',     // Default empty description
        'rank' => '',            // Default empty rank
        'ip' => getUserIP()      // Automatically collect IP address
    ];
    file_put_contents($user_json_file, json_encode($users, JSON_PRETTY_PRINT));
}

// Check if the user is banned
$banned_users_file = 'secret/logs/banned_users.txt';
$banned_users = file_exists($banned_users_file) ? file($banned_users_file, FILE_IGNORE_NEW_LINES) : [];
$is_banned = in_array($username, $banned_users);

// Function to update user count based on active sessions
function update_user_count() {
    $session_file = 'secret/logs/session_users.json';
    $session_expiration = 300; // Session expiration in seconds (5 minutes)

    $sessions = file_exists($session_file) ? json_decode(file_get_contents($session_file), true) : [];

    $sessions[session_id()] = time();

    foreach ($sessions as $session_id => $last_active) {
        if ($last_active < time() - $session_expiration) {
            unset($sessions[$session_id]);
        }
    }

    file_put_contents($session_file, json_encode($sessions, JSON_PRETTY_PRINT));

    return count($sessions);
}

// Get the number of online users
$online_users = update_user_count();

// Handle message submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !$is_banned) {
    $botUsername = 'Bot'; // Change bot username here
    $timestamp = date('g:i A');

    if (isset($_POST['message']) && !empty(trim($_POST['message']))) {
        $message = trim($_POST['message']);
        
        if (strpos($message, '!feedback') === 0) {
            $feedback = trim(str_replace('!feedback', '', $message));
            if (!empty($feedback)) {
                $feedback_entry = "$timestamp - $username: $feedback" . PHP_EOL;
                file_put_contents('secret/logs/feedback.txt', $feedback_entry, FILE_APPEND);
            }
        } elseif ($message === '!clear' && $username === 'admin') {
            $chatLogsFile = 'secret/logs/chatlogs.txt';
            $uploadsDir = 'secret/logs/uploads/';
            
            if (file_exists($chatLogsFile)) {
                file_put_contents($chatLogsFile, '');
            }

            foreach (glob($uploadsDir . '*') as $file) {
                unlink($file);
            }

            $log_entry = "$botUsername: Chat cleared by $username" . PHP_EOL;
            file_put_contents($chatLogsFile, $log_entry, FILE_APPEND);
        } elseif (strpos($message, '!botchat') === 0 && $username === 'admin') {
            $botMessage = trim(str_replace('!botchat', '', $message));
            
            if (!empty($botMessage)) {
                $log_entry = "$timestamp - $botUsername: $botMessage" . PHP_EOL;
                file_put_contents('secret/logs/chatlogs.txt', $log_entry, FILE_APPEND);
            }
        } elseif ($message === '!bot') {
            $botResponse = "$timestamp - $botUsername: Hi, I'm $botUsername, the website bot!" . PHP_EOL;
            file_put_contents('secret/logs/chatlogs.txt', $botResponse, FILE_APPEND);
        } else {
            $log_entry = "$timestamp - $username: $message" . PHP_EOL;
            file_put_contents('secret/logs/chatlogs.txt', $log_entry, FILE_APPEND);
        }
    }

    if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
        $upload_dir = 'secret/logs/uploads/';
        $upload_file = $upload_dir . basename($_FILES['image']['name']);
        $imageFileType = strtolower(pathinfo($upload_file, PATHINFO_EXTENSION));

        $check = getimagesize($_FILES['image']['tmp_name']);
        if ($check !== false) {
            if ($_FILES['image']['size'] <= 5000000) {
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
                if (in_array($imageFileType, $allowed_extensions)) {
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_file)) {
                        $log_entry = "$timestamp - $username: <img src='$upload_file' alt='Image' style='width:auto; max-height:100px;'>" . PHP_EOL;
                        file_put_contents('secret/logs/chatlogs.txt', $log_entry, FILE_APPEND);
                    } else {
                        echo "Unknown Error.";
                    }
                } else {
                    echo "JPG, JPEG, PNG & GIF files are allowed.";
                }
            } else {
                echo "Large file.";
            }
        } else {
            echo "File is not an image.";
        }
    }

    header('Location: chat.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Chat</title>
    <script>
        // Focus on the input field when the page loads
        document.addEventListener('DOMContentLoaded', function() {
            messageInput.focus();
        });

        // Check for ban status
        function checkBanStatus() {
            var xhr = new XMLHttpRequest();
            xhr.open('GET', 'check_ban_status.php', true);
            xhr.onload = function() {
                if (xhr.status === 200) {
                    if (xhr.responseText === 'banned') {
                        window.location.reload();
                    }
                }
            };
            xhr.send();
        }

        setInterval(checkBanStatus, 5000);

        // Function to show user info popup
        function showUserInfo(event) {
            if (event.target.classList.contains('user-image')) {
                var username = event.target.getAttribute('data-username');
                var rank = event.target.getAttribute('data-rank');
                var description = event.target.getAttribute('data-description');
                var imageSrc = event.target.src;
                
                var popup = document.getElementById('userInfoPopup');
                popup.querySelector('.popup-image').src = imageSrc;
                popup.querySelector('.popup-username').textContent = 'Username: ' + username;
                popup.querySelector('.popup-rank').textContent = 'Rank: ' + rank;
                popup.querySelector('.popup-description').textContent = 'Description: ' + description;
                
                popup.style.display = 'block';
            }
        }

        function closeUserInfo() {
            document.getElementById('userInfoPopup').style.display = 'none';
        }

        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('chat-log').addEventListener('click', showUserInfo);
            document.getElementById('popupClose').addEventListener('click', closeUserInfo);
        });

    </script>
    <style>
        body {
            background-color: #2b1b17;
            color: #e6b8b7;
            font-family: Arial, sans-serif;
            margin: 0;
            display: flex;
            flex-direction: column;
            height: 100vh;
            justify-content: center;
            align-items: center;
        }

        .header {
            width: 80%;
            max-width: 600px;
            margin-bottom: 10px;
        }

        a {
            color: #ffb3b3;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        .chat-log {
            width: 80%;
            max-width: 600px;
            height: 300px;
            background-color: #4d0a0a;
            border: 1px solid #b33a3a;
            padding: 10px;
            overflow-y: scroll;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
        }

        .chat-message {
            display: flex;
            align-items: flex-start;
            margin-bottom: 10px;
        }

        .user-image {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            margin-right: 10px;
        }

        .message-content {
            display: flex;
            flex-direction: column;
        }

        .timestamp {
            font-size: 0.8em;
            color: #ccc;
        }

        .username {
            font-weight: bold;
            color: #ffb3b3;
        }

        .message {
            color: #e6b8b7;
        }

        .chat-input {
            width: 80%;
            max-width: 600px;
            background-color: #330d0d;
            border: 1px solid #b33a3a;
            padding: 10px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            <?php if ($is_banned) echo 'display: none;'; ?>
        }

        .chat-input input[type="text"] {
            flex-grow: 1;
            padding: 10px;
            background-color: #330d0d;
            border: 1px solid #b33a3a;
            border-radius: 4px;
            color: #e6b8b7;
        }

        .chat-input input[type="file"] {
            display: none;
        }

        .custom-file-upload {
            width: 40px;
            height: 40px;
            background-image: url('public/images/upload.png');
            background-size: cover;
            border: 1px solid #b33a3a;
            border-radius: 4px;
            cursor: pointer;
        }

        p {
            margin: 0;
            padding: 0;
            text-align: center;
        }
        .user-image {
            cursor: pointer;
            width: 50px;
            height: 50px;
            border-radius: 50%;
        }

        .popup {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 300px;
            background-color: #4d0a0a;
            padding: 20px;
            border: 1px solid #b33a3a;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }

        .popup-image {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            display: block;
            margin: 0 auto 10px;
        }

        .popup-close {
            position: absolute;
            top: 10px;
            right: 10px;
            cursor: pointer;
        }

        .popup-content {
            text-align: center;
            color: #e6b8b7;
        }
    </style>
</head>
<body>
    <div class="header">
        <p>Online Users: <?php echo $online_users; ?></p>
        <p><a href="logout.php">Logout</a></p>
    </div>
    <div class="chat-log" id="chat-log">
        <?php include 'load_chat.php'; ?>
    </div>
    <form method="post" enctype="multipart/form-data" class="chat-input">
    <label for="file-upload" class="custom-file-upload"></label>
        <input type="file" name="image" id="file-upload">
        <input type="text" name="message" id="messageInput" placeholder="Type a message..." required>
    </form>
    <!-- User Info Popup -->
    <div id="userInfoPopup" class="popup">
        <span id="popupClose" class="popup-close">X</span>
        <img src="" alt="User Image" class="popup-image">
        <div class="popup-content">
            <div class="popup-username"></div>
            <div class="popup-rank"></div>
            <div class="popup-description"></div>
        </div>
    </div>
</body>
</html>
