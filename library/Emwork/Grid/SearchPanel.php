<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 *
 * @category   Emwork
 * @package    Emwork_Grid
 * @copyright  Copyright (c)
 * @license
 * @version  1.0
 *
 * 21 Feb 2012
 */


/**
 * @category   Emwork
 * @package    Emwork_Grid
 * @copyright  Copyright (c)
 * @license
 */
Class Emwork_Grid_SearchPanel
{

	private $panelName = "divSearch";
	private $_view;
	private $tableName;
	private $searchInitUrl;
	private $searchDefaultUrl;

	private $hasBasic = true;
	private $basicFilterOption = array();
	private $basicFilterValueInit = "";
	private $basicTextValueInit = "";
	private $sortOption = array();

	private $searchLabel = "ค้นหาจาก";
	private $searchButtonLabel = "ค้นหา";
	private $resetButtonLabel = "เริ่มใหม่";
	private $buttonPosition = "top";

	private $alwaysShowAdvance = false;
	private $hasAdvance = false;
	private $isOpenAdvance = false;
	private $advanceToogleLabel = "ค้นหาขั้นสูง";
	private $advanceControlArray = array();

	private $hasAutoComplete = false;
	private $autoCompleteUrl = "";
	private $autoCompleteMinChar = 1;
	private $autoCompleteLimit = 10;

	private $hasSort = false;

	public function __construct($panelName, $tableName, $view)
	{
		$this->setPanelName($panelName);
		$this->setTable($tableName);
		$this->setView($view);
	}

	// Panel
	public function setPanelName($panelName)
	{
		if (empty($panelName)) {
			$this->panelName = "divSearch" . uniqid();
		} else {
			$this->panelName = $panelName;
		}
	}
	public function getPanelName()
	{
		return $this->panelName;
	}

	// Button and General
	public function setButtonPosition($buttonPosition)
	{
		$buttonPosition = strtolower($buttonPosition);
		if ($buttonPosition == "top" || $buttonPosition == "bottom") {
			$this->buttonPosition = $buttonPosition;
		}
	}
	public function setSearchLabel($searchLabel)
	{
		$this->searchLabel = $searchLabel;
	}
	public function setSearchButtonLabel($searchButtonLabel)
	{
		$this->searchButtonLabel = $searchButtonLabel;
	}
	public function setResetButtonLabel($resetButtonLabel)
	{
		$this->resetButtonLabel = $resetButtonLabel;
	}

	// Basic Search
	public function setHasBasic($hasBasic)
	{
		$this->hasBasic = $hasBasic;
	}
	public function setBasicFilterOption($basicFilterOption)
	{
		$this->basicFilterOption = $basicFilterOption;
	}
	public function setBasicFilterValueInit($basicFilterValueInit)
	{
		$this->basicFilterValueInit = $basicFilterValueInit;
	}
	public function setBasicTextValueInit($basicTextValueInit)
	{
		$this->basicTextValueInit = $basicTextValueInit;
	}
	public function autoComplete($hasAutoComplete, $autoCompleteUrl, $minChar, $limit)
	{
		$this->hasAutoComplete = $hasAutoComplete;
		if ($this->hasAutoComplete && !empty($autoCompleteUrl)) {
			$this->autoCompleteUrl = $autoCompleteUrl;
			$this->autoCompleteMinChar = is_null($minChar) ? 0 : $minChar;
			$this->autoCompleteLimit = empty($limit) ? $this->autoCompleteLimit : $limit;
		} else {
			$this->hasAutoComplete = false;
			$this->autoCompleteUrl = "";
		}
	}

	// Advance Search
	public function setAdvanceSearchControl($hasAdvance, $isOpenAdvance, $alwaysShowAdvance)
	{
		if (is_bool($hasAdvance)) {
			$this->hasAdvance = $hasAdvance;
		}
		if (is_bool($isOpenAdvance)) {
			$this->isOpenAdvance = $isOpenAdvance;
		}
		if (is_bool($alwaysShowAdvance)) {
			$this->alwaysShowAdvance = $alwaysShowAdvance;
		}
	}
	public function setAdvanceToogleLabel($advanceToogleLabel)
	{
		if (!empty($advanceToogleLabel)) {
			$this->advanceToogleLabel = $advanceToogleLabel;
		}
	}
	public function getAdvanceToogleLabel()
	{
		return $this->advanceToogleLabel;
	}
	
	/**
	 * Add advance search control
	 * @param string $name	ชื่อของ Control (เป็น attr ของ name)
	 * @param string $label		ชื่อที่แสดงในตัวค้นหา
	 * @param string $controlType	ประเภทของ Control (ประกอบด้วย 'date', 'text', 'select', 'cascading')
	 * @param string $initValue	ค่าตั้งต้น
	 * @param array $attr	กำหนดค่าเพิ่มเติมให้ attr ของ Control 		
	 * @param array $option	ข้อมูล (ใช้สำหรับ Control type ประเภท 'select')
	 * @param array $cascadingOption		กำหนดข้อมูลการทำงานของ Cascading (ใช้สำหรับ Control type ประเภท 'cascading')
	 */
	public function addAdvanceSearchControl($name, $label, $controlType, $initValue, $attr, $option, $cascadingOption)
	{
		$control["name"] = $name;
		$control["label"] = $label;
		$control["controlType"] = $controlType;
		$control["initValue"] = $initValue;
		$control["attr"] = $attr;
		$control["option"] = $option;

		/*
		 * cascadingOption
		*
		* @key     string  name
		* @key     string  url
		* @key     string  label
		* @key     string  dataname
		* @key     string  datavalue
		* @key     string  parentcontrolname
		* @key     string  parentinitvalue
		*/
		$control["cascadingOption"] = $cascadingOption;

		$this->advanceControlArray[] = $control;
	}
	public function removeAllAdvanceSearchControl()
	{
		unset($this->advanceControlArray);
	}

	// Sort
	public function setSortOption($sortOption) {
		$this->sortOption = $sortOption;
	}

	// View
	public function setView($view)
	{
		$this->_view = $view;
	}

	// Search
	public function setTable($tableName)
	{
		if (!empty($tableName)) {
			$this->tableName = $tableName;
		} else {
			throw new Exception("You must declare table name.");
		}
	}
	public function setSearchDefaultUrl($url)
	{
		$this->searchDefaultUrl = $url;
	}
	public function setSearchInitUrl($url)
	{
		$this->searchInitUrl = $url;
	}


	public function renderScript()
	{
		$jsFormat = "";

		// Button
		$firstChr = "?";
		if (strpos($this->searchDefaultUrl, "?")){
			$firstChr = "&";
		}
		//// Search
		$jsFormat .= "jQuery('.imbSearch', '#{$this->panelName}').button({icons: { primary: 'ui-icon-search'}}).click(function() { \n";
		$jsFormat .= "    var textstr = ''; \n";
		if ($this->hasBasic) {
			$jsFormat .= "    textstr = jQuery('#txtSearch', '#{$this->panelName}').val(); \n";
		}
		$jsFormat .= "    var advanceFilter = ''; \n";
		$jsFormat .= "    var condition = ''; \n";
		$jsFormat .= "    var lstFilter = ''; \n";

		if(count($this->advanceControlArray) > 0) {
			$advanceCondition = array();
			foreach ($this->advanceControlArray as $control) {
				$controlName = $control["name"];
				$jsFormat .= "    var {$controlName} = jQuery('#{$controlName}', '#{$this->panelName}').val(); \n";

				$advanceCondition[] = "'&" . substr($controlName, 3) . "='+" . $controlName;
			}
			$jsFormat .= "    advanceFilter = " . implode(" + ", $advanceCondition) . "; \n";
		}
		if (count($this->basicFilterOption) > 0 && $this->hasBasic) {
			$jsFormat .= "    lstFilter = $('#lstFilter', '#{$this->panelName}').val(); \n";
		}
		$jsFormat .= "    condition = '{$firstChr}textstr=' + textstr + '&filter=' + lstFilter + advanceFilter; \n";

		$jsFormat .= "    jQuery('#{$this->tableName}').jqGrid('setGridParam', { \n";
		$jsFormat .= "        url: '{$this->searchDefaultUrl}' + encodeURI(condition), \n";
		$jsFormat .= "        page: 1 \n";
		$jsFormat .= "    }).trigger('reloadGrid'); \n";
		$jsFormat .= "}); \n";

		//// Reset
		$jsFormat .= "jQuery('.imbReset', '#{$this->panelName}').button({icons: { primary: 'ui-icon-arrowrefresh-1-n'}}).click(function() { \n";
		$jsFormat .= "    $('input[type=text]', '#{$this->panelName}').val(''); \n";
		$jsFormat .= "    $('select', '#{$this->panelName}').find('option:eq(0)').attr('selected','selected'); \n";
		$jsFormat .= "    jQuery('#{$this->tableName}').jqGrid('setGridParam', { \n";
		$jsFormat .= "            url: '{$this->searchDefaultUrl}', \n";
		$jsFormat .= "            page: 1 \n";
		$jsFormat .= "    }).trigger('reloadGrid'); \n";
		$jsFormat .= "}); \n";

		$jsFormat .= "jQuery( '#txtSearch', '#{$this->panelName}' ).keypress(function(event) {   \n";
		$jsFormat .= "      if ( event.which == 13 ) {  \n";
		$jsFormat .= "          jQuery('.imbSearch', '#{$this->panelName}').click(); \n";
		$jsFormat .= "      }  \n";
		$jsFormat .= "});  \n";

		// AutoComplete
		if ($this->hasAutoComplete && !empty ($this->autoCompleteUrl)) {
			$jsFormat .= "jQuery( '#txtSearch', '#{$this->panelName}' ).autocomplete({  \n";
			$jsFormat .= "    source: function( request, response ) {  \n";
			$jsFormat .= "        jQuery.ajax({  \n";
			$jsFormat .= "            url: '{$this->autoCompleteUrl}',  \n";
			$jsFormat .= "            dataType: 'json',  \n";
			$jsFormat .= "            data: {  \n";
			$jsFormat .= "                filter: jQuery('#lstFilter', '#{$this->panelName}').val(),  \n";
			$jsFormat .= "                text: jQuery('#txtSearch', '#{$this->panelName}').val(),  \n";
			$jsFormat .= "                limit: '{$this->autoCompleteLimit}'  \n";
			$jsFormat .= "            },  \n";
			$jsFormat .= "            success: function( data ) {  \n";
			$jsFormat .= "                response( jQuery.map( data, function( item ) {  \n";
			$jsFormat .= "                    return {   \n";
			$jsFormat .= "                        label: item,  \n";
			$jsFormat .= "                        value: item  \n";
			$jsFormat .= "                    }  \n";
			$jsFormat .= "                }));  \n";
			$jsFormat .= "            }  \n";
			$jsFormat .= "        });  \n";
			$jsFormat .= "    },  \n";
			$jsFormat .= "    minLength: {$this->autoCompleteMinChar},  \n";
			$jsFormat .= "    open: function() {  \n";
			$jsFormat .= "          var valInput = jQuery(this).val();  \n";
			$jsFormat .= "          if(valInput != '') {  \n";
			$jsFormat .= "              jQuery('.ui-menu-item a').each(function(){  \n";
			$jsFormat .= "                  var matcher = new RegExp('(' + valInput + ')', 'ig' );  \n";
			$jsFormat .= "                  var s = jQuery(this).text();  \n";
			$jsFormat .= "                  var newText = s.replace(matcher, '<b>$1</b>');  \n";
			$jsFormat .= "                  jQuery(this).html(newText);  \n";
			$jsFormat .= "              });    \n";
			$jsFormat .= "          }   \n";
			$jsFormat .= "    }  \n";
			$jsFormat .= "});  \n";
		}

		// Toogle Advance Search
		if ($this->hasAdvance && !$this->alwaysShowAdvance) {
			$jsFormat .= "jQuery('#{$this->panelName}-lnkAdvanceSearch', '#{$this->panelName}').click(function() {  \n";
			$jsFormat .= "    $('#{$this->panelName}-advance', '#{$this->panelName}').slideToggle();  \n";
			$jsFormat .= "});  \n";
		}

		// Advance Control Script
		$advanceScript = $this->renderAdvanceControlScript($this->advanceControlArray);
		if (!empty($advanceScript)) {
			$jsFormat .= $advanceScript;
		}

		return $jsFormat;
	}
	public function renderHtml()
	{
		$searchLabelHtml = empty($this->searchLabel) ? "" : "<span class='text-info'>{$this->searchLabel} </span>";
		$searchTextHtml = $this->_view->formText("txtSearch", $this->basicTextValueInit, null);
		$searchButtonHtml = "<button type='button' class='imbSearch'>{$this->searchButtonLabel}</button>";
		$resetButtonHtml = "<button type='button' class='imbReset'>{$this->resetButtonLabel}</button>";
		$advanceToogleHtml = "<span class='lnkAdvanceSearch' id='{$this->panelName}-lnkAdvanceSearch'>{$this->advanceToogleLabel}</span>";
		$advanceHtml = $this->renderAdvanceControlHtml($this->advanceControlArray);

		//
		$html = "";
		$html .= "<div class='search-zone button-zone' id='{$this->panelName}'><form onkeypress='return event.keyCode != 13;'> \n";

		// Basic
		if ($this->hasBasic) {
			$html .= "  <div class='search-zone-content' id='{$this->panelName}-basic'> \n";
			$html .= "      {$searchLabelHtml} \n";
			if (count($this->basicFilterOption) > 0) {
				$html .= "      " . $this->_view->formSelect("lstFilter", $this->basicFilterValueInit, null, $this->basicFilterOption) . " \n";
			}
			$html .= "      " . $searchTextHtml . " \n";
			if ($this->buttonPosition == "top") {
				$html .= "      " . "<span class='search-zone-button'>{$searchButtonHtml}{$resetButtonHtml}</span> \n";
			}
			if ($this->hasAdvance && !$this->alwaysShowAdvance && !empty($advanceHtml)) {
				$html .= "      " . $advanceToogleHtml . " \n";
			}
			$html .= "  </div>";
		}

		// Advance
		if ($this->hasAdvance && !empty($advanceHtml)) {
			$html .= "  <div class='search-zone-advance' id='{$this->panelName}-advance' style='" . (($this->alwaysShowAdvance || $this->isOpenAdvance) ? "" : "display:none;") . "'> \n";
			$html .= "      " . $advanceHtml . " \n";
			$html .= "      <div class='clr'></div> \n";
			$html .= "  </div> \n";
		}

		// Sort
		if ($this->hasSort) {
			$html .= "  <div class='search-zone-sort' id='{$this->panelName}-sort'> \n";
			$html .= "  </div> \n";
		}

		// Button
		if ($this->buttonPosition == "bottom" || !$this->hasBasic) {
			$html .= "  <div class='search-zone-button' id='{$this->panelName}-button'> \n";
			$html .= "      <span class='search-zone-button'>{$searchButtonHtml}{$resetButtonHtml}</span> \n";
			$html .= "  </div> \n";
		}

		$html .= "</form></div> \n";
		return $html;
	}

	private function renderAdvanceControlScript($controlArray)
	{
		$script = "";
		if (count($controlArray) > 0) {
			foreach ($controlArray as $control) {
				switch($control["controlType"]) {
					case "date" :
						$controlName = $control["name"];
						$script .= "jQuery('#{$this->panelName} #{$controlName}').datepicker( {mode:'range', inline: true, calendars: 2});  \n";
						break;
					case "cascading" :
						$controlName = $control["name"];
						$cascadingOption = $control["cascadingOption"];
						$script .= "getData('{$cascadingOption["url"]}' + '{$cascadingOption["parentinitvalue"]}', '#{$this->panelName} #{$controlName}', '{$cascadingOption["label"]}', '{$cascadingOption["dataname"]}', '{$cascadingOption["datavalue"]}', '{$control["initValue"]}');  \n";
						if (!empty($cascadingOption["parentcontrolname"])) {
							$script .= "jQuery('#{$cascadingOption["parentcontrolname"]}', '#{$this->panelName}').change(function(){  \n";
							$script .= "    var selectValue = jQuery(this).val();  \n";
							$script .= "    getData('{$cascadingOption["url"]}' + selectValue, '#{$this->panelName} #{$controlName}', '{$cascadingOption["label"]}', '{$cascadingOption["dataname"]}', '{$cascadingOption["datavalue"]}', ''); \n";
							$script .= "}); \n";
						}
						break;
				}
			}
		}
		return $script;
	}
	private function renderAdvanceControlHtml($controlArray)
	{
		$html = "";
		if (count($controlArray) > 0) {
			foreach ($controlArray as $control) {
				$controlHtml = "";
				$controlName = "";
				switch($control["controlType"]) {
					case "date" :
						$controlName = $control["name"];
						$control["attr"]["class"] = (empty($control["attr"]["class"]) ? "" : " ") . "input-date";
						$controlHtml = $this->_view->formText($controlName, $control["initValue"], $control["attr"]);
						break;
					case "select" :
						$controlName = $control["name"];
						$controlHtml = $this->_view->formSelect($controlName, $control["initValue"], $control["attr"], $control["option"]);
						break;
					case "radio" :
						break;
					case "text" :
						$controlName = $control["name"];
						$control["attr"]["class"] = (empty($control["attr"]["class"]) ? "" : " ") . "input-text";
						$controlHtml = $this->_view->formText($controlName, $control["initValue"], $control["attr"]);
						break;
					case "cascading" :
						$controlName = $control["name"];
						$controlHtml = $this->_view->formSelect($controlName, null, $control["attr"], null);
						break;
				}
				$html .= '<div class = "search-zone-advance-filter">';
				$html .= "  <label for='{$controlName}' >{$control["label"]}</label>";
				$html .= "  {$controlHtml}";
				$html .= '</div>';
			}
		}
		return $html;
	}
}

?>