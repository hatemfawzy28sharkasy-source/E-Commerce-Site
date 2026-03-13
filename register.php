<?php
// register.php - Handles User Registration

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

// Check if a POST request was made for registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'register') {
    $username = trim($_POST['username'] ?? '');
    $plain_password = $_POST['password'] ?? '';
    $email = trim($_POST['email'] ?? '');

    // Initialize messages array for session passing
    $messages = [];

    if ($username && $plain_password && $email) {
        $password_hash = password_hash($plain_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
        
        if ($stmt) {
            $stmt->bind_param("sss", $username, $email, $password_hash);
            
            if ($stmt->execute()) {
                $messages[] = ['type' => 'success', 'text' => 'Registration successful! You can now log in.'];
            } else {
                // Check for duplicate entry error (username or email)
                if ($conn->errno === 1062) {
                    $messages[] = ['type' => 'error', 'text' => 'Registration failed. Username or Email already exists.'];
                } else {
                     $messages[] = ['type' => 'error', 'text' => 'Registration failed: ' . $stmt->error];
                }
            }
            $stmt->close();
        } else {
            $messages[] = ['type' => 'error', 'text' => 'Database statement preparation failed.'];
        }
    } else {
        $messages[] = ['type' => 'error', 'text' => 'All fields required for registration.'];
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