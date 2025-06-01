<?php
session_start();

require_once 'test_settings.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check CSRF token if implemented (optional)
    $lock_test_after_completion = isset($_POST['lock_test_after_completion']) ? true : false;
    $auto_unlock_interval = $_POST['auto_unlock_interval'] ?? 'monthly';
    $test_question_count = isset($_POST['test_question_count']) ? (int)$_POST['test_question_count'] : 10;
    $prohibit_test_if_less_questions = isset($_POST['prohibit_test_if_less_questions']) ? true : false;

    // Update the test_settings.php file with the new values
    $configContent = "<?php\n// Configuration file for test options\n\n";
    $configContent .= "// Option to lock test after completion\n";
    $configContent .= "\$lock_test_after_completion = " . ($lock_test_after_completion ? 'true' : 'false') . ";\n\n";
    $configContent .= "// Option for automatic unlock interval\n";
    $configContent .= "// Possible values: 'monthly', 'quarterly', 'yearly'\n";
    $configContent .= "\$auto_unlock_interval = '" . addslashes($auto_unlock_interval) . "';\n\n";
    $configContent .= "// Number of questions for test formation\n";
    $configContent .= "\$test_question_count = " . $test_question_count . ";\n\n";
    $configContent .= "// Prohibit test if total questions less than selected count\n";
    $configContent .= "\$prohibit_test_if_less_questions = " . ($prohibit_test_if_less_questions ? 'true' : 'false') . ";\n";

    if (file_put_contents('test_settings.php', $configContent) !== false) {
        $message = 'Настройки успешно сохранены.';
    } else {
        $message = 'Ошибка при сохранении настроек.';
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="stylesheet" href="style.css" />
    <title>Настройки</title>
    <link rel="shortcut icon" href="images/shortcut.ico" class="icon" type="image/x-icon" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet" />
</head>
<body class="testlist-page">
<header class="testlist-header">
    <div class="header-testlist">
        <div class="left-corner">
            </div>
        <img src="images/logo.png" alt="Логотип ППЗ" class="testlist-logo" />
        <div class="header-buttons">
                <a href="reportwindow.php" class="testlist-exit-btn">НАЗАД</a>
                <a href="logout.php" class="testlist-exit-btn">ВЫХОД</a>
            </div>
    </div>
</header>

<main class="settings-content">
    <h1 class="testlist-title">Настройки тестов</h1>
    <?php if ($message): ?>
        <p class="settings-message"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>
    <div class="settings-container">
        <form method="post" action="settingswindow.php">
            <label class="form-label">
                <input type="checkbox" name="lock_test_after_completion" value="1" <?= $lock_test_after_completion ? 'checked' : '' ?> />
                Блокировать тест после прохождения
            </label>
            <br /><br />
            <label for="auto_unlock_interval" class="form-label">Интервал автоматического снятия блокировок:</label>
            <select name="auto_unlock_interval" id="auto_unlock_interval" class="form-select">
                <option value="monthly" <?= $auto_unlock_interval === 'monthly' ? 'selected' : '' ?>>В начале каждого месяца</option>
                <option value="quarterly" <?= $auto_unlock_interval === 'quarterly' ? 'selected' : '' ?>>В начале каждых 3х месяцев</option>
                <option value="yearly" <?= $auto_unlock_interval === 'yearly' ? 'selected' : '' ?>>В начале каждого года</option>
            </select>
            <br /><br />
            <label for="test_question_count" class="form-label">Количество вопросов для формирования теста:</label>
            <input type="number" id="test_question_count" name="test_question_count" min="1" value="<?= htmlspecialchars($test_question_count) ?>" class="form-input" />
            <br /><br />
            <label class="form-label">
                <input type="checkbox" name="prohibit_test_if_less_questions" value="1" <?= $prohibit_test_if_less_questions ? 'checked' : '' ?> />
                Запретить прохождение теста, если сумма вопросов меньше выбранного количества
            </label>
            <br /><br />
            <button type="submit" class="btn first">Сохранить</button>
        </form>
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
