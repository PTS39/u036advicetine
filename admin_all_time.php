<?php
include("config.php");
include("firebaseRDB.php");

// ตรวจสอบว่าเซสชันเริ่มต้นหรือไม่
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// ตรวจสอบว่าผู้ใช้ได้ล็อกอินหรือไม่
if (!isset($_SESSION['user_email'])) {
    header("Location: login.php");
    exit();
}

$email = $_SESSION['user_email'];
$rdb = new firebaseRDB($databaseURL);

// ดึงข้อมูลผู้ใช้ตามอีเมล
$retrieve = $rdb->retrieve("/user", "email", "EQUAL", $email);
$data = json_decode($retrieve, true);

if (!empty($data)) {
    $user = reset($data);
} else {
    echo "เกิดข้อผิดพลาดในการดึงข้อมูลผู้ใช้";
    exit();
}

// ดึงข้อมูล Clock In/Out ของผู้ใช้
$employeeId = $user['employeeId'];
$retrieveLogs = $rdb->retrieve("/datatime", "employeeId", "EQUAL", $employeeId);
$employeeLogsJson = json_decode($retrieveLogs, true);

// แปลงข้อมูลเป็นอาเรย์
$employeeLogs = [];
if (!empty($employeeLogsJson)) {
    foreach ($employeeLogsJson as $log) {
        $employeeLogs[] = $log;
    }
}

// ฟังก์ชันช่วยในการจัดรูปแบบวันที่และเวลา
function formatDate($datetime) {
    return substr($datetime, 0, 10); // YYYY-MM-DD
}

function formatTime($datetime) {
    return substr($datetime, 11); // HH:MM:SS
}

function calculateFullTime($clockIn, $clockOut) {
    $in = new DateTime($clockIn);
    $out = new DateTime($clockOut);
    $interval = $in->diff($out);
    return $interval->h . ' ชั่วโมง ' . $interval->i . ' นาที ' . $interval->s . ' วินาที';
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Time</title>
    <link rel="stylesheet" href="profile.css">
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table, th, td {
            border: 1px solid black;
        }

        th, td {
            padding: 10px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        .btn-back {
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin: 5px;
        }

        .btn-back:hover {
            background-color: #0056b3;
        }

        .popup {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .popup-content {
            background: white;
            padding: 20px;
            border-radius: 8px;
            width: 80%;
            max-width: 800px;
            position: relative;
        }

        .close-button {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 20px;
            cursor: pointer;
        }

        img {
            max-width: 150px;
            max-height: 150px;
        }

        .map-container {
            width: 100%;
            height: 400px;
        }

        #map {
            width: 100%;
            height: 100%;
        }
    </style>
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script>
        function openPopup(label, latitude, longitude) {
            var popup = document.getElementById('popup-' + label);
            popup.style.display = 'flex';

            var mapId = 'map-' + label;
            var map = L.map(mapId).setView([latitude, longitude], 15);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);

            L.marker([latitude, longitude]).addTo(map);
        }

        function closePopup(label) {
            document.getElementById('popup-' + label).style.display = 'none';
        }
    </script>
</head>
<body>
    <div class="profile-form">
        <h1>ข้อมูลทั้งหมด</h1>
        <button class="btn-back" onclick="window.location.href='profile.php'">กลับไปที่โปรไฟล์</button>
        <h2>ข้อมูลผู้ใช้</h2>
        <p><strong>รูปภาพโปรไฟล์:</strong></p>
        <img src="<?php echo htmlspecialchars($user['profileImageUrl']); ?>" alt="Profile Image">
        <p><strong>ชื่อเต็ม:</strong> <?php echo htmlspecialchars($user['fullName']); ?></p>
        <p><strong>รหัสพนักงาน:</strong> <?php echo htmlspecialchars($user['employeeId']); ?></p>
        <p><strong>ตำแหน่ง:</strong> <?php echo htmlspecialchars($user['position']); ?></p>
        <p><strong>อีเมล:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
        
        <h2>บันทึกการ Clock In/Out</h2>
        <table>
            <thead>
                <tr>
                    <th>วันที่</th>
                    <th>เวลา Clock In</th>
                    <th>ดูตำแหน่ง</th>
                    <th>เวลา Clock Out</th>
                    <th>ดูตำแหน่ง</th>
                    <th>เวลา Full Time</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $logsByDate = [];
                foreach ($employeeLogs as $log) {
                    $logDate = formatDate($log['currentTime']);
                    $logTime = formatTime($log['currentTime']);
                    $action = $log['action'];
                    $latitude = $log['latitude'];
                    $longitude = $log['longitude'];

                    if (!isset($logsByDate[$logDate])) {
                        $logsByDate[$logDate] = [
                            'Clock In' => null,
                            'Clock Out' => null,
                            'Clock In Latitude' => null,
                            'Clock In Longitude' => null,
                            'Clock Out Latitude' => null,
                            'Clock Out Longitude' => null
                        ];
                    }

                    if ($action == 'Clock In') {
                        $logsByDate[$logDate]['Clock In'] = $logTime;
                        $logsByDate[$logDate]['Clock In Latitude'] = $latitude;
                        $logsByDate[$logDate]['Clock In Longitude'] = $longitude;
                    } else if ($action == 'Clock Out') {
                        $logsByDate[$logDate]['Clock Out'] = $logTime;
                        $logsByDate[$logDate]['Clock Out Latitude'] = $latitude;
                        $logsByDate[$logDate]['Clock Out Longitude'] = $longitude;
                    }
                }

                foreach ($logsByDate as $date => $log) {
                    $clockIn = $log['Clock In'];
                    $clockOut = $log['Clock Out'];
                    $fullTime = $clockIn && $clockOut ? calculateFullTime("$date $clockIn", "$date $clockOut") : '-';
                    
                    $clockInLat = $log['Clock In Latitude'];
                    $clockInLng = $log['Clock In Longitude'];
                    $clockOutLat = $log['Clock Out Latitude'];
                    $clockOutLng = $log['Clock Out Longitude'];

                    echo "<tr>";
                    echo "<td>$date</td>";
                    echo "<td>" . ($log['Clock In'] ?? '-') . "</td>";
                    echo "<td>";
                    if ($clockInLat && $clockInLng) {
                        echo "<a onclick=\"openPopup('ClockIn-$date', $clockInLat, $clockInLng)\">ดูตำแหน่ง</a>";
                    }
                    echo "</td>";
                    echo "<td>" . ($log['Clock Out'] ?? '-') . "</td>";
                    echo "<td>";
                    if ($clockOutLat && $clockOutLng) {
                        echo "<button onclick=\"openPopup('ClockOut-$date', $clockOutLat, $clockOutLng)\">ดูตำแหน่ง</button>";
                    }
                    echo "</td>";
                    echo "<td>$fullTime</td>";
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <!-- ป๊อปอัพแสดงแผนที่ -->
    <?php
    foreach ($logsByDate as $date => $log) {
        $clockInLat = $log['Clock In Latitude'];
        $clockInLng = $log['Clock In Longitude'];
        $clockOutLat = $log['Clock Out Latitude'];
        $clockOutLng = $log['Clock Out Longitude'];

        if ($clockInLat && $clockInLng) {
            echo "
            <div id=\"popup-ClockIn-$date\" class=\"popup\" style=\"display: none;\">
                <div class=\"popup-content\">
                    <span class=\"close-button\" onclick=\"closePopup('ClockIn-$date')\">&times;</span>
                    <h3>ตำแหน่ง Clock In - $date</h3>
                    <div id=\"map-ClockIn-$date\" class=\"map-container\"></div>
                </div>
            </div>";
        }

        if ($clockOutLat && $clockOutLng) {
            echo "
            <div id=\"popup-ClockOut-$date\" class=\"popup\" style=\"display: none;\">
                <div class=\"popup-content\">
                    <span class=\"close-button\" onclick=\"closePopup('ClockOut-$date')\">&times;</span>
                    <h3>ตำแหน่ง Clock Out - $date</h3>
                    <div id=\"map-ClockOut-$date\" class=\"map-container\"></div>
                </div>
            </div>";
        }
    }
    ?>
</body>
</html>
