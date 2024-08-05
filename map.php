<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Location Confirmation</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background: #f0f0f0;
        }
        .checkin {
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s ease;
        }
        .checkin:hover {
            background-color: #0056b3;
        }
        .popup {
            display: none;
            position: fixed;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            width: 90%;
            max-width: 600px;
            background-color: white;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            border-radius: 10px;
            overflow: hidden;
            z-index: 1000;
        }
        .popup.show {
            display: block;
            animation: fadeIn 0.3s ease-out;
        }
        .overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }
        .overlay.show {
            display: block;
            animation: fadeIn 0.3s ease-out;
        }
        .popup-header {
            padding: 15px;
            background-color: #007bff;
            color: white;
            font-size: 18px;
        }
        .popup-body {
            padding: 15px;
        }
        .popup-footer {
            padding: 10px 15px;
            text-align: right;
            background-color: #f1f1f1;
        }
        .popup-footer button {
            padding: 5px 10px;
            border: none;
            background-color: #007bff;
            color: white;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s ease;
        }
        .popup-footer button:hover {
            background-color: #0056b3;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
    </style>
    <script>
        function getLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(showPosition, showError);
            } else { 
                alert("Geolocation is not supported by this browser.");
            }
        }

        function showPosition(position) {
            const lat = position.coords.latitude;
            const lon = position.coords.longitude;
            document.getElementById("latitude").textContent = lat;
            document.getElementById("longitude").textContent = lon;

            const mapUrl = `https://www.google.com/maps?q=${lat},${lon}&hl=es;z=14&output=embed`;
            const iframe = document.createElement('iframe');
            iframe.src = mapUrl;
            iframe.width = '100%';
            iframe.height = '300';
            iframe.style.border = '0';

            const mapDiv = document.getElementById('map');
            mapDiv.innerHTML = '';
            mapDiv.appendChild(iframe);

            document.getElementById('popup').classList.add('show');
            document.getElementById('overlay').classList.add('show');
        }

        function showError(error) {
            switch(error.code) {
                case error.PERMISSION_DENIED:
                    alert("User denied the request for Geolocation.");
                    break;
                case error.POSITION_UNAVAILABLE:
                    alert("Location information is unavailable.");
                    break;
                case error.TIMEOUT:
                    alert("The request to get user location timed out.");
                    break;
                case error.UNKNOWN_ERROR:
                    alert("An unknown error occurred.");
                    break;
            }
        }

        function closePopup() {
            document.getElementById('popup').classList.remove('show');
            document.getElementById('overlay').classList.remove('show');
        }
    </script>
</head>
<body>
    <button class="checkin" onclick="getLocation()">กดเพื่อยืนยันที่อยู่ของคุณ</button>

    <div id="overlay" class="overlay" onclick="closePopup()"></div>
    <div id="popup" class="popup">
        <div class="popup-header">Location Confirmation</div>
        <div class="popup-body">
            <p id="location">Latitude: <span id="latitude"></span> Longitude: <span id="longitude"></span></p>
            <div id="map"></div>
        </div>
        <div class="popup-footer">
            <button onclick="closePopup()">Close</button>
        </div>
    </div>
</body>
</html>
