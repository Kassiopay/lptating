<?php
require_once 'db.php'; // Assuming db.php contains the database connection

// Fetch users with statusID=1 and roleID=1
$userQuery = "SELECT id FROM Users WHERE statusID = 1 AND roleID = 1";
$userResult = $mysqli->query($userQuery);
$validUserIds = [];
if ($userResult && $userResult->num_rows > 0) {
    while ($row = $userResult->fetch_assoc()) {
        $validUserIds[] = $row['id'];
    }
}
$totalValidUsers = count($validUserIds);

// Fetch tests with status=1
$testQuery = "SELECT id, name FROM Tests WHERE status = 1";
$testResult = $mysqli->query($testQuery);
$tests = [];
if ($testResult && $testResult->num_rows > 0) {
    while ($row = $testResult->fetch_assoc()) {
        $tests[$row['id']] = $row['name'];
    }
}

// Calculate percentage of users who passed each test
// Assuming 'result' field indicates pass/fail, we consider result > 0 as pass
$passPercentages = [];
$correctnessPercentages = [];
foreach ($tests as $testId => $testName) {
    if ($totalValidUsers > 0) {
        // Count users who passed this test
        $passedQuery = "SELECT COUNT(DISTINCT userID) as passedCount FROM TestResults WHERE testID = $testId AND result > 0 AND userID IN (" . implode(',', $validUserIds) . ")";
        $passedResult = $mysqli->query($passedQuery);
        $passedCount = 0;
        if ($passedResult && $passedResult->num_rows > 0) {
            $passedRow = $passedResult->fetch_assoc();
            $passedCount = $passedRow['passedCount'];
        }
        $percentage = ($passedCount / $totalValidUsers) * 100;
        $passPercentages[] = round($percentage, 2);

        // Calculate correctness percentage
        $correctAnswersSum = 0;
        $totalQuestionsSum = 0;
        $resultQuery = "SELECT result FROM TestResults WHERE testID = $testId AND userID IN (" . implode(',', $validUserIds) . ")";
        $resultResult = $mysqli->query($resultQuery);
        if ($resultResult && $resultResult->num_rows > 0) {
            while ($row = $resultResult->fetch_assoc()) {
                $resultStr = $row['result'];
                $parts = explode('/', $resultStr);
                if (count($parts) == 2) {
                    $correctAnswers = floatval($parts[0]);
                    $totalQuestions = floatval($parts[1]);
                    $correctAnswersSum += $correctAnswers;
                    $totalQuestionsSum += $totalQuestions;
                }
            }
        }
        if ($totalQuestionsSum > 0) {
            $correctnessPercentages[] = round(($correctAnswersSum / $totalQuestionsSum) * 100, 2);
        } else {
            $correctnessPercentages[] = 0;
        }
    } else {
        $passPercentages[] = 0;
        $correctnessPercentages[] = 0;
    }
}

$sanitizedTestNames = array_map(function($name) {
    return htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
}, array_values($tests));

$correctnessPercentagesJson = json_encode($correctnessPercentages);

$highScoreCounts = [];
foreach ($tests as $testId => $testName) {
    $userScores = [];
    $resultQuery = "SELECT userID, result FROM TestResults WHERE testID = $testId AND userID IN (" . implode(',', $validUserIds) . ")";
    $resultResult = $mysqli->query($resultQuery);
    if ($resultResult && $resultResult->num_rows > 0) {
        while ($row = $resultResult->fetch_assoc()) {
            $userId = $row['userID'];
            $resultStr = trim($row['result']);
            $parts = explode('/', $resultStr);
            if (count($parts) == 2) {
                $correctAnswers = floatval($parts[0]);
                $totalQuestions = floatval($parts[1]);
                if ($totalQuestions > 0) {
                    $scorePercent = $correctAnswers / $totalQuestions;
                    if (!isset($userScores[$userId]) || $scorePercent > $userScores[$userId]) {
                        $userScores[$userId] = $scorePercent;
                    }
                }
            }
        }
    }
    $highCount = 0;
    foreach ($userScores as $score) {
        if ($score > 0.8) {
            $highCount++;
        }
    }
    $highScoreCounts[] = $highCount;
}

$highScoreCountsJson = json_encode($highScoreCounts);

// Debug output for verification
error_log("Test Names: " . print_r($sanitizedTestNames, true));
error_log("High Score Counts: " . print_r($highScoreCounts, true));

// Prepare data for JavaScript
$testNamesJson = json_encode($sanitizedTestNames);
$passPercentagesJson = json_encode($passPercentages);

// Calculate user counts for each chart type
$userCountsForCharts = [
    'passPercentage' => $totalValidUsers,
    'correctnessPercentage' => $totalValidUsers,
    'highScoreCount' => $totalValidUsers
];

// If there are differences in user counts per chart type, adjust here accordingly
// For now, using $totalValidUsers for all as placeholder

$userCountsForChartsJson = json_encode($userCountsForCharts);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Статистика прохождения тестирований</title>
    <link rel="stylesheet" href="style.css" />
    <link rel="shortcut icon" href="images/shortcut.ico" class="icon" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
    
    <style>
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
        }
        #chart-container {
            width: 100%;
            height: calc(100vh - 120px);
            margin: 0 auto;
            padding: 10px;
            box-sizing: border-box;
        }
        .chart-select-container {
            width: 100%;
            margin: 10px auto;
            text-align: center;
        }
        select {
            font-size: 16px;
            padding: 5px 10px;
        }
        h1 {
            text-align: center;
            margin: 10px 0;
        }
    </style>
</head>
<body>
<header class="testlist-header">
        <div class="header-testlist">
            <div class="left-corner">
            </div>
            <img src="images/logo.png" alt="Логотип ППЗ" class="testlist-logo">
            <div class="header-buttons">
                <a href="reportwindow.php" class="testlist-exit-btn">НАЗАД</a>
                <a href="logout.php" class="testlist-exit-btn">ВЫХОД</a>
            </div>
        </div>
    </header>
    <Main>
        <div class="tests-block">
        <h2>Статистика тестирования</h2>
        <div class="tests-block">
            <div class="filter-group">
            <div class="chart-select-container" style="display: flex; flex-direction: row; justify-content: space-between; align-items: center; gap: 20px; margin-bottom: 15px; width: 100%;">
                <div style="display: flex; flex-direction: column; align-items: flex-start; width: 48%;">
                    <label for="chartType" style="margin: 0; font-weight: bold; color: #B22222;">Выберите диаграмму:</label>
                    <select id="chartType" class="input" style="width: 100%; font-size: 18px; padding: 8px;">
                        <option value="passPercentage">Процент прохождения тестирований</option>
                        <option value="correctnessPercentage">Процент правильности прохождения</option>
                        <option value="highScoreCount">Количество успешных прохождений тестирования</option>
                    </select>
                </div>
                <div style="display: flex; flex-direction: column; align-items: flex-start; width: 48%;">
                    <label for="chartFormat" style="margin: 0; font-weight: bold; color: #B22222;">Формат диаграммы:</label>
                    <select id="chartFormat" class="input" style="width: 100%; font-size: 18px; padding: 8px;">
                        <option value="bar">Столбчатая</option>
                        <option value="pie">Круговая</option>
                    </select>
                </div>
            </div>
        </div>
        </div>
        
        <div class="test-block">
        <!-- Data fields section above the chart -->
        <div id="data-fields" style="margin-bottom: 15px; padding: 10px; border: 1px solid #ccc; background-color: #f9f9f9; font-size: 16px;">
            <div style="margin-bottom: 10px;">
                
                <strong>Всего сотрудников:</strong> <span id="employeesForCalculations"><?php echo $totalValidUsers; ?></span>
            </div>
            <div>
                <strong>Выбранный тест:</strong> <span id="selectedTestName">None</span><br />
                <strong>Значение:</strong> <span id="selectedTestValue">N/A</span>
            </div>
        </div>
        <div id="chart-container">
                <canvas id="myChart"></canvas>
            </div>
        </div>
        </div>
            
        
    </Main>
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
        const testNames = <?php echo $testNamesJson; ?>;
        const passPercentages = <?php echo $passPercentagesJson; ?>;
        const correctnessPercentages = <?php echo $correctnessPercentagesJson; ?>;
        const highScoreCounts = <?php echo $highScoreCountsJson; ?>;

        const ctx = document.getElementById('myChart').getContext('2d');

        let currentChart;

        function renderChart(type, format) {
            if (currentChart) {
                currentChart.destroy();
            }
            let data, config;
            if (type === 'passPercentage') {
                data = {
                    labels: testNames,
                    datasets: [{
                        label: 'Процент прохождения тестирований',
                        data: passPercentages,
                        backgroundColor: [
                            'rgba(54, 162, 235, 0.7)',
                            'rgba(75, 192, 192, 0.7)',
                            'rgba(255, 206, 86, 0.7)',
                            'rgba(255, 99, 132, 0.7)',
                            'rgba(153, 102, 255, 0.7)',
                            'rgba(255, 159, 64, 0.7)',
                            'rgba(201, 203, 207, 0.7)'
                        ],
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1
                    }]
                };
            if (format === 'bar') {
                config = {
                    type: 'bar',
                    data: data,
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            x: {
                                ticks: {}
                            },
                            y: {
                                beginAtZero: true,
                                max: 100,
                                ticks: {
                                    callback: function(value) {
                                        return value + '%';
                                    }
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: true
                            },
                            tooltip: {
                                enabled: true
                            },
                            datalabels: {
                                anchor: 'end',
                                align: 'top',
                                formatter: function(value) {
                                    return value + '%';
                                },
                                font: {
                                    weight: 'bold'
                                }
                            }
                        }
                    },
                    plugins: [ChartDataLabels]
                };
            } else if (format === 'pie') {
                config = {
                    type: 'pie',
                    data: data,
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'right'
                            },
                            datalabels: {
                                display: true,
                                color: '#fff',
                                font: {
                                    weight: 'bold',
                                    size: 14
                                },
                                formatter: function(value, context) {
                                    return value + '%';
                                }
                            }
                        }
                    }
                };
            }
            } else if (type === 'correctnessPercentage') {
                data = {
                    labels: testNames,
                    datasets: [{
                        label: 'Процент правильности прохождения',
                        data: correctnessPercentages,
                        backgroundColor: [
                            'rgba(75, 192, 192, 0.7)',
                            'rgba(54, 162, 235, 0.7)',
                            'rgba(255, 206, 86, 0.7)',
                            'rgba(255, 99, 132, 0.7)',
                            'rgba(153, 102, 255, 0.7)',
                            'rgba(255, 159, 64, 0.7)',
                            'rgba(201, 203, 207, 0.7)'
                        ],
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1
                    }]
                };
                if (format === 'bar') {
                    config = {
                        type: 'bar',
                        data: data,
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                x: {
                                    ticks: {}
                                },
                                y: {
                                    beginAtZero: true,
                                    max: 100,
                                    ticks: {
                                        callback: function(value) {
                                            return value + '%';
                                        }
                                    }
                                }
                            }
                        }
                    };
                } else if (format === 'pie') {
                    config = {
                        type: 'pie',
                        data: data,
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'right'
                                }
                            }
                        }
                    };
                }
            } else if (type === 'highScoreCount') {
                data = {
                    labels: testNames,
                    datasets: [{
                        label: 'Количество успешных прохождений тестирования',
                        data: highScoreCounts,
                        backgroundColor: [
                            'rgba(255, 159, 64, 0.7)',
                            'rgba(54, 162, 235, 0.7)',
                            'rgba(75, 192, 192, 0.7)',
                            'rgba(255, 206, 86, 0.7)',
                            'rgba(255, 99, 132, 0.7)',
                            'rgba(153, 102, 255, 0.7)',
                            'rgba(201, 203, 207, 0.7)'
                        ],
                        borderColor: 'rgba(255, 159, 64, 1)',
                        borderWidth: 1
                    }]
                };
                if (format === 'bar') {
                    config = {
                        type: 'bar',
                        data: data,
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                x: {
                                    ticks: {}
                                },
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        stepSize: 1
                                    }
                                }
                            },
                            plugins: {
                                legend: {
                                    display: true
                                },
                                tooltip: {
                                    enabled: true
                                },
                                datalabels: {
                                    anchor: 'end',
                                    align: 'top',
                                    formatter: function(value) {
                                        return value;
                                    },
                                    font: {
                                        weight: 'bold'
                                    }
                                }
                            }
                        },
                        plugins: [ChartDataLabels]
                    };
                } else if (format === 'pie') {
                    config = {
                        type: 'pie',
                        data: data,
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'right'
                                },
                                datalabels: {
                                    display: true,
                                    color: '#fff',
                                    font: {
                                        weight: 'bold',
                                        size: 14
                                    },
                                    formatter: function(value) {
                                        return value;
                                    }
                                }
                            }
                        }
                    };
                }
            }
            currentChart = new Chart(ctx, config);
        }

        document.getElementById('chartType').addEventListener('change', function() {
            const format = document.getElementById('chartFormat').value;
            renderChart(this.value, format);
        });

        document.getElementById('chartFormat').addEventListener('change', function() {
            const type = document.getElementById('chartType').value;
            renderChart(type, this.value);
        });

        // Update employees used for calculations count on chart type change
        const userCountsForCharts = <?php echo $userCountsForChartsJson; ?>;
        function updateEmployeesForCalculations(chartType) {
            const count = userCountsForCharts[chartType] || 0;
            document.getElementById('employeesForCalculations').textContent = count;
        }

        document.getElementById('chartType').addEventListener('change', function() {
            updateEmployeesForCalculations(this.value);
        });

        // Initialize employees used for calculations count
        updateEmployeesForCalculations(document.getElementById('chartType').value);

        // Initial chart render
        renderChart('passPercentage', 'bar');

        // Add click event listener to update data fields on chart element click
        document.getElementById('myChart').onclick = function(evt) {
            if (!currentChart) return;
            const points = currentChart.getElementsAtEventForMode(evt, 'nearest', { intersect: true }, false);
            if (points.length) {
                const firstPoint = points[0];
                const label = currentChart.data.labels[firstPoint.index];
                const value = currentChart.data.datasets[firstPoint.datasetIndex].data[firstPoint.index];
                document.getElementById('selectedTestName').textContent = label;
                // Format value based on chart type
                const chartType = document.getElementById('chartType').value;
                let formattedValue = value;
                if (chartType === 'passPercentage' || chartType === 'correctnessPercentage') {
                    formattedValue = value + '%';
                    // Calculate numeric value based on percentage and employees used for calculations
                    const employeesCount = parseInt(document.getElementById('employeesForCalculations').textContent) || 0;
                    const numericValue = Math.round((value / 100) * employeesCount);
                    formattedValue += ` (${numericValue} сотрудников)`;
                }
                document.getElementById('selectedTestValue').textContent = formattedValue;
            }
        };
    </script>
</body>
</html>
