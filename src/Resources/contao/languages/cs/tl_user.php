<?php

/*
 * Node Bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2019, terminal42 gmbh
 * @author     terminal42 <https://terminal42.ch>
 * @license    MIT
 */

$GLOBALS['TL_LANG']['tl_user']['nodeMounts'] = ['Nastavení přístupu', 'Zde můžete nastavit přístup k jednomu nebo více prvkům (Podprvky budou automaticky přidány).'];
$GLOBALS['TL_LANG']['tl_user']['nodePermissions'] = ['Přístupová práva', 'Zde můžete nastavit přístupová práva.'];

/*
 * Reference
 */
$GLOBALS['TL_LANG']['tl_user']['nodePermissionsRef'] = [
    \Terminal42\NodeBundle\PermissionChecker::PERMISSION_CREATE => 'Vytvořit prvky',
    \Terminal42\NodeBundle\PermissionChecker::PERMISSION_EDIT => 'Upravit prvky',
    \Terminal42\NodeBundle\PermissionChecker::PERMISSION_DELETE => 'Smazat prvky',
    \Terminal42\NodeBundle\PermissionChecker::PERMISSION_CONTENT => 'Spravovat prvky',
    \Terminal42\NodeBundle\PermissionChecker::PERMISSION_ROOT => 'Spravovat klíčové prvky',
];

/*
 * Legends
 */
$GLOBALS['TL_LANG']['tl_user']['node_legend'] = 'Nastavení';
