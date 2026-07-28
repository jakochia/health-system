<?php
// Integration with Africa's Talking
// Include your API keys and send SMS
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $phone = $_POST['phone'];
    $message = $_POST['message'];
    // Your SMS code here
    echo "SMS would be sent to $phone: $message";
}
?>