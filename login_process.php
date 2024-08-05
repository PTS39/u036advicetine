<?php
include("config.php");
include("firebaseRDB.php");

// ตั้งค่าโซนเวลา
date_default_timezone_set('Asia/Bangkok');

$email = isset($_POST['email']) ? $_POST['email'] : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';

if (empty($email)) {
    echo "Email is required";
} elseif (empty($password)) {
    echo "Password is required";
} else {
    $rdb = new firebaseRDB($databaseURL);

    // ดึงข้อมูลผู้ใช้ตามอีเมล
    $retrieve = $rdb->retrieve("/user", "email", "EQUAL", $email);
    $data = json_decode($retrieve, true); // แปลง JSON เป็น Array

    // ตรวจสอบโครงสร้างของข้อมูล
    echo '<pre>';
    print_r($data);
    echo '</pre>';

    // ตรวจสอบว่าผู้ใช้มีข้อมูลอยู่หรือไม่
    if (empty($data) || !is_array($data)) {
        echo "Email not found";
    } else {
        // ดึงข้อมูลของผู้ใช้จากคีย์แรก
        $user = reset($data); // ดึงข้อมูลผู้ใช้จากคีย์แรก

        // ตรวจสอบว่ามีข้อมูลของผู้ใช้จริงหรือไม่
        if (isset($user['password']) && !password_verify($password, $user['password'])) {
            echo "Invalid password";
        } elseif (isset($user['password'])) {
            session_start();
            $_SESSION['user_email'] = $email;
            header("Location: profile.php");
            exit();
        } else {
            echo "Invalid user data";
        }
    }
}
?>
