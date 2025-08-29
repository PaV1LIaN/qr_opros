<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Title");
?>
<?php
// /local/qr-opros/prepare.php
$dsn = 'mysql:host=localhost;dbname=qr_opros';
$username = 'root';
$password = 'root';
// 1) Инициализируем Bitrix, чтобы был доступен LocalRedirect и CRest
require_once $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php';

// 2) Подключаем БД через PDO (замените параметры на свои)
try {
    $pdo = new PDO($dsn, $username, $password, 
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    die('Ошибка подключения к БД: ' . $e->getMessage());
}

// 3) Читаем и валидируем car_id
$carId = filter_input(INPUT_GET, 'car_id', FILTER_VALIDATE_INT);
if (!$carId) {
    ShowError('Неверный идентификатор автомобиля');
    exit;
}

// 4) Проверяем, есть ли такая запись и флаг POLL_CREATED
$stmt = $pdo->prepare("SELECT POLL_CREATED FROM cars WHERE ID_CAR = :carId");
$stmt->execute([':carId' => $carId]);
$row = $stmt->fetch();

if (!$row) {
    ShowError('Автомобиль не найден');
    exit;
}

// 5) Если опрос ещё не создавался — отмечаем его созданным
if (empty($row['POLL_CREATED'])) {
    // === Здесь можно добавить REST-вызов для динамического создания опроса ===
    // Пример:
    // $resp = CRest::call('vote.vote.add', [
    //     'CHANNEL_ID' => $yourChannelId,
    //     'TITLE'      => "Опрос для машины #{$carId}",
    //     'DESCRIPTION'=> "Ваше мнение о машине №{$carId}",
    //     // …другие параметры…
    // ]);
    //
    // После этого вы можете сохранить $resp['result']['ID'] в отдельное поле, если нужен уникальный VOTE_ID.

    // Обновляем флаг в БД
    $upd = $pdo->prepare("UPDATE cars SET POLL_CREATED = 1 WHERE ID_CAR = :carId");
    $upd->execute([':carId' => $carId]);
}

// 6) Перенаправляем пользователя на страницу голосования
//    Здесь VOTE_ID=3 — ваш общий опрос, car_id пойдёт GET-параметром
   LocalRedirect("/local/qr-opros/vote.php?car_id={$carId}");
?>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>