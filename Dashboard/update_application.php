<?php
include('connection.php');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        $status = $_POST['action'] === 'approve' ? 'approved' : 'rejected';
        
        // Start transaction
        mysqli_begin_transaction($connection);
        
        try {
            // Get application details including student info
            $query = "SELECT a.*, i.phone, i.names, r.room_code, h.name as hostel_name 
                     FROM applications a 
                     JOIN info i ON i.regnumber = a.regnumber 
                     JOIN rooms r ON r.id = a.room_id 
                     JOIN hostels h ON h.id = r.hostel_id 
                     WHERE a.id = $id";
            $result = mysqli_query($connection, $query);
            
            if (!$result || mysqli_num_rows($result) === 0) {
                throw new Exception('Application not found');
            }
            
            $application = mysqli_fetch_assoc($result);
            
            if ($status === 'approved') {
                // Update application status
                $query = "UPDATE applications SET status = 'approved', updated_at = NOW() WHERE id = $id";
                if (!mysqli_query($connection, $query)) {
                    throw new Exception('Failed to update application');
                }
                
                // Send approval SMS asynchronously
                $phone = $application['phone'];
                if (!str_starts_with($phone, '+')) {
                    if (str_starts_with($phone, '0')) {
                        $phone = '+250' . substr($phone, 1);
                    }
                }
                
                $message = "Dear {$application['names']}, your hostel application for room {$application['room_code']} in {$application['hostel_name']} has been APPROVED. Welcome!";
                
                // Send SMS synchronously with proper error handling
                $sms_data = [
                    'to' => $phone,
                    'text' => $message,
                    'sender' => 'PindoTest'
                ];
                
                $ch = curl_init('https://api.pindo.io/v1/sms/');
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($sms_data));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Authorization: Bearer eyJhbGciOiJIUzUxMiIsInR5cCI6IkpXVCJ9.eyJleHAiOjE4MzcxNzUzMTIsImlhdCI6MTc0MjQ4MDkxMiwiaWQiOiJ1c2VyXzAxSlBTWjlDMTZCTUtZQzZLSkdWRkhQOTBNIiwicmV2b2tlZF90b2tlbl9jb3VudCI6MH0.KjgMZ0ht_NhUbil_3kIgHHByJSokufd2IZdC9-PYeXdkJkan4Rv8DMi0jlHXfZnyh_52bOizk9nTR3QOEBU5ZA',
                    'Content-Type: application/json'
                ]);
                
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                if ($httpCode !== 200) {
                    error_log("SMS sending failed for phone {$phone}. Response: " . $response);
                }
                
            } else if ($status === 'rejected') {
                // Always increment remaining beds when rejecting
                $query = "UPDATE rooms r
                          JOIN applications a ON a.room_id = r.id
                          SET r.remain = r.remain + 1
                          WHERE a.id = $id";
                
                if (!mysqli_query($connection, $query)) {
                    throw new Exception('Failed to update room beds');
                }
                
                // Update info table to set current_application to rejected
                $query = "UPDATE info SET current_application = 'rejected' WHERE regnumber = '{$application['regnumber']}'";
                if (!mysqli_query($connection, $query)) {
                    throw new Exception('Failed to update student info');
                }
                
                // Delete the application
                $query = "DELETE FROM applications WHERE id = $id";
                if (!mysqli_query($connection, $query)) {
                    throw new Exception('Failed to delete application');
                }
                
                // Send rejection SMS asynchronously
                $phone = $application['phone'];
                if (!str_starts_with($phone, '+')) {
                    if (str_starts_with($phone, '0')) {
                        $phone = '+250' . substr($phone, 1);
                    }
                }
                
                $message = "Dear {$application['names']}, your hostel application for room {$application['room_code']} in {$application['hostel_name']} has been REJECTED, You can apply for another room.";
                
                // Send SMS synchronously with proper error handling
                $sms_data = [
                    'to' => $phone,
                    'text' => $message,
                    'sender' => 'PindoTest'
                ];
                
                $ch = curl_init('https://api.pindo.io/v1/sms/');
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($sms_data));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Authorization: Bearer eyJhbGciOiJIUzUxMiIsInR5cCI6IkpXVCJ9.eyJleHAiOjE4MzcxNzUzMTIsImlhdCI6MTc0MjQ4MDkxMiwiaWQiOiJ1c2VyXzAxSlBTWjlDMTZCTUtZQzZLSkdWRkhQOTBNIiwicmV2b2tlZF90b2tlbl9jb3VudCI6MH0.KjgMZ0ht_NhUbil_3kIgHHByJSokufd2IZdC9-PYeXdkJkan4Rv8DMi0jlHXfZnyh_52bOizk9nTR3QOEBU5ZA',
                    'Content-Type: application/json'
                ]);
                
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                if ($httpCode !== 200) {
                    error_log("SMS sending failed for phone {$phone}. Response: " . $response);
                }
            }
            
            mysqli_commit($connection);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            mysqli_rollback($connection);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Missing required parameters'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}
?> 