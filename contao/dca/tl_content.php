<?php

use Doctrine\DBAL\Types\Types;

// Palettes
$GLOBALS['TL_DCA']['tl_content']['palettes']['__selector__'][] = 'nodesWrapper';
$GLOBALS['TL_DCA']['tl_content']['palettes']['nodes'] = '{type_legend},type;{include_legend},nodes,nodesWrapper;{template_legend:collapsed},customTpl;{protected_legend:collapsed},protected;{expert_legend:collapsed},guests;{invisible_legend:collapsed},invisible,start,stop';

$GLOBALS['TL_DCA']['tl_content']['subpalettes']['nodesWrapper'] = 'cssID';

// Fields
$GLOBALS['TL_DCA']['tl_content']['fields']['nodes'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_content']['nodes'],
    'inputType' => 'nodePicker',
    'eval' => ['mandatory' => true, 'multiple' => true, 'fieldType' => 'checkbox', 'tl_class' => 'clr'],
    'sql' => ['type' => Types::BLOB, 'notnull' => false],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['nodesWrapper'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_content']['nodesWrapper'],
    'inputType' => 'checkbox',
    'eval' => ['submitOnChange' => true, 'tl_class' => 'clr'],
    'sql' => ['type' => Types::BOOLEAN, 'default' => false],
];
