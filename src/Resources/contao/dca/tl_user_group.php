<?php

use Contao\CoreBundle\DataContainer\PaletteManipulator;

\Contao\Controller::loadDataContainer('tl_user');
\Contao\System::loadLanguageFile('tl_user');

/*
 * Palettes
 */
PaletteManipulator::create()
    ->addLegend('nodemounts_legend', 'pagemounts_legend', PaletteManipulator::POSITION_AFTER)
    ->addField('nodemounts', 'nodemounts_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('default', 'tl_user_group')
;

/*
 * Fields
 */
$GLOBALS['TL_DCA']['tl_user_group']['fields']['nodemounts'] = &$GLOBALS['TL_DCA']['tl_user']['fields']['nodemounts'];
