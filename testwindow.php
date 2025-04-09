<?php
session_start();
require_once 'db.php';

// Получаем ID теста из параметра URL
$test_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Инициализация переменных
$current_question = isset($_GET['q']) ? intval($_GET['q']) : 1;
$questions = [];
$answers = [];
$total_questions = 0;
$correct_answers = $_SESSION['correct_answers'] ?? 0;

// Process answer submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['answer'])) {
    $answer_id = intval($_POST['answer']);
    
    // Check if answer is correct
    $stmt = $mysqli->prepare("SELECT correctivity FROM Answers WHERE id = ?");
    $stmt->bind_param("i", $answer_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if ($result && $result['correctivity'] == 1) {
        $correct_answers++;
        $_SESSION['correct_answers'] = $correct_answers;
    }
}

if ($test_id > 0) {
    // Сохраняем ID теста в сессии
    $_SESSION['current_test_id'] = $test_id;
    
    // Получаем данные теста
    $stmt = $mysqli->prepare("SELECT name FROM Tests WHERE id = ?");
    $stmt->bind_param("i", $test_id);
    $stmt->execute();
    $test = $stmt->get_result()->fetch_assoc();

    // Получаем вопросы для теста (случайные 10 или меньше)
    $count_stmt = $mysqli->prepare("SELECT COUNT(*) as count FROM Questions WHERE testID = ?");
    $count_stmt->bind_param("i", $test_id);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result()->fetch_assoc();
    $total_available = $count_result['count'];
    
    if ($total_available > 10) {
        $random_ids_stmt = $mysqli->prepare("SELECT id FROM Questions WHERE testID = ? ORDER BY RAND() LIMIT 10");
        $random_ids_stmt->bind_param("i", $test_id);
        $random_ids_stmt->execute();
        $random_ids = $random_ids_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        $ids = array_column($random_ids, 'id');
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $questions_stmt = $mysqli->prepare("SELECT id, text, testID FROM Questions WHERE id IN ($placeholders) ORDER BY RAND()");
        $questions_stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
    } else {
        $questions_stmt = $mysqli->prepare("SELECT id, text, testID FROM Questions WHERE testID = ? ORDER BY RAND()");
        $questions_stmt->bind_param("i", $test_id);
    }
    
    $questions_stmt->execute();
    $questions = $questions_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $total_questions = count($questions);

    if ($current_question <= $total_questions && $current_question > 0) {
        $answers_stmt = $mysqli->prepare("SELECT id, text, correctivity FROM Answers WHERE questionID = ? ORDER BY RAND() LIMIT 3");
        $answers_stmt->bind_param("i", $questions[$current_question-1]['id']);
        $answers_stmt->execute();
        $answers = $answers_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
?>
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
<body class="test-window">
<header class="testlist-header">
        <div class="header-testlist">
            <img src="images/logo.png" alt="Логотип ППЗ" class="testlist-logo">
            <a href="logout.php" class="testlist-exit-btn">ВЫХОД</a>
        </div>
    </header>
    
    <main class="test-content">
        <div class="test-progress">
            <div class="breadcrumbs"><a href="testlist.php" class="mainlink">главная</a> &rarr; <?php echo htmlspecialchars($test['name'] ?? 'Тест'); ?></div>
            <?php if (!isset($_GET['q']) || $_GET['q'] !== 'complete'): ?>
                <div class="counter"><?php echo $current_question; ?> / <?php echo $total_questions; ?></div>
            <?php endif; ?>
        </div>
        
        <?php if ($total_questions > 0 && $current_question <= $total_questions && (!isset($_GET['q']) || $_GET['q'] !== 'complete')): ?>
        <div class="test-question">
            <h1><?php echo $current_question; ?>. <?php echo htmlspecialchars($questions[$current_question-1]['text']); ?></h1>
            <form method="post" action="?id=<?php echo $test_id; ?>&q=<?php echo ($current_question < $total_questions) ? $current_question + 1 : 'complete'; ?>" onsubmit="return validateForm(this)">
                <div class="answer-options">
                    <?php foreach ($answers as $answer): ?>
                    <label class="answer-option">
                        <input type="radio" name="answer" value="<?php echo $answer['id']; ?>">
                        <?php echo htmlspecialchars($answer['text']); ?>
                    </label>
                    <?php endforeach; ?>
                </div>
                <button type="submit" class="next-button">ДАЛЕЕ</button>
                <p id="error-message" style="color: red; display: none; margin-top: 1rem;">Пожалуйста, выберите ответ</p>
            </form>
        </div>
        <?php else: ?>
        <div class="test-completed">
            <h2>Тест завершен</h2>
            <?php
            if ($test_id > 0 && isset($_SESSION['user_id'])) {
                $score = "$correct_answers/$total_questions";
                $date = date('Y-m-d H:i:s');
                
                $stmt = $mysqli->prepare("INSERT INTO TestResults (userID, testID, result, date) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("iiss", $_SESSION['user_id'], $test_id, $score, $date);
                $stmt->execute();
                
                echo "<p>Ваш результат: $score</p>";
                
                unset($_SESSION['correct_answers']);
            }
            ?>
            <a href="testlist.php" class="mainlink">Вернуться к списку тестов</a>
        </div>
        <?php endif; ?>
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

    <script>
        function validateForm(form) {
            const selected = form.querySelector('input[name="answer"]:checked');
            if (!selected && <?php echo count($answers); ?> > 0) {
                document.getElementById('error-message').style.display = 'block';
                return false;
            }
            return true;
        }
    </script>
</body>
</html>
