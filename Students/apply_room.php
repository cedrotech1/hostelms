<?php
session_start();
include("connection.php");

// Check if student is logged in
if (!isset($_SESSION['student_id'])) {
    header("Location: ../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['room_id']) && isset($_POST['hostel_id'])) {
    $room_id = (int)$_POST['room_id'];
    $hostel_id = (int)$_POST['hostel_id'];
    $student_id = $_SESSION['student_id'];
    $student_regnumber = $_SESSION['student_regnumber'];
    $student_gender = $_SESSION['student_gender'];
    $student_year = $_SESSION['student_year'];
    $timestamp = isset($_POST['timestamp']) ? (int)$_POST['timestamp'] : 0;

    // Start transaction
    $connection->begin_transaction();

    try {
        // Check if student already has an application
        $check_query = "SELECT * FROM applications WHERE regnumber = ? AND status != 'rejected' FOR UPDATE";
        $check_stmt = $connection->prepare($check_query);
        $check_stmt->bind_param("s", $student_regnumber);
        $check_stmt->execute();
        $existing_application = $check_stmt->get_result();

        if ($existing_application->num_rows > 0) {
            throw new Exception("You already have a pending or approved application.");
        }

        // Check room availability with timestamp validation
        $room_query = "SELECT r.*, h.id as hostel_id, 
                      (SELECT COUNT(*) FROM applications a WHERE a.room_id = r.id AND a.status != 'rejected') as current_applications
                      FROM rooms r 
                      JOIN hostels h ON r.hostel_id = h.id 
                      WHERE r.id = ? AND r.remain > 0 
                      FOR UPDATE";
        $room_stmt = $connection->prepare($room_query);
        $room_stmt->bind_param("i", $room_id);
        $room_stmt->execute();
        $room = $room_stmt->get_result()->fetch_assoc();

        if (!$room) {
            throw new Exception("Room is no longer available.");
        }

        // Validate timestamp (if provided) - ensure data isn't too old
        if ($timestamp > 0) {
            $current_time = time();
            if ($current_time - $timestamp > 300) { // 5 minutes threshold
                throw new Exception("Room information is too old. Please refresh the page and try again.");
            }
        }

        // Check if room is still available considering pending applications
        if ($room['remain'] == 0) {
            throw new Exception("Room is no longer available due to pending applications.");
        }

        // Check hostel attributes against student attributes
        $attributes_query = "SELECT * FROM hostel_attributes WHERE hostel_id = ?";
        $attributes_stmt = $connection->prepare($attributes_query);
        $attributes_stmt->bind_param("i", $hostel_id);
        $attributes_stmt->execute();
        $attributes = $attributes_stmt->get_result();

        $is_eligible = true;
        while ($attr = $attributes->fetch_assoc()) {
            if ($attr['attribute_key'] === 'gender' && $attr['attribute_value'] !== $student_gender) {
                $is_eligible = false;
                break;
            }
            if ($attr['attribute_key'] === 'year_of_study' && $attr['attribute_value'] != $student_year) {
                $is_eligible = false;
                break;
            }
        }

        if (!$is_eligible) {
            throw new Exception("You are not eligible for this hostel based on the requirements.");
        }

        // Insert application with timestamp
        $insert_query = "INSERT INTO applications (regnumber, room_id, status, created_at) 
                        VALUES (?, ?, 'pending', NOW())";
        $insert_stmt = $connection->prepare($insert_query);
        $insert_stmt->bind_param("si", $student_regnumber, $room_id);
        $insert_stmt->execute();

        // Update room availability
        $update_query = "UPDATE rooms SET remain = remain - 1 WHERE id = ? AND remain > 0";
        $update_stmt = $connection->prepare($update_query);
        $update_stmt->bind_param("i", $room_id);
        $update_stmt->execute();

        if ($update_stmt->affected_rows === 0) {
            throw new Exception("Room is no longer available. Please try another room.");
        }

        // Commit transaction
        $connection->commit();
        
        $_SESSION['success_message'] = "Your application has been submitted successfully!";
        header("Location: index.php");
        exit();

    } catch (Exception $e) {
        // Rollback transaction on error
        $connection->rollback();
        $_SESSION['error_message'] = $e->getMessage();
        header("Location: index.php");
        exit();
    }
} else {
    header("Location: select_hostel.php");
    exit();
}
?> 