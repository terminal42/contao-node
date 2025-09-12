<?php

use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Doctrine\DBAL\Types\Types;

// Palettes
PaletteManipulator::create()
    ->addLegend('node_legend', 'pagemounts_legend')
    ->addField(['nodeMounts', 'nodePermissions'], 'node_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('extend', 'tl_user')
    ->applyToPalette('custom', 'tl_user')
;

// Fields
$GLOBALS['TL_DCA']['tl_user']['fields']['nodeMounts'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_user']['nodeMounts'],
    'inputType' => 'nodePicker',
    'eval' => ['multiple' => true, 'fieldType' => 'checkbox', 'tl_class' => 'clr'],
    'sql' => ['type' => Types::BLOB, 'notnull' => false],
];

$GLOBALS['TL_DCA']['tl_user']['fields']['nodePermissions'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_user']['nodePermissions'],
    'inputType' => 'checkbox',
    'options' => ['create', 'edit', 'delete', 'content', 'root'],
    'reference' => &$GLOBALS['TL_LANG']['tl_user']['nodePermissionsRef'],
    'eval' => ['multiple' => true, 'fieldType' => 'checkbox', 'tl_class' => 'clr'],
    'sql' => ['type' => Types::BLOB, 'notnull' => false],
];
