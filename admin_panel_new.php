<?php
session_start();
require_once 'db.php';

// Admin authentication check
if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-панель</title>
    <link rel="stylesheet" href="admin-styles.css">
</head>
<body>
    <header>
        <img src="images/logo.png" alt="Логотип ППЗ">
        <a href="logout.php" class="exit-button">ВЫХОД</a>
    </header>

    <main>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="error"><?= htmlspecialchars($_SESSION['error']) ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="success"><?= htmlspecialchars($_SESSION['success']) ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <div class="admin-menu">
            <a href="userlist.php">Список пользователей</a>
            <a href="edittest.php">Редактирование тестов</a>
            <a href="questionlist.php">Список вопросов</a>
            <a href="reportwindow.php">Отчеты</a>
        </div>
    </main>

    <footer>
        <div class="footer-section">
            <h3>Контакты</h3>
            <a href="https://www.kraski-perm.ru/">www.fkppz.ru</a>
            <a href="mailto:ppz-market@yandex.ru">ppz-market@yandex.ru</a>
            <a href="mailto:diakam@yandex.ru">diakam@yandex.ru</a>
        </div>
        <div class="footer-section">
            <h3>Адрес</h3>
            <p>г. Пермь, ул. Гальперины, 11</p>
            <p>Пн-Чт 08:00-17:00</p>
            <p>Пт 08:00-16:00</p>
        </div>
        <div class="footer-section">
            <h3>Информация</h3>
            <a href="privatepolicy.php">Политика конфиденциальности</a>
            <a href="about.php">О портале</a>
        </div>
    </footer>
</body>
</html>
