<?php
include("config.php");
include("firebaseRDB.php");

// ตั้งค่าโซนเวลา
date_default_timezone_set('Asia/Bangkok');

// รับค่าจากฟอร์ม
$fullName = $_POST['fullName'];
$employeeId = $_POST['employeeId'];
$position = $_POST['position'];
$email = $_POST['email'];
$password = $_POST['password'];
$profileImageUrl = isset($_POST['profileImageUrl']) ? $_POST['profileImageUrl'] : '';
$dayOff = $_POST['dayoff'] ?? ''; // รับข้อมูลวันหยุดจากฟอร์ม

// ตรวจสอบว่ามีการกรอกข้อมูลครบถ้วนหรือไม่
if (empty($fullName)) {
    echo "กรุณากรอกชื่อเต็ม";
} elseif (empty($employeeId)) {
    echo "กรุณากรอกรหัสพนักงาน";
} elseif (empty($position)) {
    echo "กรุณากรอกตำแหน่งงาน";
} elseif (empty($email)) {
    echo "กรุณากรอกอีเมล";
} elseif (empty($password)) {
    echo "กรุณากรอกรหัสผ่าน";
} elseif (empty($dayOff)) {
    echo "กรุณาเลือกวันหยุด";
} else {
    // แฮชรหัสผ่านก่อนเก็บในฐานข้อมูล
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // เชื่อมต่อ Firebase
    $rdb = new firebaseRDB($databaseURL);

    // ตรวจสอบว่าอีเมลนี้มีการใช้งานแล้วหรือไม่
    $retrieve = $rdb->retrieve("/user", "email", "EQUAL", $email);
    $data = json_decode($retrieve, true);

    if (isset($data['email'])) {
        echo "อีเมลนี้ถูกใช้งานแล้ว";
    } else {
        // แทรกข้อมูลผู้ใช้ใหม่เข้า Firebase
        $insert = $rdb->insert("/user", [
            "fullName" => $fullName,
            "employeeId" => $employeeId,
            "position" => $position,
            "email" => $email,
            "password" => $hashedPassword,
            "profileImageUrl" => $profileImageUrl,
            "dayoff" => $dayOff // บันทึกข้อมูลวันหยุดลงในฐานข้อมูล
        ]);

        $result = json_decode($insert, true);
        if (isset($result['name'])) {
            echo "สมัครสมาชิกสำเร็จ กรุณาเข้าสู่ระบบ";
            header("Location: login.php");
        } else {
            echo "การสมัครสมาชิกล้มเหลว";
        }
    }
}
?>
