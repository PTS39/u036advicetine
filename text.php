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

        function openForm(action) {
            getLocation(function(latitude, longitude) {
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

        .btn {
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin: 5px;
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
                    <input type="file" id="locationImage" name="locationImage" accept="image/*">
                    <button id="clockIn" class="btn" onclick="openForm('Clock In')">Clock In</button>
                    <button id="clockOut" class="btn" onclick="openForm('Clock Out')">Clock Out</button>
                    <form action="logout.php" method="post">
                        <button type="submit" class="btn">Logout</button>
                    </form>
                </div>
                <div class="form-img">    
                    <h2>Clock In/Out Log</h2>
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
                            foreach ($employeeLogs as $log) {
                                $logDate = formatDate($log['currentTime']);
                                $logTime = formatTime($log['currentTime']);
                                $action = $log['action'];

                                if (!isset($logsByDate[$logDate])) {
                                    $logsByDate[$logDate] = [
                                        'Clock In' => null,
                                        'Clock Out' => null
                                    ];
                                }

                                $logsByDate[$logDate][$action] = $logTime;
                            }

                            foreach ($logsByDate as $date => $log) {
                                echo "<tr>";
                                echo "<td>$date</td>";
                                echo "<td>" . ($log['Clock In'] ?? '-') . "</td>";
                                echo "<td>" . ($log['Clock Out'] ?? '-') . "</td>";
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
                <form id="clockForm" method="POST" action="profile_submit.php" enctype="multipart/form-data">
                    <input type="hidden" id="formEmployeeId" name="employeeId">
                    <input type="hidden" id="formFullName" name="fullName">
                    <input type="hidden" id="formPosition" name="position">
                    <input type="hidden" id="formLatitude" name="latitude">
                    <input type="hidden" id="formLongitude" name="longitude">
                    <input type="hidden" id="formAction" name="action">
                    <input type="hidden" id="formCurrentTime" name="currentTime">
                    <p id="formFullNameText"></p>
                    <p id="formEmployeeIdText"></p>
                    <p id="formPositionText"></p>
                    <p id="formLatitudeText"></p>
                    <p id="formLongitudeText"></p>
                    <p id="formActionText"></p>
                    <p id="formCurrentTimeText"></p>
                    <button type="button" class="btn" onclick="submitForm()">ยืนยัน</button>
                    <button type="button" class="btn btn-danger" onclick="closeForm()">ยกเลิก</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
