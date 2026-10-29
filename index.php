<?php
session_start();

// Redirect to dashboard if user is already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // These database settings match what we set in docker-compose.yml
    $host = 'db';
    $db   = 'contact_db';
    $user = 'root';
    $pass = 'secret_pass';
    $charset = 'utf8mb4';

    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";

    try {
        // Establish network connection to the database container
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $username_input = $_POST['username'];
        $password_input = $_POST['password'];

        // Query to check if the user exists
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username_input]);
        $user_record = $stmt->fetch();

        // Verify the hashed password
        if ($user_record && password_verify($password_input, $user_record['password'])) {
            $_SESSION['user_id'] = $user_record['id'];
            $_SESSION['username'] = $user_record['username'];
            header("Location: dashboard.php");
            exit;
        } else {
            $error = "Invalid username or password!";
        }
    } catch (\PDOException $e) {
        $error = "Database Connection Failed: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Distributed System Demo - Login</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f9; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-card { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); width: 320px; }
        h2 { margin-top: 0; color: #333; text-align: center; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; color: #666; }
        input[type="text"], input[type="password"] { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background-color: #0056b3; border: none; color: white; border-radius: 4px; cursor: pointer; font-size: 16px; }
        button:hover { background-color: #004085; }
        .error { color: red; font-size: 14px; text-align: center; margin-bottom: 10px; }
        .links { margin-top: 15px; text-align: center; font-size: 14px; }
        .links a { color: #0056b3; text-decoration: none; }
    </style>
</head>
<body>

<div class="login-card">
    <h2>System Login</h2>
    <?php if (!empty($error)): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form action="index.php" method="POST">
        <div class="form-group">
            <label>Username</label>
            <input type="text" name="username" required>
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" required>
        </div>
        <button type="submit">Login</button>
    </form>

    <div class="links">
        <!-- Task A Link -->
        <a href="forgot-password.php">Forgot Password?</a>
        <br><br>
        <a href="register.php" style="color: #28a745;">Create New Account</a>
    </div>
</div>

</body>
</html>