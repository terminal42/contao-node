<?php

use Contao\Controller;
use Contao\System;

Controller::loadDataContainer('tl_content');
System::loadLanguageFile('tl_content');

// Palettes
$GLOBALS['TL_DCA']['tl_module']['palettes']['__selector__'][] = 'nodesWrapper';
$GLOBALS['TL_DCA']['tl_module']['palettes']['nodes'] = '{title_legend},name,type;{include_legend},nodes,nodesWrapper;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests';

$GLOBALS['TL_DCA']['tl_module']['subpalettes']['nodesWrapper'] = &$GLOBALS['TL_DCA']['tl_content']['subpalettes']['nodesWrapper'];

// Fields
$GLOBALS['TL_DCA']['tl_module']['fields']['nodes'] = &$GLOBALS['TL_DCA']['tl_content']['fields']['nodes'];
$GLOBALS['TL_DCA']['tl_module']['fields']['nodesWrapper'] = &$GLOBALS['TL_DCA']['tl_content']['fields']['nodesWrapper'];
