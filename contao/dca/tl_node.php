<?php

use Contao\Controller;
use Contao\DataContainer;
use Contao\DC_Table;
use Terminal42\NodeBundle\Model\NodeModel;

$GLOBALS['TL_DCA']['tl_node'] = [
    // Config
    'config' => [
        'label' => &$GLOBALS['TL_LANG']['MOD']['nodes'][0],
        'dataContainer' => DC_Table::class,
        'ctable' => ['tl_content'],
        'enableVersioning' => true,
        'markAsCopy' => 'name',
        'onload_callback' => [
            ['terminal42_node.listener.data_container', 'onLoadCallback'],
        ],
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
            'paste_button_callback' => ['terminal42_node.listener.data_container', 'onPasteButtonCallback'],
            'panelLayout' => 'filter;search',
        ],
        'label' => [
            'fields' => ['name'],
            'format' => '%s',
            'label_callback' => ['terminal42_node.listener.data_container', 'onLabelCallback'],
        ],
        'global_operations' => [
            'toggleNodes' => [
                'label' => &$GLOBALS['TL_LANG']['MSC']['toggleAll'],
                'href' => 'ptg=all',
                'class' => 'header_toggle',
                'showOnSelect' => true,
            ],
            'all' => [
                'label' => &$GLOBALS['TL_LANG']['MSC']['all'],
                'href' => 'act=select',
                'class' => 'header_edit_all',
                'attributes' => 'onclick="Backend.getScrollOffset()" accesskey="e"',
            ],
        ],
        'operations' => [
            'edit' => [
                'label' => &$GLOBALS['TL_LANG']['tl_node']['edit'],
                'href' => 'table=tl_content',
                'icon' => 'edit.svg',
                'button_callback' => ['terminal42_node.listener.data_container', 'onEditButtonCallback'],
            ],
            'editheader' => [
                'label' => &$GLOBALS['TL_LANG']['tl_node']['editheader'],
                'href' => 'act=edit',
                'icon' => 'header.svg',
                'button_callback' => ['terminal42_node.listener.data_container', 'onEditHeaderButtonCallback'],
            ],
            'copy' => [
                'label' => &$GLOBALS['TL_LANG']['tl_node']['copy'],
                'href' => 'act=paste&amp;mode=copy',
                'icon' => 'copy.svg',
                'attributes' => 'onclick="Backend.getScrollOffset()"',
                'button_callback' => ['terminal42_node.listener.data_container', 'onCopyButtonCallback'],
            ],
            'copyChilds' => [
                'label' => &$GLOBALS['TL_LANG']['tl_node']['copyChilds'],
                'href' => 'act=paste&amp;mode=copy&amp;childs=1',
                'icon' => 'copychilds.svg',
                'attributes' => 'onclick="Backend.getScrollOffset()"',
                'button_callback' => ['terminal42_node.listener.data_container', 'onCopyChildsButtonCallback'],
            ],
            'cut' => [
                'label' => &$GLOBALS['TL_LANG']['tl_node']['cut'],
                'href' => 'act=paste&amp;mode=cut',
                'icon' => 'cut.svg',
                'attributes' => 'onclick="Backend.getScrollOffset()"',
            ],
            'delete' => [
                'label' => &$GLOBALS['TL_LANG']['tl_node']['delete'],
                'href' => 'act=delete',
                'icon' => 'delete.svg',
                'attributes' => 'onclick="if(!confirm(\''.($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? null).'\'))return false;Backend.getScrollOffset()"',
                'button_callback' => ['terminal42_node.listener.data_container', 'onDeleteButtonCallback'],
            ],
            'show' => [
                'label' => &$GLOBALS['TL_LANG']['tl_node']['show'],
                'href' => 'act=show',
                'icon' => 'show.svg',
            ],
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
            'sql' => ['type' => 'integer', 'unsigned' => true, 'autoincrement' => true],
        ],
        'pid' => [
            'label' => &$GLOBALS['TL_LANG']['tl_node']['pid'],
            'foreignKey' => 'tl_node.name',
            'sql' => ['type' => 'integer', 'unsigned' => true, 'default' => 0],
        ],
        'sorting' => [
            'sql' => ['type' => 'integer', 'unsigned' => true, 'default' => 0],
        ],
        'tstamp' => [
            'label' => &$GLOBALS['TL_LANG']['tl_node']['tstamp'],
            'sql' => ['type' => 'integer', 'unsigned' => true, 'default' => 0],
        ],
        'name' => [
            'label' => &$GLOBALS['TL_LANG']['tl_node']['name'],
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => ['type' => 'string', 'length' => 255, 'default' => ''],
        ],
        'type' => [
            'label' => &$GLOBALS['TL_LANG']['tl_node']['type'],
            'exclude' => true,
            'filter' => true,
            'inputType' => 'select',
            'options' => [
                NodeModel::TYPE_CONTENT,
                NodeModel::TYPE_FOLDER,
            ],
            'reference' => &$GLOBALS['TL_LANG']['tl_node']['typeRef'],
            'eval' => ['tl_class' => 'w50'],
            'sql' => ['type' => 'string', 'length' => 7, 'default' => ''],
        ],
        'wrapper' => [
            'label' => &$GLOBALS['TL_LANG']['tl_node']['wrapper'],
            'exclude' => true,
            'inputType' => 'checkbox',
            'eval' => ['submitOnChange' => true, 'tl_class' => 'clr'],
            'sql' => ['type' => 'string', 'length' => 1, 'default' => ''],
        ],
        'nodeTpl' => [
            'exclude' => true,
            'inputType' => 'select',
            'options_callback' => static fn () => Controller::getTemplateGroup('node_'),
            'eval' => ['includeBlankOption' => true, 'chosen' => true, 'tl_class' => 'w50'],
            'sql' => ['type' => 'string', 'length' => 64, 'default' => ''],
        ],
        'cssID' => [
            'label' => &$GLOBALS['TL_LANG']['tl_node']['cssID'],
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['multiple' => true, 'size' => 2, 'tl_class' => 'w50'],
            'sql' => ['type' => 'string', 'length' => 255, 'default' => ''],
        ],
        'languages' => [
            'label' => &$GLOBALS['TL_LANG']['tl_node']['languages'],
            'exclude' => true,
            'filter' => true,
            'inputType' => 'select',
            'options_callback' => ['terminal42_node.listener.data_container', 'onLanguagesOptionsCallback'],
            'eval' => ['multiple' => true, 'chosen' => true, 'csv' => ',', 'tl_class' => 'clr'],
            'sql' => ['type' => 'string', 'length' => 255, 'default' => ''],
        ],
        'tags' => [
            'label' => &$GLOBALS['TL_LANG']['tl_node']['tags'],
            'exclude' => true,
            'filter' => true,
            'inputType' => 'cfgTags',
            'eval' => ['tagsManager' => 'terminal42_node', 'tl_class' => 'clr'],
        ],
        'protected' => [
            'label' => &$GLOBALS['TL_LANG']['tl_node']['protected'],
            'exclude' => true,
            'inputType' => 'checkbox',
            'eval' => ['submitOnChange' => true],
            'sql' => "char(1) NOT NULL default ''",
        ],
        'groups' => [
            'label' => &$GLOBALS['TL_LANG']['tl_node']['groups'],
            'exclude' => true,
            'inputType' => 'checkbox',
            'foreignKey' => 'tl_member_group.name',
            'eval' => ['mandatory' => true, 'multiple' => true],
            'sql' => 'blob NULL',
            'relation' => ['type' => 'hasMany', 'load' => 'lazy'],
        ],
        'guests' => [
            'label' => &$GLOBALS['TL_LANG']['tl_node']['guests'],
            'exclude' => true,
            'inputType' => 'checkbox',
            'eval' => ['tl_class' => 'clr'],
            'sql' => "char(1) NOT NULL default ''",
        ],
    ],
];
