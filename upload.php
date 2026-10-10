<?php
session_start();
include 'koneksi.php';

// Proteksi: harus login dan harus mahasiswa
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if ($_SESSION['role'] !== 'mahasiswa') {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $task_id = intval($_POST['task_id']);
    $user_id = $_SESSION['user_id'];
    $file    = $_FILES['file'];

    // Validasi: ada file dan tidak ada error upload
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        header("Location: dashboard.php?upload=error&msg=file_error");
        exit();
    }

    // Validasi ekstensi file
    $allowed_ext  = ['pdf', 'doc', 'docx', 'zip'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed_ext)) {
        header("Location: dashboard.php?upload=error&msg=invalid_type");
        exit();
    }

    // Validasi ukuran file (maks 10MB)
    $max_size = 10 * 1024 * 1024;
    if ($file['size'] > $max_size) {
        header("Location: dashboard.php?upload=error&msg=too_large");
        exit();
    }

    // Validasi MIME type
    $allowed_mime = ['application/pdf', 'application/msword',
                     'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                     'application/zip', 'application/x-zip-compressed'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, $allowed_mime)) {
        header("Location: dashboard.php?upload=error&msg=invalid_mime");
        exit();
    }

    // Validasi task_id milik room_code yang sama
    $room_code = $_SESSION['room_code'];
    $check = $conn->prepare("SELECT id FROM tasks WHERE id = ? AND room_code = ?");
    $check->bind_param("is", $task_id, $room_code);
    $check->execute();
    if ($check->get_result()->num_rows === 0) {
        header("Location: dashboard.php?upload=error&msg=invalid_task");
        exit();
    }

    // Buat folder uploads jika belum ada
    $upload_dir = __DIR__ . "/uploads/";
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    // Generate nama file unik agar aman
    $safe_name = uniqid('file_', true) . '.' . $ext;
    $target    = $upload_dir . $safe_name;
    $db_path   = "uploads/" . $safe_name;

    if (move_uploaded_file($file['tmp_name'], $target)) {
        // Simpan ke database dengan prepared statement
        $stmt = $conn->prepare("INSERT INTO submissions (task_id, mahasiswa_id, file_path) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $task_id, $user_id, $db_path);

        if ($stmt->execute()) {
            header("Location: dashboard.php?upload=success");
        } else {
            header("Location: dashboard.php?upload=error&msg=db_error");
        }
    } else {
        header("Location: dashboard.php?upload=error&msg=move_failed");
    }
    exit();
}

// Jika akses langsung tanpa POST
header("Location: dashboard.php");
exit();
?>
