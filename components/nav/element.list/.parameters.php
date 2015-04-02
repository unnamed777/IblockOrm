<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if(!CModule::IncludeModule("iblock"))
    return;

$arTypesEx = CIBlockParameters::GetIBlockTypes(Array("-"=>" "));

$arIBlocks=Array();
$db_iblock = CIBlock::GetList(Array("SORT"=>"ASC"), Array("SITE_ID"=>$_REQUEST["site"], "TYPE" => ($arCurrentValues["IBLOCK_TYPE"]!="-"?$arCurrentValues["IBLOCK_TYPE"]:"")));
while($arRes = $db_iblock->Fetch())
    $arIBlocks[$arRes["ID"]] = $arRes["NAME"];

$arComponentParameters = array(
    "GROUPS" => array(
    ),
    "PARAMETERS" => array(
        "IBLOCK_TYPE" => Array(
            "PARENT" => "BASE",
            "NAME" => 'Тип информационного блока (используется только для проверки)',
            "TYPE" => "LIST",
            "VALUES" => $arTypesEx,
            "DEFAULT" => "",
            "REFRESH" => "Y",
        ),
        "IBLOCK_ID" => Array(
            "PARENT" => "BASE",
            "NAME" => 'Код информационного блока',
            "TYPE" => "LIST",
            "VALUES" => $arIBlocks,
            "DEFAULT" => '',
            "ADDITIONAL_VALUES" => "Y",
            "REFRESH" => "Y",
        ),
        "COUNT_ON_PAGE" => Array(
            "PARENT" => "BASE",
            "NAME" => 'Количество элементов на странице',
            "TYPE" => "STRING",
            "DEFAULT" => '5',
        ),
        "CACHE_TIME"  =>  Array("DEFAULT"=>3600000),
    ),
);
?>
