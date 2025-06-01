

<?php

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

include 'db.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $techID = isset($_POST['techID']) ? trim($_POST['techID']) : '';
    $login = isset($_POST['login']) ? trim($_POST['login']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

    if ($password !== $confirm_password) {
        $errors[] = 'Пароли не совпадают.';
    }

    if (empty($errors)) {
        // Prepare statement to find user with matching login, techID, roleID=1, statusID=1
        $stmt = $mysqli->prepare("SELECT id FROM Users WHERE login = ? AND techID = ? AND roleID != 2 AND statusID = 1");
        if ($stmt) {
            $stmt->bind_param('si', $login, $techID);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows === 1) {
                $stmt->bind_result($user_id);
                $stmt->fetch();

                // Hash the new password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Update password
                $update_stmt = $mysqli->prepare("UPDATE Users SET password = ? WHERE id = ?");
                if ($update_stmt) {
                    $update_stmt->bind_param('si', $hashed_password, $user_id);
                    if ($update_stmt->execute()) {
                        $success = 'Пароль успешно изменен.';
                    } else {
                        $errors[] = 'Ошибка при обновлении пароля.';
                    }
                    $update_stmt->close();
                } else {
                    $errors[] = 'Ошибка подготовки запроса обновления.';
                }
            } else {
                $errors[] = 'Пользователь не найден или условия не выполнены.';
            }
            $stmt->close();
        } else {
            $errors[] = 'Ошибка подготовки запроса.';
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
    <title>Восстановление пароля</title>
</head>
<body class="body-auth">
    <div class="container">
        <img src="images/logo.png" alt="Логотип ППЗ" class="logo-main">
        <h1 class="registration-head">Восстановление</h1>
        
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
            <input type="number" name="techID" placeholder="Теб. номер (5 цифр)" min="10000" max="99999" class="input" required>
            <input type="text" name="login" placeholder="Логин" required class="input">
            <input type="password" name="password" placeholder="Новый пароль" required class="input">
            <input type="password" name="confirm_password" placeholder="Подтвердите пароль" required class="input">
            <button type="submit" name="submit" class="btn first">Изменить</button>
            <a href="index.php" class="btn secondary registration-text">Назад к входу</a>
        </form>
    </div>
</body>
</html>
