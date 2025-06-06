<?php
$dsn = 'mysql:host=localhost;dbname=qr_opros';
$username = 'root';
$password = 'root';



$pdo = new PDO($dsn, $username, $password);
$nameCar = $_POST['nameCar'];
$typeCar = $_POST['typeCar'];
$nomCar = $_POST['nomCar'];


$sql = "INSERT INTO cars (NAME_CAR, TYPE_CAR, NOM_CAR) VALUES (?, ?, ?)";
$stmt = $pdo->prepare($sql);
$stmt->execute([$nameCar, $typeCar, $nomCar]);

$carId = $pdo->lastInsertId();
echo json_encode(['id' => $carId, 'nameCar' => $nameCar, 'typeCar' => $typeCar, 'nomCar' => $nomCar]);

?>