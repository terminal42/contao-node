<?php

/*
 * Node Bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2019, terminal42 gmbh
 * @author     terminal42 <https://terminal42.ch>
 * @license    MIT
 */

$GLOBALS['TL_LANG']['tl_user']['nodeMounts'] = ['Node-Mounts', 'Hier können Sie den Zugriff auf ein oder mehrere Nodes gewähren (Subnodes werden automatisch inkludiert).'];
$GLOBALS['TL_LANG']['tl_user']['nodePermissions'] = ['Node-Rechte', 'Hier können Sie die Node Rechte einstellen.'];

/*
 * Reference
 */
$GLOBALS['TL_LANG']['tl_user']['nodePermissionsRef'] = [
    \Terminal42\NodeBundle\PermissionChecker::PERMISSION_CREATE => 'Nodes erstellen',
    \Terminal42\NodeBundle\PermissionChecker::PERMISSION_EDIT => 'Nodes bearbeiten',
    \Terminal42\NodeBundle\PermissionChecker::PERMISSION_DELETE => 'Nodes löschen',
    \Terminal42\NodeBundle\PermissionChecker::PERMISSION_CONTENT => 'Nodes verwalten',
    \Terminal42\NodeBundle\PermissionChecker::PERMISSION_ROOT => 'Root Nodes verwalten',
];

/*
 * Legends
 */
$GLOBALS['TL_LANG']['tl_user']['node_legend'] = 'Node Einstellungen';
