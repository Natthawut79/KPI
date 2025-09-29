<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KPI Management System - Login</title>
    <link rel="stylesheet" href="css/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="login-container">
        <div class="welcome-section">
            <img src="img/logo.png" alt="UTCC Logo" class="logo">
            <p class="welcome-text">Welcome to</p>
            <h1 class="system-name">KPI Management System</h1>
        </div>
        <div class="login-form-container">
            <form id="loginForm" class="login-form">
                <h2>LOGIN</h2>
                <div class="input-group">
                    <i class="fas fa-user"></i>
                    <input type="text" placeholder="Username" required>
                </div>
                <div class="input-group">
                    <i class="fas fa-keyboard"></i>
                    <input type="password" placeholder="Password" required>
                </div>
                <button type="submit" class="login-btn">LOGIN</button>
            </form>
        </div>
    </div>

    <div class="footer-shape">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320">
            <path fill="#0b3d1c" fill-opacity="1" d="M0,224L48,208C96,192,192,160,288,165.3C384,171,480,213,576,240C672,267,768,277,864,256C960,235,1056,181,1152,160C1248,139,1344,149,1392,154.7L1440,160L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path>
            <path fill="#ffffff" fill-opacity="1" d="M0,256L48,240C96,224,192,192,288,197.3C384,203,480,245,576,256C672,267,768,245,864,224C960,203,1056,181,1152,186.7C1248,192,1344,224,1392,240L1440,256L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path>
        </svg>
    </div>

    <script src="js/login.js"></script>

</body>
</html>