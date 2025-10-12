<?php
session_start();

$servername = "localhost";
$username = "root"; // XAMPP username
$password = "";     // XAMPP password
$dbname = "form";   // Database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if form submitted via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST["email"];
    $password_input = $_POST["password"];

    // Prepare and execute query
    $stmt = $conn->prepare("SELECT * FROM cllgsignup WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    // Verify email exists
    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();

        // Plain text password check
        if ($password_input === $row["password"]) {
            // Set session variables
            $_SESSION["email"] = $row["email"];
            $_SESSION["college_name"] = $row["college_name"];
            $_SESSION["admin_name"] = $row["admin_name"];

            // Redirect to admin page
            header("Location: admin.php");
            exit();
        } else {
            echo "<p style='color:red;'>Invalid email or password.</p>";
        }
    } else {
        echo "<p style='color:red;'>Email not found.</p>";
    }

    $stmt->close();
}

$conn->close();
?>
