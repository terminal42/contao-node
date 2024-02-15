<?php

use Terminal42\NodeBundle\PermissionChecker;

$GLOBALS['TL_LANG']['tl_user']['nodeMounts'] = ['Node-Mounts', 'Hier können Sie den Zugriff auf ein oder mehrere Nodes gewähren (Subnodes werden automatisch inkludiert).'];
$GLOBALS['TL_LANG']['tl_user']['nodePermissions'] = ['Node-Rechte', 'Hier können Sie die Node Rechte einstellen.'];

/*
 * Reference
 */
$GLOBALS['TL_LANG']['tl_user']['nodePermissionsRef'] = [
    PermissionChecker::PERMISSION_CREATE => 'Nodes erstellen',
    PermissionChecker::PERMISSION_EDIT => 'Nodes bearbeiten',
    PermissionChecker::PERMISSION_DELETE => 'Nodes löschen',
    PermissionChecker::PERMISSION_CONTENT => 'Nodes verwalten',
    PermissionChecker::PERMISSION_ROOT => 'Root Nodes verwalten',
];

/*
 * Legends
 */
$GLOBALS['TL_LANG']['tl_user']['node_legend'] = 'Node Einstellungen';
