<?php
require 'vendor/autoload.php';

use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;

include("config.php");
include("firebaseRDB.php");

// รับค่าจากฟอร์ม
$formFullName = $_POST['fullName'];
$formEmployeeId = $_POST['employeeId'];
$formPosition = $_POST['position'];
$formLatitude = $_POST['latitude'];
$formLongitude = $_POST['longitude'];
$formAction = $_POST['action'];
$formCurrentTime = $_POST['currentTime'];

// สร้าง instance ของ firebaseRDB
$rdb = new firebaseRDB($databaseURL);

// ดึงข้อมูลการลงเวลาของพนักงานทั้งหมด
$employeeLogsJson = $rdb->retrieve("/datatime");
$userJson = $rdb->retrieve("/user");

// ตรวจสอบว่าข้อมูลที่ดึงมาไม่ใช่ null และไม่ใช่ string ว่าง
$employeeLogs = $employeeLogsJson ? json_decode($employeeLogsJson, true) : [];
$userData = $userJson ? json_decode($userJson, true) : [];

// ดึงข้อมูลวันหยุดของผู้ใช้
$dayOff = "";
foreach ($userData as $user) {
    if (isset($user['employeeId']) && $user['employeeId'] == $formEmployeeId) {
        $dayOff = $user['dayoff'];
        break;
    }
}

// ตรวจสอบว่าพนักงานได้ Clock In หรือ Clock Out ไปแล้วหรือไม่
$date = date('Y-m-d', strtotime($formCurrentTime));
$hasClockedIn = false;
$hasClockedOut = false;

foreach ($employeeLogs as $log) {
    if (isset($log['employeeId']) && $log['employeeId'] == $formEmployeeId) {
        if (isset($log['currentTime']) && isset($log['action'])) {
            $logDate = date('Y-m-d', strtotime($log['currentTime']));
            if ($logDate == $date) {
                if ($log['action'] == 'Clock In') {
                    $hasClockedIn = true;
                } elseif ($log['action'] == 'Clock Out') {
                    $hasClockedOut = true;
                }
            }
        }
    }
}

// ตรวจสอบวันหยุด
$currentDay = date('l', strtotime($formCurrentTime));
$isDayOff = $currentDay == $dayOff;

if ($isDayOff && !$hasClockedIn && !$hasClockedOut) {
    // กำหนดเวลา Clock In และ Clock Out อัตโนมัติในวันหยุด
    $clockInTime = date('Y-m-d') . ' 09:00:00';
    $clockOutTime = date('Y-m-d') . ' 18:00:00';

    $dataClockIn = [
        'fullName' => $formFullName,
        'employeeId' => $formEmployeeId,
        'position' => $formPosition,
        'latitude' => $formLatitude,
        'longitude' => $formLongitude,
        'action' => 'Clock In',
        'currentTime' => $clockInTime,
        'imageUrl' => ''
    ];

    $dataClockOut = [
        'fullName' => $formFullName,
        'employeeId' => $formEmployeeId,
        'position' => $formPosition,
        'latitude' => $formLatitude,
        'longitude' => $formLongitude,
        'action' => 'Clock Out',
        'currentTime' => $clockOutTime,
        'imageUrl' => ''
    ];

    $rdb->insert("/datatime", $dataClockIn);
    $rdb->insert("/datatime", $dataClockOut);

    echo "<script>alert('วันนี้เป็นวันหยุด ข้อมูล Clock In/Out ถูกบันทึกอัตโนมัติ'); window.location.href='profile.php';</script>";
    exit();
}

// เพิ่มเงื่อนไขใหม่ก่อนบันทึกข้อมูล
if (!$isDayOff && !$hasClockedIn && !$hasClockedOut) {
    $dataAbsent = [
        'fullName' => $formFullName,
        'employeeId' => $formEmployeeId,
        'position' => $formPosition,
        'latitude' => $formLatitude,
        'longitude' => $formLongitude,
        'action' => 'Absent',
        'currentTime' => $formCurrentTime,
        'imageUrl' => ''
    ];

    $rdb->insert("/datatime", $dataAbsent);
    echo "<script>alert('วันนี้คุณขาดงาน'); window.location.href='profile.php';</script>";
    exit();
}

// ตรวจสอบเงื่อนไขและบันทึกข้อมูล
if ($formAction == 'Clock In' && $hasClockedIn) {
    echo "<script>alert('วันนี้คุณได้ Clock In แล้ว'); window.location.href='profile.php';</script>";
    exit();
} elseif ($formAction == 'Clock Out' && !$hasClockedIn) {
    echo "<script>alert('กรุณา Clock In ก่อน'); window.location.href='profile.php';</script>";
    exit();
} elseif ($formAction == 'Clock Out' && $hasClockedOut) {
    echo "<script>alert('วันนี้คุณได้บันทึกเวลาครบแล้ว'); window.location.href='profile.php';</script>";
    exit();
} else {
    // บันทึกไฟล์ภาพลงใน Firebase Storage
    $uploadedFileUrl = '';

    if (isset($_FILES['locationImage']) && $_FILES['locationImage']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['locationImage']['tmp_name'];
        $fileName = $_FILES['locationImage']['name'];
        $fileType = $_FILES['locationImage']['type'];
        $fileSize = $_FILES['locationImage']['size'];

        $firebase = (new Factory)
            ->withServiceAccount('path/to/firebase_credentials.json')
            ->create();

        $storage = $firebase->getStorage();
        $bucket = $storage->getBucket();

        $filePath = 'imgmap/' . $fileName;
        $bucket->upload(
            fopen($fileTmpPath, 'r'),
            [
                'name' => $filePath
            ]
        );

        $uploadedFileUrl = "https://storage.googleapis.com/your-bucket-name/" . $filePath;
    }

    // บันทึกข้อมูลลง Firebase Realtime Database
    $data = [
        'fullName' => $formFullName,
        'employeeId' => $formEmployeeId,
        'position' => $formPosition,
        'latitude' => $formLatitude,
        'longitude' => $formLongitude,
        'action' => $formAction,
        'currentTime' => $formCurrentTime,
        'imageUrl' => $uploadedFileUrl
    ];

    $insert = $rdb->insert("/datatime", $data);

    $result = json_decode($insert, true);
    if (isset($result['name'])) {
        echo "ลงเวลาสำเร็จ";
        header("Location: profile.php");
    } else {
        echo "ลงเวลาล้มเหลว";
    }
}
?>
