<?php if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die(); ?>
<div class="demo-items-list">
    <? foreach ($arResult['ITEMS'] as $arItem): ?>
        <a href="<?=$arItem['DETAIL_PAGE_URL']?>"><?=$arItem['NAME']?></a><br/>
        <input type="checkbox" id="demo_checkbox_<?=$arItem['ID']?>"><label for="demo_checkbox_<?=$arItem['ID']?>"></label>
        <pre><? var_dump($arItem); ?></pre>
    <? endforeach; ?>
</div>
<?=$arResult['NAV_STRING']?>

<style>
.demo-items-list input[type="checkbox"] {
    display: none;
}

.demo-items-list pre {
    display: none;
}

.demo-items-list input[type="checkbox"] + label::before {
    content: 'Развернуть';
}

.demo-items-list input[type="checkbox"]:checked + label::before {
    content: 'Свернуть';
}

.demo-items-list input[type="checkbox"]:checked + label + pre {
    display: block;
}
</style>
