Вот как можно привязать каждому автомобилю свой опрос и показывать уникальную статистику:

---

## 1. Создаём веб-форму (опрос) в админке

1. В административной панели сайта перейдите в **Контент → Веб-формы** (или «CRM-формы» в зависимости от вашей сборки).
2. Нажмите **Добавить форму**.

   * **Название**: «Опрос автомобиля»
   * **Символьный код**: `survey_car`
3. В полях формы создайте:

   * Основные вопросы (например, «Оцените состояние автомобиля» и т.п.).
   * Скрытое поле **ID автомобиля** (тип «Скрытое поле», код `CAR_ID`).

> Запомните (или запишите) ID этой формы — далее в примерах будем считать, что это `$formId = 3`.

---

## 2. Добавляем кнопку «Пройти опрос» в шаблон списка машин

В вашем шаблоне `/local/templates/…/components/bitrix/news.list/cars_list/template.php` после вывода данных элемента добавьте:

```php
<a 
  class="btn btn-primary" 
  href="/cars/survey.php?CAR_ID=<?= $item['ID'] ?>"
>
  Пройти опрос
</a>
```

Теперь на каждой карточке будет ссылка вида

```
/cars/survey.php?CAR_ID=123
```

где `123` — ID конкретного элемента инфоблока.

---

## 3. Создаём страницу опроса `/cars/survey.php`

```php
<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
use Bitrix\Main\Loader;

Loader::includeModule("form");            // Модуль веб-форм
Loader::includeModule("iblock");          // Модуль инфоблоков (если понадобятся данные о машине)

$carId  = intval($_REQUEST["CAR_ID"]);    // из URL
$formId = 3;                              // замените на реальный ID формы

$APPLICATION->SetTitle("Опрос для машины № ".$carId);

// 1) Компонент вывода формы
$APPLICATION->IncludeComponent(
  "bitrix:form.result.new",
  "",
  [
    "WEB_FORM_ID"            => $formId,
    "IGNORE_CUSTOM_TEMPLATE" => "Y",
    "SEF_MODE"               => "N",
    "SUCCESS_URL"            => "/cars/survey.php?CAR_ID={$carId}&show_stats=Y",
    "VARIABLE_ALIASES"       => [],
    "LIST_URL"               => "/cars/",
    // Подставляем скрытое значение CAR_ID
    "DEFAULT_FIELDS"         => [
      "CAR_ID" => $carId
    ],
  ],
  false
);

// 2) После успешной отправки (параметр show_stats=Y) — показываем статистику
if ($_REQUEST["show_stats"] === "Y"):

  // Можно вывести общее число ответов
  $rs = CFormResult::GetList(
    $formId,
    $by = "s_id",
    $order = "asc",
    ["CAR_ID" => $carId],
    $isFiltered
  );
  $count = 0;
  while ($rs->Fetch()) { $count++; }
  echo "<p>Всего ответов: <strong>{$count}</strong></p>";

  // Или подключить компонент для детального списка/графиков
  $APPLICATION->IncludeComponent(
    "bitrix:form.result.list",
    "",
    [
      "FORM_ID"       => $formId,
      "FILTER"        => ["=PROPERTY_CAR_ID" => $carId],
      "CHAIN_ITEM_LINK" => "",
      "CHAIN_ITEM_TEXT" => "",
      "AJAX_MODE"     => "Y",
    ],
    false
  );

endif;

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");
```

**Пояснения:**

* При заходе на `/cars/survey.php?CAR_ID=…` пользователь увидит форму, где скрытое поле `CAR_ID` автоматически получит значение текущей машины.
* После отправки его перенаправит на тот же URL с `&show_stats=Y` — и под формой появится статистика именно по этому `CAR_ID`.

---

## 4. Итоговая навигация

1. Пользователь на `/cars/index.php` видит список машин с кнопкой «Пройти опрос».
2. Клик → `/cars/survey.php?CAR_ID=123` — форма опроса для машины № 123.
3. После submit → `/cars/survey.php?CAR_ID=123&show_stats=Y` — форма + статистика ответов только по этой машине.

---

Если нужно добавить более продвинутые графики или фильтрацию, можно подключить сторонние JS-библиотеки или расширить компонент `bitrix:form.result.list`. Дай знать, если нужна помощь с визуализацией!
