<?php

declare(strict_types=1);

namespace Terminal42\NodeBundle\EventListener;

use Codefog\HasteBundle\Model\DcaRelationsModel;
use Codefog\TagsBundle\Manager\ManagerInterface;
use Codefog\TagsBundle\Tag;
use Contao\Backend;
use Contao\BackendUser;
use Contao\Controller;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Exception\ResponseException;
use Contao\CoreBundle\Intl\Locales;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\Security\DataContainer\ReadAction;
use Contao\CoreBundle\Twig\Finder\FinderFactory;
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
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;
use Terminal42\NodeBundle\Model\NodeModel;
use Terminal42\NodeBundle\Security\NodePermissions;
use Terminal42\NodeBundle\Widget\NodePickerWidget;

class DataContainerListener
{
    public const BREADCRUMB_SESSION_KEY = 'tl_node_node';

    public function __construct(
        private readonly Connection $connection,
        private readonly FinderFactory $finderFactory,
        private readonly Locales $locales,
        private readonly LoggerInterface $logger,
        private readonly RequestStack $requestStack,
        private readonly Security $security,
        private readonly TranslatorInterface $translator,
        private readonly ManagerInterface $tagsManager,
    ) {
    }

    #[AsCallback('tl_node', 'config.onload')]
    public function onLoadCallback(DataContainer $dc): void
    {
        $this->addBreadcrumb($dc);
        $this->checkPermissions();
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

    #[AsCallback('tl_node', 'list.operations.children.button')]
    public function onChildrenButtonCallback(array $row, string $href, string $label, string $title, string $icon, string $attributes): string
    {
        return $this->generateButton($row, $href, $label, $title, $icon, $attributes, NodeModel::TYPE_FOLDER !== $row['type']);
    }

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

        $extras = [];

        if ([] !== $languages) {
            $extras[] = implode(', ', $languages);
        }

        if ([] !== $tags) {
            $extras[] = implode(', ', $tags);
        }

        if (NodeModel::TYPE_CONTENT === $row['type']) {
            $extras[] = \sprintf('ID: %d', $row['id']);

            if ($row['alias']) {
                $extras[] = \sprintf('%s: %s', $GLOBALS['TL_LANG']['tl_node']['alias'][0], $row['alias']);
            }
        }

        return \sprintf(
            '%s <a href="%s" title="%s">%s</a>%s',
            Image::getHtml($image, '', $imageAttribute),
            Backend::addToUrl('nn='.$row['id']),
            StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['selectNode']),
            $label,
            $extras ? ' '.implode('', array_map(static fn (string $v) => \sprintf('<span class="tl_gray" style="margin-left:3px;">[%s]</span>', $v), $extras)) : '',
        );
    }

    #[AsCallback('tl_node', 'fields.languages.options')]
    public function onLanguagesOptionsCallback(): array
    {
        return $this->locales->getLocales(null, true);
    }

    #[AsCallback('tl_node', 'fields.groups.options')]
    public function onGroupsOptionsCallback(): array
    {
        $options = [-1 => $this->translator->trans('MSC.guests', [], 'contao_default')];
        $groups = $this->connection->fetchAllAssociative('SELECT id, name FROM tl_member_group WHERE tstamp>0 ORDER BY name');

        foreach ($groups as $group) {
            $options[$group['id']] = $group['name'];
        }

        return $options;
    }

    #[AsCallback('tl_node', 'fields.nodeTpl.options')]
    public function onNodeTplOptionsCallback(): array
    {
        return $this->finderFactory
            ->create()
            ->identifier('node')
            ->extension('html.twig')
            ->withVariants()
            ->asTemplateOptions()
        ;
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

    private function checkPermissions(): void
    {
        $user = $this->security->getUser();

        if (!$user instanceof BackendUser) {
            return;
        }

        if ($user->isAdmin) {
            return;
        }

        // Set root IDs
        if (empty($user->nodeMounts) || !\is_array($user->nodeMounts)) {
            $root = [0];
        } else {
            $root = $user->nodeMounts;
        }

        $GLOBALS['TL_DCA']['tl_node']['list']['sorting']['root'] = $root;
    }

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
                if (!$this->security->isGranted(ContaoCorePermissions::DC_PREFIX.$dc->table, new ReadAction($dc->table, $node))) {
                    break;
                }

                $id = $node['pid'];
            } while ($id > 0 && 'root' !== $node['type']);
        }

        // Check whether the node is mounted
        foreach ($ids as $id) {
            if (!$this->security->isGranted(ContaoCorePermissions::DC_PREFIX.$dc->table, new ReadAction($dc->table, ['id' => $id]))) {
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
