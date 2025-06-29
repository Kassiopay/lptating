<?php
session_start();
require_once 'db.php';

// Проверка авторизации администратора
if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header('Location: index.php');
    exit;
}

// Обработка добавления нового пользователя
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_user') {
    $login = trim($_POST['login']);
    $password = trim($_POST['password']);
    $roleID = (int)$_POST['roleID'];
    $techID = (int)$_POST['techID'];

    if (empty($login) || empty($password)) {
        $_SESSION['error'] = 'Логин и пароль не могут быть пустыми';
        header('Location: userlist.php');
        exit;
    }

    // Проверка существования пользователя
    $check_query = "SELECT id FROM Users WHERE login = ?";
    $stmt = $mysqli->prepare($check_query);
    $stmt->bind_param("s", $login);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $_SESSION['error'] = 'Пользователь с таким логином уже существует';
        header('Location: userlist.php');
        exit;
    }

    // Хеширование пароля
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Добавление пользователя
    $insert_query = "INSERT INTO Users (login, password, createdata, roleID, statusID, techID) VALUES (?, ?, NOW(), ?, 1, ?)";
    $stmt = $mysqli->prepare($insert_query);
    $stmt->bind_param("ssis", $login, $hashed_password, $roleID, $techID);

    if ($stmt->execute()) {
        $_SESSION['success'] = 'Пользователь успешно добавлен';
    } else {
        $_SESSION['error'] = 'Ошибка при добавлении пользователя';
    }

    header('Location: userlist.php');
    exit;
}

// Обработка изменения статуса пользователя
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_user_status') {
    if (!isset($_POST['user_id']) || !is_numeric($_POST['user_id'])) {
        $_SESSION['error'] = 'Неверный ID пользователя';
        header('Location: userlist.php');
        exit;
    }

    $user_id = (int)$_POST['user_id'];
    
    // Получаем текущий статус пользователя
    $status_query = "SELECT statusID FROM Users WHERE id = $user_id";
    $status_result = $mysqli->query($status_query);
    
    if ($status_result && $status_result->num_rows > 0) {
        $row = $status_result->fetch_assoc();
        $new_status = $row['statusID'] == 1 ? 2 : 1;
        
        $query = "UPDATE Users SET statusID = $new_status WHERE id = $user_id";
        
        if ($mysqli->query($query)) {
            $action = $new_status == 1 ? 'возвращен' : 'скрыт';
            $_SESSION['success'] = "Пользователь успешно $action";
        } else {
            $_SESSION['error'] = 'Ошибка при изменении статуса: ' . $mysqli->error;
        }
    } else {
        $_SESSION['error'] = 'Пользователь не найден';
    }
    
    header('Location: userlist.php');
    exit;
}

// Если действие не распознано
$_SESSION['error'] = 'Неизвестное действие';
header('Location: userlist.php');
exit;
