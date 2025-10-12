<?php
session_start();

$servername = "localhost";
$username = "root"; // XAMPP username
$password = ""; // XAMPP password
$dbname = "form"; // Database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if form submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST["email"];
    $password_input = $_POST["password"];

    // Fetch student data from signup table
    $sql = "SELECT * FROM signup WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if email exists
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();

        // Compare plain text password
        if ($password_input === $row["password"]) {
            $_SESSION["email"] = $email; // Create session
            header("Location: payment.html"); // Redirect after successful login
            exit();
        } else {
            echo "Invalid email or password.";
        }
    } else {
        echo "Email not found.";
    }
}

$conn->close();
?>
