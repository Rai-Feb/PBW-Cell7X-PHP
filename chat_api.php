<?php
session_start();
require_once 'config/koneksi.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$role = $_SESSION['role'] ?? '';
$my_id = $_SESSION['user_id'] ?? 0;

if ($my_id === 0) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// 1. KIRIM PESAN & GAMBAR
if ($action === 'send') {
    $msg = trim($_POST['message'] ?? '');
    $target_id = (int) ($_POST['target_id'] ?? 0);
    $uid = ($role === 'admin') ? $target_id : $my_id;

    $attachment = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $attachment = 'chat_' . time() . '_' . uniqid() . '.' . $ext;
            $path = 'uploads/chat/';
            if (!is_dir($path))
                mkdir($path, 0777, true); // Auto-create folder
            move_uploaded_file($_FILES['image']['tmp_name'], $path . $attachment);
        }
    }

    if ($msg !== '' || $attachment !== null) {
        $stmt = mysqli_prepare($conn, "INSERT INTO chats (user_id, sender_role, message, attachment, created_at) VALUES (?, ?, ?, ?, NOW())");
        mysqli_stmt_bind_param($stmt, "isss", $uid, $role, $msg, $attachment);
        mysqli_stmt_execute($stmt);
    }
    echo json_encode(['status' => 'ok']);
    exit;
}

// 2. AMBIL PESAN
if ($action === 'fetch') {
    $target_id = (int) ($_GET['target_id'] ?? 0);
    $uid = ($role === 'admin') ? $target_id : $my_id;

    $stmt = mysqli_prepare($conn, "SELECT id, sender_role, message, attachment, created_at FROM chats WHERE user_id = ? ORDER BY created_at ASC");
    mysqli_stmt_bind_param($stmt, "i", $uid);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    $data = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $data[] = $row;
    }
    echo json_encode($data);
    exit;
}

// 3. HAPUS PESAN
if ($action === 'delete') {
    $msg_id = (int) $_POST['msg_id'];
    $stmt = mysqli_prepare($conn, "SELECT attachment FROM chats WHERE id = ? AND sender_role = ?");
    mysqli_stmt_bind_param($stmt, "is", $msg_id, $role);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($res)) {
        if ($row['attachment'] && file_exists('uploads/chat/' . $row['attachment'])) {
            unlink('uploads/chat/' . $row['attachment']); // Hapus file fisiknya
        }
        mysqli_query($conn, "DELETE FROM chats WHERE id = $msg_id");
    }
    echo json_encode(['status' => 'ok']);
    exit;
}

// 4. CEK STATUS
if ($action === 'status') {
    $active = (int) ($_GET['active'] ?? 1);
    mysqli_query($conn, "UPDATE users SET is_online = $active, last_seen = NOW() WHERE id = $my_id");
    echo json_encode(['status' => 'ok']);
    exit;
}

if ($action === 'check_status') {
    $target_id = (int) ($_GET['target_id'] ?? 0);
    if ($role === 'customer') {
        $res = mysqli_query($conn, "SELECT is_online, last_seen FROM users WHERE role = 'admin' ORDER BY last_seen DESC LIMIT 1");
    } else {
        $res = mysqli_query($conn, "SELECT is_online, last_seen FROM users WHERE id = $target_id");
    }
    $data = mysqli_fetch_assoc($res);
    echo json_encode([
        'is_online' => $data['is_online'] ?? 0,
        'last_seen' => $data['last_seen'] ?? null
    ]);
    exit;
}

echo json_encode(['error' => 'Invalid Action']);
?>