<?php

/*
 * Node Bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2019, terminal42 gmbh
 * @author     terminal42 <https://terminal42.ch>
 * @license    MIT
 */

/*
 * Backend modules
 */
$GLOBALS['BE_MOD']['content']['nodes'] = [
    'tables' => ['tl_node', 'tl_content'],
    'table' => &$GLOBALS['BE_MOD']['content']['article']['table'],
    'list' => &$GLOBALS['BE_MOD']['content']['article']['list'],
];

/*
 * Back end form fields
 */
$GLOBALS['BE_FFL']['nodePicker'] = \Terminal42\NodeBundle\Widget\NodePickerWidget::class;

/*
 * Frontend modules
 */
$GLOBALS['FE_MOD']['miscellaneous']['nodes'] = \Terminal42\NodeBundle\FrontendModule\NodesModule::class;

/*
 * Content elements
 */
$GLOBALS['TL_CTE']['includes']['nodes'] = \Terminal42\NodeBundle\ContentElement\NodesContentElement::class;

/*
 * Models
 */
$GLOBALS['TL_MODELS']['tl_node'] = \Terminal42\NodeBundle\Model\NodeModel::class;

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
