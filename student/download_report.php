<?php
require_once '../includes/auth.php';
$auth = new Auth();
if (!$auth->isLoggedIn() || !$auth->hasRole('student')) header("Location: ../index.php");

require_once '../config/database.php';
$db = new Database();
$conn = $db->getConnection();

$userId = $_SESSION['user_id'];
$student = $conn->query("SELECT * FROM students WHERE id = (SELECT student_id FROM users WHERE id = $userId)")->fetch_assoc();
$visits = $conn->query("SELECT * FROM visits WHERE student_id = {$student['id']} ORDER BY visit_date DESC");

$html = "<h1>Health Report for {$student['full_name']}</h1>";
$html .= "<p>Admission No: {$student['admission_no']} | Class: {$student['class']}</p>";
$html .= "<table border='1' cellpadding='5'><tr><th>Date</th><th>Diagnosis</th><th>Treatment</th></tr>";
while($v = $visits->fetch_assoc()) {
    $html .= "<tr><td>{$v['visit_date']}</td><td>{$v['diagnosis']}</td><td>{$v['treatment']}</td></tr>";
}
$html .= "</table>";

// In production, use TCPDF or Dompdf
// For now, we'll just output HTML as PDF
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="health_report_'.$student['admission_no'].'.pdf"');
echo $html;
exit;
?>