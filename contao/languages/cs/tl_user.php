<?php

use Terminal42\NodeBundle\PermissionChecker;

$GLOBALS['TL_LANG']['tl_user']['nodeMounts'] = ['Nastavení přístupu', 'Zde můžete nastavit přístup k jednomu nebo více prvkům (Podprvky budou automaticky přidány).'];
$GLOBALS['TL_LANG']['tl_user']['nodePermissions'] = ['Přístupová práva', 'Zde můžete nastavit přístupová práva.'];

/*
 * Reference
 */
$GLOBALS['TL_LANG']['tl_user']['nodePermissionsRef'] = [
    PermissionChecker::PERMISSION_CREATE => 'Vytvořit prvky',
    PermissionChecker::PERMISSION_EDIT => 'Upravit prvky',
    PermissionChecker::PERMISSION_DELETE => 'Smazat prvky',
    PermissionChecker::PERMISSION_CONTENT => 'Spravovat prvky',
    PermissionChecker::PERMISSION_ROOT => 'Spravovat klíčové prvky',
];

/*
 * Legends
 */
$GLOBALS['TL_LANG']['tl_user']['node_legend'] = 'Nastavení';
