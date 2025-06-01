<?php
session_start();
require_once 'db.php';
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}



if (isset($_GET['abort_test']) && $_GET['abort_test'] == 1) {
    // Clear session data related to the test answers and progress
    unset($_SESSION['correct_answers']);
    unset($_SESSION['answered_question_ids']);
    unset($_SESSION['current_test_question_ids']);
    unset($_SESSION['current_test_id']);
    // Redirect to test list after clearing session
    header('Location: testlist.php');
    exit;
}

// Получаем ID теста из параметра URL
$test_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Инициализация переменных
$current_question = isset($_GET['q']) ? intval($_GET['q']) : 1;
$questions = [];
$answers = [];
$total_questions = 0;
$correct_answers = $_SESSION['correct_answers'] ?? 0;

require_once 'test_settings.php';

// Fetch or load questions before processing POST
if ($test_id > 0) {
    // Save test ID in session
    $_SESSION['current_test_id'] = $test_id;

    // Get test data
    $stmt = $mysqli->prepare("SELECT name FROM Tests WHERE id = ?");
    $stmt->bind_param("i", $test_id);
    $stmt->execute();
    $test = $stmt->get_result()->fetch_assoc();

    // Load or fetch question IDs for the test
    if (!isset($_SESSION['current_test_question_ids']) || empty($_SESSION['current_test_question_ids'])) {
        // Fetch question IDs randomly limited by $test_question_count
        $count_stmt = $mysqli->prepare("SELECT COUNT(*) as count FROM Questions WHERE testID = ? AND status = 1");
        $count_stmt->bind_param("i", $test_id);
        $count_stmt->execute();
        $count_result = $count_stmt->get_result()->fetch_assoc();
        $total_available = $count_result['count'];

        if ($total_available > $test_question_count) {
            $random_ids_stmt = $mysqli->prepare("SELECT id FROM Questions WHERE testID = ? AND status = 1 ORDER BY RAND() LIMIT ?");
            $random_ids_stmt->bind_param("ii", $test_id, $test_question_count);
            $random_ids_stmt->execute();
            $random_ids = $random_ids_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $_SESSION['current_test_question_ids'] = array_column($random_ids, 'id');
        } else {
            $all_ids_stmt = $mysqli->prepare("SELECT id FROM Questions WHERE testID = ? AND status = 1 ORDER BY id");
            $all_ids_stmt->bind_param("i", $test_id);
            $all_ids_stmt->execute();
            $all_ids = $all_ids_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $_SESSION['current_test_question_ids'] = array_column($all_ids, 'id');
        }
    }

    // Load questions based on stored IDs in session
    $ids = $_SESSION['current_test_question_ids'];
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $questions_stmt = $mysqli->prepare("SELECT id, text, testID FROM Questions WHERE id IN ($placeholders)");
    $questions_stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
    $questions_stmt->execute();
    $questions_unsorted = $questions_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Sort questions to match the order of IDs in session
    $questions = [];
    $questions_map = [];
    foreach ($questions_unsorted as $q) {
        $questions_map[$q['id']] = $q;
    }
    foreach ($ids as $id) {
        if (isset($questions_map[$id])) {
            $questions[] = $questions_map[$id];
        }
    }
    $total_questions = count($questions);
} else {
    $questions = [];
    $total_questions = 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['answer'])) {
    $answer_id = intval($_POST['answer']);
    
    // Check if answer is correct
    $stmt = $mysqli->prepare("SELECT correctivity, questionID FROM Answers WHERE id = ?");
    $stmt->bind_param("i", $answer_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if ($result) {
        if ($result['correctivity'] == 1) {
            $correct_answers++;
            $_SESSION['correct_answers'] = $correct_answers;
        }
        // Track answered question IDs in session
        if (!isset($_SESSION['answered_question_ids'])) {
            $_SESSION['answered_question_ids'] = [];
        }
        $answered_question_id = $result['questionID'];
        if (!in_array($answered_question_id, $_SESSION['answered_question_ids'])) {
            $_SESSION['answered_question_ids'][] = $answered_question_id;
        }
        // Store selected answer for the question
        if (!isset($_SESSION['selected_answers'])) {
            $_SESSION['selected_answers'] = [];
        }
        $_SESSION['selected_answers'][$answered_question_id] = $answer_id;
    }
}

    if ($current_question <= $total_questions && $current_question > 0) {
        $answers_stmt = $mysqli->prepare("SELECT id, text, correctivity FROM Answers WHERE questionID = ? ORDER BY id LIMIT 3");
        $answers_stmt->bind_param("i", $questions[$current_question-1]['id']);
        $answers_stmt->execute();
        $answers = $answers_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    } else {
        $answers = [];
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
            <div class="left-corner">
            </div>
            <img src="images/logo.png" alt="Логотип ППЗ" class="testlist-logo">
            <div class="header-buttons">
                <a href="testresults.php" class="testlist-exit-btn">РЕЗУЛЬТАТЫ</a>
                <a href="logout.php" class="testlist-exit-btn">ВЫХОД</a>
            </div>
        </div>
    </header>
    
    <main class="test-content">
        <div class="test-progress">
            <div class="breadcrumbs"><a href="testlist.php"  id="homeLink" class="mainlink">Главная</a> &rarr; <?php echo htmlspecialchars($test['name'] ?? 'Тест'); ?></div>
            <?php if (!isset($_GET['q']) || $_GET['q'] !== 'complete'): ?>
                <div class="counter"><?php echo $current_question; ?> / <?php echo $total_questions; ?></div>
            <?php endif; ?>
        </div>
        
        <?php if ($total_questions > 0 && $current_question <= $total_questions && (!isset($_GET['q']) || $_GET['q'] !== 'complete')): ?>
        <div class="test-question">
            <?php if ($total_questions > 1): ?>
            <div class="test-question-nav">
                <?php
                $answered_question_ids = $_SESSION['answered_question_ids'] ?? [];
                for ($i = 1; $i <= $total_questions; $i++):
                    $question_id = $questions[$i - 1]['id'];
                    $is_answered = in_array($question_id, $answered_question_ids);
                    $btn_class = $is_answered ? 'answered' : '';
                    if ($i === $current_question) {
                        $btn_class .= ' current';
                        $disabled = 'disabled';
                    } else {
                        $disabled = '';
                    }
                    $btn_link = "?id=$test_id&q=$i";
                ?>
                <button class="<?php echo trim($btn_class); ?>" <?php echo $disabled; ?> onclick="window.location.href='<?php echo $btn_link; ?>'"><?php echo $i; ?></button>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
            <h1><?php echo $current_question; ?>. <?php echo htmlspecialchars($questions[$current_question-1]['text']); ?></h1>
            <form id="answerForm" method="post" action="?id=<?php echo $test_id; ?>&q=<?php echo ($current_question < $total_questions) ? $current_question + 1 : 'complete'; ?>" onsubmit="return validateForm(this) && confirmSubmission(event)" class="<?php echo ($current_question === 1) ? 'only-next' : ''; ?>">
                <div class="answer-options">
                    <?php foreach ($answers as $answer): ?>
                    <label class="answer-option">
                        <input type="radio" name="answer" value="<?php echo $answer['id']; ?>" <?php 
                            $selected_answers = $_SESSION['selected_answers'] ?? [];
                            $selected_answer_id = $selected_answers[$questions[$current_question-1]['id']] ?? null;
                            echo ($selected_answer_id === $answer['id']) ? 'checked' : '';
                        ?>>
                        <?php echo htmlspecialchars($answer['text']); ?>
                    </label>
                    <?php endforeach; ?>
                </div>
                <div class="move-block">
                    <?php if ($current_question > 1): ?>
                <button type="button" class="back-button" onclick="window.location.href='?id=<?php echo $test_id; ?>&q=<?php echo $current_question - 1; ?>'">НАЗАД</button>
                <?php endif; ?>
                <button type="submit" class="next-button"><?php echo ($current_question === $total_questions) ? 'ЗАВЕРШИТЬ' : 'ДАЛЕЕ'; ?></button>
                
                </div>
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
                
                // Clear session data related to the test answers
                unset($_SESSION['correct_answers']);
                unset($_SESSION['answered_question_ids']);
                unset($_SESSION['current_test_question_ids']);
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
        document.getElementById('homeLink').addEventListener('click', function(event) {
            event.preventDefault();
            if (confirm('Прервать тестирование?')) {
                // Clear session data related to the test answers via AJAX or redirect with a flag
                // Since PHP session clearing requires server-side, redirect with a flag to clear session
                window.location.href = 'testlist.php?abort_test=1';
            }
        });

        function validateForm(form) {
            const selected = form.querySelector('input[name="answer"]:checked');
            if (!selected && <?php echo count($answers); ?> > 0) {
                document.getElementById('error-message').style.display = 'block';
                return false;
            }
            return true;
        }

        function confirmSubmission(event) {
            const currentQuestion = <?php echo $current_question; ?>;
            const totalQuestions = <?php echo $total_questions; ?>;
            const answeredCount = <?php echo isset($_SESSION['answered_question_ids']) ? count($_SESSION['answered_question_ids']) : 0; ?>;

            // Check if an answer is selected for the current question
            const selected = document.querySelector('input[name="answer"]:checked');
            let effectiveAnsweredCount = answeredCount;
            if (currentQuestion === totalQuestions && selected) {
                // If last question and answer selected, consider it answered
                effectiveAnsweredCount = answeredCount + 1;
            }

            if (currentQuestion === totalQuestions) {
                if (effectiveAnsweredCount >= totalQuestions) {
                    return confirm('Вы ответили на все вопросы. Подтвердите завершение теста.');
                } else {
                    return confirm('Вы не ответили на все вопросы. Подтвердите завершение теста.');
                }
            }
            return true;
        }
    </script>
    <style>
    button.current {
        background-color: #ccc;
        color: #666;
        cursor: default;
    }
    button:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
    /* Make radio buttons red */
    input[type="radio"] {
        accent-color: red;
    }
    </style>
</body>
</html>
