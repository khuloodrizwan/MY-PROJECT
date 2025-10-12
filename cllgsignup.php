<?php
// Start session (optional)
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "form";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect and trim form data
    $college_name = trim($_POST["college_name"]);
    $admin_name = trim($_POST["admin_name"]);
    $email = trim($_POST["email"]);
    $phone = trim($_POST["phone"]);
    $password_input = trim($_POST["password"]);

    // Check if email already exists
    $stmt = $conn->prepare("SELECT * FROM cllgsignup WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Email already registered
        echo "This email is already registered!";
        exit();
    }
    $stmt->close();

    // Insert new college into database
    $stmt = $conn->prepare("INSERT INTO cllgsignup (college_name, admin_name, email, phone, password) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $college_name, $admin_name, $email, $phone, $password_input);

    if ($stmt->execute()) {
        // Success: Redirect to home.html
        $stmt->close();
        $conn->close();
        header("Location: home.html");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}

$conn->close();
?>
