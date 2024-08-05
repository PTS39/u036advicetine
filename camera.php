<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เปิดใช้งานกล้อง</title>
    <style>
        #cameraModal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            justify-content: center;
            align-items: center;
        }

        #cameraContent {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }

        #video, #canvas {
            width: 100%;
            max-width: 640px;
            height: auto;
        }

        #closeCamera {
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <h1>เปิดใช้งานกล้อง</h1>
    <button id="start">เปิดกล้อง</button>

    <div id="cameraModal">
        <div id="cameraContent">
            <video id="video" width="640" height="480" autoplay></video>
            <br>
            <button id="snap">Capture</button>
            <button id="closeCamera">ปิดกล้อง</button>
            <canvas id="canvas" width="640" height="480" style="display:none;"></canvas>
            <button id="retake" style="display:none;">ถ่ายใหม่</button>
            <button id="save" style="display:none;">บันทึกรูป</button>
        </div>
    </div>

    <script>
        const startButton = document.getElementById('start');
        const cameraModal = document.getElementById('cameraModal');
        const video = document.getElementById('video');
        const snapButton = document.getElementById('snap');
        const canvas = document.getElementById('canvas');
        const closeCameraButton = document.getElementById('closeCamera');
        const retakeButton = document.getElementById('retake');
        const saveButton = document.getElementById('save');
        let stream;

        // ฟังก์ชันเปิดใช้งานกล้องเมื่อกดปุ่ม
        startButton.addEventListener('click', function() {
            cameraModal.style.display = 'flex';
            if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
                navigator.mediaDevices.getUserMedia({ video: true }).then(function(mediaStream) {
                    stream = mediaStream;
                    video.srcObject = stream;
                    video.play();
                });
            }
        });

        // จับภาพจากกล้องและแสดงบน canvas แล้วหยุดการใช้งานกล้อง
        snapButton.addEventListener('click', function() {
            const context = canvas.getContext('2d');
            context.drawImage(video, 0, 0, canvas.width, canvas.height);

            // หยุดการใช้งานกล้อง
            stream.getTracks().forEach(track => track.stop());

            // ซ่อน video และปุ่ม capture แสดง canvas และปุ่ม retake แทน
            video.style.display = 'none';
            snapButton.style.display = 'none';
            closeCameraButton.style.display = 'none';
            canvas.style.display = 'block';
            retakeButton.style.display = 'block';
            saveButton.style.display = 'block';
        });

        // ฟังก์ชันถ่ายใหม่
        retakeButton.addEventListener('click', function() {
            if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
                navigator.mediaDevices.getUserMedia({ video: true }).then(function(mediaStream) {
                    stream = mediaStream;
                    video.srcObject = stream;
                    video.play();
                    video.style.display = 'block';
                    snapButton.style.display = 'block';
                    closeCameraButton.style.display = 'block';
                    canvas.style.display = 'none';
                    retakeButton.style.display = 'none';
                    saveButton.style.display = 'none';
                });
            }
        });

        // ฟังก์ชันปิดกล้องและป๊อปอัพ
        closeCameraButton.addEventListener('click', function() {
            stream.getTracks().forEach(track => track.stop());
            cameraModal.style.display = 'none';
            video.style.display = 'block';
            snapButton.style.display = 'block';
            closeCameraButton.style.display = 'block';
            canvas.style.display = 'none';
            retakeButton.style.display = 'none';
            saveButton.style.display = 'none';
        });

        // ฟังก์ชันบันทึกรูป
        saveButton.addEventListener('click', function() {
            const dataURL = canvas.toDataURL('image/png');
            // ส่งค่า dataURL ไปยัง backend เพื่อบันทึกในฐานข้อมูล
            fetch('/save-image', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ image: dataURL })
            }).then(response => {
                if (response.ok) {
                    alert('บันทึกรูปสำเร็จ!');
                } else {
                    alert('บันทึกรูปไม่สำเร็จ!');
                }
            }).catch(error => {
                console.error('Error:', error);
                alert('บันทึกรูปไม่สำเร็จ!');
            });
        });
    </script>
</body>
</html>
