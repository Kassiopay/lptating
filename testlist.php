<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Тестовая страница</title>
    <link rel="shortcut icon" href="images/shortcut.ico" class="icon" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
</head>
<body class="testlist-page">
<header class="testlist-header">
        <div class="header-testlist">
            <img src="images/logo.png" alt="Логотип ППЗ" class="testlist-logo">
            <a href="logout.php" class="testlist-exit-btn">ВЫХОД</a>
        </div>
    </header>
    
    <main class="testlist-content">
        <h1 class="testlist-title">Охрана труда</h1>
        <div class="testlist-container">
            <?php
            require_once 'db.php';
            
            $query = "SELECT id, name FROM Tests WHERE status=1 ORDER BY id";
            $result = $mysqli->query($query);
            
            if ($result->num_rows > 0) {
                echo '<ul class="testlist-items">';
                while ($row = $result->fetch_assoc()) {
                    echo '<li class="testlist-item">
                            <a href="testwindow.php?id='.$row['id'].'" class="testlist-link">
                                <span class="testlist-link-icon">→</span>
                                <span class="testlist-link-text">'.$row['name'].'</span>
                            </a>
                          </li>';
                }
                echo '</ul>';
            } else {
                echo '<p class="testlist-empty">Нет доступных тестов</p>';
            }
            ?>
        </div>
    </main>

    <footer class="testlist-footer">
        <div class="footer-sections">
            <div class="footer-section">
                <h3 class="footer-title">Контакты</h3>
                <a href="https://www.kraski-perm.ru/" class="footer-link">www.fkppz.ru</a>
                <a href="mailto:ppz-market@yandex.ru" class="footer-link">ppz-market@yandex.ru</a>
                <a href="mailto:diakam@yandex.ru" class="footer-link">diakam@yandex.ru</a>
            </div>
            <div class="footer-section">
                <h3 class="footer-title">Адрес</h3>
                <p class="footer-text">г. Пермь, ул. Гальперины, 11</p>
                <p class="footer-text">Пн-Чт 08:00-17:00</p>
                <p class="footer-text">Пт 08:00-16:00</p>
            </div>
            <div class="footer-section">
                <h3 class="footer-title">Информация</h3>
                <a href="privatepolicy.php" class="footer-link">Политика конфиденциальности</a>
                <a href="about.php" class="footer-link">О портале</a>
            </div>
        </div>
    </footer>


    <script src="script.js"></script>
</body>
</html>
