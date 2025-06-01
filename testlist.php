<?php
session_start();

if (isset($_GET['abort_test']) && $_GET['abort_test'] == 1) {
    // Clear session data related to the test answers and progress
    unset($_SESSION['correct_answers']);
    unset($_SESSION['answered_question_ids']);
    unset($_SESSION['current_test_question_ids']);
    unset($_SESSION['current_test_id']);
    unset($_SESSION['selected_answers']);
    // Redirect to testlist.php without parameters to avoid repeated clearing
    header('Location: testlist.php');
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once 'db.php';

require_once 'test_settings.php';

$user_id = $_SESSION['user_id'] ?? null;

$completed_tests = [];
$completed_tests_dates = [];

if ($lock_test_after_completion && $user_id !== null) {
    // Calculate unlock date based on auto_unlock_interval
    $current_date = new DateTime();
    $unlock_date = new DateTime();

    switch ($auto_unlock_interval) {
        case 'monthly':
            $unlock_date->modify('first day of this month midnight');
            break;
        case 'quarterly':
            $month = (int)$current_date->format('n');
            $quarter_start_month = $month - (($month - 1) % 3);
            $unlock_date->setDate((int)$current_date->format('Y'), $quarter_start_month, 1);
            $unlock_date->setTime(0, 0, 0);
            break;
        case 'yearly':
            $unlock_date->setDate((int)$current_date->format('Y'), 1, 1);
            $unlock_date->setTime(0, 0, 0);
            break;
        default:
            $unlock_date->modify('first day of this month midnight');
            break;
    }

    // Query TestResults for completed tests and their completion dates by current user
    $stmt = $mysqli->prepare("SELECT testID, date FROM TestResults WHERE userID = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result_completed = $stmt->get_result();
    while ($row_completed = $result_completed->fetch_assoc()) {
        $completed_tests_dates[$row_completed['testID']] = new DateTime($row_completed['date']);
    }
    $stmt->close();

    // Determine which tests are locked based on completion date and unlock date
    foreach ($completed_tests_dates as $test_id => $completion_date) {
        if ($completion_date >= $unlock_date) {
            $completed_tests[] = $test_id;
        }
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
<body class="testlist-page">
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
    
    <main class="testlist-content">
        <h1 class="testlist-title">Охрана труда</h1>
        <div class="testlist-container">
            <?php
            
            $query = "SELECT id, name FROM Tests WHERE status=1 ORDER BY id";
            $result = $mysqli->query($query);
            
            if ($result->num_rows > 0) {
                echo '<ul class="testlist-items">';
                while ($row = $result->fetch_assoc()) {
                    $test_id = $row['id'];
                    $test_name = htmlspecialchars($row['name']);
            // Check question count for the test
            $question_count = 0;
            $stmt_count = $mysqli->prepare("SELECT COUNT(*) as count FROM Questions WHERE testID = ? AND status = 1");
            $stmt_count->bind_param("i", $test_id);
            $stmt_count->execute();
            $result_count = $stmt_count->get_result()->fetch_assoc();
            if ($result_count) {
                $question_count = $result_count['count'];
            }
            $stmt_count->close();

            $lock_due_to_question_count = false;
            if ($prohibit_test_if_less_questions && $question_count < $test_question_count) {
                $lock_due_to_question_count = true;
            }

            if (($lock_test_after_completion && in_array($test_id, $completed_tests)) || $lock_due_to_question_count) {
                // Disable link for completed test or due to insufficient questions
                echo '<li class="testlist-item disabled">
                        <span class="testlist-link-icon">→</span>
                        <span class="testlist-link-text">'.$test_name;
                if ($lock_due_to_question_count) {
                    echo ' (Недоступно)';
                } else {
                    echo ' (завершён)';
                }
                echo '</span>
                      </li>';
            } else {
                // Normal link for available test
                echo '<li class="testlist-item">
                        <a href="testwindow.php?id='.$test_id.'" class="testlist-link">
                            <span class="testlist-link-icon">→</span>
                            <span class="testlist-link-text">'.$test_name.'</span>
                        </a>
                      </li>';
            }
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

