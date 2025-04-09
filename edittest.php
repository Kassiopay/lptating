<?php
session_start();
require_once 'db.php';

// Проверка авторизации администратора
if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header('Location: index.php');
    exit;
}

// Логика админ-панели
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-панель</title>
    <link rel="shortcut icon" href="images/shortcut.ico" class="icon" type="image/x-icon">
    <link rel="stylesheet" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet"></body>

</head>
<body>
<header class="testlist-header">
        <div class="header-testlist">
            <img src="images/logo.png" alt="Логотип ППЗ" class="testlist-logo">
            <div class="header-buttons">
                <a href="adminpanel.php" class="testlist-exit-btn">НАЗАД</a>
                <a href="logout.php" class="testlist-exit-btn">ВЫХОД</a>
            </div>
        </div>
    </header>

    <main>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="error-message">
                <?= htmlspecialchars($_SESSION['error']) ?>
                <?php unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="success-message">
                <?= htmlspecialchars($_SESSION['success']) ?>
                <?php unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <div class="tests-block">
            <h2>Список тестов</h2>
            <?php
            $query = "SELECT id, name, description, status FROM Tests";
            $result = $mysqli->query($query);
            
            if ($result && $result->num_rows > 0): ?>
                <table class="tests-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Название</th>
                            <th>Описание</th>
                            <th>Статус</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['id']) ?></td>
                                <td><?= htmlspecialchars($row['name']) ?></td>
                                <td><?= htmlspecialchars($row['description']) ?></td>
                                <td><?= $row['status'] == 1 ? 'Активен' : 'Скрыт' ?></td>
                                <td>
                                    <form method="POST" action="edittest_actions.php" onsubmit="return confirm('Вы уверены, что хотите <?= $row['status'] == 1 ? 'скрыть' : 'вернуть' ?> этот тест?');">
                                        <input type="hidden" name="action" value="toggle_test_status">
                                        <input type="hidden" name="test_id" value="<?= $row['id'] ?>">
                                        <button type="submit"  class="<?= $row['status'] == 1 ? 'delete-button' : 'add-button' ?>">
                                            <?= $row['status'] == 1 ? 'Скрыть' : 'Вернуть' ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        <tr>
                            <td>Новый</td>
                            <td>
                                <form method="POST" action="admin_actions.php">
                                    <input type="hidden" name="action" value="add_test">
                                    <input type="text" name="test_name" placeholder="Название теста" required class="test-input">
                            </td>
                            <td>
                                <input type="text" name="test_description" placeholder="Описание теста" class="test-input">
                            </td>
                            <td></td>
                            <td>
                                <button type="submit" class="add-button">Добавить</button>
                                </form>
                            </td>
                        </tr>
                    </tbody>
                </table>
            <?php else: ?>
                <p>Нет доступных тестов</p>
            <?php endif; ?>
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
</body><script src="script.js"></script>
</html>
