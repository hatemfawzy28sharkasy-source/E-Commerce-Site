<?php
// login.php - Handles User Login

session_start();

// --- CONFIG & CONNECTION ---
$host = "localhost";
$user = "root";
$password = "";
$database = "computer_shop";

// Attempt database connection
$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if a POST request was made for login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'login') {
    $username = trim($_POST['username'] ?? '');
    $plain_password = $_POST['password'] ?? '';

    // Initialize messages array for session passing
    $messages = [];

    if (!$username || !$plain_password) {
        $messages[] = ['type' => 'error', 'text' => 'Both username and password are required.'];
    } else {
        $stmt = $conn->prepare("SELECT user_id, password_hash FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user_row = $result->fetch_assoc();
        
        if ($user_row) {
            // Check if a row was found AND verify the password
            if (password_verify($plain_password, $user_row['password_hash'])) {
                $_SESSION['user_id'] = $user_row['user_id'];
                $messages[] = ['type' => 'success', 'text' => 'Login successful! Welcome back.'];
            } else {
                // Password was wrong
                $messages[] = ['type' => 'error', 'text' => 'Invalid credentials.'];
            }
        } else {
            // Username not found
            $messages[] = ['type' => 'error', 'text' => 'Invalid credentials.'];
        }
        $stmt->close();
    }
    
    $conn->close();
    
    // Pass messages back to index.php via session
    $_SESSION['messages'] = $messages;
    
    // Redirect back to the main page
    header("Location: index.php");
    exit();
} else {
    // If someone accesses this file directly without a POST, redirect them
    header("Location: index.php");
    exit();
}
?>