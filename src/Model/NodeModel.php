<?php

declare(strict_types=1);

namespace Terminal42\NodeBundle\Model;

use Contao\ContentModel;
use Contao\Model;
use Contao\Model\Collection;

class NodeModel extends Model
{
    public const TYPE_CONTENT = 'content';

    public const TYPE_FOLDER = 'folder';

    /**
     * Table name.
     *
     * @var string
     */
    protected static $strTable = 'tl_node';

    /**
     * @return Collection<ContentModel>|null
     */
    public function getContentElements(): Collection|null
    {
        return ContentModel::findPublishedByPidAndTable($this->id, static::getTable());
    }
}
