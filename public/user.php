<?php
require_once(__DIR__ . '/../config/configuration.php');
require_once(__DIR__ . '/../config/validation.php');
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch user data from the session
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_type = $_SESSION['user_type'];

// Process post submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['post_content'])) {
    $content = htmlspecialchars($_POST['post_content']); // Sanitize the input

    // Handle video upload if provided
    $video_path = '';
    if ($_FILES['video']['name'] != '') {
        $video_name = $_FILES['video']['name'];
        $video_tmp_name = $_FILES['video']['tmp_name'];
        $video_path = 'uploads/videos/' . $video_name;

        if (move_uploaded_file($video_tmp_name, __DIR__ . '/../' . $video_path)) {
            // Video uploaded successfully
        } else {
            echo "Error uploading video.";
        }
    }

    // Insert the post into the database
    $sql = "INSERT INTO posts (user_id, content, video_path, created_at) VALUES ('$user_id', '$content', '$video_path', NOW())";
    if ($conn->query($sql) === TRUE) {
        // Post inserted successfully, redirect to refresh the page
        header("Location: user.php?id=$user_id");
        exit();
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}

// Fetch posts for the current user from the database
if (isset($_GET['id']) && $_GET['id'] == $user_id) {
    $user_page_id = $_GET['id'];
    $sql_posts = "SELECT * FROM posts WHERE user_id = '$user_page_id' ORDER BY created_at DESC";
    $result_posts = $conn->query($sql_posts);
} else {
    // Redirect to user's own page if the ID in the URL doesn't match
    header("Location: user.php?id=$user_id");
    exit();
}

// Fetch posts from the database
$sql_posts = "SELECT posts.*, 
              (SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.id) AS like_count,
              (SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.id AND likes.user_id = '$user_id') AS user_liked
              FROM posts ORDER BY created_at DESC";
$result_posts = $conn->query($sql_posts);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script>
        function openCommentForm(postId) {
            document.getElementById("commentForm" + postId).style.display = "block";
        }

        function closeCommentForm(postId) {
            document.getElementById("commentForm" + postId).style.display = "none";
        }

        function likePost(postId) {
            document.getElementById("likeForm" + postId).submit();
        }
    </script>
</head>
<body class="bg-gray-100">
<div class="bg-gray-800 text-white p-4">
    <div class="container mx-auto flex justify-between items-center">
        <h1 class="text-xl font-bold">User Dashboard</h1>
        <a href="logout.php" class="text-sm px-3 py-1 bg-red-500 hover:bg-red-600 rounded-lg">Logout</a>
    </div>
</div>
<div class="min-h-screen flex flex-col items-center justify-center">
    <!-- Post Form -->
    <form action="" method="POST" enctype="multipart/form-data" class="bg-white p-8 rounded-lg shadow-lg w-full max-w-4xl mt-4">
        <h2 class="text-2xl font-bold mb-6 text-center">User Dashboard</h2>
        <textarea name="post_content" rows="4" placeholder="Write something..." class="w-full p-2 border border-gray-300 rounded-lg mb-4"></textarea>
        <input type="file" name="video" accept="video/*" class="w-full p-2 border border-gray-300 rounded-lg mb-4">
        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-lg">Post</button>
    </form>
    <!-- Display Posts -->
    <div class="mt-8 w-full max-w-4xl">
        <?php
        if ($result_posts->num_rows > 0) {
            while ($row = $result_posts->fetch_assoc()) {
                // Display each post
                echo '<div class="bg-white p-4 rounded-lg shadow-lg mb-4">';
                echo '<p>' . htmlspecialchars($row['content']) . '</p>';
                if (!empty($row['video_path'])) {
                    echo '<video controls class="w-full mt-4">';
                    echo '<source src="' . htmlspecialchars($row['video_path']) . '" type="video/mp4">';
                    echo 'Your browser does not support the video tag.';
                    echo '</video>';
                }
                echo '<div class="flex items-center justify-between mt-2">';
                echo '<form id="likeForm' . $row['id'] . '" action="" method="POST">';
                echo '<input type="hidden" name="like_post_id" value="' . $row['id'] . '">';
                echo '</form>';
                // Check if user_liked key exists
                if (array_key_exists('user_liked', $row) && $row['user_liked'] == 0) {
                    echo '<button class="text-blue-500" onclick="likePost(' . $row['id'] . ')">Like</button>';
                } else {
                    echo '<button class="text-gray-500" disabled>Liked</button>';
                }
                echo '<span>' . $row['like_count'] . ' Likes</span>';
                echo '<button class="text-blue-500" onclick="openCommentForm(' . $row['id'] . ')">Comment</button>';
                echo '<button class="text-blue-500">Share</button>';
                echo '</div>';
                
                // Comment Form
                echo '<div id="commentForm' . $row['id'] . '" style="display:none;" class="mt-4">';
                echo '<form action="comment.php" method="POST">';
                echo '<input type="hidden" name="post_id" value="' . $row['id'] . '">';
                echo '<textarea name="comment_content" rows="2" placeholder="Write a comment..." class="w-full p-2 border border-gray-300 rounded-lg mb-2"></textarea>';
                echo '<button type="submit" class="bg-green-500 text-white px-4 py-2 rounded-lg">Post Comment</button>';
                echo '<button type="button" onclick="closeCommentForm(' . $row['id'] . ')" class="bg-red-500 text-white px-4 py-2 rounded-lg">Cancel</button>';
                echo '</form>';
                echo '</div>';

                // Fetch and display comments for the current post
                $post_id = $row['id'];
                $sql_comments = "SELECT * FROM comments WHERE post_id = '$post_id' ORDER BY created_at ASC";
                $result_comments = $conn->query($sql_comments);

                if ($result_comments->num_rows > 0) {
                    echo '<div class="mt-4">';
                    while ($comment = $result_comments->fetch_assoc()) {
                        echo '<div class="bg-gray-100 p-2 rounded-lg shadow-sm mb-2">';
                        echo '<p>' . htmlspecialchars($comment['content']) . '</p>';
                        echo '</div>';
                    }
                    echo '</div>';
                } else {
                    echo '<p class="mt-4">No comments yet.</p>';
                }

                echo '</div>';
            }
        } else {
            echo '<p class="text-center">No posts yet.</p>';
        }
        ?>
    </div>
</div>
</body>
</html>
