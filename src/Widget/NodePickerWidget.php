<?php

/*
 * Node Bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2019, terminal42 gmbh
 * @author     terminal42 <https://terminal42.ch>
 * @license    MIT
 */

namespace Terminal42\NodeBundle\Widget;

use Contao\Database;
use Contao\StringUtil;
use Contao\System;
use Contao\Widget;
use Terminal42\NodeBundle\EventListener\DataContainerListener;
use Terminal42\NodeBundle\Model\NodeModel;

class NodePickerWidget extends Widget
{
    /**
     * Submit user input.
     *
     * @var bool
     */
    protected $blnSubmitInput = true;

    /**
     * Template.
     *
     * @var string
     */
    protected $strTemplate = 'be_widget';

    /**
     * Generate the widget and return it as string.
     *
     * @return string
     */
    public function generate()
    {
        $container = System::getContainer();
        $values = [];

        // Can be an array
        if (!empty($this->varValue) && null !== ($nodes = NodeModel::findMultipleByIds((array) $this->varValue))) {
            /** @var DataContainerListener $eventListener */
            $eventListener = $container->get('terminal42_node.listener.data_container');

            /** @var NodeModel $node */
            foreach ($nodes as $node) {
                $values[$node->id] = strip_tags($eventListener->onLabelCallback($node->row(), $node->name, $this->objDca), '<img><span>');
            }
        }

        $return = '<input type="hidden" name="'.$this->strName.'" id="ctrl_'.$this->strId.'" value="'.implode(',', array_keys($values)).'">
  <input type="hidden" name="'.$this->strOrderName.'" id="ctrl_'.$this->strOrderId.'" value="'.implode(',', array_keys($values)).'">
  <div class="selector_container">'.((\count($values) > 1) ? '
    <p class="sort_hint">'.$GLOBALS['TL_LANG']['MSC']['dragItemsHint'].'</p>' : '').'
    <ul id="sort_'.$this->strId.'" class="'.($this->sorting ? 'sortable' : '').'">';

        foreach ($values as $k => $v) {
            $return .= '<li style="cursor:move;" data-id="'.$k.'">'.$v.'</li>';
        }

        $return .= '</ul>';
        $pickerBuilder = $container->get('contao.picker.builder');

        if (!$pickerBuilder->supportsContext('node')) {
            $return .= '
	<p><button class="tl_submit" disabled>'.$GLOBALS['TL_LANG']['MSC']['changeSelection'].'</button></p>';
        } else {
            $extras = ['fieldType' => $this->fieldType];

            if (\is_array($this->rootNodes)) {
                $extras['rootNodes'] = array_values($this->rootNodes);
            }

            $return .= '
	<p><a href="'.ampersand($pickerBuilder->getUrl('node', $extras)).'" class="tl_submit" id="pt_'.$this->strName.'">'.$GLOBALS['TL_LANG']['MSC']['changeSelection'].'</a></p>
	<script>
	  $("pt_'.$this->strName.'").addEvent("click", function(e) {
		e.preventDefault();
		Backend.openModalSelector({
		  "id": "tl_listing",
		  "title": "'.StringUtil::specialchars(str_replace("'", "\\'", $GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['label'][0])).'",
		  "url": this.href + document.getElementById("ctrl_'.$this->strId.'").value,
		  "callback": function(table, value) {
			new Request.Contao({
			  evalScripts: false,
			  onSuccess: function(txt, json) {
				$("ctrl_'.$this->strId.'").getParent("div").set("html", json.content);
				json.javascript && Browser.exec(json.javascript);
			  }
			}).post({"action":"reloadNodePickerWidget", "name":"'.$this->strId.'", "value":value.join("\t"), "REQUEST_TOKEN":"'.REQUEST_TOKEN.'"});
		  }
		});
	  });
	</script>
	<script>Backend.makeMultiSrcSortable("sort_'.$this->strId.'", "ctrl_'.$this->strId.'", "ctrl_'.$this->strId.'")</script>';
        }

        $return = '<div>'.$return.'</div></div>';

        return $return;
    }

    /**
     * Return an array if the "multiple" attribute is set.
     *
     * @param mixed $input
     *
     * @return mixed
     */
    protected function validator($input)
    {
        $this->checkValue($input);

        if ($this->hasErrors()) {
            return '';
        }

        if (!$input) {
            if ($this->mandatory) {
                $this->addError(sprintf($GLOBALS['TL_LANG']['ERR']['mandatory'], $this->strLabel));
            }

            return '';
        } elseif (false === strpos($input, ',')) {
            return $this->multiple ? [(int) $input] : (int) $input;
        }

        $value = array_map('intval', array_filter(explode(',', $input)));

        return $this->multiple ? $value : $value[0];
    }

    /**
     * Check the selected value.
     *
     * @param string $input
     */
    protected function checkValue($input)
    {
        if ('' === $input || !\is_array($this->rootNodes)) {
            return;
        }

        if (false === strpos($input, ',')) {
            $ids = [(int) $input];
        } else {
            $ids = array_map('intval', array_filter(explode(',', $input)));
        }

        if (\count(array_diff($ids, array_merge($this->rootNodes, Database::getInstance()->getChildRecords($this->rootNodes, 'tl_node')))) > 0) {
            $this->addError($GLOBALS['TL_LANG']['ERR']['invalidPages']);
        }
    }
}
