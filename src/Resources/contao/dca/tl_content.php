<?php

/*
 * Dynamically add the permission check and parent table
 */
if (\Contao\Input::get('do') === 'nodes') {
    $GLOBALS['TL_DCA']['tl_content']['config']['ptable'] = 'tl_node';
    $GLOBALS['TL_DCA']['tl_content']['config']['onload_callback'][] = ['terminal42_node.listener.content', 'onLoadCallback'];
    $GLOBALS['TL_DCA']['tl_content']['list']['sorting']['headerFields'] = ['pid', 'name', 'tstamp'];
}

/*
 * Palettes
 */
$GLOBALS['TL_DCA']['tl_content']['palettes']['nodes'] = '{type_legend},type;{include_legend},nodes;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID;{invisible_legend:hide},invisible,start,stop';

/*
 * Fields
 */
$GLOBALS['TL_DCA']['tl_content']['fields']['nodes'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_content']['nodes'],
    'exclude' => true,
    'inputType' => 'nodePicker',
    'eval' => ['mandatory' => true, 'multiple' => true, 'fieldType' => 'checkbox', 'tl_class' => 'clr'],
    'sql' => ['type' => 'blob', 'notnull' => false],
    'save_callback' => [
        ['terminal42_node.listener.content', 'onNodesSaveCallback'],
    ],
];
