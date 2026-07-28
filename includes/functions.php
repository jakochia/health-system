<?php
function getSetting($key, $conn) {
    $stmt = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        return $row['setting_value'];
    }
    return null;
}

function generatePDF($html, $filename) {
    // Placeholder – integrate with TCPDF or Dompdf
    // For now, we'll just output the HTML
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="'.$filename.'"');
    echo $html;
    exit;
}

function exportExcel($data, $filename) {
    // Placeholder – integrate with PhpSpreadsheet
    // For now, output CSV
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="'.$filename.'.csv"');
    $output = fopen('php://output', 'w');
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
}
?>