<?php
include("config.php");
include("firebaseRDB.php");

// ตั้งค่าโซนเวลา
date_default_timezone_set('Asia/Bangkok');

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
    echo "Error retrieving user data.";
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile</title>
    <link rel="stylesheet" href="profile.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <script src="https://www.gstatic.com/firebasejs/8.6.8/firebase-app.js"></script>
    <script src="https://www.gstatic.com/firebasejs/8.6.8/firebase-firestore.js"></script>
    <script src="https://www.gstatic.com/firebasejs/8.6.8/firebase-storage.js"></script>
    <script>
        function updateTime() {
            var now = new Date();
            var formattedTime = now.getFullYear() + '-' +
                ('0' + (now.getMonth() + 1)).slice(-2) + '-' +
                ('0' + now.getDate()).slice(-2) + ' ' +
                ('0' + now.getHours()).slice(-2) + ':' +
                ('0' + now.getMinutes()).slice(-2) + ':' +
                ('0' + now.getSeconds()).slice(-2);
            document.getElementById('currentTime').innerText = formattedTime;
        }

        setInterval(updateTime, 1000);
        window.onload = updateTime;

        function getLocation(callback) {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    var latitude = position.coords.latitude;
                    var longitude = position.coords.longitude;
                    callback(latitude, longitude);
                });
            } else {
                alert("Geolocation is not supported by this browser.");
            }
        }

        function calculateDistance(lat1, lon1, lat2, lon2) {
            var R = 6371e3; // รัศมีของโลก (เมตร)
            var φ1 = lat1 * Math.PI/180;
            var φ2 = lat2 * Math.PI/180;
            var Δφ = (lat2 - lat1) * Math.PI/180;
            var Δλ = (lon2 - lon1) * Math.PI/180;

            var a = Math.sin(Δφ/2) * Math.sin(Δφ/2) +
                    Math.cos(φ1) * Math.cos(φ2) *
                    Math.sin(Δλ/2) * Math.sin(Δλ/2);
            var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));

            var distance = R * c; // ระยะทางเป็นเมตร
            return distance;
        }

        function openForm(action) {
            getLocation(function(latitude, longitude) {
                var officeLatitude = 15.2752837;  // กำหนดค่าที่ตั้งสำนักงาน
                var officeLongitude = 104.7960272;  // กำหนดค่าที่ตั้งสำนักงาน
                var distance = calculateDistance(latitude, longitude, officeLatitude, officeLongitude);

                if (distance > 999) {
                    var distanceKm = Math.floor(distance / 1000);
                    var distanceM = distance % 1000;
                    var confirmMessage = "คุณอยู่นอกพื้นที่บริษัท คุณอยู่ห่างจากบริษัทประมาณ " + distanceKm + " กิโลเมตร " + distanceM.toFixed(2) + " เมตร คุณต้องการบันทึกข้อมูลหรือไม่?";
                } else if (distance > 10) {
                    var confirmMessage = "คุณอยู่นอกพื้นที่บริษัท คุณอยู่ห่างจากบริษัทประมาณ " + distance.toFixed(2) + " เมตร คุณต้องการบันทึกข้อมูลหรือไม่?";
                }

                if (distance > 10 && !confirm(confirmMessage)) {
                    return;
                }

                var fullName = document.getElementById('fullName').innerText;
                var employeeId = document.getElementById('employeeId').innerText;
                var position = document.getElementById('position').innerText;
                var currentTime = document.getElementById('currentTime').innerText;

                document.getElementById('formEmployeeId').value = employeeId;
                document.getElementById('formFullName').value = fullName;
                document.getElementById('formPosition').value = position;
                document.getElementById('formLatitude').value = latitude;
                document.getElementById('formLongitude').value = longitude;
                document.getElementById('formAction').value = action;
                document.getElementById('formCurrentTime').value = currentTime;

                document.getElementById('formFullNameText').innerText = 'ชื่อ: ' + fullName;
                document.getElementById('formEmployeeIdText').innerText = 'รหัสพนักงาน: ' + employeeId;
                document.getElementById('formPositionText').innerText = 'ตำแหน่ง: ' + position;
                document.getElementById('formLatitudeText').innerText = 'Latitude: ' + latitude;
                document.getElementById('formLongitudeText').innerText = 'Longitude: ' + longitude;
                document.getElementById('formActionText').innerText = 'Action: ' + action;
                document.getElementById('formCurrentTimeText').innerText = 'Current Time: ' + currentTime;

                document.getElementById('overlay').style.display = 'block';
                document.getElementById('popupForm').style.display = 'block';
            });
        }

        function closeForm() {
            document.getElementById('overlay').style.display = 'none';
            document.getElementById('popupForm').style.display = 'none';
        }

        function submitForm() {
            document.getElementById('clockForm').submit();
        }

        function showLocationPopup() {
            getLocation(function(latitude, longitude) {
                var locationText = "Latitude: " + latitude + "\nLongitude: " + longitude;
                alert(locationText);
            });
        }
    </script>
    <style>
        #popupForm {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: white;
            padding: 20px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            border-radius: 8px;
            width: 300px;
        }

        #overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
            display: none;
        }

        .button-container {
            display: flex;
            gap: 10px; /* ระยะห่างระหว่างปุ่ม */
            margin-top: 20px; /* ระยะห่างจากเนื้อหาข้างบน */
        }

        .btn {
            padding: 10px 20px;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .btn-clock-in {
            background-color: #28a745; /* สีเขียว */
        }

        .btn-clock-in:hover {
            background-color: #218838; /* สีเขียวเข้ม */
        }

        .btn-clock-out {
            background-color: #ffc107; /* สีเหลือง */
            color: black; /* เปลี่ยนสีข้อความให้เข้ากับปุ่ม */
        }

        .btn-clock-out:hover {
            background-color: #e0a800; /* สีเหลืองเข้ม */
        }

        .btn-logout {
            background-color: #dc3545; /* สีแดง */
        }

        .btn-logout:hover {
            background-color: #c82333; /* สีแดงเข้ม */
        }

        .btn:hover {
            background-color: #0056b3;
        }

        .btn-danger {
            background-color: #dc3545;
        }

        .btn-danger:hover {
            background-color: #c82333;
        }

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
    </style>
</head>
<body>
    <div class="profile-form">
        <h1>Profile</h1>
        <div class="container">
            <div class="main">
                <div class="content" id="contentid">
                    <h2>Profile Information</h2>
                    <p><strong>Full Name:</strong> <span id="fullName"><?php echo htmlspecialchars($user['fullName']); ?></span></p>
                    <p><strong>Employee ID:</strong> <span id="employeeId"><?php echo htmlspecialchars($user['employeeId']); ?></span></p>
                    <p><strong>Position:</strong> <span id="position"><?php echo htmlspecialchars($user['position']); ?></span></p>
                    <p><strong>Current Time:</strong> <span id="currentTime"></span></p>
                    <button type="button" class="btn btn-map" onclick="showLocationPopup()">กดเพื่อเช็คที่อยู่ของคุณ</button>

                    <div class="button-container">
                        <button id="clockIn" class="btn btn-clock-in" onclick="openForm('Clock In')">Clock In</button>
                        <button id="clockOut" class="btn btn-clock-out" onclick="openForm('Clock Out')">Clock Out</button>
                    </div>

                    <form action="logout.php" method="post">
                        <button type="submit" class="btn btn-logout">Logout</button>
                    </form>
                </div>
                <div id="map" style="height: 400px; width: 100%; display: none;"></div>
                <div class="form-img">
                    <h2>Clock In/Out Log</h2>
                    <button class="btn btn-green" onclick="window.location.href='all_time.php'">All Time</button>
                    <table>
                        <thead>
                            <tr>
                                <th>วันที่</th>
                                <th>เวลา Clock In</th>
                                <th>เวลา Clock Out</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $logsByDate = [];
                            $currentDate = date('Y-m-d');

                            foreach ($employeeLogs as $log) {
                                $logDate = date('Y-m-d', strtotime($log['currentTime']));
                                $logTime = date('H:i:s', strtotime($log['currentTime']));
                                $action = $log['action'];

                                if ($logDate == $currentDate) {
                                    if (!isset($logsByDate[$logDate])) {
                                        $logsByDate[$logDate] = [
                                            'Clock In' => null,
                                            'Clock Out' => null
                                        ];
                                    }

                                    $logsByDate[$logDate][$action] = $logTime;
                                }
                            }

                            if (isset($logsByDate[$currentDate])) {
                                $log = $logsByDate[$currentDate];
                                echo "<tr>";
                                echo "<td>$currentDate</td>";
                                echo "<td>" . ($log['Clock In'] ?? '-') . "</td>";
                                echo "<td>" . ($log['Clock Out'] ?? '-') . "</td>";
                                echo "</tr>";
                            } else {
                                echo "<tr>";
                                echo "<td colspan='3' style='text-align: center;'>ยังไม่มีข้อมูล/วันหยุด</td>";
                                echo "</tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>

                <!-- อื่นๆ ของคุณที่นี่ -->
            </div>

            <div id="overlay"></div>
            <div id="popupForm" style="display:none;">
            <h2>Clock In/Out Confirmation</h2>
                <p id="formFullNameText"></p>
                <p id="formEmployeeIdText"></p>
                <p id="formPositionText"></p>
                <p id="formLatitudeText"></p>
                <p id="formLongitudeText"></p>
                <p id="formActionText"></p>
                <p id="formCurrentTimeText"></p>
                <form id="clockForm" method="POST" action="profile_submit.php" enctype="multipart/form-data">
                    <input type="hidden" id="formEmployeeId" name="employeeId">
                    <input type="hidden" id="formFullName" name="fullName">
                    <input type="hidden" id="formPosition" name="position">
                    <input type="hidden" id="formLatitude" name="latitude">
                    <input type="hidden" id="formLongitude" name="longitude">
                    <input type="hidden" id="formAction" name="action">
                    <input type="hidden" id="formCurrentTime" name="currentTime">
                    <input type="file" id="locationImage" name="locationImage" accept="image/*">
                    <div class="button-container">
                        <button type="button" class="btn btn-danger" onclick="closeForm()">Close</button>
                        <button type="button" class="btn btn-primary" onclick="submitForm()">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>