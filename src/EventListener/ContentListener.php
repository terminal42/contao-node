<?php

namespace Terminal42\NodeBundle\EventListener;

use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Doctrine\DBAL\Connection;
use Terminal42\NodeBundle\Model\NodeModel;

class ContentListener
{
    /**
     * @var Connection
     */
    private $db;

    /**
     * @var ContaoFrameworkInterface
     */
    private $framework;

    /**
     * ContentListener constructor.
     *
     * @param Connection               $db
     * @param ContaoFrameworkInterface $framework
     */
    public function __construct(Connection $db, ContaoFrameworkInterface $framework)
    {
        $this->db = $db;
        $this->framework = $framework;
    }

    /**
     * On data container load callback.
     */
    public function onLoadCallback(): void
    {
        $node = $this->db->fetchColumn('SELECT type FROM tl_node WHERE id=?', [CURRENT_ID]);

        // Throw an exception if the node is not present or is of a folder type
        if (!$node || $node === NodeModel::TYPE_FOLDER) {
            throw new AccessDeniedException('Node of folder type cannot have content elements');
        }
    }
}
