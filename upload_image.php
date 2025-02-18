<?php
session_start();
require 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "error" => "Unauthorized"]);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);

// Check if image data is provided
if (!isset($data['image']) || empty($data['image'])) {
    echo json_encode(["success" => false, "error" => "No image provided"]);
    exit();
}

// Decode base64 image data
$imageData = $data['image'];
$imageData = str_replace('data:image/jpeg;base64,', '', $imageData);
$imageData = str_replace(' ', '+', $imageData);
$image = base64_decode($imageData);

if (!$image) {
    echo json_encode(["success" => false, "error" => "Invalid image data"]);
    exit();
}

// Save the image file
$uploadsDir = 'uploads/';
if (!file_exists($uploadsDir)) {
    mkdir($uploadsDir, 0755, true);
}

$filename = uniqid('photo_') . '.jpg';
$filePath = $uploadsDir . $filename;

if (file_put_contents($filePath, $image) === false) {
    echo json_encode(["success" => false, "error" => "Failed to save image"]);
    exit();
}

// Get the last upload time for the user
$stmt = $pdo->prepare("SELECT * FROM uploads WHERE user_id = ? ORDER BY uploaded_at DESC LIMIT 1");
$stmt->execute([$_SESSION['user_id']]);
$lastUpload = $stmt->fetch();

// Determine location based on time difference
if ($lastUpload) {
    $lastTime = new DateTime($lastUpload['uploaded_at']);
    $currentTime = new DateTime();
    $interval = $lastTime->diff($currentTime);

    // Check if the time difference is greater than 9 hours
    if ($interval->h >= 9) {
        $location = "Out"; // More than 9 hours gap, it's "Out"
    } elseif ($interval->h < 9 && $interval->h >= 8) {
        $location = "Overtime"; // Less than 9 but more than 8 hours, it's "Overtime"
    } elseif ($interval->h < 8) {
        $location = "Undertime"; // Less than 8 hours, it's "Undertime"
    }
} else {
    // First image uploaded, set location to 'In'
    $location = "In";
}

// Insert the file path into the database with location
$stmt = $pdo->prepare("INSERT INTO uploads (user_id, image_path, location) VALUES (?, ?, ?)");
if ($stmt->execute([$_SESSION['user_id'], $filePath, $location])) {
    echo json_encode(["success" => true, "location" => $location]);
} else {
    echo json_encode(["success" => false, "error" => "Database error"]);
}
?>
