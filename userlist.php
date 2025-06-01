<?php
session_start();
require_once 'db.php';

// Проверка авторизации администратора
if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header('Location: index.php');
    exit;
}

// Получение списка ролей из таблицы Roles
$roles = [];
$roles_result = $mysqli->query("SELECT id, name FROM Roles");
if ($roles_result) {
    while ($role = $roles_result->fetch_assoc()) {
        $roles[$role['id']] = $role['name'];
    }
}

// Получение выбранного фильтра роли из GET параметров
$selected_role = isset($_GET['roleID']) ? intval($_GET['roleID']) : 0;

// Формирование запроса пользователей с учетом фильтра роли
$query = "SELECT id, login, createdata, roleID, statusID, techID FROM Users";
if ($selected_role > 0) {
    $query .= " WHERE roleID = " . $selected_role;
}
$result = $mysqli->query($query);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Список пользователей</title>
    <link rel="shortcut icon" href="images/shortcut.ico" class="icon" type="image/x-icon">
    <link rel="stylesheet" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
</head>
<body>
<header class="testlist-header">
        <div class="header-testlist">
            <div class="left-corner">
            </div>
            <img src="images/logo.png" alt="Логотип ППЗ" class="testlist-logo">
            <div class="header-buttons">
                <a href="reportwindow.php" class="testlist-exit-btn">НАЗАД</a>
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
            <div class="userlist-header" style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1em;">
                <h2 style="margin: 0;">Список пользователей</h2>
                <!-- Форма фильтрации по ролям -->
                <form method="GET" action="userlist.php" class="role-filter-form" style="margin: 0;">
                    <label for="roleID" style="margin-right: 0.5em;">Фильтр по роли:</label>
                    <select name="roleID" id="roleID" class="role-input" onchange="this.form.submit()">
                        <option value="0">Все роли</option>
                        <?php foreach ($roles as $role_id => $role_name): ?>
                            <option value="<?= $role_id ?>" <?= $selected_role === $role_id ? 'selected' : '' ?>>
                                <?= htmlspecialchars($role_name) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>

            <?php if ($result && $result->num_rows > 0): ?>
                <table class="tests-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Логин</th>
                            <th>Дата создания</th>
                            <th>Роль</th>
                            <th>Статус</th>
                            <th>Таб. номер</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $counter = 1; ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= $counter ?></td>
                                <td style="text-align: center;"><?= htmlspecialchars($row['login']) ?></td>
                                <td><?= htmlspecialchars($row['createdata']) ?></td>
                                <td><?= isset($roles[$row['roleID']]) ? htmlspecialchars($roles[$row['roleID']]) : 'Неизвестно' ?></td>
                                <td><?= $row['statusID'] == 1 ? 'Активен' : 'Скрыт' ?></td>
                                <td><?= htmlspecialchars($row['techID'] ?? '') ?></td>
<td>
    <?php if (isset($roles[$row['roleID']]) && $roles[$row['roleID']] === 'Администратор'): ?>
        <!-- Статус администратора нельзя менять -->
        <button type="button" class="disabled-button" disabled>Недоступно</button>
    <?php else: ?>
        <form method="POST" action="userlist_action.php" onsubmit="return confirm('Вы уверены, что хотите <?= $row['statusID'] == 1 ? 'скрыть' : 'вернуть' ?> этого пользователя?');">
            <input type="hidden" name="action" value="toggle_user_status">
            <input type="hidden" name="user_id" value="<?= $row['id'] ?>">
            <button type="submit" class="<?= $row['statusID'] == 1 ? 'delete-button' : 'add-button' ?>">
                <?= $row['statusID'] == 1 ? 'Скрыть' : 'Вернуть' ?>
            </button>
        </form>
    <?php endif; ?>
</td>
                            </tr>
                            <?php $counter++; ?>
                        <?php endwhile; ?>
                        <tr>
                            <td>Новый</td>
                            <td>
                                <form method="POST" action="userlist_action.php">
                                    <input type="hidden" name="action" value="add_user">
                                    <input type="text" name="login" placeholder="Логин" required class="test-input">
                            </td>
                            <td>
                                <input type="password" name="password" placeholder="Пароль" required class="test-input">
                            </td>
                            <td>
                                <select name="roleID" class="test-input">
                                    <?php foreach ($roles as $role_id => $role_name): ?>
                                        <option value="<?= $role_id ?>" <?= $role_id == 2 ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($role_name) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>Активен</td>
                            <td>
                                <input type="text" name="techID" placeholder="Таб. номер" class="test-input">
                            </td>
                            <td>
                                <button type="submit" class="add-button">Добавить</button>
                                </form>
                            </td>
                        </tr>
                    </tbody>
                </table>
            <?php else: ?>
                <p>Нет пользователей</p>
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

</body>
<script src="script.js"></script>
</html>
