<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__ . '/../config/configuration.php');
require_once(__DIR__ . '/../config/validation.php');

session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['post_id']) && isset($_POST['comment_content'])) {
    // Process the posted comment
    $post_id = intval($_POST['post_id']);
    $user_id = $_SESSION['user_id'];
    $comment_content = htmlspecialchars($_POST['comment_content']); // Sanitize the input

    // Insert the comment into the database
    $sql = "INSERT INTO comments (post_id, user_id, content, created_at) VALUES ('$post_id', '$user_id', '$comment_content', NOW())";
    if ($conn->query($sql) === TRUE) {
        // Redirect back to user.php with the current user ID
        header("Location: user.php?id=$user_id");
        exit();
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}
?>
