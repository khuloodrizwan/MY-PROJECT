<?php
$servername = "localhost";
$username = "root"; // Default MySQL username
$password = ""; // Default MySQL password (empty in XAMPP)
$dbname = "form"; // Database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect form data from HTML
    $fullname = $_POST["fullname"];
    $city = $_POST["city"];
    $email = $_POST["email"];
    $number = $_POST["number"]; // matches HTML name
    $gender = $_POST["gender"];
    $mothername = $_POST["mothername"];
    $collegename = $_POST["collegename"];
    $coursename = $_POST["coursename"];
    $currentyear = $_POST["currentyear"];
    $academicyear = $_POST["academicyear"];
    $username_input = $_POST["username"];
    $password_input = $_POST["password"];

    // Set default values for total_fees and due_fees
    $total_fees = 0;
    $due_fees = 0;

    // Insert into database
    $sql = "INSERT INTO signup 
            (fullname, city, email, number, gender, mothername, collegename, coursename, total_fees, due_fees, currentyear, academicyear, username, password) 
            VALUES 
            ('$fullname', '$city', '$email', '$number', '$gender', '$mothername', '$collegename', '$coursename', '$total_fees', '$due_fees', '$currentyear', '$academicyear', '$username_input', '$password_input')";

   if ($conn->query($sql) === TRUE) {
        // Redirect to home.html after successful registration
        header("Location: home.html");
        exit(); // Always use exit() after header redirect
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}

$conn->close();
?>
