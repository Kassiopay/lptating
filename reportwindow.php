<?php
session_start();
require_once 'db.php';

// Проверка авторизации администратора
if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header('Location: index.php');
    exit;
}

// Получение данных из базы
$users = [];
$tests = [];
$results = [];

try {
    // Получаем активных пользователей (statusID = 1 и roleID = 1)
    $users_query = $mysqli->query("SELECT id, techID FROM users WHERE statusID = 1 AND roleID = 1");
    $users = $users_query->fetch_all(MYSQLI_ASSOC);

    // Получаем активные тесты (status = 1)
    $tests_query = $mysqli->query("SELECT id, name FROM tests WHERE status = 1");
    $tests = $tests_query->fetch_all(MYSQLI_ASSOC);

    // Получаем результаты тестирования (фильтруем по параметрам если они есть)
    $where = [];
    $params = [];
    $types = '';

    if (!empty($_GET['user'])) {
        // Find user ID by techID
        $techID = $_GET['user'];
        $user_id = null;
        foreach ($users as $user) {
            if ($user['techID'] == $techID) {
                $user_id = $user['id'];
                break;
            }
        }
        if ($user_id) {
            $where[] = "userID = ?";
            $params[] = $user_id;
            $types .= 'i';
        }
    }

    if (!empty($_GET['test'])) {
        $where[] = "testID = ?";
        $params[] = $_GET['test'];
        $types .= 'i';
    }

    if (!empty($_GET['date_from'])) {
        $where[] = "date >= ?";
        $params[] = $_GET['date_from'];
        $types .= 's';
    }

    if (!empty($_GET['date_to'])) {
        $where[] = "date <= ?";
        $params[] = $_GET['date_to'];
        $types .= 's';
    }

    $sql = "SELECT r.id, u.techID, t.name as test_name, r.result, r.date 
            FROM testresults r
            JOIN users u ON r.userID = u.id AND u.statusID = 1 AND u.roleID = 1
            JOIN tests t ON r.testID = t.id";
            
    if (!empty($where)) {
        $sql .= " WHERE " . implode(' AND ', $where);
    }

    $stmt = $mysqli->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $_SESSION['error'] = "Ошибка получения данных: " . $e->getMessage();
}

    // Function to transliterate Russian characters to English
    function transliterate($text) {
        $transliteration = [
            'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G', 'Д' => 'D',
            'Е' => 'E', 'Ё' => 'Yo', 'Ж' => 'Zh', 'З' => 'Z', 'И' => 'I',
            'Й' => 'Y', 'К' => 'K', 'Л' => 'L', 'М' => 'M', 'Н' => 'N',
            'О' => 'O', 'П' => 'P', 'Р' => 'R', 'С' => 'S', 'Т' => 'T',
            'У' => 'U', 'Ф' => 'F', 'Х' => 'Kh', 'Ц' => 'Ts', 'Ч' => 'Ch',
            'Ш' => 'Sh', 'Щ' => 'Shch', 'Ъ' => '', 'Ы' => 'Y', 'Ь' => '',
            'Э' => 'E', 'Ю' => 'Yu', 'Я' => 'Ya',
            'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd',
            'е' => 'e', 'ё' => 'yo', 'ж' => 'zh', 'з' => 'z', 'и' => 'i',
            'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n',
            'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't',
            'у' => 'u', 'ф' => 'f', 'х' => 'kh', 'ц' => 'ts', 'ч' => 'ch',
            'ш' => 'sh', 'щ' => 'shch', 'ъ' => '', 'ы' => 'y', 'ь' => '',
            'э' => 'e', 'ю' => 'yu', 'я' => 'ya'
        ];
        return strtr($text, $transliteration);
    }
    
    // Обработка экспорта в PDF
if (isset($_GET['export']) && $_GET['export'] === 'pdf') {
    require('fpdf.php');
    
    class PDF extends FPDF {
        function __construct() {
            parent::__construct('P', 'mm', 'A4');
            // Use FPDF core fonts (Helvetica is Arial equivalent)
            $this->SetFont('Helvetica', '', 10);
        }
        
        function Header() {
            $this->SetFont('Helvetica', 'B', 12);
            $this->Cell(0, 10, 'Test report', 0, 1, 'C');
            $this->Ln(5);
        }
        
        function Footer() {
            $this->SetY(-15);
            $this->SetFont('Helvetica', '', 8);
            $this->Cell(0, 10, 'Страница '.$this->PageNo().'/{nb}', 0, 0, 'C');
        }
    }

    $pdf = new PDF();
    $pdf->AliasNbPages();
    $pdf->AddPage();

    // Заголовки таблицы
    $pdf->SetFillColor(200,220,255);
    $pdf->Cell(15,7,'ID',1,0,'C',true);
    $pdf->Cell(50,7,'User',1,0,'C',true);
    $pdf->Cell(60,7,'Test',1,0,'C',true);
    $pdf->Cell(30,7,'Result',1,0,'C',true);
    $pdf->Cell(35,7,'Data',1,1,'C',true);

    // Данные таблицы
    $pdf->SetFillColor(255,255,255);
    foreach($results as $row) {
        $pdf->Cell(15,7,$row['id'],1,0,'C');
        $pdf->Cell(50,7,$row['techID'],1,0,'L');
        $pdf->Cell(60,7,transliterate($row['test_name']),1,0,'L');
        $pdf->Cell(30,7,$row['result'],1,0,'C');
        $pdf->Cell(35,7,$row['date'],1,1,'C');
    }

    $pdf->Output('I', 'report_'.date('Y-m-d').'.pdf');
    exit;
}

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Отчеты по тестированию</title>
    <link rel="shortcut icon" href="images/shortcut.ico" class="icon" type="image/x-icon">
    <link rel="stylesheet" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
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
            <div class="error-message">
                <?= htmlspecialchars($_SESSION['error']) ?>
                <?php unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        
        <div class="tests-block">
            <h2>Фильтры отчетов</h2>
            <form method="get" class="modal-form">
                <div style="display: flex; justify-content: space-between; gap: 20px; width: 100%;">
                    <div style="flex: 1; min-width: 300px;">
                        <div class="form-group">
                            <label for="user">Пользователь:</label>
                            <select name="user" id="user" class="input">
                                <option value="">Все пользователи</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?= htmlspecialchars($user['techID']) ?>" <?= (!empty($_GET['user']) && $_GET['user'] == $user['techID']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($user['techID']) ?> (ID: <?= htmlspecialchars($user['id']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="test">Тест:</label>
                            <select name="test" id="test" class="input">
                                <option value="">Все тесты</option>
                                <?php foreach ($tests as $test): ?>
                                    <option value="<?= htmlspecialchars($test['id']) ?>" <?= (!empty($_GET['test']) && $_GET['test'] == $test['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($test['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="date-row">
                        <div class="form-group">
                            <label for="date_from">Дата начала:</label>
                            <input type="date" name="date_from" id="date_from" class="input" value="<?= htmlspecialchars($_GET['date_from'] ?? '') ?>" style="width: auto;">
                        </div>

                        <div class="form-group">
                            <label for="date_to">Дата конца:</label>
                            <input type="date" name="date_to" id="date_to" class="input" value="<?= htmlspecialchars($_GET['date_to'] ?? '') ?>" style="width: auto;">
                        </div>
                    </div>
                </div>

                <div class="form-buttons" style="display: flex; gap: 10px;">
                    <button type="submit" class="btn-filter">Применить фильтры</button>
                    <button type="button" class="btn-filter" onclick="exportToPDF()">Экспорт в PDF</button>
                </div>

                <script>
                function exportToPDF() {
                    // Сохраняем текущие параметры фильтрации
                    const params = new URLSearchParams(window.location.search);
                    // Добавляем параметр для экспорта
                    params.set('export', 'pdf');
                    // Отправляем запрос
                    window.location.href = window.location.pathname + '?' + params.toString();
                }
                </script>
            </form>
        </div>

        <div class="tests-block">
            <table class="tests-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Пользователь</th>
                        <th>Тест</th>
                        <th>Результат</th>
                        <th>Дата</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($results)): ?>
                        <?php foreach ($results as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['id']) ?></td>
                                <td><?= htmlspecialchars($row['techID']) ?></td>
                                <td><?= htmlspecialchars($row['test_name']) ?></td>
                                <td><?= htmlspecialchars($row['result']) ?></td>
                                <td><?= htmlspecialchars($row['date']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="no-data">Нет данных для отображения</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
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

</body>
</html>
