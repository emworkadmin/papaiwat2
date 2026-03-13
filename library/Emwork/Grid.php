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
 * @see Emwork_Grid_Table
 */
require_once 'Emwork/Grid/Table.php';

/**
 * @see Emwork_Grid_TableColumn
 */
require_once 'Emwork/Grid/TableColumn.php';

/**
 * @see Emwork_Grid_SearchPanel
 */
require_once 'Emwork/Grid/SearchPanel.php';

/**
 * @category   Emwork
 * @package    Emwork_Grid
 * @copyright  Copyright (c)
 * @license
 */
Class Emwork_Grid
{
	/**
	 * @var string
	 */
	private $name = "grid";

	/**
	 * @var Emwork_Grid_Table
	 */
	private $_table ;
	/**
	 * @var Emwork_Grid_SearchPanel
	 */
	private $_searchPanel ;

	private $_hasSearch = true;

	private $_hasBasicSearch = true;

	public function __construct($name, $view)
	{
		if (empty($name)) {
			throw new Exceptin("You must name the Name.");
		}

		$this->name = $name;
		$tableName = "tbl" . $name;
		$this->_table = new Emwork_Grid_Table($tableName);
		$this->_searchPanel = new Emwork_Grid_SearchPanel("divSearch" . $name, $tableName, $view);

		$this->_searchPanel->setTable($tableName);
	}

	// Table Part

	/**
	 * Returns the identified Table name
	 *
	 * The name of this Table is concatenate "tbl" and GridName
	 *
	 * @return string
	 */
	public function getTableName() {
		return $this->_table->getTableName();
	}

	/**
	 * Returns the identified pager name
	 *
	 * The name of this Table is concatenate "pager_tbl" and GridName
	 *
	 * @return string
	 */
	public function getTablePagerName() {
		return $this->_table->getPagerName();
	}

	/**
	 * Set Caption on Table
	 *
	 * It show caption on the table. if you don't set,
	 * it don't show caption part on the table.
	 *
	 * @param  string $caption  caption
	 */
	public function setTableCaption($caption)
	{
		$this->_table->setCaption($caption);
	}
	public function getTableCaption()
	{
		return $this->_table->getCaption();
	}
	public function setTableHeight($height)
	{
		$this->_table->setHeight($height);
	}
	public function setTableWidth($width)
	{
		$this->_table->setWidth($width);
	}
	public function setTablePostData($postData)
	{
		$this->_table->setPostData($postData);
	}
	public function setUrlPath($urlPath)
	{
		$this->_table->setUrlPath($urlPath);
		$this->_searchPanel->setSearchDefaultUrl($urlPath);
	}
	public function getUrlPath()
	{
		return $this->_table->getUrlPath();
	}
	public function setUrlInitPath($urlPath)
	{
		$this->_table->setUrlInitPath($urlPath);
	}
	public function setLinkPath($linkPath)
	{
		$this->_table->setLinkPath($linkPath);
	}
	public function setTableMultiselect($multiselect)
	{
		$this->_table->setMultiselect($multiselect);
	}
	public function setTablePage($page)
	{
		$this->_table->setPage($page);
	}
	public function setTableRowNum($rowNum)
	{
		$this->_table->setRowNum($rowNum);
	}
	public function setTableRowList($rowList)
	{
		$this->_table->setRowList($rowList);
	}
	public function setTableAutowidth($autowidth)
	{
		$this->_table->setAutowidth($autowidth);
	}
	public function setTableSortName($sortName)
	{
		$this->_table->setSortName($sortName);
	}
	public function setTableSortOrder($sortorder = 'desc')
	{
		$this->_table->setSortOrder($sortorder);
	}

	public function setTableColArray($colArray)
	{
		$this->_table->setColArray($colArray);
	}

	public function setTableGridComplete($jsScript)
	{
		$this->_table->setGridComplete($jsScript);
	}
	public function setTableGridFunction($jsScript)
	{
		$this->_table->setGridFunction($jsScript);
	}

	public function setTableLoadFunction($jsScript)
	{
		$this->_table->setLoadFunction($jsScript);
	}
	public function addTableColumn(Emwork_Grid_TableColumn $col)
	{
		$this->_table->addColumn($col);
	}

	// Search Part
	public function setHasSearch($hasSearch) {
		$this->_hasSearch = $hasSearch;
	}
	public function setHasBasicSearch($hasBasicSearch) {
		$this->_searchPanel->setHasBasic($hasBasicSearch);
	}
	public function getSearchPanelName()
	{
		return $this->_searchPanel->getPanelName();
	}

	public function setSearchButtonPosition($buttonPosition)
	{
		$this->_searchPanel->setButtonPosition($buttonPosition);
	}
	public function setSearchLabel($searchLabel)
	{
		$this->_searchPanel->setSearchLabel($searchLabel);
	}
	public function setSearchButtonLabel($searchButtonLabel)
	{
		$this->_searchPanel->setSearchButtonLabel($searchButtonLabel);
	}
	public function setResetButtonLabel($resetButtonLabel)
	{
		$this->_searchPanel->setResetButtonLabel($resetButtonLabel);
	}

	public function setSearchBasicFilterOption($basicFilterOption)
	{
		$this->_searchPanel->setBasicFilterOption($basicFilterOption);
	}
	public function setSearchBasicFilterValueInit($basicFilterValueInit)
	{
		$this->_searchPanel->setBasicFilterValueInit($basicFilterValueInit);
	}
	public function setSearchBasicTextValueInit($basicTextValueInit)
	{
		$this->_searchPanel->setBasicTextValueInit($basicTextValueInit);
	}
	public function autoComplete($hasAutoComplete, $autoCompleteUrl, $minChar, $limit)
	{
		$this->_searchPanel->autoComplete($hasAutoComplete, $autoCompleteUrl, $minChar, $limit);
	}

	public function setAdvanceSearchControl($hasAdvance, $isOpenAdvance, $alwaysShowAdvance)
	{
		$this->_searchPanel->setAdvanceSearchControl($hasAdvance, $isOpenAdvance, $alwaysShowAdvance);
	}
	public function setAdvanceToogleLabel($advanceToogleLabel)
	{
		$this->_searchPanel->setAdvanceToogleLabel($advanceToogleLabel);
	}
	public function getAdvanceToogleLabel()
	{
		$this->_searchPanel->getAdvanceToogleLabel();
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
	public function addAdvanceSearchControl($name, $label = '', $controlType = "select", $initValue = null, $attr = null, $option = array(), $cascadingOption = array())
	{
		$this->_searchPanel->addAdvanceSearchControl($name, $label, $controlType, $initValue, $attr, $option, $cascadingOption);
	}
	
	/**
	 * ล้างการกำหนดค่าของ Advance search control
	 */
	public function removeAllAdvanceSearchControl()
	{
		$this->_searchPanel->removeAllAdvanceSearchControl();
	}

	//    // Sort
	//    public function setSortOption($sortOption) {
	//        $this->sortOption = $sortOption;
	//    }

	/**
	 * 
	 * @return string render script
	 */
	public function renderScript()
	{
		$jsFormat = "";
		if ($this->_hasSearch) {
			$jsFormat .= $this->_searchPanel->renderScript();
		}
		$jsFormat .= $this->_table->renderScript();

		return $jsFormat;
	}

	/**
	 *
	 * @return string render Html
	 */
	public function renderHtml()
	{
		$html = "";
		if ($this->_hasSearch) {
			$html .= $this->_searchPanel->renderHtml();
		}
		$html .= $this->_table->renderHtml('simple');

		return $html;
	}
}

?>