<?php
ini_set('display_errors', 0);
error_reporting(0);
session_start();
require_once 'db.php';

// Проверка авторизации администратора
if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF защита
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo json_encode(['success' => false, 'error' => 'Security error']);
        exit;
    }
    
    $action = $_POST['action'] ?? '';
    $mysqli->autocommit(FALSE);
    
    try {
        switch ($action) {
            case 'update_correct_answer':
                $answer_id = (int)$_POST['answer_id'];
                $correct = (int)$_POST['correct'];
                
                if ($correct == 1) {
                    $question_id = $mysqli->query("SELECT questionID FROM Answers WHERE id = $answer_id")->fetch_assoc()['questionID'];
                    $mysqli->query("UPDATE Answers SET correctivity = 0 WHERE questionID = $question_id");
                }
                
                if (!$mysqli->query("UPDATE Answers SET correctivity = $correct WHERE id = $answer_id")) {
                    throw new Exception($mysqli->error);
                }
                
                echo json_encode(['success' => true]);
                break;

            case 'update_answer_text':
                $answer_id = (int)$_POST['answer_id'];
                $text = trim($mysqli->real_escape_string($_POST['text']));
                
                if (empty($text)) {
                    throw new Exception('Текст ответа не может быть пустым');
                }
                
                if (!$mysqli->query("UPDATE Answers SET text = '$text' WHERE id = $answer_id")) {
                    throw new Exception($mysqli->error);
                }
                
                echo json_encode(['success' => true]);
                break;
                
            case 'toggle_question_status':
                $question_id = (int)$_POST['question_id'];
                $new_status = (int)$_POST['new_status'];
                
                // Verify status is valid (1 or 2)
                if (!in_array($new_status, [1, 2])) {
                    throw new Exception('Invalid status value');
                }
                
                // Verify question exists
                $question_exists = $mysqli->query("SELECT id FROM Questions WHERE id = $question_id")->fetch_assoc();
                if (!$question_exists) {
                    throw new Exception('Question not found');
                }
                
                // Update status
                if (!$mysqli->query("UPDATE Questions SET status = $new_status WHERE id = $question_id")) {
                    throw new Exception($mysqli->error);
                }
                
                $mysqli->commit();
                echo json_encode(['success' => true]);
                break;

            case 'add_question':
                $test_id = (int)$_POST['test_id'];
                $question_text = trim($mysqli->real_escape_string($_POST['question_text']));
                $correct_answer = (int)$_POST['correct_answer'];
                
                if (empty($question_text)) {
                    throw new Exception('Текст вопроса не может быть пустым');
                }
                
                // Проверяем существование теста
                $test_exists = $mysqli->query("SELECT id FROM Tests WHERE id = $test_id")->fetch_assoc();
                if (!$test_exists) {
                    throw new Exception('Указанный тест не существует');
                }
                
                // Добавляем вопрос с categoryID = 1 по умолчанию
                if (!$mysqli->query("INSERT INTO Questions (testID, text, status) VALUES ($test_id, '$question_text', 1)")) {
                    throw new Exception($mysqli->error);
                }
                
                $question_id = $mysqli->insert_id;
                $answers_added = 0;
                
                // Добавляем ответы
                for ($i = 1; $i <= 3; $i++) {
                    if (!empty($_POST["answer_$i"])) {
                        $answer_text = trim($mysqli->real_escape_string($_POST["answer_$i"]));
                        $is_correct = ($i == $correct_answer) ? 1 : 0;
                        
                        if (!$mysqli->query("INSERT INTO Answers (questionID, text, correctivity) VALUES ($question_id, '$answer_text', $is_correct)")) {
                            throw new Exception($mysqli->error);
                        }
                        $answers_added++;
                    }
                }
                
                if ($answers_added == 0) {
                    throw new Exception('Не добавлено ни одного ответа');
                }
                
                $mysqli->commit();
                header('Location: questionlist.php?test_id=' . $test_id);
                exit;
                
                
            case 'update_test_info':
                $test_id = (int)$_POST['test_id'];
                $test_name = trim($mysqli->real_escape_string($_POST['test_name'] ?? ''));
                $test_description = trim($mysqli->real_escape_string($_POST['test_description'] ?? ''));

                if (empty($test_name)) {
                    throw new Exception('Название теста не может быть пустым');
                }

                // Verify test exists
                $test_exists = $mysqli->query("SELECT id FROM Tests WHERE id = $test_id")->fetch_assoc();
                if (!$test_exists) {
                    throw new Exception('Тест не найден');
                }

                // Update test info
                if (!$mysqli->query("UPDATE Tests SET name = '$test_name', description = '$test_description' WHERE id = $test_id")) {
                    throw new Exception($mysqli->error);
                }

                $mysqli->commit();
                header('Location: questionlist.php?test_id=' . $test_id);
                exit;

            default:
                throw new Exception('Неизвестное действие');
        }
    } catch (Exception $e) {
        $mysqli->rollback();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

header('Location: questionlist.php');
exit;
?>
