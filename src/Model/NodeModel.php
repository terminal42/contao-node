<?php

/*
 * Node Bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2018, terminal42 gmbh
 * @author     terminal42 <https://terminal42.ch>
 * @license    MIT
 */

namespace Terminal42\NodeBundle\Model;

use Contao\Model;

class NodeModel extends Model
{
    /**
     * Types.
     */
    const TYPE_CONTENT = 'content';
    const TYPE_FOLDER = 'folder';

    /**
     * Table name.
     *
     * @var string
     */
    protected static $strTable = 'tl_node';
}
