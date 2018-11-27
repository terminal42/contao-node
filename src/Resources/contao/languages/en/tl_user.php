<?php

/*
 * Node Bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2018, terminal42 gmbh
 * @author     terminal42 <https://terminal42.ch>
 * @license    MIT
 */

$GLOBALS['TL_LANG']['tl_user']['nodeMounts'] = ['Node mounts', 'Here you can grant access to one or more nodes (subnodes are included automatically).'];
$GLOBALS['TL_LANG']['tl_user']['nodePermissions'] = ['Node permissions', 'Here you can define the node permissions.'];

/*
 * Reference
 */
$GLOBALS['TL_LANG']['tl_user']['nodePermissionsRef'] = [
    \Terminal42\NodeBundle\PermissionChecker::PERMISSION_CREATE => 'Create nodes',
    \Terminal42\NodeBundle\PermissionChecker::PERMISSION_EDIT => 'Edit nodes',
    \Terminal42\NodeBundle\PermissionChecker::PERMISSION_DELETE => 'Delete nodes',
    \Terminal42\NodeBundle\PermissionChecker::PERMISSION_CONTENT => 'Manage content',
    \Terminal42\NodeBundle\PermissionChecker::PERMISSION_ROOT => 'Manage root nodes',
];

/*
 * Legends
 */
$GLOBALS['TL_LANG']['tl_user']['node_legend'] = 'Node settings';
