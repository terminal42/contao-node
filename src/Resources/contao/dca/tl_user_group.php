<?php

/*
 * Node Bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2018, terminal42 gmbh
 * @author     terminal42 <https://terminal42.ch>
 * @license    MIT
 */

use Contao\CoreBundle\DataContainer\PaletteManipulator;

\Contao\Controller::loadDataContainer('tl_user');
\Contao\System::loadLanguageFile('tl_user');

/*
 * Palettes
 */
PaletteManipulator::create()
    ->addLegend('node_legend', 'pagemounts_legend', PaletteManipulator::POSITION_AFTER)
    ->addField('nodeMounts', 'node_legend', PaletteManipulator::POSITION_APPEND)
    ->addField('nodePermissions', 'node_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('default', 'tl_user_group')
;

/*
 * Fields
 */
$GLOBALS['TL_DCA']['tl_user_group']['fields']['nodeMounts'] = &$GLOBALS['TL_DCA']['tl_user']['fields']['nodeMounts'];
$GLOBALS['TL_DCA']['tl_user_group']['fields']['nodePermissions'] = &$GLOBALS['TL_DCA']['tl_user']['fields']['nodePermissions'];
