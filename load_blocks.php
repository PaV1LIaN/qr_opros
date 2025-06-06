<?php
$dsn = 'mysql:host=localhost;dbname=qr_opros';
$username = 'root';
$password = 'root';

$pdo = new PDO($dsn, $username, $password);
$blocks = $pdo->query("SELECT ID_CAR, NAME_CAR, TYPE_CAR, NOM_CAR FROM cars")->fetchAll(PDO::FETCH_ASSOC);

foreach($blocks as $block) {
    echo '<div class="block" data-id="' . $block['ID_CAR'] . '">';
    echo '<h4>' . htmlspecialchars($block['NAME_CAR']) . '</h4>';
    echo '<p>' . htmlspecialchars($block['TYPE_CAR']) . '</p>';
	echo '<p>' . htmlspecialchars($block['NOM_CAR']) . '</p>';
	echo '<a href="application/index.php?block_id=' . $block['ID_CAR'] . '" class="order-btn">Оформить</a><br />';
    echo '<div class="remove-btn">Деактивировать</div>';
    echo '</div>';
}
?>