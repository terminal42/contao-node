<?php

use Terminal42\NodeBundle\ContentElement\NodesContentElement;
use Terminal42\NodeBundle\FrontendModule\NodesModule;
use Terminal42\NodeBundle\Model\NodeModel;
use Terminal42\NodeBundle\Widget\NodePickerWidget;

/*
 * Backend modules
 */
$GLOBALS['BE_MOD']['content']['nodes'] = [
    'tables' => array_values(array_unique(array_merge(['tl_node', 'tl_content'], $GLOBALS['BE_MOD']['content']['article']['tables'] ?? []))),
    'table' => &$GLOBALS['BE_MOD']['content']['article']['table'],
    'list' => &$GLOBALS['BE_MOD']['content']['article']['list'],
];

/*
 * Back end form fields
 */
$GLOBALS['BE_FFL']['nodePicker'] = NodePickerWidget::class;

/*
 * Frontend modules
 */
$GLOBALS['FE_MOD']['miscellaneous']['nodes'] = NodesModule::class;

/*
 * Content elements
 */
$GLOBALS['TL_CTE']['includes']['nodes'] = NodesContentElement::class;

/*
 * Models
 */
$GLOBALS['TL_MODELS']['tl_node'] = NodeModel::class;

/*
 * Hooks
 */
$GLOBALS['TL_HOOKS']['executePostActions'][] = ['terminal42_node.listener.data_container', 'onExecutePostActions'];
$GLOBALS['TL_HOOKS']['replaceInsertTags'][] = ['terminal42_node.listener.insert_tags', 'onReplace'];

/*
 * User permissions
 */
$GLOBALS['TL_PERMISSIONS'][] = 'nodeMounts';
$GLOBALS['TL_PERMISSIONS'][] = 'nodePermissions';
