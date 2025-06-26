<?php
// $host = 'sql112.infinityfree.com';
// $user = 'if0_39124391';
// $pwd = 'vTSBrypbI5h0Ku';
// $db = 'if0_39124391_delicioudelicious_cake';

$host = 'localhost';
$user = 'root';
$pwd = '';
$db = 'delicious_cake';

try {
    $ln = "mysql:host=$host;dbname=$db";
    $conn = new PDO($ln, $user, $pwd);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>
