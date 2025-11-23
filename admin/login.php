<?php
// login.php - Login Page
session_start();
require_once '../includes/config.php';

$error = '';

// 1. Ensure login_attempts table exists
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS login_attempts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ip_address VARCHAR(45),
        attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
} catch (PDOException $e) {
    die("Database setup failed: " . $e->getMessage());
}

// 2. Check Rate Limit
$ip_address = $_SERVER['REMOTE_ADDR'];
$limit_window = date('Y-m-d H:i:s', strtotime('-15 minutes'));
$max_attempts = 5;

$stmt = $pdo->prepare("SELECT COUNT(*) FROM login_attempts WHERE ip_address = ? AND attempt_time > ?");
$stmt->execute([$ip_address, $limit_window]);
$attempts = $stmt->fetchColumn();

if ($attempts >= $max_attempts) {
    $error = "Too many failed attempts. Please try again in 15 minutes.";
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = $_POST['username'] ?? '';
    $pass = $_POST['password'] ?? '';

    if ($user === $admin_user && $pass === $admin_pass) {
        // Optional: Clear attempts on success
        // $stmt = $pdo->prepare("DELETE FROM login_attempts WHERE ip_address = ?");
        // $stmt->execute([$ip_address]);

        $_SESSION['logged_in'] = true;
        header('Location: index.php');
        exit;
    } else {
        // 3. Log Failed Attempt
        $stmt = $pdo->prepare("INSERT INTO login_attempts (ip_address) VALUES (?)");
        $stmt->execute([$ip_address]);

        $remaining = $max_attempts - ($attempts + 1);
        $error = "Invalid username or password. " . ($remaining > 0 ? "Attempts remaining: $remaining" : "Locked out.");
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Visitor Stats</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background-color: #f8f9fa;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .login-box {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            width: 300px;
        }

        h2 {
            text-align: center;
            margin-top: 0;
            color: #2d3748;
        }

        input {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            box-sizing: border-box;
        }

        button {
            width: 100%;
            padding: 10px;
            background: #3182ce;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }

        button:hover {
            background: #2c5282;
        }

        .error {
            color: #e53e3e;
            font-size: 0.9rem;
            text-align: center;
            margin-bottom: 10px;
        }
    </style>
</head>

<body>
    <div class="login-box">
        <h2>Login</h2>
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="post">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Sign In</button>
        </form>
    </div>
</body>

</html>