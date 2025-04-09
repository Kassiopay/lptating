<?php
require_once 'db.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    $techID = '';

    // Only validate if form was submitted
    if (isset($_POST['submit'])) {
        $techID = trim($_POST['techID'] ?? '');

        // Validation
        if (empty($login)) {
            $errors[] = 'Логин обязателен';
        }
        if (empty($password)) {
            $errors[] = 'Пароль обязателен';
        } elseif (strlen($password) < 6) {
            $errors[] = 'Пароль должен содержать минимум 6 символов';
        }
        if ($password !== $confirm_password) {
            $errors[] = 'Пароли не совпадают';
        }
        if (empty($techID) || !is_numeric($techID)) {
            $errors[] = 'TechID должен быть числом';
        } elseif ($techID <= 0) {
            $errors[] = 'TechID должен быть положительным числом';
        } elseif (strlen((string)$techID) !== 5) {
            $errors[] = 'TechID должен содержать 5 цифр';
        } else {
            // Check if techID already exists
            $stmt = $mysqli->prepare("SELECT id FROM Users WHERE techID = ?");
            $stmt->bind_param("i", $techID);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $errors[] = 'Этот TechID уже используется';
            }
        }

        // Check if login already exists
        if (!empty($login)) {
            $stmt = $mysqli->prepare("SELECT id FROM Users WHERE login = ?");
            $stmt->bind_param("s", $login);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $errors[] = 'Этот логин уже занят';
            }
        }
    }

    if (empty($errors)) {
        try {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $created_at = date('Y-m-d H:i:s');
            $roleID = 1; // Default role
            $statusID = 1; // Active status
            
            $stmt = $mysqli->prepare("INSERT INTO Users (login, password, techID, createdata, roleID, statusID) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssisii", 
                $login,
                $hashed_password,
                $techID,
                $created_at,
                $roleID,
                $statusID
            );
            $stmt->execute();

            $success = 'Регистрация успешна!';
        } catch (Exception $e) {
            $errors[] = 'Ошибка регистрации: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="images/shortcut.ico" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="style.css">
    <title>Регистрация</title>
</head>
<body class="body-auth">
    <div class="container">
        <img src="images/logo.png" alt="Логотип ППЗ" class="logo-main">
        <h1 class="registration-head">Регистрация</h1>
        
        <?php if (!empty($errors)): ?>
            <div class="error">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success">
                <p><?php echo htmlspecialchars($success); ?></p>
            </div>
        <?php endif; ?>

        <form method="post">
            <input type="number" name="techID" placeholder="Тех. номер (5 цифр)" min="10000" max="99999" class="input" required>
            <input type="text" name="login" placeholder="Логин" required class="input">
            <input type="password" name="password" placeholder="Пароль" required class="input">
            <input type="password" name="confirm_password" placeholder="Подтвердите пароль" required class="input">
            <button type="submit" name="submit" class="btn first">Зарегистрироваться</button>
            <a href="index.php" class="btn secondary registration-text">Назад к входу</a>
        </form>
    </div>
</body>
</html>
