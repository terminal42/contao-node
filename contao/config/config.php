<?php

use Terminal42\NodeBundle\Model\NodeModel;
use Terminal42\NodeBundle\Widget\NodePickerWidget;

// Backend modules
$GLOBALS['BE_MOD']['content']['nodes'] = [
    'tables' => array_values(array_unique(array_merge(['tl_node', 'tl_content'], $GLOBALS['BE_MOD']['content']['article']['tables'] ?? []))),
    'table' => &$GLOBALS['BE_MOD']['content']['article']['table'],
    'list' => &$GLOBALS['BE_MOD']['content']['article']['list'],
];

// Back end form fields
$GLOBALS['BE_FFL']['nodePicker'] = NodePickerWidget::class;

// Models
$GLOBALS['TL_MODELS']['tl_node'] = NodeModel::class;

// User permissions
$GLOBALS['TL_PERMISSIONS'][] = 'nodeMounts';
$GLOBALS['TL_PERMISSIONS'][] = 'nodePermissions';
