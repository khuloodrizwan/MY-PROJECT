<?php
/**
 * Student Registration Handler
 * Registers students and links them to their college
 */

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
    $username_input = trim($_POST["username"]);
    $password_input = trim($_POST["password"]);

    // Use fees from hidden inputs
    $total_fees = isset($_POST['total_fees']) ? intval($_POST['total_fees']) : 0;
    $due_fees = isset($_POST['due_fees']) ? intval($_POST['due_fees']) : $total_fees;

    // Map college names to their c_id in cllgsignup table
    // You need to get the actual c_id based on college name
    $college_map = [
        'arihant' => 1,  // Replace with actual c_id from your cllgsignup table
        'poona' => 2,    // Replace with actual c_id
        'allana' => 3    // Replace with actual c_id
    ];
    
    // Get c_id for the selected college
    // Better approach: Query the database to get c_id
    $stmt = $conn->prepare("SELECT c_id FROM cllgsignup WHERE college_name LIKE ? LIMIT 1");
    $search_name = "%{$collegename}%";
    $stmt->bind_param("s", $search_name);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $college_data = $result->fetch_assoc();
        $c_id = $college_data['c_id'];
    } else {
        // If college not found, use default or show error
        // For now, we'll use NULL (modify based on your needs)
        $c_id = null;
    }
    $stmt->close();

    // Prepared statement to insert student
    $stmt = $conn->prepare("INSERT INTO signup 
        (fullname, city, email, number, gender, mothername, collegename, coursename, 
        total_fees, due_fees, currentyear, academicyear, username, password, c_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $stmt->bind_param("ssssssssddssss" . ($c_id !== null ? "i" : ""), 
        $fullname, $city, $email, $number, $gender, $mothername, 
        $collegename, $coursename, $total_fees, $due_fees, 
        $currentyear, $academicyear, $username_input, $password_input, $c_id
    );

    if ($stmt->execute()) {
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