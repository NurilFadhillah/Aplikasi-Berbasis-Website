<?php
session_start();
require_once 'config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$room_id = isset($_GET['room_id']) ? $_GET['room_id'] : null;
$date = isset($_GET['date']) ? $_GET['date'] : null;
$user_id = isset($_GET['user_id']) ? $_GET['user_id'] : $_SESSION['user_id'];

if (!$room_id || !$date) {
    echo json_encode(['error' => 'Missing parameters']);
    exit();
}

// Ambil semua booking untuk ruangan dan tanggal tersebut
$stmt = $pdo->prepare("
    SELECT b.start_time, b.end_time, b.status, u.name as user_name 
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    WHERE b.room_id = ? AND b.date = ? AND b.status IN ('approved', 'pending')
    ORDER BY b.start_time
");
$stmt->execute([$room_id, $date]);
$bookings = $stmt->fetchAll();

// Ambil semua booking user pada tanggal tersebut (semua ruangan)
$stmt = $pdo->prepare("
    SELECT b.start_time, b.end_time, b.status, r.name as room_name
    FROM bookings b
    JOIN rooms r ON b.room_id = r.id
    WHERE b.user_id = ? AND b.date = ? AND b.status IN ('approved', 'pending')
    ORDER BY b.start_time
");
$stmt->execute([$user_id, $date]);
$user_bookings = $stmt->fetchAll();

// Generate slot waktu dengan range
$time_slots = [];

// Buat array untuk tracking jam yang sudah dibooking
$booked_ranges = [];
foreach ($bookings as $booking) {
    $booked_ranges[] = [
        'start' => $booking['start_time'],
        'end' => $booking['end_time'],
        'status' => $booking['status'],
        'user' => $booking['user_name']
    ];
}

// Buat array untuk tracking jam yang sudah dibooking oleh user (semua ruangan)
$user_booked_ranges = [];
foreach ($user_bookings as $booking) {
    $user_booked_ranges[] = [
        'start' => $booking['start_time'],
        'end' => $booking['end_time'],
        'status' => $booking['status'],
        'room' => $booking['room_name']
    ];
}

// Generate slot waktu dari 07:00 sampai 17:00
for ($hour = 7; $hour <= 16; $hour++) {
    $start_time = str_pad($hour, 2, '0', STR_PAD_LEFT) . ':00:00';
    $end_time = str_pad($hour + 1, 2, '0', STR_PAD_LEFT) . ':00:00';
    
    $slot_status = 'available';
    $booked_by = null;
    $user_has_booking = false;
    $user_booking_room = null;
    
    // Cek apakah user sudah booking di jam ini (di ruangan manapun)
    foreach ($user_booked_ranges as $user_booked) {
        $slot_start = strtotime($start_time);
        $slot_end = strtotime($end_time);
        $booking_start = strtotime($user_booked['start']);
        $booking_end = strtotime($user_booked['end']);
        
        // Cek overlap
        if (!(($slot_end <= $booking_start) || ($slot_start >= $booking_end))) {
            $user_has_booking = true;
            $user_booking_room = $user_booked['room'];
            break;
        }
    }
    
    // Cek apakah slot ini sudah dibooking di ruangan ini
    if (!$user_has_booking) {
        foreach ($booked_ranges as $booked) {
            $slot_start = strtotime($start_time);
            $slot_end = strtotime($end_time);
            $booking_start = strtotime($booked['start']);
            $booking_end = strtotime($booked['end']);
            
            // Cek overlap: jika ada irisan waktu antara slot dengan booking
            if (!(($slot_end <= $booking_start) || ($slot_start >= $booking_end))) {
                $slot_status = strtolower($booked['status']);
                $booked_by = $booked['user'];
                break;
            }
        }
    }
    
    $time_slots[] = [
        'time' => date('H:i', strtotime($start_time)) . ' - ' . date('H:i', strtotime($end_time)),
        'start' => date('H:i', strtotime($start_time)),
        'end' => date('H:i', strtotime($end_time)),
        'status' => $slot_status,
        'booked_by' => $booked_by,
        'user_has_booking' => $user_has_booking,
        'user_booking_room' => $user_booking_room
    ];
}

echo json_encode([
    'success' => true,
    'slots' => $time_slots,
    'bookings' => $booked_ranges,
    'user_bookings' => $user_booked_ranges
]);
?>