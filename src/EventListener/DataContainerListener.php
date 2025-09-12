<?php

declare(strict_types=1);

namespace Terminal42\NodeBundle\EventListener;

use Codefog\HasteBundle\Model\DcaRelationsModel;
use Codefog\TagsBundle\Manager\ManagerInterface;
use Codefog\TagsBundle\Tag;
use Contao\Backend;
use Contao\Controller;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Exception\ResponseException;
use Contao\CoreBundle\Intl\Locales;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\Security\DataContainer\ReadAction;
use Contao\DataContainer;
use Contao\Environment;
use Contao\Image;
use Contao\Input;
use Contao\StringUtil;
use Contao\System;
use Contao\Validator;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Terminal42\NodeBundle\Model\NodeModel;
use Terminal42\NodeBundle\Security\NodePermissions;
use Terminal42\NodeBundle\Widget\NodePickerWidget;

class DataContainerListener
{
    public const BREADCRUMB_SESSION_KEY = 'tl_node_node';

    public function __construct(
        private readonly Connection $connection,
        private readonly Locales $locales,
        private readonly LoggerInterface $logger,
        private readonly RequestStack $requestStack,
        private readonly Security $security,

        #[Autowire(service: 'codefog_tags.manager.terminal42_node')]
        private readonly ManagerInterface $tagsManager,
    ) {
    }

    #[AsCallback('tl_node', 'config.onload')]
    public function onLoadCallback(DataContainer $dc): void
    {
        $this->addBreadcrumb($dc);
        // TODO: needed?
        //$this->checkPermissions($dc);
        $this->toggleSwitchToEditFlag($dc);
    }

    #[AsCallback('tl_node', 'list.sorting.paste_button_callback')]
    public function onPasteButtonCallback(DataContainer $dc, array $row, string $table, bool $cr, array|false|null $clipboard = null): string
    {
        $disablePA = false;
        $disablePI = false;

        // Disable all buttons if there is a circular reference
        if (false !== $clipboard && (('cut' === $clipboard['mode'] && ($cr || (int) $clipboard['id'] === (int) $row['id'])) || ('cutAll' === $clipboard['mode'] && ($cr || \in_array((int) $row['id'], array_map('intval', $clipboard['id']), true))))) {
            $disablePA = true;
            $disablePI = true;
        }

        // Disable paste into if the node is of content type
        if (!$disablePI && NodeModel::TYPE_CONTENT === ($row['type'] ?? '')) {
            $disablePI = true;
        }

        // Disable "paste after" button if the parent node is a root node and the user is not allowed
        if (!$disablePA && !$this->security->isGranted(NodePermissions::USER_CAN_CREATE_ROOT_NODES) && (!$row['pid'] || \in_array((int) $row['id'], $dc->rootIds, true))) {
            $disablePA = true;
        }

        $return = '';

        // Return the buttons
        $imagePasteAfter = Image::getHtml('pasteafter.svg', \sprintf($GLOBALS['TL_LANG'][$table]['pasteafter'][1], $row['id']));
        $imagePasteInto = Image::getHtml('pasteinto.svg', \sprintf($GLOBALS['TL_LANG'][$table]['pasteinto'][1], $row['id']));

        if ($row['id'] > 0) {
            $return = $disablePA ? Image::getHtml('pasteafter_.svg').' ' : '<a href="'.Backend::addToUrl('act='.$clipboard['mode'].'&amp;mode=1&amp;pid='.$row['id'].(!\is_array($clipboard['id']) ? '&amp;id='.$clipboard['id'] : '')).'" title="'.StringUtil::specialchars(\sprintf($GLOBALS['TL_LANG'][$table]['pasteafter'][1], $row['id'])).'" onclick="Backend.getScrollOffset()">'.$imagePasteAfter.'</a> ';
        }

        return $return.($disablePI ? Image::getHtml('pasteinto_.svg').' ' : '<a href="'.Backend::addToUrl('act='.$clipboard['mode'].'&amp;mode=2&amp;pid='.$row['id'].(!\is_array($clipboard['id']) ? '&amp;id='.$clipboard['id'] : '')).'" title="'.StringUtil::specialchars(\sprintf($GLOBALS['TL_LANG'][$table]['pasteinto'][1], $row['id'])).'" onclick="Backend.getScrollOffset()">'.$imagePasteInto.'</a> ');
    }

    #[AsCallback('tl_node', 'list.operations.edit.button')]
    public function onEditButtonCallback(array $row, string $href, string $label, string $title, string $icon, string $attributes): string
    {
        return $this->generateButton($row, $href, $label, $title, $icon, $attributes, NodeModel::TYPE_FOLDER !== $row['type']);
    }

    // TODO: needed?
    //#[AsCallback('tl_node', 'list.operations.copyChilds.button')]
    //public function onCopyChildsButtonCallback(array $row, string $href, string $label, string $title, string $icon, string $attributes, string $table): string
    //{
    //    if ($GLOBALS['TL_DCA'][$table]['config']['closed'] ?? null) {
    //        return '';
    //    }
    //
    //    $active = (NodeModel::TYPE_FOLDER === $row['type']) && $this->permissionChecker->hasUserPermission(PermissionChecker::PERMISSION_CREATE);
    //
    //    // Make the button active only if there are subnodes
    //    if ($active) {
    //        $active = $this->connection->fetchOne("SELECT COUNT(*) FROM $table WHERE pid=?", [$row['id']]) > 0;
    //    }
    //
    //    return $this->generateButton($row, $href, $label, $title, $icon, $attributes, $active);
    //}


    #[AsCallback('tl_node', 'list.label.label')]
    public function onLabelCallback(array $row, string $label, DataContainer|null $dc = null, string $imageAttribute = '', bool $returnImage = false): string
    {
        $image = NodeModel::TYPE_CONTENT === $row['type'] ? 'articles.svg' : 'folderC.svg';

        // Return the image only
        if ($returnImage) {
            return Image::getHtml($image, '', $imageAttribute);
        }

        $languages = [];
        $allLanguages = $this->locales->getLocales(null, true);

        // Generate the languages
        foreach (StringUtil::trimsplit(',', $row['languages']) as $language) {
            $languages[] = $allLanguages[$language] ?? $language;
        }

        $tags = [];
        $tagIds = DcaRelationsModel::getRelatedValues('tl_node', 'tags', $row['id']);

        // Generate the tags
        if (\count($tagIds) > 0) {
            /** @var Tag $tag */
            foreach ($this->tagsManager->getFilteredTags($tagIds) as $tag) {
                $tags[] = $tag->getName();
            }
        }

        return \sprintf(
            '%s <a href="%s" title="%s">%s</a>%s%s',
            Image::getHtml($image, '', $imageAttribute),
            Backend::addToUrl('nn='.$row['id']),
            StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['selectNode']),
            $label,
            \count($languages) > 0 ? \sprintf(' <span class="tl_gray" style="margin-left:3px;">[%s]</span>', implode(', ', $languages)) : '',
            \count($tags) > 0 ? \sprintf(' <span class="tl_gray" style="margin-left:3px;">[%s]</span>', implode(', ', $tags)) : '',
        );
    }

    #[AsCallback('tl_node', 'fields.languages.options')]
    public function onLanguagesOptionsCallback(): array
    {
        return $this->locales->getLocales(null, true);
    }

    #[AsCallback('tl_node', 'fields.nodeTpl.options')]
    public function onNodeTplOptionsCallback(): array
    {
        return Controller::getTemplateGroup('node_');
    }

    #[AsHook('executePostActions')]
    public function onExecutePostActions($action, DataContainer $dc): void
    {
        if ('reloadNodePickerWidget' === $action) {
            $this->reloadNodePickerWidget($dc);
        }
    }

    /**
     * Generate the button.
     */
    private function generateButton(array $row, string $href, string $label, string $title, string $icon, string $attributes, bool $active): string
    {
        if (!$active) {
            return Image::getHtml(preg_replace('/\.svg$/i', '_.svg', $icon)).' ';
        }

        return '<a href="'.Backend::addToUrl($href.'&amp;id='.$row['id']).'" title="'.StringUtil::specialchars($title).'"'.$attributes.'>'.Image::getHtml($icon, $label).'</a> ';
    }

    /**
     * Reload the node picker widget.
     */
    private function reloadNodePickerWidget(DataContainer $dc): void
    {
        $id = Input::get('id');
        $field = $dc->inputName = Input::post('name');

        // Handle the keys in "edit multiple" mode
        if ('editAll' === Input::get('act')) {
            $id = preg_replace('/.*_([0-9a-zA-Z]+)$/', '$1', $field);
            $field = preg_replace('/(.*)_[0-9a-zA-Z]+$/', '$1', $field);
        }

        $dc->field = $field;
        $id = (int) $id;

        // The field does not exist
        if (!isset($GLOBALS['TL_DCA'][$dc->table]['fields'][$field])) {
            $this->logger->log(
                LogLevel::ERROR,
                \sprintf('Field "%s" does not exist in DCA "%s"', $field, $dc->table),
                ['contao' => new ContaoContext(__METHOD__, ContaoContext::ERROR)],
            );

            throw new BadRequestHttpException('Bad request');
        }

        $value = null;

        // Load the value
        if ('overrideAll' !== Input::get('act') && $id > 0 && $this->connection->createSchemaManager()->tablesExist([$dc->table])) {
            $row = $this->connection->fetchAssociative("SELECT * FROM {$dc->table} WHERE id=?", [$id]);

            // The record does not exist
            if (!$row) {
                $this->logger->log(
                    LogLevel::ERROR,
                    \sprintf('A record with the ID "%s" does not exist in table "%s"', $id, $dc->table),
                    ['contao' => new ContaoContext(__METHOD__, ContaoContext::ERROR)],
                );

                throw new BadRequestHttpException('Bad request');
            }

            $value = $row[$field];
            $dc->activeRecord = (object) $row;
        }

        // Call the load_callback
        if (\is_array($GLOBALS['TL_DCA'][$dc->table]['fields'][$field]['load_callback'] ?? null)) {
            foreach ($GLOBALS['TL_DCA'][$dc->table]['fields'][$field]['load_callback'] as $callback) {
                if (\is_array($callback)) {
                    $value = System::importStatic($callback[0])->{$callback[1]}($value, $dc);
                } elseif (\is_callable($callback)) {
                    $value = $callback($value, $dc);
                }
            }
        }

        // Set the new value
        $value = Input::post('value', true);

        // Convert the selected values
        if ($value) {
            $value = StringUtil::trimsplit("\t", $value);
            $value = serialize($value);
        }

        /** @var NodePickerWidget $strClass */
        $strClass = $GLOBALS['BE_FFL']['nodePicker'];

        /** @var NodePickerWidget $objWidget */
        $objWidget = new $strClass($strClass::getAttributesFromDca($GLOBALS['TL_DCA'][$dc->table]['fields'][$field], $dc->inputName, $value, $field, $dc->table, $dc));

        throw new ResponseException(new Response($objWidget->generate()));
    }

    /**
     * Toggle switchToEdit flag.
     */
    private function toggleSwitchToEditFlag(DataContainer $dc): void
    {
        if (!$dc->id) {
            return;
        }

        if (!$this->security->isGranted(NodePermissions::USER_CAN_EDIT_NODE_CONTENT)) {
            return;
        }

        $type = $this->connection->fetchOne('SELECT type FROM tl_node WHERE id=?', [$dc->id]);

        if (NodeModel::TYPE_CONTENT === $type) {
            $GLOBALS['TL_DCA'][$dc->table]['config']['switchToEdit'] = true;
        }
    }

    // TODO: needed?
    //private function checkPermissions(DataContainer $dc): void
    //{
    //    if ($this->permissionChecker->isUserAdmin()) {
    //        return;
    //    }
    //
    //    // Close the table if user is not allowed to create new records
    //    if (!$this->permissionChecker->hasUserPermission(PermissionChecker::PERMISSION_CREATE)) {
    //        $GLOBALS['TL_DCA'][$dc->table]['config']['closed'] = true;
    //        $GLOBALS['TL_DCA'][$dc->table]['config']['notCopyable'] = true;
    //    }
    //
    //    // Set the flag if user is not allowed to edit records
    //    if (!$this->permissionChecker->hasUserPermission(PermissionChecker::PERMISSION_EDIT)) {
    //        $GLOBALS['TL_DCA'][$dc->table]['config']['notEditable'] = true;
    //    }
    //
    //    // Set the flag if user is not allowed to delete records
    //    if (!$this->permissionChecker->hasUserPermission(PermissionChecker::PERMISSION_DELETE)) {
    //        $GLOBALS['TL_DCA'][$dc->table]['config']['notDeletable'] = true;
    //    }
    //
    //    $session = $this->requestStack->getSession()->all();
    //
    //    // Filter allowed page IDs
    //    if (\is_array($session['CURRENT']['IDS'] ?? null)) {
    //        $session['CURRENT']['IDS'] = $this->permissionChecker->filterAllowedIds(
    //            $session['CURRENT']['IDS'],
    //            'deleteAll' === Input::get('act') ? PermissionChecker::PERMISSION_DELETE : PermissionChecker::PERMISSION_EDIT,
    //        );
    //
    //        $this->requestStack->getSession()->replace($session);
    //    }
    //
    //    // Limit the allowed roots for the user
    //    if (null !== ($roots = $this->permissionChecker->getUserAllowedRoots())) {
    //        if (!empty($GLOBALS['TL_DCA'][$dc->table]['list']['sorting']['root']) && \is_array($GLOBALS['TL_DCA'][$dc->table]['list']['sorting']['root'])) {
    //            $GLOBALS['TL_DCA'][$dc->table]['list']['sorting']['root'] = array_intersect($GLOBALS['TL_DCA'][$dc->table]['list']['sorting']['root'], $roots);
    //        } else {
    //            $GLOBALS['TL_DCA'][$dc->table]['list']['sorting']['root'] = $roots;
    //        }
    //
    //        // Allow root paste if the user has enough permission
    //        if ($this->permissionChecker->hasUserPermission(PermissionChecker::PERMISSION_ROOT)) {
    //            $GLOBALS['TL_DCA'][$dc->table]['list']['sorting']['rootPaste'] = true;
    //        }
    //
    //        // Check current action
    //        if (($action = Input::get('act')) && 'paste' !== $action) {
    //            switch ($action) {
    //                case 'edit':
    //                    $nodeId = (int) Input::get('id');
    //
    //                    // Dynamically add the record to the user profile
    //                    if (!$this->permissionChecker->isUserAllowedNode($nodeId)) {
    //                        /** @var AttributeBagInterface $sessionBag */
    //                        $sessionBag = $this->requestStack->getSession()->getbag('contao_backend');
    //
    //                        $newRecords = $sessionBag->get('new_records');
    //                        $newRecords = \is_array($newRecords[$dc->table]) ? array_map('intval', $newRecords[$dc->table]) : [];
    //
    //                        if (\in_array($nodeId, $newRecords, true)) {
    //                            $this->permissionChecker->addNodeToAllowedRoots($nodeId);
    //                        }
    //                    }
    //                    // no break;
    //
    //                case 'copy':
    //                case 'delete':
    //                case 'show':
    //                    if (!isset($nodeId)) {
    //                        $nodeId = (int) Input::get('id');
    //                    }
    //
    //                    if (!$this->permissionChecker->isUserAllowedNode($nodeId)) {
    //                        throw new AccessDeniedException(\sprintf('Not enough permissions to %s node ID %s.', $action, $nodeId));
    //                    }
    //                    break;
    //
    //                case 'editAll':
    //                case 'deleteAll':
    //                case 'overrideAll':
    //                    if (\is_array($session['CURRENT']['IDS'])) {
    //                        $session['CURRENT']['IDS'] = array_intersect($session['CURRENT']['IDS'], $roots);
    //                        $this->requestStack->getSession()->replace($session);
    //                    }
    //                    break;
    //            }
    //        }
    //    }
    //}

    /**
     * Add a breadcrumb menu.
     *
     * @throws \RuntimeException
     */
    private function addBreadcrumb(DataContainer $dc): void
    {
        /** @var AttributeBagInterface $session */
        $session = $this->requestStack->getSession()->getBag('contao_backend');

        // Set a new node
        if (isset($_GET['nn'])) {
            // Check the path
            if (Validator::isInsecurePath(Input::get('nn', true))) {
                throw new \RuntimeException('Insecure path '.Input::get('nn', true));
            }

            $session->set(self::BREADCRUMB_SESSION_KEY, Input::get('nn', true));
            Controller::redirect(preg_replace('/&nn=[^&]*/', '', Environment::get('request')));
        }

        if (($nodeId = $session->get(self::BREADCRUMB_SESSION_KEY)) < 1) {
            return;
        }

        // Check the path
        if (Validator::isInsecurePath($nodeId)) {
            throw new \RuntimeException('Insecure path '.$nodeId);
        }

        $ids = [];
        $links = [];

        // Generate breadcrumb trail
        if ($nodeId) {
            $id = $nodeId;

            do {
                $node = $this->connection->fetchAssociative("SELECT * FROM {$dc->table} WHERE id=?", [$id]);

                if (!$node) {
                    // Currently selected node does not exist
                    if ((int) $id === (int) $nodeId) {
                        $session->set(self::BREADCRUMB_SESSION_KEY, 0);

                        return;
                    }

                    break;
                }

                $ids[] = $id;

                // No link for the active node
                if ((int) $node['id'] === (int) $nodeId) {
                    $links[] = $this->onLabelCallback($node, '', null, '', true).' '.$node['name'];
                } else {
                    $links[] = $this->onLabelCallback($node, '', null, '', true).' <a href="'.Backend::addToUrl('nn='.$node['id']).'" title="'.StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['selectNode']).'">'.$node['name'].'</a>';
                }

                // Do not show the mounted nodes
                if (!$this->security->isGranted(ContaoCorePermissions::DC_PREFIX . $dc->table, new ReadAction($dc->table, $node))) {
                    break;
                }

                $id = $node['pid'];
            } while ($id > 0 && 'root' !== $node['type']);
        }

        // Check whether the node is mounted
        foreach ($ids as $id) {
            if (!$this->security->isGranted(ContaoCorePermissions::DC_PREFIX . $dc->table, new ReadAction($dc->table, ['id' => $id]))) {
                $session->set(self::BREADCRUMB_SESSION_KEY, 0);

                throw new AccessDeniedException('Node ID '.$nodeId.' is not mounted.');
            }
        }

        // Limit tree
        $GLOBALS['TL_DCA'][$dc->table]['list']['sorting']['root'] = [$nodeId];
        $GLOBALS['TL_DCA'][$dc->table]['list']['sorting']['visibleRoot'] = $nodeId;

        // Add root link
        $links[] = Image::getHtml($GLOBALS['TL_DCA'][$dc->table]['list']['sorting']['icon']).' <a href="'.Backend::addToUrl('nn=0').'" title="'.StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['selectAllNodes']).'">'.$GLOBALS['TL_LANG']['MSC']['filterAll'].'</a>';
        $links = array_reverse($links);

        // Insert breadcrumb menu
        $GLOBALS['TL_DCA'][$dc->table]['list']['sorting']['breadcrumb'] = '

<nav aria-label="'.$GLOBALS['TL_LANG']['MSC']['breadcrumbMenu'].'">
  <ul id="tl_breadcrumb">
    <li>'.implode(' › </li><li>', $links).'</li>
  </ul>
</nav>';
    }
}
