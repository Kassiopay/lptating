<?php
session_start();
require_once 'db.php';

// Проверка авторизации администратора
if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header('Location: index.php');
    exit;
}

// Обработка добавления теста
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_test') {
    $name = $mysqli->real_escape_string(trim($_POST['test_name']));
    $description = $mysqli->real_escape_string(trim($_POST['test_description'] ?? ''));
    
    if (empty($name)) {
        $_SESSION['error'] = 'Название теста не может быть пустым';
        header('Location: edittest.php');
        exit;
    }

    $query = "INSERT INTO Tests (name, description,status) VALUES ('$name', '$description',1)";
    if ($mysqli->query($query)) {
        $_SESSION['success'] = 'Тест успешно добавлен';
    } else {
        $_SESSION['error'] = 'Ошибка при добавлении теста: ' . $mysqli->error;
    }
    
    header('Location: edittest.php');
    exit;
}

    // Обработка скрытия/восстановления теста
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_test_status') {
        if (!isset($_POST['test_id']) || !is_numeric($_POST['test_id'])) {
            $_SESSION['error'] = 'Неверный ID теста';
            header('Location: edittest.php');
            exit;
        }

        $test_id = (int)$_POST['test_id'];
        
        // Получаем текущий статус теста
        $status_query = "SELECT status FROM Tests WHERE id = $test_id";
        $status_result = $mysqli->query($status_query);
        
        if ($status_result && $status_result->num_rows > 0) {
            $row = $status_result->fetch_assoc();
            $new_status = $row['status'] == 1 ? 2 : 1;
            
            $query = "UPDATE Tests SET status = $new_status WHERE id = $test_id";
            
            if ($mysqli->query($query)) {
                $action = $new_status == 1 ? 'восстановлен' : 'скрыт';
                $_SESSION['success'] = "Тест успешно $action";
            } else {
                $_SESSION['error'] = 'Ошибка при изменении статуса теста: ' . $mysqli->error;
            }
        } else {
            $_SESSION['error'] = 'Тест не найден';
        }
        
        header('Location: edittest.php');
        exit;
    }



