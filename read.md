Никаких сторонних «FormDesigner»-приложений ставить не нужно – достаточно штатного модуля Веб-формы, который уже есть в Сервисы → Веб-формы. Для реализации опросов используем компонент bitrix:form.result.new и скрытое поле CAR_ID, чтобы у каждого автомобиля была своя статистика.

## Кратко

1. Модуль «Веб-формы» уже установлен и включает в себя компонент form.result.new для отображения и сохранения ответов пользователя ([dev.1c-bitrix.ru][1]).
2. Создаём форму в Сервисы → Веб-формы, добавляем там все вопросы и поле «CAR\_ID» (тип «Скрытое поле») ([dev.1c-bitrix.ru][2]).
3. На странице опроса выводим компонент bitrix:form.result.new, передавая в параметре WEB_FORM_ID ID формы и через INITIAL_VALUES['CAR_ID'] текущий ID автомобиля.
4. Для просмотра статистики в админке фильтруем результаты по полю CAR_ID или на фронте через form.result.list ([dev.1c-bitrix.ru][1]).

---

## 1. Проверяем модуль «Веб-формы»

Убедитесь, что модуль включён в Настройки продукта → Модули → Веб-формы. Он даёт следующие компоненты ([dev.1c-bitrix.ru][1]):

* bitrix:form.result.new – вывод и сохранение формы
* bitrix:form.result.list – вывод списка ответов
* bitrix:form.result.view – просмотр конкретного результата

---

## 2. Создаём форму с полем CAR\_ID

1. Зайдите в Сервисы → Веб-формы и нажмите Добавить форму.
2. В настройках формы добавьте основные вопросы опроса.
3. Добавьте скрытое поле с кодом CAR_ID (тип «Скрытое поле») – именно по нему будем фильтровать ответы ([dev.1c-bitrix.ru][2]).
4. Сохраните и запишите ID созданной формы (например, $formId = 5).

---

## 3. Встраиваем форму на страницу опроса

На странице /cars/survey.php разместите:

<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
use Bitrix\Main\Loader;
Loader::includeModule("form");

$carId  = intval($_GET["CAR_ID"]);  // получаем ID машины из URL
$formId = 5;                       // замените на ваш ID формы

$APPLICATION->SetTitle("Опрос для автомобиля № ".$carId);

// 1) Вывод формы
$APPLICATION->IncludeComponent(
  "bitrix:form.result.new",
  "",
  [
    "WEB_FORM_ID"            => $formId,
    "IGNORE_CUSTOM_TEMPLATE" => "Y",
    "SEF_MODE"               => "N",
    "SUCCESS_URL"            => "/cars/survey.php?CAR_ID={$carId}&show_stats=Y",
    "VARIABLE_ALIASES"       => [],
    "INITIAL_VALUES"         => ["CAR_ID" => $carId]
  ],
  false
);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");
?>
* Параметр INITIAL_VALUES (или DEFAULT_FIELDS в старых версиях) предзаполняет скрытое поле CAR_ID значением текущей машины ([dev.1c-bitrix.ru][3]).

---

## 4. Просмотр и фильтрация результатов

### В админке

1. Сервисы → Веб-формы → Результаты.
2. В фильтре укажите поле CAR\_ID = нужный ID – увидите только ответы по этой машине.

### На фронте (опционально)

После отправки формы вы можете на той же странице подключить form.result.list:

<?php if ($_GET["show_stats"] === "Y"): ?>
  <? $APPLICATION->IncludeComponent(
       "bitrix:form.result.list",
       "",
       [
         "WEB_FORM_ID" => $formId,
         "FILTER"      => ["=PROPERTY_CAR_ID" => $carId],
         "AJAX_MODE"   => "Y"
       ],
       false
     );
  ?>
<?php endif; ?>
Это выведет таблицу всех ответов для текущего CAR_ID ([dev.1c-bitrix.ru][1]).

---

Итог: не нужен никакой сторонний «FormDesigner» – всё делается штатным модулем Веб-форм и стандартными компонентами form.result.new и form.result.list. Если что-то осталось неясно, уточните, какой шаг вызывает затруднения!

[1]: https://dev.1c-bitrix.ru/user_help/components/services/web_forms/index.php?utm_source=chatgpt.com "Веб-формы - 1С-Битрикс"
[2]: https://dev.1c-bitrix.ru/user_help/components/services/web_forms/form_result_new.php?utm_source=chatgpt.com "Заполнение веб-формы - 1С-Битрикс"
[3]: https://dev.1c-bitrix.ru/community/forums/forum6/topic130222/?utm_source=chatgpt.com "Предзаполнение полей веб формы form.result.new с ..."