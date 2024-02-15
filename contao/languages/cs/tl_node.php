<?php

/*
 * Node Bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2019, terminal42 gmbh
 * @author     terminal42 <https://terminal42.ch>
 * @license    MIT
 */

$GLOBALS['TL_LANG']['tl_node']['name'] = ['Název', 'Zadejte prosím vlastní název tohoto prvku.'];
$GLOBALS['TL_LANG']['tl_node']['type'] = ['Typ', 'Zde můžete vybrat jeden z dostupných typů.'];
$GLOBALS['TL_LANG']['tl_node']['languages'] = ['Jazyky', 'Zde můžete nastavit jazyky, které lze použít pro filtraci prvků v backendu.'];
$GLOBALS['TL_LANG']['tl_node']['tags'] = ['Štítky', 'Sem můžete zadat štítky, které lze použít pro filtraci prvků v backendu.'];
$GLOBALS['TL_LANG']['tl_node']['pid'] = ['Rodičkovský node'];
$GLOBALS['TL_LANG']['tl_node']['tstamp'] = ['Datum změny'];

/*
 * Legends
 */
$GLOBALS['TL_LANG']['tl_node']['name_legend'] = 'Název a typ';
$GLOBALS['TL_LANG']['tl_node']['filter_legend'] = 'Nastavení filtru';

/*
 * Reference
 */
$GLOBALS['TL_LANG']['tl_node']['typeRef'] = [
    \Terminal42\NodeBundle\Model\NodeModel::TYPE_CONTENT => 'Obsah',
    \Terminal42\NodeBundle\Model\NodeModel::TYPE_FOLDER => 'Složka',
];

/*
 * Buttons
 */
$GLOBALS['TL_LANG']['tl_node']['new'] = ['Nový prvek', 'Vytvořit nový prvek'];
$GLOBALS['TL_LANG']['tl_node']['show'] = ['Podrobnosti', 'Zobrazit podrobnosti k ID %s'];
$GLOBALS['TL_LANG']['tl_node']['edit'] = ['Upravit prvek', 'Upravit ID %s'];
$GLOBALS['TL_LANG']['tl_node']['editheader'] = ['Upravit nastavení prvku', 'Změnit nastavení prvku'];
$GLOBALS['TL_LANG']['tl_node']['cut'] = ['Přesunout prvek', 'Přesunout prvek ID %s'];
$GLOBALS['TL_LANG']['tl_node']['copy'] = ['Duplikovat prvek', 'Duplikovat ID %s'];
$GLOBALS['TL_LANG']['tl_node']['copyChilds'] = ['Duplikovat prvek i s jeho podprvky', 'Duplikovat prvek i s jeho podprvky ID %s'];
$GLOBALS['TL_LANG']['tl_node']['delete'] = ['Smazat prvek', 'Smazat prvek ID %s'];
$GLOBALS['TL_LANG']['tl_node']['pasteafter'] = ['Včlenit do', 'Včlenit prvek ID %s'];
$GLOBALS['TL_LANG']['tl_node']['pasteinto'] = ['Umístit za', 'Umístit ID %s za'];
