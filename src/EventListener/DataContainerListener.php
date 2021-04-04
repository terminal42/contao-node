<?php

declare(strict_types=1);

/*
 * Node Bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2019, terminal42 gmbh
 * @author     terminal42 <https://terminal42.ch>
 * @license    MIT
 */

namespace Terminal42\NodeBundle\EventListener;

use Codefog\TagsBundle\Manager\ManagerInterface;
use Codefog\TagsBundle\Tag;
use Contao\Backend;
use Contao\BackendUser;
use Contao\Controller;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Exception\ResponseException;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\DataContainer;
use Contao\Environment;
use Contao\Image;
use Contao\Input;
use Contao\StringUtil;
use Contao\System;
use Contao\Validator;
use Doctrine\DBAL\Connection;
use Haste\Model\Model;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Terminal42\NodeBundle\Model\NodeModel;
use Terminal42\NodeBundle\PermissionChecker;
use Terminal42\NodeBundle\Widget\NodePickerWidget;

class DataContainerListener
{
    const BREADCRUMB_SESSION_KEY = 'tl_node_node';

    /**
     * @var Connection
     */
    private $db;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var PermissionChecker
     */
    private $permissionChecker;

    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var ManagerInterface
     */
    private $tagsManager;

    /**
     * DataContainerListener constructor.
     *
     * @param Connection        $db
     * @param LoggerInterface   $logger
     * @param PermissionChecker $permissionChecker
     * @param SessionInterface  $session
     * @param ManagerInterface  $tagsManager
     */
    public function __construct(
        Connection $db,
        LoggerInterface $logger,
        PermissionChecker $permissionChecker,
        SessionInterface $session,
        ManagerInterface $tagsManager
    ) {
        $this->db = $db;
        $this->logger = $logger;
        $this->permissionChecker = $permissionChecker;
        $this->session = $session;
        $this->tagsManager = $tagsManager;
    }

    /**
     * On load callback.
     *
     * @param DataContainer $dc
     */
    public function onLoadCallback(DataContainer $dc): void
    {
        $this->addBreadcrumb($dc);
        $this->checkPermissions($dc);
        $this->toggleSwitchToEditFlag($dc);
    }

    /**
     * On paste button callback.
     *
     * @param DataContainer $dc
     * @param array         $row
     * @param string        $table
     * @param bool          $cr
     * @param array|null    $clipboard
     *
     * @return string
     */
    public function onPasteButtonCallback(DataContainer $dc, array $row, string $table, bool $cr, $clipboard = null): string
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
        if (!$disablePA && !$this->permissionChecker->hasUserPermission(PermissionChecker::PERMISSION_ROOT) && (!$row['pid'] || (\in_array((int) $row['id'], $dc->rootIds, true)))) {
            $disablePA = true;
        }

        $return = '';

        // Return the buttons
        $imagePasteAfter = Image::getHtml('pasteafter.svg', sprintf($GLOBALS['TL_LANG'][$table]['pasteafter'][1], $row['id']));
        $imagePasteInto = Image::getHtml('pasteinto.svg', sprintf($GLOBALS['TL_LANG'][$table]['pasteinto'][1], $row['id']));

        if ($row['id'] > 0) {
            $return = $disablePA ? Image::getHtml('pasteafter_.svg').' ' : '<a href="'.Backend::addToUrl('act='.$clipboard['mode'].'&amp;mode=1&amp;pid='.$row['id'].(!\is_array($clipboard['id']) ? '&amp;id='.$clipboard['id'] : '')).'" title="'.StringUtil::specialchars(sprintf($GLOBALS['TL_LANG'][$table]['pasteafter'][1], $row['id'])).'" onclick="Backend.getScrollOffset()">'.$imagePasteAfter.'</a> ';
        }

        return $return.($disablePI ? Image::getHtml('pasteinto_.svg').' ' : '<a href="'.Backend::addToUrl('act='.$clipboard['mode'].'&amp;mode=2&amp;pid='.$row['id'].(!\is_array($clipboard['id']) ? '&amp;id='.$clipboard['id'] : '')).'" title="'.StringUtil::specialchars(sprintf($GLOBALS['TL_LANG'][$table]['pasteinto'][1], $row['id'])).'" onclick="Backend.getScrollOffset()">'.$imagePasteInto.'</a> ');
    }

    /**
     * On "edit" button callback.
     *
     * @param array  $row
     * @param string $href
     * @param string $label
     * @param string $title
     * @param string $icon
     * @param string $attributes
     *
     * @return string
     */
    public function onEditButtonCallback(array $row, string $href, string $label, string $title, string $icon, string $attributes): string
    {
        return $this->generateButton($row, $href, $label, $title, $icon, $attributes, NodeModel::TYPE_FOLDER !== $row['type'] && $this->permissionChecker->hasUserPermission(PermissionChecker::PERMISSION_CONTENT));
    }

    /**
     * On "edit header" button callback.
     *
     * @param array  $row
     * @param string $href
     * @param string $label
     * @param string $title
     * @param string $icon
     * @param string $attributes
     *
     * @return string
     */
    public function onEditHeaderButtonCallback(array $row, string $href, string $label, string $title, string $icon, string $attributes): string
    {
        return $this->generateButton($row, $href, $label, $title, $icon, $attributes, $this->permissionChecker->hasUserPermission(PermissionChecker::PERMISSION_EDIT));
    }

    /**
     * On "copy" button callback.
     *
     * @param array  $row
     * @param string $href
     * @param string $label
     * @param string $title
     * @param string $icon
     * @param string $attributes
     * @param string $table
     *
     * @return string
     */
    public function onCopyButtonCallback(array $row, string $href, string $label, string $title, string $icon, string $attributes, string $table): string
    {
        if ($GLOBALS['TL_DCA'][$table]['config']['closed'] ?? null) {
            return '';
        }

        return $this->generateButton($row, $href, $label, $title, $icon, $attributes, $this->permissionChecker->hasUserPermission(PermissionChecker::PERMISSION_CREATE));
    }

    /**
     * On "copy childs" button callback.
     *
     * @param array  $row
     * @param string $href
     * @param string $label
     * @param string $title
     * @param string $icon
     * @param string $attributes
     * @param string $table
     *
     * @return string
     */
    public function onCopyChildsButtonCallback(array $row, string $href, string $label, string $title, string $icon, string $attributes, string $table): string
    {
        if ($GLOBALS['TL_DCA'][$table]['config']['closed'] ?? null) {
            return '';
        }

        $active = (NodeModel::TYPE_FOLDER === $row['type']) && $this->permissionChecker->hasUserPermission(PermissionChecker::PERMISSION_CREATE);

        // Make the button active only if there are subnodes
        if ($active) {
            $active = $this->db->fetchColumn("SELECT COUNT(*) FROM $table WHERE pid=?", [$row['id']]) > 0;
        }

        return $this->generateButton($row, $href, $label, $title, $icon, $attributes, $active);
    }

    /**
     * On "delete" button callback.
     *
     * @param array  $row
     * @param string $href
     * @param string $label
     * @param string $title
     * @param string $icon
     * @param string $attributes
     *
     * @return string
     */
    public function onDeleteButtonCallback(array $row, string $href, string $label, string $title, string $icon, string $attributes): string
    {
        $active = true;

        // Allow delete if the user has permission
        if (!$this->permissionChecker->isUserAdmin()) {
            $rootIds = (array) func_get_arg(7);
            $active = $this->permissionChecker->hasUserPermission(PermissionChecker::PERMISSION_DELETE);

            // If the node is a root one, check if the user has permission to manage it
            if ($active && \in_array((int) $row['id'], $rootIds, true)) {
                $active = $this->permissionChecker->hasUserPermission(PermissionChecker::PERMISSION_ROOT);
            }
        }

        return $this->generateButton($row, $href, $label, $title, $icon, $attributes, $active);
    }

    /**
     * On label callback.
     *
     * @param array              $row
     * @param string             $label
     * @param DataContainer|null $dc
     * @param string             $imageAttribute
     * @param bool               $returnImage
     *
     * @return string
     */
    public function onLabelCallback(array $row, string $label, DataContainer $dc = null, string $imageAttribute = '', bool $returnImage = false): string
    {
        $image = (NodeModel::TYPE_CONTENT === $row['type']) ? 'articles.svg' : 'folderC.svg';

        // Return the image only
        if ($returnImage) {
            return Image::getHtml($image, '', $imageAttribute);
        }

        $languages = [];
        $allLanguages = System::getLanguages();

        // Generate the languages
        foreach (StringUtil::trimsplit(',', $row['languages']) as $language) {
            $languages[] = $allLanguages[$language];
        }

        $tags = [];
        $tagIds = Model::getRelatedValues('tl_node', 'tags', $row['id']);

        // Generate the tags
        if (\count($tagIds) > 0) {
            /** @var Tag $tag */
            foreach ($this->tagsManager->getFilteredTags($tagIds) as $tag) {
                $tags[] = $tag->getName();
            }
        }

        return sprintf(
            '%s <a href="%s" title="%s">%s</a>%s%s',
            Image::getHtml($image, '', $imageAttribute),
            Backend::addToUrl('nn='.$row['id']),
            StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['selectNode']),
            $label,
            (\count($languages) > 0) ? sprintf(' <span class="tl_gray" style="margin-left:3px;">[%s]</span>', implode(', ', $languages)) : '',
            (\count($tags) > 0) ? sprintf(' <span class="tl_gray" style="margin-left:3px;">[%s]</span>', implode(', ', $tags)) : ''
        );
    }

    /**
     * On languages options callback.
     *
     * @param DataContainer|null $dc
     *
     * @return array
     */
    public function onLanguagesOptionsCallback(): array
    {
        return System::getLanguages();
    }

    /**
     * On execute the post actions.
     *
     * @param string        $action
     * @param DataContainer $dc
     */
    public function onExecutePostActions($action, DataContainer $dc): void
    {
        if ('reloadNodePickerWidget' === $action) {
            $this->reloadNodePickerWidget($dc);
        }
    }

    /**
     * Generate the button.
     *
     * @param array  $row
     * @param string $href
     * @param string $label
     * @param string $title
     * @param string $icon
     * @param string $attributes
     * @param bool   $active
     *
     * @return string
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
     *
     * @param DataContainer $dc
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

        // The field does not exist
        if (!isset($GLOBALS['TL_DCA'][$dc->table]['fields'][$field])) {
            $this->logger->log(
                LogLevel::ERROR,
                sprintf('Field "%s" does not exist in DCA "%s"', $field, $dc->table),
                ['contao' => new ContaoContext(__METHOD__, TL_ERROR)]
            );

            throw new BadRequestHttpException('Bad request');
        }

        $row = null;
        $value = null;

        // Load the value
        if ('overrideAll' !== Input::get('act') && $id > 0 && $this->db->getSchemaManager()->tablesExist([$dc->table])) {
            $row = $this->db->fetchAssoc("SELECT * FROM {$dc->table} WHERE id=?", [$id]);

            // The record does not exist
            if (!$row) {
                $this->logger->log(
                    LogLevel::ERROR,
                    sprintf('A record with the ID "%s" does not exist in table "%s"', $id, $dc->table),
                    ['contao' => new ContaoContext(__METHOD__, TL_ERROR)]
                );

                throw new BadRequestHttpException('Bad request');
            }

            $value = $row->$field;
            $dc->activeRecord = $row;
        }

        // Call the load_callback
        if (\is_array($GLOBALS['TL_DCA'][$dc->table]['fields'][$field]['load_callback'])) {
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
     *
     * @param DataContainer $dc
     */
    private function toggleSwitchToEditFlag(DataContainer $dc): void
    {
        if (!$dc->id || !$this->permissionChecker->hasUserPermission(PermissionChecker::PERMISSION_CONTENT)) {
            return;
        }

        $type = $this->db->fetchColumn('SELECT type FROM tl_node WHERE id=?', [$dc->id]);

        if (NodeModel::TYPE_CONTENT === $type) {
            $GLOBALS['TL_DCA'][$dc->table]['config']['switchToEdit'] = true;
        }
    }

    /**
     * Check the permissions.
     *
     * @param DataContainer $dc
     */
    private function checkPermissions(DataContainer $dc): void
    {
        if ($this->permissionChecker->isUserAdmin()) {
            return;
        }

        // Close the table if user is not allowed to create new records
        if (!$this->permissionChecker->hasUserPermission(PermissionChecker::PERMISSION_CREATE)) {
            $GLOBALS['TL_DCA'][$dc->table]['config']['closed'] = true;
            $GLOBALS['TL_DCA'][$dc->table]['config']['notCopyable'] = true;
        }

        // Set the flag if user is not allowed to edit records
        if (!$this->permissionChecker->hasUserPermission(PermissionChecker::PERMISSION_EDIT)) {
            $GLOBALS['TL_DCA'][$dc->table]['config']['notEditable'] = true;
        }

        // Set the flag if user is not allowed to delete records
        if (!$this->permissionChecker->hasUserPermission(PermissionChecker::PERMISSION_DELETE)) {
            $GLOBALS['TL_DCA'][$dc->table]['config']['notDeletable'] = true;
        }

        $session = $this->session->all();

        // Filter allowed page IDs
        if (\is_array($session['CURRENT']['IDS'])) {
            $session['CURRENT']['IDS'] = $this->permissionChecker->filterAllowedIds(
                $session['CURRENT']['IDS'],
                ('deleteAll' === Input::get('act')) ? PermissionChecker::PERMISSION_DELETE : PermissionChecker::PERMISSION_EDIT
            );

            $this->session->replace($session);
        }

        // Limit the allowed roots for the user
        if (null !== ($roots = $this->permissionChecker->getUserAllowedRoots())) {
            if (isset($GLOBALS['TL_DCA'][$dc->table]['list']['sorting']['root']) && \is_array($GLOBALS['TL_DCA'][$dc->table]['list']['sorting']['root'])) {
                $GLOBALS['TL_DCA'][$dc->table]['list']['sorting']['root'] = array_intersect($GLOBALS['TL_DCA'][$dc->table]['list']['sorting']['root'], $roots);
            } else {
                $GLOBALS['TL_DCA'][$dc->table]['list']['sorting']['root'] = $roots;
            }

            // Allow root paste if the user has enough permission
            if ($this->permissionChecker->hasUserPermission(PermissionChecker::PERMISSION_ROOT)) {
                $GLOBALS['TL_DCA'][$dc->table]['list']['sorting']['rootPaste'] = true;
            }

            // Check current action
            if (($action = Input::get('act')) && 'paste' !== $action) {
                switch ($action) {
                    case 'edit':
                        $nodeId = (int) Input::get('id');

                        // Dynamically add the record to the user profile
                        if (!$this->permissionChecker->isUserAllowedNode($nodeId)) {
                            /** @var \Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface $sessionBag */
                            $sessionBag = $this->session->getbag('contao_backend');

                            $newRecords = $sessionBag->get('new_records');
                            $newRecords = \is_array($newRecords[$dc->table]) ? array_map('intval', $newRecords[$dc->table]) : [];

                            if (\in_array($nodeId, $newRecords, true)) {
                                $this->permissionChecker->addNodeToAllowedRoots($nodeId);
                            }
                        }
                    // no break;

                    case 'copy':
                    case 'delete':
                    case 'show':
                        if (!isset($nodeId)) {
                            $nodeId = (int) Input::get('id');
                        }

                        if (!$this->permissionChecker->isUserAllowedNode($nodeId)) {
                            throw new AccessDeniedException(sprintf('Not enough permissions to %s node ID %s.', $action, $nodeId));
                        }
                        break;
                    case 'editAll':
                    case 'deleteAll':
                    case 'overrideAll':
                        if (\is_array($session['CURRENT']['IDS'])) {
                            $session['CURRENT']['IDS'] = array_intersect($session['CURRENT']['IDS'], $roots);
                            $this->session->replace($session);
                        }
                        break;
                }
            }
        }
    }

    /**
     * Add a breadcrumb menu.
     *
     * @param DataContainer $dc
     *
     * @throws \RuntimeException
     */
    private function addBreadcrumb(DataContainer $dc): void
    {
        /** @var AttributeBagInterface $session */
        $session = $this->session->getBag('contao_backend');

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
        $user = BackendUser::getInstance();

        // Generate breadcrumb trail
        if ($nodeId) {
            $id = $nodeId;

            do {
                $node = $this->db->fetchAssoc("SELECT * FROM {$dc->table} WHERE id=?", [$id]);

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
                if (!$user->isAdmin && $user->hasAccess($node['id'], 'nodeMounts')) {
                    break;
                }

                $id = $node['pid'];
            } while ($id > 0 && 'root' !== $node['type']);
        }

        // Check whether the node is mounted
        if (!$user->hasAccess($ids, 'nodeMounts')) {
            $session->set(self::BREADCRUMB_SESSION_KEY, 0);
            throw new AccessDeniedException('Node ID '.$nodeId.' is not mounted.');
        }

        // Limit tree
        $GLOBALS['TL_DCA'][$dc->table]['list']['sorting']['root'] = [$nodeId];

        // Add root link
        $links[] = Image::getHtml($GLOBALS['TL_DCA'][$dc->table]['list']['sorting']['icon']).' <a href="'.Backend::addToUrl('nn=0').'" title="'.StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['selectAllNodes']).'">'.$GLOBALS['TL_LANG']['MSC']['filterAll'].'</a>';
        $links = array_reverse($links);

        // Insert breadcrumb menu
        $GLOBALS['TL_DCA'][$dc->table]['list']['sorting']['breadcrumb'] .= '

<nav aria-label="'.$GLOBALS['TL_LANG']['MSC']['breadcrumbMenu'].'">
  <ul id="tl_breadcrumb">
    <li>'.implode(' › </li><li>', $links).'</li>
  </ul>
</nav>';
    }
}
