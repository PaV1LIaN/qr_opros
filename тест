<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Доступы к сайту");

\Bitrix\Main\UI\Extension::load("ui.entity-selector");

use Bitrix\Main\Context;

require_once $_SERVER["DOCUMENT_ROOT"] . "/local/typical_sites/lib/SiteService.php";

global $USER;
$request = Context::getCurrent()->getRequest();

$code = trim((string)$request->getQuery('code'));
if ($code === '') {
    ShowError("Не указан code");
    require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");
    exit;
}

if (!$USER->IsAuthorized()) {
    ShowError("Требуется авторизация");
    require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");
    exit;
}

$service = new \Cc10\SiteService();
$site = $service->getSiteByCode($code);

if (!$site) {
    ShowError("Сайт не найден или не активен");
    require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");
    exit;
}

$siteId = (int)$site['ID'];
$role = $service->getUserRole((int)$USER->GetID(), $siteId);

if (!$USER->IsAdmin() && !in_array($role, ['ADMIN','DEVELOPER'], true)) {
    ShowError("Нет прав на управление доступами");
    require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");
    exit;
}

$canManageDevelopers = ($USER->IsAdmin() || $role === 'DEVELOPER');

$errors = [];

$canManageDevelopers = ($USER->IsAdmin() || $role === 'DEVELOPER');

if ($request->isPost() && check_bitrix_sessid()) {
    $viewers = array_unique(array_filter(array_map('intval', (array)$request->getPost('VIEWERS'))));
    $admins  = array_unique(array_filter(array_map('intval', (array)$request->getPost('ADMINS'))));
    $devsReq = array_unique(array_filter(array_map('intval', (array)$request->getPost('DEVELOPERS'))));

    // Текущие dev'ы из базы
    $access = $service->getAccessForSite($siteId);
    $devsCurrent = [];
    foreach ($access as $row) {
        if (($row['UF_ROLE'] ?? '') === 'DEVELOPER') {
            $devsCurrent[] = (int)$row['UF_USER'];
        }
    }
    $devsCurrent = array_values(array_unique($devsCurrent));

    // ADMIN не имеет права менять DEVELOPERS — сохраняем как было
    $devs = $canManageDevelopers ? $devsReq : $devsCurrent;

    // Защита: нельзя оставить сайт без DEVELOPER
    if (count($devs) === 0) {
        $errors[] = "Нельзя убрать последнего DEVELOPER. У сайта должен быть хотя бы один DEVELOPER.";
    }

    // Защита: нельзя потерять себе управление
    $me = (int)$USER->GetID();

    if (!$USER->IsAdmin()) {
        // если ты DEVELOPER — должен оставаться DEVELOPER или ADMIN
        if ($role === 'DEVELOPER') {
            if (!in_array($me, $devs, true) && !in_array($me, $admins, true)) {
                $errors[] = "Нельзя убрать себе DEVELOPER/ADMIN на этом сайте";
            }
        }
        // если ты ADMIN — должен оставаться ADMIN
        if ($role === 'ADMIN') {
            if (!in_array($me, $admins, true)) {
                $errors[] = "Нельзя убрать себе ADMIN на этом сайте";
            }
        }
    }

    // Если ADMIN попытался подменить devs через POST — просто игнорируем
    // (мы уже сделали $devs = $devsCurrent), можно ещё и сообщение показать при желании.

    if (empty($errors)) {
        try {
            $service->setSiteAccess($siteId, $viewers, $admins, $devs);
            $service->logAction($siteId, (int)$USER->GetID(), 'ACCESS_UPDATE', 'access', 0, [
                'viewers' => $viewers,
                'admins' => $admins,
                'developers' => $devs,
            ]);
            LocalRedirect('/local/typical_sites/site.php?code=' . urlencode($code));
        } catch (\Throwable $e) {
            $errors[] = $e->getMessage();
        }
    }
}

// текущие права
$access = $service->getAccessForSite($siteId);
$viewersInit = [];
$adminsInit  = [];
$devsInit    = [];

foreach ($access as $row) {
    if ($row['UF_ROLE'] === 'VIEWER') $viewersInit[] = (int)$row['UF_USER'];
    if ($row['UF_ROLE'] === 'ADMIN')  $adminsInit[]  = (int)$row['UF_USER'];
    if ($row['UF_ROLE'] === 'DEVELOPER') $devsInit[] = (int)$row['UF_USER'];
}
?>

<h1>Доступы: <?= htmlspecialcharsbx((string)$site['UF_NAME']) ?></h1>

<p>
    <a href="/local/typical_sites/site.php?code=<?= urlencode($code) ?>">← Назад к сайту</a>
</p>

<?php if (!empty($errors)): ?>
    <div style="color:red;">
        <?php foreach ($errors as $e): ?>
            <div><?= htmlspecialcharsbx($e) ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<form method="post" id="ts-access-form">
    <?= bitrix_sessid_post() ?>

    <div style="margin-top:10px;">
        <label>VIEWERS:</label><br>
        <div id="ts-viewers"></div>
        <div id="ts-viewers-inputs"></div>
    </div>

    <div style="margin-top:10px;">
        <label>ADMINS:</label><br>
        <div id="ts-admins"></div>
        <div id="ts-admins-inputs"></div>
    </div>

    <?php if ($canManageDevelopers): ?>
        <div style="margin-top:10px;">
            <label>DEVELOPERS:</label><br>
            <div id="ts-devs"></div>
            <div id="ts-devs-inputs"></div>
            <small>Осторожно: Developer = полный доступ к управлению.</small>
        </div>
    <?php else: ?>
        <div style="margin-top:10px; padding:10px; background:#fff3cd; border:1px solid #ffeeba;">
            DEVELOPERS может менять только DEVELOPER или глобальный админ.
        </div>
    <?php endif; ?>

    <div style="margin-top:15px;">
        <button type="submit">Сохранить доступы</button>
    </div>
</form>

<script>
BX.ready(function () {
  function renderHidden(selector, inputsId, name) {
    const box = document.getElementById(inputsId);
    box.innerHTML = "";
    selector.getTags().forEach(tag => {
      if (tag.getEntityId && tag.getEntityId() === "user") {
        const id = parseInt(tag.getId(), 10);
        if (!id) return;
        const input = document.createElement("input");
        input.type = "hidden";
        input.name = name + "[]";
        input.value = id;
        box.appendChild(input);
      }
    });
  }

  function make(renderId, inputsId, name, context, initIds) {
    const selector = new BX.UI.EntitySelector.TagSelector({
      id: renderId,
      multiple: true,
      dialogOptions: {
        context: context,
        entities: [{ id: "user" }],
        multiple: true,
        enableSearch: true
      },
      events: {
        onAfterTagAdd: () => renderHidden(selector, inputsId, name),
        onAfterTagRemove: () => renderHidden(selector, inputsId, name),
      }
    });

    selector.renderTo(document.getElementById(renderId));

    (initIds || []).forEach(id => {
      selector.addTag({
        id: String(id),
        entityId: "user",
        title: "user#" + id
      });
    });

    renderHidden(selector, inputsId, name);
    return selector;
  }

  const viewersInit = <?= json_encode($viewersInit) ?>;

  const adminsInit  = <?= json_encode($adminsInit) ?>;
  <?php if ($canManageDevelopers): ?>
  const devsInit    = <?= json_encode($devsInit) ?>;
  const devsSel    = make("ts-devs",    "ts-devs-inputs",    "DEVELOPERS", "TS_DEVS_ACCESS", devsInit);
  <?php endif; ?>
  

  const viewersSel = make("ts-viewers", "ts-viewers-inputs", "VIEWERS", "TS_VIEWERS_ACCESS", viewersInit);
  const adminsSel  = make("ts-admins",  "ts-admins-inputs",  "ADMINS",  "TS_ADMINS_ACCESS", adminsInit);
  

  document.getElementById("ts-access-form").addEventListener("submit", function () {
    renderHidden(viewersSel, "ts-viewers-inputs", "VIEWERS");
    renderHidden(adminsSel,  "ts-admins-inputs",  "ADMINS");
    renderHidden(devsSel,    "ts-devs-inputs",    "DEVELOPERS");
  });
});
</script>

<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");
