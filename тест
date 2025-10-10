<?php
// --- Ранняя обработка AJAX без header.php ---
if (isset($_POST['AJAX']) && $_POST['AJAX'] === 'UPDATE_ROLE') {
    require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");
    global $USER;

    if ($USER->IsAdmin() && check_bitrix_sessid() && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $roleId    = (int)($_POST['ROLE_ID'] ?? 0);
        $roleUsers = array_map('intval', (array)($_POST['ROLE_USERS'] ?? []));

        // Текущие участники роли
        $currentUsers = [];
        $by = "id"; $order = "asc";
        $rs = \CUser::GetList($by, $order, ["GROUPS_ID" => [$roleId]], ["FIELDS" => ["ID"]]);
        while ($u = $rs->Fetch()) {
            $currentUsers[] = (int)$u["ID"];
        }

        // Кого добавить / убрать
        $toAdd    = array_diff($roleUsers, $currentUsers);
        $toRemove = array_diff($currentUsers, $roleUsers);

        foreach ($toAdd as $uid) {
            $uid = (int)$uid;
            $groups = array_map('intval', (array)\CUser::GetUserGroup($uid));
            if (!in_array($roleId, $groups, true)) {
                $groups[] = $roleId;
                \CUser::SetUserGroup($uid, $groups);
            }
        }

        foreach ($toRemove as $uid) {
            $uid = (int)$uid;
            $groups = array_map('intval', (array)\CUser::GetUserGroup($uid));
            $groups = array_values(array_diff($groups, [$roleId]));
            \CUser::SetUserGroup($uid, $groups);
        }

        // Имена для ответа
        $names = [];
        if (!empty($roleUsers)) {
            $ids = implode("|", array_map('intval', $roleUsers));
            $rsU = \CUser::GetList($by, $order, ["ID" => $ids], ["FIELDS" => ["ID","NAME","LAST_NAME","LOGIN"]]);
            while ($u = $rsU->Fetch()) {
                $title = trim($u["NAME"]." ".$u["LAST_NAME"]);
                if ($title === "") { $title = $u["LOGIN"]; }
                $names[] = $title;
            }
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(["status" => "success", "users" => $names], JSON_UNESCAPED_UNICODE);
        die();
    }

    // Недостаточно прав/сессии
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(["status" => "error"], JSON_UNESCAPED_UNICODE);
    die();
}

// --- Обычный рендер страницы ---
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

use Bitrix\Main\Loader;
use PortalHub\Manager as PH;

global $USER;

Loader::includeModule("iblock");
require_once __DIR__ . "/lib/functions.php";

// Обработка создания сайта (POST)
if (
    $USER->IsAdmin() &&
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    check_bitrix_sessid() &&
    ($_POST["create_site"] ?? "") === "Y"
) {
    $siteTitle = trim((string)($_POST["site_title"] ?? ""));
    $siteCode  = trim((string)($_POST["site_code"] ?? ""));
    $roleName  = trim((string)($_POST["role_name"] ?? ""));

    $roleUsersRaw = trim((string)($_POST["role_users"] ?? ""));
    $roleUsers = $roleUsersRaw !== '' ? array_map('intval', explode(',', $roleUsersRaw)) : [];

    $result = PH::createPortalSite($siteTitle, $siteCode, $roleName, $roleUsers);

    $removeAck = ["create_site","created","warning","error","exists_code"];

    if (!empty($result["success"])) {
        LocalRedirect($APPLICATION->GetCurPageParam("created=" . rawurlencode($siteTitle), $removeAck));
        die();
    } elseif (($result["status"] ?? "") === "exists") {
        $q = "warning=" . rawurlencode($result["message"]);
        if (!empty($result["code"])) {
            $q .= "&exists_code=" . rawurlencode((string)$result["code"]);
        }
        LocalRedirect($APPLICATION->GetCurPageParam($q, $removeAck));
        die();
    } else {
        LocalRedirect($APPLICATION->GetCurPageParam("error=" . rawurlencode($result["message"]), $removeAck));
        die();
    }
}

// Данные для списка
$userGroups = array_map('intval', (array)$USER->GetUserGroupArray());
$sites = PH::getPortalSites($userGroups);

// Вывод шаблонов
require __DIR__ . "/templates/form.php";
require __DIR__ . "/templates/list.php";

// --- Показ уведомлений (успех, предупреждение, ошибка) ---
$type = '';
$data = '';
$existsCode = '';

if (!empty($_GET['error'])) {
    $type = 'error';
    $data = $_GET['error'];
} elseif (!empty($_GET['warning'])) {
    $type = 'warning';
    $data = $_GET['warning'];
    $existsCode = $_GET['exists_code'] ?? '';
} elseif (!empty($_GET['created'])) {
    $type = 'success';
    $data = $_GET['created'];
}

if ($type !== ''): ?>
<script>
BX.ready(function() {
    var type = '<?= CUtil::JSEscape($type) ?>';
    var data = '<?= CUtil::JSEscape($data) ?>';
    var existsCode = '<?= CUtil::JSEscape($existsCode) ?>';

    var content = '';
    var opts = { autoHideDelay: 6000, position: 'top-right', category: 'portal_hub' };

    if (type === 'success') {
        content = '✅ Сайт <b>' + BX.util.htmlspecialchars(data) + '</b> успешно создан!';
    } else if (type === 'warning') {
        content = '⚠️ ' + BX.util.htmlspecialchars(data);
        if (existsCode) {
            var url = '/local/portal_hub/sites/' + existsCode + '/';
            content += '<br><a class="ui-link ui-link-primary" href="'+url+'" target="_blank">Открыть существующий сайт</a>';
        }
        opts.color = 'warning';
    } else if (type === 'error') {
        content = '❌ Ошибка: <b>' + BX.util.htmlspecialchars(data) + '</b>';
        opts.color = 'danger';
    }

    BX.UI.Notification.Center.notify(Object.assign({ content: content }, opts));

    // Очистка URL от служебных параметров
    try {
        var u = new URL(window.location.href);
        ['created','warning','error','exists_code'].forEach(function(p){ u.searchParams.delete(p); });
        var cleaned = u.pathname + (u.searchParams.toString() ? '?' + u.searchParams.toString() : '') + u.hash;
        window.history.replaceState(null, '', cleaned);
    } catch (e) {}
});
</script>
<?php endif; ?>

<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");
