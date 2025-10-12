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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST["email"];
    $password_input = $_POST["password"];

    // Fetch college admin data from cllgsignup table
    $sql = "SELECT * FROM cllgsignup WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if email exists
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();

        // Plain text password check
        if ($password_input === $row["password"]) {
            // Store session variables
            $_SESSION["email"] = $email;
            $_SESSION["college_name"] = $row["college_name"];
            $_SESSION["admin_name"] = $row["admin_name"];

            // Redirect to home or dashboard
            header("Location: home.html");
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
