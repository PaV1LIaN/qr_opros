<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Title");
?>
<?php
<?php
// /local/qr-opros/result.php

// 1) Подключаем пролог Битрикса, чтобы были доступны все C- и API-функции
require_once $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php';

// 2) Подключаем REST-модуль (чтобы класс CRest стал доступен)
if (!CModule::IncludeModule('rest')) {
    ShowError('Не удалось подключить REST-модуль');
    exit;
}

// 3) Явно подгружаем файл, где объявлен класс CRest
//    В большинстве версий Битрикса это /bitrix/modules/rest/general/rest.php
require_once $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/rest/general/rest.php';

// 4) Валидируем car_id из GET
$carId = filter_input(INPUT_GET, 'car_id', FILTER_VALIDATE_INT);
if (!$carId) {
    ShowError('Неверный идентификатор автомобиля');
    exit;
}

// 5) Делаем REST-запрос за голосами
try {
    $response = CRest::call(
        'vote.vote.get',
        [
            'VOTE_ID'  => 3,
            'arFilter' => ['=PARAMS.CAR_ID' => $carId],
        ]
    );
} catch (Exception $e) {
    ShowError('Ошибка REST-запроса: ' . $e->getMessage());
    exit;
}

$votes = $response['result'] ?? [];

// 6) Считаем голоса по вариантам
$results = [];
foreach ($votes as $v) {
    $ansId = $v['ID_ANSWER'];
    $results[$ansId] = ($results[$ansId] ?? 0) + 1;
}

// 7) Выводим HTML
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <title>Результаты опроса — Машина #<?= htmlspecialchars($carId) ?></title>
  <style>
    table { border-collapse: collapse; width: 100%; max-width: 400px; }
    th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
    th { background: #f4f4f4; }
  </style>
</head>
<body>
  <h2>Результаты опроса для машины №<?= htmlspecialchars($carId) ?></h2>
  <?php if (empty($results)): ?>
    <p>Голосов ещё нет.</p>
  <?php else: ?>
    <table>
      <tr><th>Вариант ответа</th><th>Голоса</th></tr>
      <?php foreach ($results as $ans => $cnt): ?>
        <tr>
          <td>Ответ #<?= htmlspecialchars($ans) ?></td>
          <td><?= htmlspecialchars($cnt) ?></td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>
</body>
</html>


<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>