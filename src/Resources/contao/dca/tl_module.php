<?php

/*
 * Node Bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2019, terminal42 gmbh
 * @author     terminal42 <https://terminal42.ch>
 * @license    MIT
 */

\Contao\Controller::loadDataContainer('tl_content');
\Contao\System::loadLanguageFile('tl_content');

/*
 * Palettes
 */
$GLOBALS['TL_DCA']['tl_module']['palettes']['nodes'] = '{title_legend},name,type;{include_legend},nodes;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';

/*
 * Fields
 */
$GLOBALS['TL_DCA']['tl_module']['fields']['nodes'] = &$GLOBALS['TL_DCA']['tl_content']['fields']['nodes'];
