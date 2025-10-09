<?php

use Contao\DataContainer;
use Contao\DC_Table;
use Doctrine\DBAL\Types\Types;
use Terminal42\NodeBundle\Model\NodeModel;

$GLOBALS['TL_DCA']['tl_node'] = [
    // Config
    'config' => [
        'label' => &$GLOBALS['TL_LANG']['MOD']['nodes'][0],
        'dataContainer' => DC_Table::class,
        'ctable' => ['tl_content'],
        'enableVersioning' => true,
        'markAsCopy' => 'name',
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'pid,type,languages' => 'index',
            ],
        ],
    ],

    // List
    'list' => [
        'sorting' => [
            'mode' => DataContainer::MODE_TREE,
            'icon' => 'folderC.svg',
            'rootPaste' => true,
            'panelLayout' => 'filter;search',
        ],
        'label' => [
            'fields' => ['name'],
            'format' => '%s',
        ],
        'global_operations' => [
            'toggleNodes' => [
                'href' => 'ptg=all',
                'class' => 'header_toggle',
                'showOnSelect' => true,
            ],
            'all',
        ],
    ],

    // Palettes
    'palettes' => [
        '__selector__' => ['wrapper', 'protected'],
        'default' => '{name_legend},name,type;{wrapper_legend},wrapper;{filter_legend},languages,tags;{protected_legend:hide},protected,guests',
    ],

    // Subpalettes
    'subpalettes' => [
        'wrapper' => 'nodeTpl,cssID',
        'protected' => 'groups',
    ],

    // Fields
    'fields' => [
        'id' => [
            'sql' => ['type' => Types::INTEGER, 'unsigned' => true, 'autoincrement' => true],
        ],
        'pid' => [
            'label' => &$GLOBALS['TL_LANG']['tl_node']['pid'],
            'foreignKey' => 'tl_node.name',
            'sql' => ['type' => Types::INTEGER, 'unsigned' => true, 'default' => 0],
        ],
        'sorting' => [
            'sql' => ['type' => Types::INTEGER, 'unsigned' => true, 'default' => 0],
        ],
        'tstamp' => [
            'label' => &$GLOBALS['TL_LANG']['tl_node']['tstamp'],
            'sql' => ['type' => Types::INTEGER, 'unsigned' => true, 'default' => 0],
        ],
        'name' => [
            'search' => true,
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => ['type' => Types::STRING, 'length' => 255, 'default' => ''],
        ],
        'type' => [
            'filter' => true,
            'inputType' => 'select',
            'options' => [
                NodeModel::TYPE_CONTENT,
                NodeModel::TYPE_FOLDER,
            ],
            'reference' => &$GLOBALS['TL_LANG']['tl_node']['typeRef'],
            'eval' => ['tl_class' => 'w50'],
            'sql' => ['type' => Types::STRING, 'length' => 7, 'default' => ''],
        ],
        'wrapper' => [
            'inputType' => 'checkbox',
            'eval' => ['submitOnChange' => true, 'tl_class' => 'clr'],
            'sql' => ['type' => Types::STRING, 'length' => 1, 'default' => ''],
        ],
        'nodeTpl' => [
            'inputType' => 'select',
            'eval' => ['includeBlankOption' => true, 'chosen' => true, 'tl_class' => 'w50'],
            'sql' => ['type' => Types::STRING, 'length' => 64, 'default' => ''],
        ],
        'cssID' => [
            'inputType' => 'text',
            'eval' => ['multiple' => true, 'size' => 2, 'tl_class' => 'w50'],
            'sql' => ['type' => Types::STRING, 'length' => 255, 'default' => ''],
        ],
        'languages' => [
            'filter' => true,
            'inputType' => 'select',
            'eval' => ['multiple' => true, 'chosen' => true, 'csv' => ',', 'tl_class' => 'clr'],
            'sql' => ['type' => Types::STRING, 'length' => 255, 'default' => ''],
        ],
        'tags' => [
            'filter' => true,
            'inputType' => 'cfgTags',
            'eval' => ['tagsManager' => 'terminal42_node', 'tl_class' => 'clr'],
        ],
        'protected' => [
            'inputType' => 'checkbox',
            'eval' => ['submitOnChange' => true],
            'sql' => ['type' => Types::BOOLEAN, 'default' => false],
        ],
        'groups' => [
            'inputType' => 'checkbox',
            'eval' => ['mandatory' => true, 'multiple' => true],
            'sql' => ['type' => Types::BLOB, 'notnull' => false],
            'relation' => ['type' => 'hasMany', 'load' => 'lazy', 'table' => 'tl_member_group'],
        ],
    ],
];
