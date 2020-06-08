<?php

/*
 * Node Bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2019, terminal42 gmbh
 * @author     terminal42 <https://terminal42.ch>
 * @license    MIT
 */

$GLOBALS['TL_LANG']['tl_node']['name'] = ['Name', 'Please enter the internal node name.'];
$GLOBALS['TL_LANG']['tl_node']['type'] = ['Type', 'Here you can choose the node type.'];
$GLOBALS['TL_LANG']['tl_node']['wrapper'] = ['Add wrapper element', 'Here you can define if this node should have a wrapper.'];
$GLOBALS['TL_LANG']['tl_node']['cssID'] = ['CSS ID/class', 'Here you can set an ID and one or more classes.'];
$GLOBALS['TL_LANG']['tl_node']['languages'] = ['Languages', 'Here you can choose the node languages that will be used for filtering in the backend.'];
$GLOBALS['TL_LANG']['tl_node']['tags'] = ['Tags', 'Here you can enter the tags that will be used for filtering in the backend.'];
$GLOBALS['TL_LANG']['tl_node']['pid'] = ['Parent node'];
$GLOBALS['TL_LANG']['tl_node']['tstamp'] = ['Revision date'];

/*
 * Legends
 */
$GLOBALS['TL_LANG']['tl_node']['name_legend'] = 'Name and type';
$GLOBALS['TL_LANG']['tl_node']['filter_legend'] = 'Filter settings';
$GLOBALS['TL_LANG']['tl_node']['wrapper_legend'] = 'Wrapper settings';

/*
 * Reference
 */
$GLOBALS['TL_LANG']['tl_node']['typeRef'] = [
    \Terminal42\NodeBundle\Model\NodeModel::TYPE_CONTENT => 'Content',
    \Terminal42\NodeBundle\Model\NodeModel::TYPE_FOLDER => 'Folder',
];

/*
 * Buttons
 */
$GLOBALS['TL_LANG']['tl_node']['new'] = ['New node', 'Create a new node'];
$GLOBALS['TL_LANG']['tl_node']['show'] = ['Node details', 'Show the details of node ID %s'];
$GLOBALS['TL_LANG']['tl_node']['edit'] = ['Edit node', 'Edit node ID %s'];
$GLOBALS['TL_LANG']['tl_node']['editheader'] = ['Edit node settings', 'Edit node settings ID %s'];
$GLOBALS['TL_LANG']['tl_node']['cut'] = ['Move node', 'Move node ID %s'];
$GLOBALS['TL_LANG']['tl_node']['copy'] = ['Duplicate node', 'Duplicate node ID %s'];
$GLOBALS['TL_LANG']['tl_node']['copyChilds'] = ['Duplicate with subnodes', 'Duplicate node ID %s with its subnodes'];
$GLOBALS['TL_LANG']['tl_node']['delete'] = ['Delete node', 'Delete node ID %s'];
$GLOBALS['TL_LANG']['tl_node']['pasteafter'] = ['Paste after', 'Paste after node ID %s'];
$GLOBALS['TL_LANG']['tl_node']['pasteinto'] = ['Paste into', 'Paste into node ID %s'];
