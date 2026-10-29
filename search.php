<?php
session_start();

// Security boundary: only authenticated users may search contacts
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$results = [];
$error = "";
$search_performed = false;
$registration_number = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $registration_number = trim($_POST["registration_number"] ?? "");
    $search_performed = true;

    if ($registration_number === "") {
        $error = "Please enter a registration number.";
    } else {
        try {
            // Connect to the MySQL service using its Docker Compose name
            $host = "db";
            $db = "contact_db";
            $user = "root";
            $pass = "secret_pass";
            $charset = "utf8mb4";

            $dsn = "mysql:host=$host;dbname=$db;charset=$charset";

            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);

            // Use a prepared statement to prevent SQL injection
            $stmt = $pdo->prepare(
                "SELECT id, registration_number, mobile_number, email, address, created_at
                 FROM contacts
                 WHERE registration_number = ?"
            );

            $stmt->execute([$registration_number]);
            $results = $stmt->fetchAll();

            if (count($results) === 0) {
                $error = "No contact record found for that registration number.";
            }
        } catch (PDOException $e) {
            // Log technical details on the server, not to the user
            error_log("Contact search failed: " . $e->getMessage());
            $error = "The search could not be completed. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Contact Records</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f8f3e8;
            margin: 0;
            padding: 30px 15px;
        }

        .container {
            max-width: 900px;
            margin: auto;
            background: #ffffff;
            padding: 25px;
            border-top: 6px solid #800020;
            border-radius: 8px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.12);
        }

        h1 {
            color: #800020;
            margin-top: 0;
        }

        label {
            display: block;
            margin: 15px 0 7px;
            font-weight: bold;
        }

        input[type="text"] {
            width: 100%;
            box-sizing: border-box;
            padding: 12px;
            border: 1px solid #cccccc;
            border-radius: 4px;
        }

        button {
            background: #800020;
            color: white;
            border: none;
            padding: 12px 20px;
            margin-top: 15px;
            border-radius: 4px;
            cursor: pointer;
        }

        button:hover {
            background: #5f0018;
        }

        .message {
            padding: 12px;
            margin: 15px 0;
            border-radius: 4px;
            background: #f7e8e8;
            color: #800020;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            border: 1px solid #dddddd;
            padding: 10px;
            text-align: left;
            overflow-wrap: anywhere;
        }

        th {
            background: #800020;
            color: white;
        }

        tr:nth-child(even) {
            background: #f8f3e8;
        }

        a {
            color: #438a32;
            text-decoration: none;
            font-weight: bold;
        }

        .links {
            margin-top: 25px;
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }
    </style>
</head>

<body>
<div class="container">
    <h1>Search Contact Records</h1>
    <p>Enter a registration number to retrieve a matching contact record.</p>

    <form method="POST" action="search.php">
        <label for="registration_number">Registration Number</label>

        <input
            type="text"
            id="registration_number"
            name="registration_number"
            value="<?php echo htmlspecialchars($registration_number, ENT_QUOTES, 'UTF-8'); ?>"
            placeholder="Enter registration number"
            required
        >

        <button type="submit">Search Service</button>
    </form>

    <?php if ($error !== ""): ?>
        <div class="message">
            <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($results)): ?>
        <h2>Search Results</h2>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Registration Number</th>
                    <th>Mobile Number</th>
                    <th>Email</th>
                    <th>Address</th>
                    <th>Date Created</th>
                </tr>
            </thead>

            <tbody>
            <?php foreach ($results as $row): ?>
                <tr>
                    <td><?php echo htmlspecialchars((string)$row['id'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($row['registration_number'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($row['mobile_number'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($row['email'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($row['address'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($row['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <div class="links">
        <a href="dashboard.php">Back to Dashboard</a>
        <a href="logout.php">Logout</a>
    </div>
</div>
</body>
</html>