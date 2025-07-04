<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../employee/utils/redirectMessage.php';
include '../configs/db.php';

$subject = $_POST['subject'] ?? '';
$content = $_POST['query'] ?? '';
$customerId = $_SESSION['customerId'] ?? null;

$senderType = $customerId ? 'customer' : 'guest';
$guestName = $_POST['name'] ?? 'Anonymous';
$guestEmail = $_POST['email'] ?? '-';

try {
    if ($senderType === 'customer') {
        $stmt = $conn->prepare("INSERT INTO Messages (SenderType, SenderId, Subject, Content) VALUES (:senderType, :senderId, :subject, :content)");
        $stmt->execute([
            ':senderType' => $senderType,
            ':senderId' => $customerId,
            ':subject' => $subject,
            ':content' => $content,
        ]);
        header("Location: ../profile.php#queries");
        exit;
    } else {
        $stmt = $conn->prepare("INSERT INTO Messages (SenderType, GuestName, GuestEmail, Subject, Content) VALUES (:senderType, :guestName, :guestEmail, :subject, :content)");
        $stmt->execute([
            ':senderType' => $senderType,
            ':guestName' => $guestName,
            ':guestEmail' => $guestEmail,
            ':subject' => $subject,
            ':content' => $content,
        ]);
        header("Location: ../contact.php?success=1");
        exit;
    }
} catch (PDOException $e) {
    if ($senderType === 'customer') {
        redirectWithMessage("../profile.php", $e);
    } else {
        redirectWithMessage("../contact.php", $e);
        exit;
    }
}
