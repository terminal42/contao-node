<?php

use Terminal42\NodeBundle\PermissionChecker;

$GLOBALS['TL_LANG']['tl_user']['nodeMounts'] = ['Node mounts', 'Here you can grant access to one or more nodes (subnodes are included automatically).'];
$GLOBALS['TL_LANG']['tl_user']['nodePermissions'] = ['Node permissions', 'Here you can define the node permissions.'];

/*
 * Reference
 */
$GLOBALS['TL_LANG']['tl_user']['nodePermissionsRef'] = [
    PermissionChecker::PERMISSION_CREATE => 'Create nodes',
    PermissionChecker::PERMISSION_EDIT => 'Edit nodes',
    PermissionChecker::PERMISSION_DELETE => 'Delete nodes',
    PermissionChecker::PERMISSION_CONTENT => 'Manage content',
    PermissionChecker::PERMISSION_ROOT => 'Manage root nodes',
];

/*
 * Legends
 */
$GLOBALS['TL_LANG']['tl_user']['node_legend'] = 'Node settings';
