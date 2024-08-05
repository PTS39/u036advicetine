<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="login-form">
        <h1>Login</h1>
        <div class="container">
            <div class="main">
                <div class="content">
                    <h2>Log In</h2>
                    <form id="loginForm" method="POST" action="login_process.php">
                        <input type="email" name="email" placeholder="email" required autofocus>
                        <input type="password" name="password" placeholder="Password" required>
                        <button class="btn" type="submit">
                            Login
                        </button>
                    </form>
                    <p class="account">Don't Have An Account? <a href="signup.php">Register</a></p>
                </div>
                <div class="form-img">
                    <img src="bg.png" alt="">
                </div>
            </div>
        </div>
    </div>
</body>
</html>
