<?php
session_start();
require_once 'db.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
// Проверка авторизации администратора
if ((!isset($_SESSION['analyst']) || $_SESSION['analyst'] !== true) && (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true  )) {
    header('Location: index.php');
    exit;
}


// Получение данных из базы
$users = [];
$tests = [];
$results = [];

try {
    // Получаем активных пользователей (statusID = 1 и roleID = 1)
    $users_query = $mysqli->query("SELECT id, techID FROM Users WHERE statusID = 1 AND roleID = 1");
    $users = $users_query->fetch_all(MYSQLI_ASSOC);

    // Получаем активные тесты (status = 1)
    $tests_query = $mysqli->query("SELECT id, name FROM Tests WHERE status = 1");
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
            FROM TestResults r
            JOIN Users u ON r.userID = u.id AND u.statusID = 1 AND u.roleID = 1
            JOIN Tests t ON r.testID = t.id";
            
    if (!empty($where)) {
        $sql .= " WHERE " . implode(' AND ', $where);
    }
    $sql .= " ORDER BY r.id ASC";

    $stmt = $mysqli->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $_SESSION['error'] = "Ошибка получения данных: " . $e->getMessage();
}

    

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Set column headers
    $headers = ['Номер', 'Пользователь', 'Тест', 'Результат', 'Дата'];
    $col = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($col . '1', $header);
        $col++;
    }

    // Make header row bold
    $sheet->getStyle('A1:E1')->getFont()->setBold(true);

    // Set width of test_name column (C) to 80
     $sheet->getColumnDimension('B')->setWidth(20);
    $sheet->getColumnDimension('C')->setWidth(80);
    $sheet->getColumnDimension('E')->setWidth(12);

    // Fill data rows
    $rowNum = 2;
    $counter = 1;
    foreach ($results as $row) {
        $sheet->setCellValue('A' . $rowNum, $counter);
        $sheet->setCellValue('B' . $rowNum, $row['techID']);
        $sheet->setCellValue('C' . $rowNum, $row['test_name']);
        $sheet->setCellValue('D' . $rowNum, $row['result']);
        $sheet->setCellValue('E' . $rowNum, $row['date']);
        $rowNum++;
        $counter++;
    }

    // Apply borders to the entire table including headers
    $lastRow = $rowNum - 1;
    $styleArray = [
        'borders' => [
            'allBorders' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                'color' => ['argb' => 'FF000000'],
            ],
        ],
    ];
    $sheet->getStyle('A1:E' . $lastRow)->applyFromArray($styleArray);

    // Center align data in columns A (ID), B (User), and D (Result)
    $centerAlign = \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER;
    $sheet->getStyle('A1:A' . $lastRow)->getAlignment()->setHorizontal($centerAlign);
    $sheet->getStyle('B1:B' . $lastRow)->getAlignment()->setHorizontal($centerAlign);
    $sheet->getStyle('D1:D' . $lastRow)->getAlignment()->setHorizontal($centerAlign);
    $sheet->getStyle('C1' . $lastRow)->getAlignment()->setHorizontal($centerAlign);
    $sheet->getStyle('E1:E' . $lastRow)->getAlignment()->setHorizontal($centerAlign);

    // Set headers for download
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="report_' . date('Y-m-d') . '.xlsx"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
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
            <div class="left-corner">
            </div>
            <img src="images/logo.png" alt="Логотип ППЗ" class="testlist-logo">
            <div class="header-buttons">
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
                <div class="filter-group">
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
                    <button type="button" class="btn-filter" onclick="exportToExcel()">Экспорт в Excel</button>
                <button type="button" class="btn-filter" onclick="window.location.href='statistics.php'">Статистика</button>
                <button type="button" class="btn-filter" onclick="window.location.href='edittest.php'">Список тестов</button>
                <button type="button" class="btn-filter" onclick="window.location.href='settingswindow.php'">Настройки тестирования</button>
                <?php if (isset($_SESSION['admin']) && $_SESSION['admin'] === true): ?>
                    <button type="button" class="btn-filter" onclick="window.location.href='userlist.php'">Пользователи</button>
                <?php endif; ?>
                </div>

                <script>
                function exportToExcel() {
                    // Сохраняем текущие параметры фильтрации
                    const params = new URLSearchParams(window.location.search);
                    // Добавляем параметр для экспорта
                    params.set('export', 'excel');
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
                        <?php $counter = 1; ?>
                        <?php foreach ($results as $row): ?>
                            <tr>
                                <td><?= $counter ?></td>
                                <td style="text-align: center;"><?= htmlspecialchars($row['techID']) ?></td>
                                <td style="text-align: left;"><?= htmlspecialchars($row['test_name']) ?></td>
                                <td><?= htmlspecialchars($row['result']) ?></td>
                                <td><?= htmlspecialchars($row['date']) ?></td>
                            </tr>
                            <?php $counter++; ?>
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
