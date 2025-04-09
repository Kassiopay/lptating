<?php
ob_start();
require_once 'db.php';

// Тестовые данные
$test_users = [
    [
        'login' => 'testuser1',
        'password' => 'Test123!',
        'techID' => '12345'
    ],
    [
        'login' => 'testuser2', 
        'password' => 'Test456!',
        'techID' => '54321'
    ]
];

// 1. Сначала зарегистрируем тестовых пользователей
foreach ($test_users as $user) {
    $hashed_password = password_hash($user['password'], PASSWORD_DEFAULT);
    $stmt = $mysqli->prepare("INSERT INTO Users (login, password, techID) VALUES (?, ?, ?)");
    $stmt->bind_param("ssi", $user['login'], $hashed_password, $user['techID']);
    $stmt->execute();
    echo "Зарегистрирован пользователь: " . $user['login'] . "\n";
}

// 2. Тестирование авторизации
foreach ($test_users as $user) {
    // Правильные учетные данные
    $_POST['loginform'] = $user['login'];
    $_POST['passwordform'] = $user['password'];
    require 'login.php';
    
    // Проверяем сессию
    session_start();
    if (isset($_SESSION['username']) && $_SESSION['username'] == $user['login']) {
        echo "Успешная авторизация для: " . $user['login'] . "\n";
    } else {
        echo "Ошибка авторизации для: " . $user['login'] . "\n";
    }
    session_destroy();

    // Неправильный пароль
    $_POST['loginform'] = $user['login'];
    $_POST['passwordform'] = 'wrongpassword';
    require 'login.php';
    session_start();
    if (isset($_SESSION['error'])) {
        echo "Проверка неверного пароля сработала для: " . $user['login'] . "\n";
    }
    session_destroy();
}

// Удаляем тестовых пользователей
foreach ($test_users as $user) {
    $stmt = $mysqli->prepare("DELETE FROM users WHERE login = ?");
    $stmt->bind_param("s", $user['login']);
    $stmt->execute();
}
?>
