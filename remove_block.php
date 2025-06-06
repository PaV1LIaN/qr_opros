<?php 
$dsn = 'mysql:host=localhost;dbname=test_db';
$username = 'root';
$password = 'root';

$pdo = new PDO($dsn, $username, $password);
$id = $_POST['id'];
$sql = ("UPDATE blocks SET is_activate = FALSE WHERE id = ?");
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
?>