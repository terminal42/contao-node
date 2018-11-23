<?php

/*
 * Node Bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2018, terminal42 gmbh
 * @author     terminal42 <https://terminal42.ch>
 * @license    MIT
 */

/*
 * Backend modules
 */
$GLOBALS['BE_MOD']['content']['nodes'] = [
    'tables' => ['tl_node', 'tl_content'],
];

/*
 * Frontend modules
 */
$GLOBALS['FE_MOD']['includes']['nodes'] = \Terminal42\NodeBundle\FrontendModule\NodesModule::class;

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
$GLOBALS['TL_HOOKS']['replaceInsertTags'] = ['terminal42_node.listener.insert_tags', 'onReplace'];

/*
 * User permissions
 */
$GLOBALS['TL_PERMISSIONS'][] = 'nodemounts';
