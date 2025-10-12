<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "form";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect form data
    $fullname = trim($_POST["fullname"]);
    $city = trim($_POST["city"]);
    $email = trim($_POST["email"]);
    $number = trim($_POST["number"]);
    $gender = trim($_POST["gender"]);
    $mothername = trim($_POST["mothername"]);
    $collegename = trim($_POST["collegename"]);
    $coursename = trim($_POST["coursename"]);
    $currentyear = trim($_POST["currentyear"]);
    $academicyear = trim($_POST["academicyear"]);
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);

    // Use fees from hidden inputs
    $total_fees = isset($_POST['total_fees']) ? $_POST['total_fees'] : 0;
    $due_fees = isset($_POST['due_fees']) ? $_POST['due_fees'] : $total_fees;

    // Prepared statement
    $stmt = $conn->prepare("INSERT INTO signup 
        (fullname, city, email, number, gender, mothername, collegename, coursename, total_fees, due_fees, currentyear, academicyear, username, password)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssssddssss", 
        $fullname, $city, $email, $number, $gender, $mothername, 
        $collegename, $coursename, $total_fees, $due_fees, 
        $currentyear, $academicyear, $username, $password
    );

    if ($stmt->execute()) {
        header("Location: home.html");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}

$conn->close();
?>
