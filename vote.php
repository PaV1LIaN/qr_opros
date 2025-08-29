<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Опрос");
?>   
<?php
   // /local/qr-opros/vote.php
   require_once $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php';
   $voteId = 3;           // ваш ID опроса
   $carId  = (int)$_GET['car_id'];

   $APPLICATION->IncludeComponent(
     "bitrix:voting.current",
     "",
     [
       "VOTE_ID"            => $voteId,
       "VOTE_UNIQUE_TYPE"   => "IP",
       "CACHE_TYPE"         => "A",
       "CACHE_TIME"         => "3600",
       // чтобы после голосования можно было уйти на страницу с результатами
       "VOTE_RESULT_TEMPLATE" => "/local/qr-opros/result.php?car_id={$carId}"
     ],
     false
   );
   require_once $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php';
?>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>