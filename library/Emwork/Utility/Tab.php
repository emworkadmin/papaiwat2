<?php
/**
 * EMWORK Library
 *
 * @category   Emwork
 * @package    Emwork_Utility
 * @copyright
 * @license
 * @version
 */

/**
 * Static methods for Generate Tab Load Page
 *
 * @category   Emwork
 * @package    Emwork_Utility
 * @copyright
 * @license
 */
class Emwork_Utility_Tab
{
    protected $tabArray = array();
    protected $selectedIndex = 0;
    protected $tabName;

    public function __construct($tabName, $selectedIndex = 0, $tabArray = array()) {
        $this->setSelectedIndex($selectedIndex);
        $this->setTabArray($tabArray);
        if (empty($tabName))
            $tabName = uniqid();
        $this->tabName = $tabName;
    }

    public function setSelectedIndex($index) {
        if (empty($index)) {
            $index = 0;
        }
        $this->selectedIndex = $index;
    }
    public function getSelectedIndex() {
        return $this->selectedIndex;
    }


    public function setTabArray($tabArray) {
        if (!is_array($tabArray)) {
            return;
        }
        $this->tabArray = $tabArray;
    }
    public function addTabArray($tabArray) {
        if (!is_array($tabArray)) {
            return;
        }
        foreach ($tabArray as $tab) {
            $t = array();
            $t["ID"] = empty($tab["ID"]) ? uniqid() : $tab["ID"];
            $t["Title"] = $tab["Title"];
            $t["Url"] = $tab["Url"];
            $this->tabArray[] = $t;
        }
    }
    public function addTab($title, $url, $id) {
        $t = array();
        $t["ID"] = empty($id) ? uniqid() : $id;
        $t["Title"] = $title;
        $t["Url"] = $url;
        $this->tabArray[] = $t; 
    }
    public function getTabArray() {
        return $this->tabArray;
    }

    public function generateHtml() {
        $html = "";
        if (count($this->tabArray) > 0) {
            $html .= "<div id='{$this->tabName}' class='formTab'>" . "\n";
            $html .= "<ul>" . "\n";
            foreach($this->tabArray as $tab) {
                $html .= "<li><a href='#{$tab["ID"]}' page='{$tab["Url"]}'>{$tab["Title"]}</a></li>" . "\n";
            }
            $html .= "</ul>" . "\n";
            foreach($this->tabArray as $tab) {
                $html .= "<div id='{$tab["ID"]}'></div>" . "\n";
            }
            $html .= "</div>" . "\n";
        }
        return $html;
    }
    public function generateScript() {
        $script = "";
        if (count($this->tabArray) > 0) {
            $script =
                "jQuery('#{$this->tabName}').tabs({selected: -1, select : function(event, ui){ " . "\n"
               ."   var page = $(ui.tab).attr('page'); " . "\n"
               ."   var divTab = $(ui.tab).parent().parent().parent(); " . "\n"
               ."   divTab.find('.ui-tabs-panel').empty(); " . "\n"
               ."   if (page != null && page != ''){ " . "\n"
               ."       LoadPage(ui.panel, page); " . "\n"
               ."   } " . "\n"
               ."}}); " . "\n"
               ."$('#{$this->tabName}').tabs('select', 0);" . "\n";
        }
        return $script;
    }

}
