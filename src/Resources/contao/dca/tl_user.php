<?php

/*
 * Node Bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2018, terminal42 gmbh
 * @author     terminal42 <https://terminal42.ch>
 * @license    MIT
 */

use Contao\CoreBundle\DataContainer\PaletteManipulator;

/*
 * Palettes
 */
PaletteManipulator::create()
    ->addLegend('node_legend', 'pagemounts_legend', PaletteManipulator::POSITION_AFTER)
    ->addField('nodeMounts', 'node_legend', PaletteManipulator::POSITION_APPEND)
    ->addField('nodePermissions', 'node_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('extend', 'tl_user')
    ->applyToPalette('custom', 'tl_user')
;

/*
 * Fields
 */
$GLOBALS['TL_DCA']['tl_user']['fields']['nodeMounts'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_user']['nodeMounts'],
    'exclude' => true,
    'inputType' => 'nodePicker',
    'eval' => ['multiple' => true, 'fieldType' => 'checkbox', 'tl_class' => 'clr'],
    'sql' => ['type' => 'blob', 'notnull' => false],
];

$GLOBALS['TL_DCA']['tl_user']['fields']['nodePermissions'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_user']['nodePermissions'],
    'exclude' => true,
    'inputType' => 'checkbox',
    'options' => [
        \Terminal42\NodeBundle\PermissionChecker::PERMISSION_CREATE,
        \Terminal42\NodeBundle\PermissionChecker::PERMISSION_EDIT,
        \Terminal42\NodeBundle\PermissionChecker::PERMISSION_DELETE,
        \Terminal42\NodeBundle\PermissionChecker::PERMISSION_CONTENT,
        \Terminal42\NodeBundle\PermissionChecker::PERMISSION_ROOT,
    ],
    'reference' => &$GLOBALS['TL_LANG']['tl_user']['nodePermissionsRef'],
    'eval' => ['multiple' => true, 'fieldType' => 'checkbox', 'tl_class' => 'clr'],
    'sql' => ['type' => 'blob', 'notnull' => false],
];
