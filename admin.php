<?php
session_start();

// Redirect if user is not logged in or not an admin
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}
if ($_SESSION['username'] !== 'admin') {
    header('Location: place.php');
    exit;
}

// Handle toggle mute/unmute
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['toggle']) && isset($_POST['toggle_action'])) {
    $username = $_POST['username'];
    $banned_users = file_exists('secret/logs/banned_users.txt') ? file('secret/logs/banned_users.txt', FILE_IGNORE_NEW_LINES) : [];

    if (in_array($username, $banned_users)) {
        $banned_users = array_diff($banned_users, [$username]);
    } else {
        $banned_users[] = $username;
    }

    file_put_contents('secret/logs/banned_users.txt', implode(PHP_EOL, $banned_users) . PHP_EOL);
}

// Load users, banned users, feedback, and reports
$users = file('secret/logs/users.txt', FILE_IGNORE_NEW_LINES);
$banned_users = file_exists('secret/logs/banned_users.txt') ? file('secret/logs/banned_users.txt', FILE_IGNORE_NEW_LINES) : [];
$feedback_entries = file_exists('secret/logs/feedback.txt') ? file('secret/logs/feedback.txt', FILE_IGNORE_NEW_LINES) : [];
$report_json_file = 'secret/logs/reports.json';
$reports = file_exists($report_json_file) ? json_decode(file_get_contents($report_json_file), true) : [];

// Functions for image processing
function get_image_dimensions($file) {
    list($width, $height) = getimagesize($file);
    return [$width, $height];
}

function display_image($file) {
    list($width, $height) = get_image_dimensions($file);
    $max_dim = 150;
    if ($width > $max_dim || $height > $max_dim) {
        $ratio = $width / $height;
        if ($width > $height) {
            $width = $max_dim;
            $height = $max_dim / $ratio;
        } else {
            $height = $max_dim;
            $width = $max_dim * $ratio;
        }
    }
    return "<img src=\"$file\" width=\"$width\" height=\"$height\" />";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
            padding: 10px;
        }
        th {
            background-color: #f2f2f2;
        }
        .toggle-button {
            padding: 5px 10px;
            background-color: #f44336;
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 5px;
        }
        .toggle-on {
            background-color: #4CAF50;
        }
        .feedback-list, .report-list {
            max-height: 300px;
            overflow-y: auto;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            padding: 10px;
            background-color: #fff;
        }
        .report-list img {
            max-width: 150px;
            max-height: 150px;
        }
    </style>
</head>
<body>

<div class="admin-container">
    <h1>Admin Panel</h1>
    <p>Logged in as <?php echo htmlspecialchars($_SESSION['username']); ?></p>

    <!-- User management -->
    <form method="POST">
        <table>
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Status</th>
                    <th>Toggle Mute</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): 
                    $user_details = explode(':', $user); // Username:HashedPassword
                    $username = $user_details[0];
                    $is_banned = in_array($username, $banned_users);
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($username); ?></td>
                    <td><?php echo $is_banned ? 'Muted' : 'Active'; ?></td>
                    <td>
                        <button class="toggle-button <?php echo $is_banned ? '' : 'toggle-on'; ?>" name="toggle" value="Toggle" type="submit">
                            <?php echo $is_banned ? 'Unmute' : 'Mute'; ?>
                        </button>
                        <input type="hidden" name="username" value="<?php echo htmlspecialchars($username); ?>">
                        <input type="hidden" name="toggle_action" value="1">
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </form>

    <!-- Feedback entries -->
    <h2>Feedback Entries:</h2>
    <div class="feedback-list">
        <?php if (!empty($feedback_entries)): ?>
            <ul>
                <?php foreach ($feedback_entries as $entry): ?>
                    <li><?php echo htmlspecialchars($entry); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>No feedback entries available.</p>
        <?php endif; ?>
    </div>

    <!-- Reports section -->
    <h2>Reports:</h2>
    <div class="report-list">
        <?php if (!empty($reports)): ?>
            <ul>
                <?php foreach ($reports as $report): ?>
                    <li>
                        <strong>Reported by:</strong> <?php echo htmlspecialchars($report['reported_by']); ?><br>
                        <strong>Reported User:</strong> <?php echo htmlspecialchars($report['reported_user']); ?><br>
                        <strong>Abuse Type:</strong> <?php echo htmlspecialchars($report['abuse_type']); ?><br>
                        <strong>Description:</strong> <?php echo htmlspecialchars($report['description']); ?><br>
                        <strong>Timestamp:</strong> <?php echo htmlspecialchars($report['timestamp']); ?><br>
                        <?php if (!empty($report['image'])): ?>
                            <strong>Image:</strong> <?php echo display_image($report['image']); ?><br>
                        <?php endif; ?>
                    </li>
                    <hr>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>No reports available.</p>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
