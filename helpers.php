<?php
/**
 * Helper Functions untuk Sistem Penjadwalan
 */

/**
 * Mendapatkan nama role dalam bahasa Indonesia
 */
function getRoleName($role) {
    $roles = [
        'admin' => 'Administrator',
        'kepala_sekolah' => 'Kepala Sekolah',
        'admin_ruangan' => 'Admin Ruangan',
        'guru_lab' => 'Guru Laboratorium',
        'guru' => 'Guru',
        'guru_pramuka' => 'Guru Pramuka',
        'guru_olahraga' => 'Guru Olahraga',
        'guru_silat' => 'Guru Silat',
        'guru_marching_band' => 'Guru Marching Band',
        'guru_paskibra' => 'Guru Paskibra',
        'siswa' => 'Siswa'
    ];
    
    return isset($roles[$role]) ? $roles[$role] : ucfirst(str_replace('_', ' ', $role));
}

/**
 * Mendapatkan nama tipe ruangan dalam bahasa Indonesia
 */
function getRoomTypeName($type) {
    $types = [
        'rapat' => 'Ruang Rapat',
        'laboratorium' => 'Laboratorium',
        'lapangan' => 'Lapangan'
    ];
    
    return isset($types[$type]) ? $types[$type] : ucfirst($type);
}

/**
 * Mendapatkan icon untuk role
 */
function getRoleIcon($role) {
    $icons = [
        'admin' => 'fa-user-shield',
        'kepala_sekolah' => 'fa-user-tie',
        'admin_ruangan' => 'fa-door-open',
        'guru_lab' => 'fa-flask',
        'guru' => 'fa-chalkboard-teacher',
        'guru_pramuka' => 'fa-campground',
        'guru_olahraga' => 'fa-running',
        'guru_silat' => 'fa-hand-rock',
        'guru_marching_band' => 'fa-music',
        'guru_paskibra' => 'fa-flag',
        'siswa' => 'fa-user-graduate'
    ];
    
    return isset($icons[$role]) ? $icons[$role] : 'fa-user';
}

/**
 * Mendapatkan icon untuk tipe ruangan
 */
function getRoomTypeIcon($type) {
    $icons = [
        'rapat' => 'fa-users',
        'laboratorium' => 'fa-microscope',
        'lapangan' => 'fa-futbol'
    ];
    
    return isset($icons[$type]) ? $icons[$type] : 'fa-door-open';
}

/**
 * Cek apakah role bisa booking
 */
function canBookRoom($role) {
    $booking_roles = [
        'guru', 
        'guru_lab', 
        'guru_pramuka', 
        'guru_olahraga', 
        'guru_silat', 
        'guru_marching_band', 
        'guru_paskibra', 
        'siswa'
    ];
    
    return in_array($role, $booking_roles);
}

/**
 * Cek apakah role bisa booking laboratorium
 */
function canBookLaboratory($role) {
    $allowed_roles = [
        'admin',
        'guru_lab', 
        'guru_pramuka', 
        'guru_olahraga', 
        'guru_silat', 
        'guru_marching_band', 
        'guru_paskibra'
    ];
    
    return in_array($role, $allowed_roles);
}

/**
 * Format status booking
 */
function getBookingStatusText($status) {
    $statuses = [
        'pending' => 'Menunggu Persetujuan',
        'approved' => 'Disetujui',
        'rejected' => 'Ditolak'
    ];
    
    return isset($statuses[$status]) ? $statuses[$status] : ucfirst($status);
}

/**
 * Get CSS class untuk status badge
 */
function getStatusBadgeClass($status) {
    return 'status-badge ' . strtolower($status);
}

/**
 * Format tanggal Indonesia
 */
function formatTanggalIndonesia($date) {
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    
    $split = explode('-', date('Y-m-d', strtotime($date)));
    return $split[2] . ' ' . $bulan[(int)$split[1]] . ' ' . $split[0];
}
?>