// uploadProfileImage.js

// Initialize Firebase
const firebaseConfig = {
    apiKey: "AIzaSyCgsj5QEE4e1MDBWhM3cvPC5q3BH1grgEE",
    authDomain: "timeinout-b1c36.firebaseapp.com",
    databaseURL: "https://timeinout-b1c36-default-rtdb.firebaseio.com",
    projectId: "timeinout-b1c36",
    storageBucket: "timeinout-b1c36.appspot.com",
    messagingSenderId: "264418895873",
    appId: "1:264418895873:web:9c74bcdfdcf99b68a43800",
    measurementId: "G-TXFNTBKWPC"
};

firebase.initializeApp(firebaseConfig);
const storage = firebase.storage();
let file;

document.getElementById('profileImage').addEventListener('change', function(event) {
    file = event.target.files[0];

    // Show image preview
    const reader = new FileReader();
    reader.onload = function(e) {
        document.getElementById('profilePic').style.backgroundImage = `url(${e.target.result})`;
    }
    reader.readAsDataURL(file);
});

document.getElementById('registerForm').addEventListener('submit', function(event) {
    event.preventDefault();
    
    if (!file) {
        // If no file selected, proceed with form submission
        this.submit();
        return;
    }

    const storageRef = storage.ref();
    const uploadTask = storageRef.child('img/' + file.name).put(file);

    uploadTask.on('state_changed', 
        function(snapshot) {
            const progress = (snapshot.bytesTransferred / snapshot.totalBytes) * 100;
            console.log('Upload is ' + progress + '% done');
        }, 
        function(error) {
            console.error('Upload failed:', error);
        }, 
        function() {
            uploadTask.snapshot.ref.getDownloadURL().then(function(downloadURL) {
                console.log('File available at', downloadURL);
                let hiddenInput = document.getElementById('profileImageUrl');
                if (!hiddenInput) {
                    hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = 'profileImageUrl';
                    hiddenInput.id = 'profileImageUrl';
                    document.getElementById('registerForm').appendChild(hiddenInput);
                }
                hiddenInput.value = downloadURL;

                // Submit the form after image is uploaded
                document.getElementById('registerForm').submit();
            });
        }
    );
});
