<?php

use Contao\CoreBundle\DataContainer\PaletteManipulator;

/*
 * Palettes
 */
PaletteManipulator::create()
    ->addLegend('nodemounts_legend', 'pagemounts_legend', PaletteManipulator::POSITION_AFTER)
    ->addField('nodemounts', 'nodemounts_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('extend', 'tl_user')
    ->applyToPalette('custom', 'tl_user')
;

/*
 * Fields
 */
$GLOBALS['TL_DCA']['tl_user']['fields']['nodemounts'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_user']['nodemounts'],
    'exclude' => true,
    'inputType' => 'nodeTree',
    'eval' => ['multiple' => true, 'fieldType' => 'checkbox'],
    'sql' => ['type' => 'blob', 'notnull' => false],
];
