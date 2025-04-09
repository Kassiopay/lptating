<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once 'db.php';

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
    <title>Список вопросов</title>
    <link rel="shortcut icon" href="images/shortcut.ico" class="icon" type="image/x-icon">
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
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
            <div class="error-message"><?= htmlspecialchars($_SESSION['error']) ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="success-message"><?= htmlspecialchars($_SESSION['success']) ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <div class="tests-block">
            <h2>Список вопросов</h2>
            
            <form method="GET" action="" class="test-select-form">
                <select name="test_id" id="test-select" class="test-input" onchange="this.form.submit()">
                    <option value="">Выберите тест</option>
                    <?php
                    $tests_result = $mysqli->query("SELECT id, name FROM Tests");
                    if ($tests_result && $tests_result->num_rows > 0) {
                        while ($test = $tests_result->fetch_assoc()) {
                            $selected = isset($_GET['test_id']) && $_GET['test_id'] == $test['id'] ? 'selected' : '';
                            echo "<option value='{$test['id']}' $selected>{$test['name']}</option>";
                        }
                    }
                    ?>
                </select>
            </form>

            <?php if (isset($_GET['test_id']) && !empty($_GET['test_id'])): ?>
                <?php
                $test_id = $mysqli->real_escape_string($_GET['test_id']);
                $questions_result = $mysqli->query("SELECT id, text, status FROM Questions WHERE testID = $test_id");
                
                if ($questions_result && $questions_result->num_rows > 0): ?>
                    <table class="tests-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Текст вопроса</th>
                                <th>Раскрыть</th>
                                <th>Видимость</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($question = $questions_result->fetch_assoc()): ?>
                                <tr class="question-row">
                                    <td><?= htmlspecialchars($question['id']) ?></td>
                                    <td><?= htmlspecialchars($question['text']) ?></td>
                                    <td>
                                        <button type="button" class="show-answers-button" onclick="toggleAnswers(<?= $question['id'] ?>)">
                                            Показать ответы
                                        </button>
                                    </td>
                                    <td>
                                        <button type="button" class="status-toggle-button <?= $question['status'] == 2 ? 'status-restore' : '' ?>" 
                                                onclick="toggleQuestionStatus(<?= $question['id'] ?>, <?= $question['status'] ?>, this)">
                                            <?= $question['status'] == 2 ? 'Вернуть' : 'Скрыть' ?>
                                        </button>
                                    </td>
                                </tr>
                                <tr class="answers-row" id="answers-<?= $question['id'] ?>" style="display: none;">
                                    <td colspan="4">
                                        <div class="answers-container">
                                            <?php
                                            $answers_result = $mysqli->query("SELECT id, text, correctivity FROM Answers WHERE questionID = {$question['id']}");
                                            if ($answers_result && $answers_result->num_rows > 0): ?>
                                                <table class="answers-table">
                                                    <?php while ($answer = $answers_result->fetch_assoc()): ?>
                                                    <tr>
                                                        <td>
                                                            <input type="radio" name="correct_answer_<?= $question['id'] ?>"
                                                                   value="<?= $answer['id'] ?>"
                                                                   <?= $answer['correctivity'] ? 'checked' : '' ?>
                                                                   disabled>
                                                        </td>
                                                        <td><?= htmlspecialchars($answer['text']) ?></td>
                                                    </tr>
                                                    <?php endwhile; ?>
                                                </table>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>Нет вопросов для выбранного теста</p>
                <?php endif; ?>

                <div class="add-question-form">
                    <h3>Добавить новый вопрос</h3>
                    <form method="POST" action="questionlist_activity.php">
                        <input type="hidden" name="action" value="add_question">
                        <input type="hidden" name="test_id" value="<?= htmlspecialchars($_GET['test_id']) ?>">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                        
                        <div class="form-group">
                            <label for="question_text">Текст вопроса:</label>
                            <textarea id="question_text" name="question_text" required></textarea>
                        </div>
                        
                        <div class="answers-group">
                            <h4>Ответы:</h4>
                            <?php for ($i = 1; $i <= 3; $i++): ?>
                                <div class="answer-input">
                                    <input type="radio" name="correct_answer" value="<?= $i ?>" <?= $i === 1 ? 'checked' : '' ?>>
                                    <input type="text" name="answer_<?= $i ?>" placeholder="Текст ответа <?= $i ?>" required>
                                </div>
                            <?php endfor; ?>
                        </div>
                        
                        <button type="submit" class="submit-button">Добавить вопрос</button>
                    </form>
                </div>
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


    <script>
        function toggleAnswers(questionId) {
            const answersRow = document.getElementById(`answers-${questionId}`);
            const button = document.querySelector(`button[onclick="toggleAnswers(${questionId})"]`);
            
            if (answersRow.style.display === 'none') {
                answersRow.style.display = 'table-row';
                button.textContent = 'Скрыть ответы';
            } else {
                answersRow.style.display = 'none';
                button.textContent = 'Показать ответы';
            }
        }

        function toggleQuestionStatus(questionId, currentStatus, buttonElement) {
            const newStatus = currentStatus == 1 ? 2 : 1;
            
            fetch('questionlist_activity.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=toggle_question_status&question_id=${questionId}&new_status=${newStatus}&csrf_token=<?= $_SESSION['csrf_token'] ?? '' ?>`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    buttonElement.classList.toggle('status-restore');
                    buttonElement.textContent = newStatus == 2 ? 'Вернуть' : 'Скрыть';
                } else {
                    alert('Ошибка при изменении статуса: ' + (data.error || ''));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Ошибка при изменении статуса');
            });
        }
    </script>
</body>
</html>
