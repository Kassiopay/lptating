<?php
try {
    session_start();

    require_once 'db.php';
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }



    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
       
            $loginform = $_POST["loginform"] ?? '';
            $passwordform = $_POST["passwordform"] ?? '';
            
            // Prepare statement to prevent SQL injection
            $stmt = $mysqli->prepare("SELECT id, login, password, roleID FROM Users WHERE login = ?");
            $stmt->bind_param("s", $loginform);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $row = $result->fetch_assoc();

                // Verify password against stored hash
                if (empty($passwordform)) {
                    error_log("Empty password for user: " . $row['login']);
                    throw new Exception("Пароль не может быть пустым");
                }
                
                if (strlen($passwordform) < 6) {
                    error_log("Password too short for user: " . $row['login']);
                    throw new Exception("Длинна пароля должна быть больше 6 символов");
                }

                file_put_contents('debug_auth.log', "Trying auth for: ".$row['login']."\n", FILE_APPEND);
                file_put_contents('debug_auth.log', "Input pass: ".$passwordform."\n", FILE_APPEND); 
                file_put_contents('debug_auth.log', "Stored hash: ".$row['password']."\n", FILE_APPEND);
                $verify = password_verify($passwordform, $row['password']);
                file_put_contents('debug_auth.log', "Verify result: ".($verify ? 'true' : 'false')."\n", FILE_APPEND);
                
                if ($verify) {
                    $_SESSION['username'] = $row['login'];
                    $_SESSION['user_id'] = $row['id'];
                    $_SESSION['last_login'] = time();
                    $_SESSION['roleID'] = $row['roleID'];
                    
                    // Check if user is admin (roleID = 2)
                    if ($row['roleID'] == 2) {
                        $_SESSION['admin'] = true;
                        header('Location: reportwindow.php');
                    } elseif ($row['roleID'] == 3) {
                        $_SESSION['analyst'] = true;
                        header('Location: reportwindow.php');
                    } else {
                        header('Location: testlist.php');
                    }
                    exit();
                } else {
                    error_log("Password verification failed for user: " . $row['login']);
                    error_log("Input password: " . $passwordform);
                    error_log("Input password hash: " . password_hash($passwordform, PASSWORD_DEFAULT));
                    error_log("Stored password hash: " . $row['password']);
                    error_log("Verification result: " . password_verify($passwordform, $row['password']) ? 'true' : 'false');
                    throw new Exception("Неверный пароль");
                }
            }
            
            // If we get here, login failed
            $_SESSION['error'] = 'Неверное имя пользователя или пароль';
            header('Location: index.php');
            exit();
            
        
    }
} catch (Exception $e) {
    
    $_SESSION['error'] = 'Ошибка при входе в систему: ' . $e->getMessage();
    header('Location: index.php');
    exit();
}
?>
