Супер. **Шаг 7**: делаем “страницы внутри сайта”.

Идея простая:

* у нас уже есть “Сайты”
* теперь для каждого сайта будет список **страниц**
* пока храним страницы тоже в JSON (как и сайты), чтобы отработать механику

Мы сделаем:

1. API: `page.list`, `page.create`, `page.delete`
2. UI: кнопка **“Страницы”** у сайта → открываем модалку со списком страниц этого сайта и кнопкой “Создать страницу”

---

# Шаг 7.1 — API: добавляем страницы (в `api.php`)

## 7.1.1 Добавь функции для pages.json

В `api.php` после `sb_read_sites()` / `sb_write_sites()` добавь 2 функции (они почти такие же, только для `pages.json`):

```php
function sb_read_pages(): array {
    $path = sb_data_path('pages.json');
    if (!file_exists($path)) {
        return [];
    }
    $raw = file_get_contents($path);
    $data = json_decode((string)$raw, true);
    return is_array($data) ? $data : [];
}

function sb_write_pages(array $pages): void {
    $dir = dirname(sb_data_path('pages.json'));
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }

    $path = sb_data_path('pages.json');
    $fp = fopen($path, 'c+');
    if (!$fp) {
        throw new \RuntimeException('Cannot open pages.json');
    }

    if (!flock($fp, LOCK_EX)) {
        fclose($fp);
        throw new \RuntimeException('Cannot lock pages.json');
    }

    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode(array_values($pages), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
}
```

> Объяснение: страницы будут лежать в `/upload/sitebuilder/pages.json`.
> Точно так же безопасно пишем через `flock`.

---

## 7.1.2 Добавь action `page.list`

В `api.php` **после site.delete** (или перед unknown action) вставь:

```php
// --- список страниц сайта ---
if ($action === 'page.list') {
    $siteId = (int)($_POST['siteId'] ?? 0);
    if ($siteId <= 0) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'error' => 'SITE_ID_REQUIRED'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $pages = sb_read_pages();

    // фильтруем по siteId
    $pages = array_values(array_filter($pages, fn($p) => (int)($p['siteId'] ?? 0) === $siteId));

    usort($pages, fn($a, $b) => (int)($a['id'] ?? 0) <=> (int)($b['id'] ?? 0));

    echo json_encode(['ok' => true, 'pages' => $pages], JSON_UNESCAPED_UNICODE);
    exit;
}
```

---

## 7.1.3 Добавь action `page.create`

Сразу после `page.list` вставь:

```php
// --- создать страницу ---
if ($action === 'page.create') {
    $siteId = (int)($_POST['siteId'] ?? 0);
    $title  = trim((string)($_POST['title'] ?? ''));
    $slugIn = trim((string)($_POST['slug'] ?? ''));

    if ($siteId <= 0) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'error' => 'SITE_ID_REQUIRED'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    if ($title === '') {
        http_response_code(422);
        echo json_encode(['ok' => false, 'error' => 'TITLE_REQUIRED'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Генерим slug из title, если не дали
    $slug = $slugIn !== '' ? sb_slugify($slugIn) : sb_slugify($title);

    $pages = sb_read_pages();

    // Новый id = max(id)+1 (общий для всех страниц)
    $maxId = 0;
    foreach ($pages as $p) {
        $maxId = max($maxId, (int)($p['id'] ?? 0));
    }
    $id = $maxId + 1;

    // slug должен быть уникальным ВНУТРИ сайта
    $existing = array_map(
        fn($x) => (string)($x['slug'] ?? ''),
        array_filter($pages, fn($p) => (int)($p['siteId'] ?? 0) === $siteId)
    );
    $base = $slug;
    $i = 2;
    while (in_array($slug, $existing, true)) {
        $slug = $base . '-' . $i;
        $i++;
    }

    $page = [
        'id' => $id,
        'siteId' => $siteId,
        'title' => $title,
        'slug' => $slug,
        'parentId' => 0,        // пока без дерева
        'sort' => 500,          // пока фикс
        'createdBy' => (int)$USER->GetID(),
        'createdAt' => date('c'),
    ];

    $pages[] = $page;
    sb_write_pages($pages);

    echo json_encode(['ok' => true, 'page' => $page], JSON_UNESCAPED_UNICODE);
    exit;
}
```

---

## 7.1.4 Добавь action `page.delete`

После `page.create` вставь:

```php
// --- удалить страницу ---
if ($action === 'page.delete') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'error' => 'ID_REQUIRED'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $pages = sb_read_pages();

    $before = count($pages);
    $pages = array_values(array_filter($pages, fn($p) => (int)($p['id'] ?? 0) !== $id));
    $after = count($pages);

    if ($after === $before) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'NOT_FOUND'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    sb_write_pages($pages);

    echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
    exit;
}
```

---

# Шаг 7.2 — UI: кнопка “Страницы” у каждого сайта (в `index.php`)

## 7.2.1 В таблице сайтов добавь кнопку “Страницы”

В `renderSites` в колонке “Действия” замени содержимое на две кнопки:

```html
<td style="padding:8px;border-bottom:1px solid #eee;">
  <button class="ui-btn ui-btn-light ui-btn-xs" data-open-pages-site-id="${s.id}" data-open-pages-site-name="${BX.util.htmlspecialchars(s.name)}">Страницы</button>
  <button class="ui-btn ui-btn-danger ui-btn-xs" data-delete-site-id="${s.id}">Удалить</button>
</td>
```

---

## 7.2.2 Добавь обработчик “Страницы”

В JS (внутри `BX.ready`) добавь ещё одно делегирование:

```js
document.addEventListener('click', function (e) {
  const btn = e.target.closest('[data-open-pages-site-id]');
  if (!btn) return;

  const siteId = parseInt(btn.getAttribute('data-open-pages-site-id'), 10);
  const siteName = btn.getAttribute('data-open-pages-site-name') || ('ID ' + siteId);

  openPagesDialog(siteId, siteName);
});
```

И добавь функции `openPagesDialog`, `loadPages`, `renderPages`, `createPage` (ниже). Вставь их рядом с другими функциями (после `loadSites()` удобно):

```js
function renderPages(container, pages) {
  if (!pages || !pages.length) {
    container.innerHTML = '<div style="color:#6a737f;">Страниц пока нет.</div>';
    return;
  }

  const rows = pages.map(p => `
    <tr>
      <td style="padding:8px;border-bottom:1px solid #eee;">${p.id}</td>
      <td style="padding:8px;border-bottom:1px solid #eee;">${BX.util.htmlspecialchars(p.title)}</td>
      <td style="padding:8px;border-bottom:1px solid #eee;"><code>${BX.util.htmlspecialchars(p.slug)}</code></td>
      <td style="padding:8px;border-bottom:1px solid #eee;">
        <button class="ui-btn ui-btn-danger ui-btn-xs" data-delete-page-id="${p.id}" data-delete-page-site-id="${p.siteId}">Удалить</button>
      </td>
    </tr>
  `).join('');

  container.innerHTML = `
    <table style="width:100%; border-collapse:collapse; margin-top:6px;">
      <thead>
        <tr>
          <th style="text-align:left;padding:8px;border-bottom:1px solid #eee;">ID</th>
          <th style="text-align:left;padding:8px;border-bottom:1px solid #eee;">Заголовок</th>
          <th style="text-align:left;padding:8px;border-bottom:1px solid #eee;">Slug</th>
          <th style="text-align:left;padding:8px;border-bottom:1px solid #eee;">Действия</th>
        </tr>
      </thead>
      <tbody>${rows}</tbody>
    </table>
  `;
}

function loadPages(siteId, container) {
  api('page.list', { siteId }).then(res => {
    if (!res || res.ok !== true) {
      BX.UI.Notification.Center.notify({ content: 'Не удалось загрузить страницы' });
      return;
    }
    renderPages(container, res.pages);
  }).catch(() => {
    BX.UI.Notification.Center.notify({ content: 'Ошибка запроса page.list' });
  });
}

function openPagesDialog(siteId, siteName) {
  const html = `
    <div>
      <div style="margin-bottom:10px;">
        <button class="ui-btn ui-btn-primary ui-btn-xs" id="btnCreatePage">Создать страницу</button>
      </div>
      <div id="pagesBox"></div>
    </div>
  `;

  BX.UI.Dialogs.MessageBox.show({
    title: 'Страницы сайта: ' + BX.util.htmlspecialchars(siteName),
    message: html,
    buttons: BX.UI.Dialogs.MessageBoxButtons.CLOSE,
    onShow: function () {
      const container = document.getElementById('pagesBox');
      loadPages(siteId, container);

      document.getElementById('btnCreatePage').addEventListener('click', function () {
        openCreatePageDialog(siteId, container);
      });
    }
  });
}

function openCreatePageDialog(siteId, pagesContainer) {
  const formHtml = `
    <div style="display:flex; flex-direction:column; gap:10px;">
      <div>
        <div style="font-size:12px;color:#6a737f;margin-bottom:4px;">Заголовок страницы</div>
        <input type="text" id="pg_title"
          style="width:100%; padding:8px; border:1px solid #d0d7de; border-radius:8px;"
          placeholder="Например: Главная" />
      </div>
      <div>
        <div style="font-size:12px;color:#6a737f;margin-bottom:4px;">Slug (необязательно)</div>
        <input type="text" id="pg_slug"
          style="width:100%; padding:8px; border:1px solid #d0d7de; border-radius:8px;"
          placeholder="home (если пусто — сделаем автоматически)" />
      </div>
    </div>
  `;

  BX.UI.Dialogs.MessageBox.show({
    title: 'Создать страницу',
    message: formHtml,
    buttons: BX.UI.Dialogs.MessageBoxButtons.OK_CANCEL,
    onOk: function (mb) {
      const title = document.getElementById('pg_title')?.value?.trim() || '';
      const slug  = document.getElementById('pg_slug')?.value?.trim() || '';

      if (!title) {
        BX.UI.Notification.Center.notify({ content: 'Введите заголовок страницы' });
        return;
      }

      api('page.create', { siteId, title, slug }).then(res => {
        if (!res || res.ok !== true) {
          BX.UI.Notification.Center.notify({ content: 'Не удалось создать страницу' });
          return;
        }
        BX.UI.Notification.Center.notify({
          content: `Страница создана: ${BX.util.htmlspecialchars(res.page.title)} (${BX.util.htmlspecialchars(res.page.slug)})`
        });
        mb.close();
        loadPages(siteId, pagesContainer);
      }).catch(() => {
        BX.UI.Notification.Center.notify({ content: 'Ошибка запроса page.create' });
      });
    }
  });
}

// удаление страницы (делегирование)
document.addEventListener('click', function (e) {
  const btn = e.target.closest('[data-delete-page-id]');
  if (!btn) return;

  const id = parseInt(btn.getAttribute('data-delete-page-id'), 10);
  const siteId = parseInt(btn.getAttribute('data-delete-page-site-id'), 10);
  if (!id || !siteId) return;

  BX.UI.Dialogs.MessageBox.show({
    title: 'Удалить страницу?',
    message: 'Продолжить?',
    buttons: BX.UI.Dialogs.MessageBoxButtons.OK_CANCEL,
    onOk: function (mb) {
      api('page.delete', { id }).then(res => {
        if (!res || res.ok !== true) {
          BX.UI.Notification.Center.notify({ content: 'Не удалось удалить страницу' });
          return;
        }
        BX.UI.Notification.Center.notify({ content: 'Страница удалена' });
        mb.close();

        // если модалка со страницами ещё открыта — обновим список
        const pagesBox = document.getElementById('pagesBox');
        if (pagesBox) loadPages(siteId, pagesBox);
      }).catch(() => {
        BX.UI.Notification.Center.notify({ content: 'Ошибка запроса page.delete' });
      });
    }
  });
});
```

---

## Проверка шага 7

1. Обнови страницу `/local/sitebuilder/index.php`
2. В таблице сайтов нажми **“Страницы”**
3. Откроется модалка, там нажми **“Создать страницу”**
4. Создай пару страниц → появятся в списке, сохранятся в `/upload/sitebuilder/pages.json`
5. Удаление страницы — работает

---

Когда это заработает, **Шаг 8** будет: сделать “просмотр страницы сайта” (роут по `siteSlug/pageSlug` пока без красивой ссылки) и начать добавлять блоки.
Но сначала напиши: шаг 7 сделал — страницы создаются/показываются?
