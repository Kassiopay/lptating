<?php
error_reporting(0);
require 'vendor/autoload.php';
require_once 'db.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Disable output buffering to prevent corrupting the Excel file
if (ob_get_length()) {
    ob_end_clean();
}
ob_clean();
flush();

// Validate chart type parameter
$chartType = isset($_GET['type']) ? $_GET['type'] : 'passPercentage';
if (!in_array($chartType, ['passPercentage', 'correctnessPercentage'])) {
    die('Invalid chart type');
}

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

if ($totalValidUsers === 0) {
    die('No valid users found');
}

// Fetch tests with status=1
$testQuery = "SELECT id, name FROM Tests WHERE status = 1";
$testResult = $mysqli->query($testQuery);
$tests = [];
if ($testResult && $testResult->num_rows > 0) {
    while ($row = $testResult->fetch_assoc()) {
        $tests[$row['id']] = $row['name'];
    }
}

$percentages = [];

if ($chartType === 'passPercentage') {
    // Calculate percentage of users who passed each test
    foreach ($tests as $testId => $testName) {
        $passedCount = 0;
        if ($totalValidUsers > 0) {
            $passedQuery = "SELECT COUNT(DISTINCT userID) as passedCount FROM TestResults WHERE testID = $testId AND result > 0 AND userID IN (" . implode(',', $validUserIds) . ")";
            $passedResult = $mysqli->query($passedQuery);
            if ($passedResult && $passedResult->num_rows > 0) {
                $passedRow = $passedResult->fetch_assoc();
                $passedCount = $passedRow['passedCount'];
            }
            $percentage = ($passedCount / $totalValidUsers) * 100;
            $percentages[] = round($percentage, 2);
        } else {
            $percentages[] = 0;
        }
    }
} else if ($chartType === 'correctnessPercentage') {
    // Calculate correctness percentage for each test
    foreach ($tests as $testId => $testName) {
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
            $percentages[] = round(($correctAnswersSum / $totalQuestionsSum) * 100, 2);
        } else {
            $percentages[] = 0;
        }
    }
}

// Create new Spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Set header
$sheet->setCellValue('A1', 'Test Name');
$sheet->setCellValue('B1', 'Percentage');

// Fill data
$rowNum = 2;
$testNames = array_values($tests);
foreach ($testNames as $index => $testName) {
    $sheet->setCellValue('A' . $rowNum, $testName);
    $sheet->setCellValue('B' . $rowNum, $percentages[$index]);
    $rowNum++;
}

// Set header for download
$filename = $chartType . '_chart_data.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$filename\"");
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>
