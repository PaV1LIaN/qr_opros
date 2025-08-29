<?php
// generate_qr.php

// 1) Подключение к базе данных (PDO). 
// Скопируйте свой код подключения, например, из .section.php или add_block.php
require_once __DIR__ . '/.section.php'; 
//QRcode::png('https://snipp.ru/', __DIR__ . '/qr.png');

// 2) Подключаем библиотеку phpqrcode
require_once __DIR__ . '/phpqrcode/qrlib.php';
//QRcode::png('https://snipp.ru/');

// 3) Проверяем параметр car_id
if (!isset($_GET['car_id']) || !ctype_digit($_GET['car_id'])) {
    header("HTTP/1.1 400 Bad Request");
    echo "Не указан или неверный car_id";
    exit;
}
$carId = intval($_GET['car_id']);

// 4) Дополнительно можно проверить, что такая машина есть в БД
$sql = "SELECT NAME_CAR, TYPE_CAR, NOM_CAR FROM cars WHERE ID_CAR = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$carId]);
$car = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$car) {
    header("HTTP/1.1 404 Not Found");
    echo "Машина не найдена";
    exit;
}

// 5) Формируем ссылку на опрос Vote с передачей car_id
//    Замените «ваш_домен» и «17» на свои реальные значения
$voteId = 3; 
$surveyLink = "https://bitrix24-test.itsnn.ru/bitrix/services/vote/vote.php?lang=ru"
            . "&VOTE_ID={$voteId}"
            . "&car_id=" . $carId;

// 6) Генерируем QR-код и сразу отдаем PNG в браузер
header('Content-Type: image/png');
QRcode::png(
    $surveyLink,      // текст (URL) для кодирования
    false,            // false → выводит сразу, не сохраняет в файл
    QR_ECLEVEL_M,     // уровень коррекции ошибок (L, M, Q, H)
    8,                // размер «точки» в пикселях
    2                 // запас вокруг (margin)
);
exit;

// --- Если вместо вывода сразу в браузер нужно сохранять файл в папку qr_images/,
// --- используйте этот блок (закомментировано):
/*
$outFile = __DIR__ . "/qr_images/qr_car_{$carId}.png";
QRcode::png($surveyLink, $outFile, QR_ECLEVEL_M, 8, 2);
header("Location: qr_images/qr_car_{$carId}.png");
exit;
*/