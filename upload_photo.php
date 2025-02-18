<?php
session_start();
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];

    // Decode the base64 image
    if (isset($_POST['photoData'])) {
        $photoData = $_POST['photoData'];
        $photoData = str_replace('data:image/jpeg;base64,', '', $photoData);
        $photoData = str_replace(' ', '+', $photoData);
        $decodedData = base64_decode($photoData);

        // Save the photo to the server
        $uploadDir = 'uploads';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $fileName = uniqid('photo_', true) . '.jpg';
        $filePath = $uploadDir . $fileName;

        if (file_put_contents($filePath, $decodedData)) {
            // Insert the file path into the database
            $stmt = $pdo->prepare("INSERT INTO uploads (user_id, image_path, location, uploaded_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$userId, $filePath, 'Location not set']); // Update location as needed
            echo json_encode(['status' => 'success', 'message' => 'Photo uploaded successfully.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to save photo.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No photo data received.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
}