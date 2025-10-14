<?php
// Enable errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "form";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (empty($_POST['email']) || empty($_POST['password'])) {
        echo "Please enter both email and password.";
        exit();
    }

    $email = trim($_POST['email']);
    $password_input = trim($_POST['password']);

    $stmt = $conn->prepare("SELECT s_id, password FROM signup WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if ($password_input === $row['password']) { // Plain text for now
            $_SESSION['email'] = $email;
            $_SESSION['s_id'] = $row['s_id'];

            header("Location: payment.php");
            exit();
        } else {
            echo "Invalid password. Please try again.";
        }
    } else {
        echo "Email not found. Please check and try again.";
    }

    $stmt->close();
}

$conn->close();
?>
