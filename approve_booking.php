<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin_ruangan') {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id']) && isset($_GET['action'])) {
    $booking_id = $_GET['id'];
    $action = $_GET['action'];
    
    $status = ($action == 'approve') ? 'approved' : 'rejected';
    
    $stmt = $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ?");
    if ($stmt->execute([$status, $booking_id])) {
        header("Location: approve_bookings.php?success=Booking berhasil " . $status);
    } else {
        header("Location: approve_bookings.php?error=Terjadi kesalahan");
    }
    exit();
} else {
    header("Location: approve_bookings.php");
    exit();
}
?>