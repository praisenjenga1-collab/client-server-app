<?php
session_start();

$message = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $host = 'db';
    $db   = 'contact_db';
    $user = 'root';
    $pass = 'secret_pass';
    $charset = 'utf8mb4';
    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";

    try {
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $username_input = trim($_POST['username']);
        $email_input    = trim($_POST['email']);
        $password_input = $_POST['password'];

        // Securely hash the password before sending it to the database
        $hashed_password = password_hash($password_input, PASSWORD_BCRYPT);

        // Insert the new user into the database container
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $stmt->execute([$username_input, $email_input, $hashed_password]);

        $message = "Account created successfully! You can now log in.";
    } catch (\PDOException $e) {
        if ($e->getCode() == 23000) {
            $error = "Error: That username or email address is already registered.";
        } else {
            $error = "Database Write Error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Distributed System Demo - Register Account</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f9; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .card { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); width: 320px; }
        h2 { margin-top: 0; color: #333; text-align: center; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; color: #666; }
        input[type="text"], input[type="email"], input[type="password"] { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background-color: #28a745; border: none; color: white; border-radius: 4px; cursor: pointer; font-size: 16px; margin-top: 10px; }
        button:hover { background-color: #218838; }
        .alert { padding: 10px; border-radius: 4px; margin-bottom: 15px; font-size: 14px; text-align: center; }
        .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .links { margin-top: 15px; text-align: center; font-size: 14px; }
        .links a { color: #0056b3; text-decoration: none; }
    </style>
</head>
<body>

<div class="card">
    <h2>Create Account</h2>

    <?php if (!empty($message)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form action="register.php" method="POST">
        <div class="form-group">
            <label>Username</label>
            <input type="text" name="username" required>
        </div>
        <div class="form-group">
            <label>Email Address</label>
            <input type="email" name="email" required>
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" required>
        </div>
        <button type="submit">Sign Up</button>
    </form>

    <div class="links">
        <a href="index.php">← Already have an account? Login</a>
    </div>
</div>

</body>
</html>