<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="signup.css">
    <!-- Firebase App (the core Firebase SDK) is always required and must be listed first -->
    <script src="https://www.gstatic.com/firebasejs/9.6.1/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.6.1/firebase-storage-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.6.1/firebase-auth-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.6.1/firebase-database-compat.js"></script>
    <script src="uploadProfileImage.js" defer></script>
</head>
<body>
    <div class="login-form">
        <div class="container">
            <h1>ลงทะเบียน</h1>
            <div class="main">
                <div class="form-img">
                    <div class="upload-btn-wrapper">
                        <div class="profile-pic" id="profilePic"></div>
                        <label class="upload-btn">
                            <input type="file" id="profileImage" accept="image/*">
                            อัพโหลดรูป
                        </label>
                    </div>
                </div>
                <div class="content">
                    <form id="registerForm" method="POST" action="register_process.php">
                        <div class="form-group">
                            <label for="fullName">ชื่อ-นามสกุล</label>
                            <input type="text" id="fullName" name="fullName" required>
                        </div>
                        <div class="form-group">
                            <label for="employeeId">รหัสพนักงาน</label>
                            <input type="text" id="employeeId" name="employeeId" required>              
                        </div>
                        <div class="form-group">
                            <label for="position">ตำแหน่ง</label>
                            <input type="text" id="position" name="position" required>
                        </div>
                        <div class="form-group">
                            <label for="email">อีเมล</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label for="password">รหัสผ่าน</label>
                            <input type="password" id="password" name="password" required>
                        </div>
                        <div class="form-group">
                            <label for="dayoff">วันหยุด</label>
                            <div class="dayoff">
                                <select id="dayoff" name="dayoff" style="width:100%; padding: 10px; border: 1px solid #ccc; border-radius: 50px;}" required>
                                    <option value="">เลือกวันหยุด</option>
                                    <option value="Sunday">Sunday</option>
                                    <option value="Monday">Monday</option>
                                    <option value="Tuesday">Tuesday</option>
                                    <option value="Wednesday">Wednesday</option>
                                    <option value="Thursday">Thursday</option>
                                    <option value="Friday">Friday</option>
                                    <option value="Saturday">Saturday</option>
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="btn">บันทึกข้อมูล</button>
                    </form>
                    <p class="account">Don't Have An Account? <a href="login.php">Login</a></p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
