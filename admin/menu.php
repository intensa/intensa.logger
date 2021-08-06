<?php
// @todo тут нужно сделать корректное определение урла
$pathAdmin = '/bitrix/admin/intensa_logger.php';
$menu = array(
    "parent_menu" => "global_menu_settings",
    "url" => $pathAdmin,
    "sort" => 1,
    "text" => 'Intensa Logger',
    "icon" => "form_menu_icon",
    "page_icon" => "form_menu_icon",
    "items_id" => "menu_intensa_logger",
    "items" => array(),
);

return $menu;