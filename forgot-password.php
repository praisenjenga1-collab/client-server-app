<?php
$message = "";
$error = "";

/**
 * Send a plain-text email by talking SMTP directly to the Mailpit container.
 * Returns true if the mail server accepted the message.
 */
function send_mail_smtp($host, $port, $from, $to, $subject, $body)
{
    $fp = @fsockopen($host, $port, $errno, $errstr, 5);
    if (!$fp) {
        return false;
    }

    // Read one (possibly multi-line) SMTP reply
    $read = function () use ($fp) {
        $data = "";
        while (($line = fgets($fp, 515)) !== false) {
            $data .= $line;
            if (substr($line, 3, 1) === " ") {
                break;
            }
        }
        return $data;
    };

    // Send one command and return the server's reply
    $cmd = function ($command) use ($fp, $read) {
        fwrite($fp, $command . "\r\n");
        return $read();
    };

    // Remove line breaks so user input can't inject extra headers
    $clean = function ($value) {
        return str_replace(["\r", "\n"], "", $value);
    };

    $from = $clean($from);
    $to = $clean($to);
    $subject = $clean($subject);

    $read(); // server greeting
    $cmd("HELO localhost");
    $cmd("MAIL FROM:<$from>");
    $cmd("RCPT TO:<$to>");
    $reply = $cmd("DATA");
    if (strpos($reply, "354") !== 0) {
        fclose($fp);
        return false;
    }

    // Normalise line endings and escape lines starting with a dot
    $body = str_replace(["\r\n", "\r"], "\n", $body);
    $body = str_replace("\n", "\r\n", $body);
    $body = str_replace("\r\n.", "\r\n..", $body);

    $headers  = "From: $from\r\n";
    $headers .= "To: $to\r\n";
    $headers .= "Subject: $subject\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    fwrite($fp, $headers . "\r\n" . $body . "\r\n.\r\n");
    $reply = $read();

    $cmd("QUIT");
    fclose($fp);

    return strpos($reply, "250") === 0;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email_input = trim($_POST['email']);

    // 1. Database connection settings matching docker-compose.yml
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

        // Check if the email exists in the users table
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email_input]);
        $user_record = $stmt->fetch();

        if ($user_record) {
            // Generate a random reset token
            $token = bin2hex(random_bytes(16));

            // Save the token to the database container
            $update_stmt = $pdo->prepare("UPDATE users SET reset_token = ? WHERE email = ?");
            $update_stmt->execute([$token, $email_input]);

            // 2. Build the email and send it to the Mailpit container
            $subject = "Password Reset Request - Distributed Demo App";
            $reset_link = "http://localhost:8080/reset-password.php?token=" . $token;
            $email_body = "Hello,\n\nYou requested a password reset. Click the link below to change it:\n\n"
                        . $reset_link
                        . "\n\nIf you did not request this, please ignore this message.";

            // 'mail' is the Docker Compose service name, 1025 is Mailpit's SMTP port
            $sent = send_mail_smtp(
                "mail",
                1025,
                "system-noreply@kabarak.ac.ke",
                $email_input,
                $subject,
                $email_body
            );

            if ($sent) {
                $message = "A reset password message has been sent. Check the Mailpit inbox.";
            } else {
                $error = "Failed to send the email through the mail service.";
            }
        } else {
            // For class testing we show this message; real systems should not reveal this
            $error = "No user account found matching that email address.";
        }
    } catch (\PDOException $e) {
        $error = "Database link error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Distributed System Demo - Forgot Password</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f9; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .card { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); width: 340px; }
        h2 { margin-top: 0; color: #333; text-align: center; }
        p { color: #666; font-size: 14px; text-align: center; line-height: 1.4; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; color: #666; font-weight: bold; }
        input[type="email"] { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background-color: #0056b3; border: none; color: white; border-radius: 4px; cursor: pointer; font-size: 16px; margin-top: 10px; }
        button:hover { background-color: #004085; }
        .alert { padding: 10px; border-radius: 4px; margin-bottom: 15px; font-size: 14px; text-align: center; }
        .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .links { margin-top: 20px; text-align: center; font-size: 14px; }
        .links a { color: #0056b3; text-decoration: none; }
    </style>
</head>
<body>

<div class="card">
    <h2>Task A: Reset Password</h2>
    <p>Enter your registration email address below to send a password reset request.</p>

    <?php if (!empty($message)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form action="forgot-password.php" method="POST">
        <div class="form-group">
            <label>Your Account Email</label>
            <input type="email" name="email" placeholder="e.g., you@example.com" required>
        </div>
        <button type="submit">Send Reset Message</button>
    </form>

    <div class="links">
        <a href="index.php">← Back to Login Screen</a>
    </div>
</div>

</body>
</html>