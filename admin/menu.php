<?php
// @todo тут нужно сделать корректное определение урла
$pathAdmin = '/local/modules/intensa.logger/admin/index.php';
$menu = array(
    "parent_menu" => "global_menu_services",
    "url" => $pathAdmin,
    "sort" => 1,
    "text" => 'Intensa Logger',
    "icon" => "form_menu_icon",
    "page_icon" => "form_menu_icon",
    "items_id" => "menu_intensa_logger",
    "items" => array(),
);

return $menu;