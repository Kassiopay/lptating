<?php
session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="images/shortcut.ico" class="icon" type="image/x-icon">
    <link rel="stylesheet" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">

    <title>Тестирование ОТ</title>
</head>
<body class="body-auth">
<div class="container">
        <img src="images\logo.png" alt="Логотип ППЗ" class="logo-main">
        <h1>Тестирование ОТ</h1>
        <h2>среди сотрудников ППЗ</h2>
        <form action="login.php" method="POST" id="mainform">
            <input type="text" placeholder="Логин" required name="loginform" class="input">
            <input type="password" placeholder="Пароль" required name="passwordform" class="input">
            <button type="sumbit" class="btn first" id="enterbtn">Вход</button>
            <!--<a href="registration.php" class="btn secondary registration-text" id="registrationbtn">Регистрация</a>-->
            <a href="reset_password.php" class="reset-link">Забыли пароль?</a>
        </form>
        <?php
        if (isset($_SESSION['error'])) {
            echo '<p class="error">' . $_SESSION['error'] . '</p>';
            unset($_SESSION['error']);
        }
        ?>
        
    </div>
    
    <script src="script.js"></script> 
</body>
</html>