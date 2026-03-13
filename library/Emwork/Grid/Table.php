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
 * @see Emwork_Grid_TableColumn
 */
require_once 'Emwork/Grid/TableColumn.php';

/**
 * @category   Emwork
 * @package    Emwork_Grid
 * @copyright  Copyright (c)
 * @license
 */
Class Emwork_Grid_Table
{

	private $tblName = "emGrid";
	private $pagerName = "pager_emGrid";
	private $caption = "";
	private $width = 700;
	private $height = 250;
	private $urlInitPath = "";
	private $urlPath = "";
	private $linkPath = "";
	private $autowidth = true;
	private $colArray = array();
	private $jsGridComplete = '';
	private $jsGridFunctionComplete = '';
	private $jsLoadFunctionComplete = '';
	private $multiSelect = false;
	private $page = 1;
	private $rowNum = 10;
	private $rowList = array(10, 20, 30);
	private $sortName = '';
	private $sortOrder = 'desc';
	private $postData = "{}";


	public function setTableName($tblName)
	{
		if (empty($tblName)) {
			$this->tblName = "tbl" . uniqid();
		} else {
			$this->tblName = $tblName;
		}
		$this->pagerName = "pager_" . $this->tblName;
	}
	public function getTableName() {
		return $this->tblName;
	}
	public function getPagerName() {
		return $this->pagerName;
	}
	public function setCaption($caption)
	{
		$this->caption = $caption;
	}
	public function getCaption($caption)
	{
		return $this->caption;
	}
	public function setHeight($height)
	{
		if ($height > 0) {
			$this->height = $height;
		}
	}
	public function setWidth($width)
	{
		if ($width > 0) {
			$this->width = $width;
		}
	}
	public function setPostData($postData)
	{
		if (!is_array($postData)){

		}
		$this->postData = Zend_Json_Encoder::encode($postData);
	}
	public function setUrlPath($urlPath)
	{
		$this->urlPath = $urlPath;
	}
	public function getUrlPath()
	{
		return $this->urlPath;
	}
	public function setUrlInitPath($urlPath)
	{
		$this->urlInitPath = $urlPath;
	}
	public function getUrlInitPath()
	{
		return $this->urlInitPath;
	}
	public function setLinkPath($linkPath)
	{
		$this->linkPath = $linkPath;
	}
	public function setMultiselect($multiselect)
	{
		if (!is_null($multiselect)) {
			$this->multiSelect = $multiselect;
		}
	}
	public function setPage($page)
	{
		if ($page > 0) {
			$this->page = $page;
		}
	}
	public function setRowNum($rowNum)
	{
		if (!empty($rowNum)) {
			$this->rowNum = $rowNum;
		}
	}
	public function setRowList($rowList)
	{
		if (!is_null($rowList)) {
			if (!is_array($rowList)){
				$rowList = explode(',', $rowList);
			}
			if (count($rowList) > 0) {
				$this->rowList = $rowList;
			}
		}
	}
	public function setAutowidth($autowidth)
	{
		$this->autowidth = $autowidth;
	}
	public function setSortName($sortName)
	{
		$this->sortName = $sortName;
	}
	public function setSortOrder($sortorder = 'desc')
	{
		$sortorder = strtolower($sortorder);
		if ($sortorder == 'desc' || $sortorder == 'asc') {
			$this->sortOrder = $sortorder;
		}
	}

	public function setColArray($colArray)
	{
		unset($this->colArray);
		foreach ($colArray as $col) {
			$this->addColumn($col);
		}
	}

	public function setGridComplete($jsScript)
	{
		$this->jsGridComplete = $jsScript;
	}
	public function setGridFunction($jsScript)
	{
		$this->jsGridFunctionComplete = $jsScript;
	}
	public function setLoadFunction($jsScript)
	{
		$this->jsLoadFunctionComplete = $jsScript;
	}
	public function __construct($tblName, $caption = null, $urlPath = '', $linkPath = '', $width = 700, $height = 250, $page = 1, $rowNum = 10, $rowList = array(10, 20, 30))
	{
		$this->setTableName($tblName);
		$this->setCaption($caption);
		$this->setUrlPath($urlPath);
		$this->setLinkPath($linkPath);
		$this->setWidth($width);
		$this->setHeight($height);
		$this->setPage($page);
		$this->setRowNum($rowNum);
		$this->setRowList($rowList);
	}

	public function addColumn(Emwork_Grid_TableColumn $col)
	{
		if ($col instanceof Emwork_Grid_TableColumn) {
			$this->colArray[] = $col;
		}
	}

	public function renderScript()
	{

		$caption = empty($this->caption) ? "" : 'caption: "' . $this->caption . '",';
		$colModelValue = $this->renderColumns($this->colArray);
		$rowListValue = $this->renderRowList($this->rowList);
		$url = $this->urlPath;

		if (!empty($this->urlInitPath)) {
			$url = $this->urlInitPath;
		}

		$jsFormat .= 'jQuery("#' . $this->tblName . '").jqGrid({
				datatype: "json",
				postData : ' . $this->postData . ',
						url: "' . $url . '" ,
								height: ' . $this->height . ' ,
										width: ' . $this->width . ',
												autowidth:' . ($this->autowidth ? "true" : "false") . ',
														colModel: [
														' . $colModelValue . '
																],
																multiselect: ' . ($this->multiSelect ? "true" : "false") . ',
																		' . $caption . '
																				pager: "#' . $this->pagerName . '",
																						rowList: [' . $rowListValue . '],
																								viewrecords: true,
																								rownumbers: true,
																								rownumWidth: 40,
																								rowNum: ' . $this->rowNum . ',
																										sortname: "' . $this->sortName . '",
																												sortorder: "' . $this->sortOrder . '",
																														gridComplete: function() {
																														' . $this->jsGridFunctionComplete . '
																																jQuery(".jqselect", "#' . $this->tblName . '").click(function() {
																																		var id = jQuery(this).attr("dataid");
																																		if (id) {
																																		' . $this->gridComplete() . '
	}
	});
	},
																																				loadComplete: function () {
																																				' . $this->jsLoadFunctionComplete . '
	},
																																						jsonReader: {
																																						page: "page",
																																						total: "total",
																																						records: "records",
																																						repeatitems: false,
																																						root: "rows",
																																						cell: "cell",
																																						id: "id"
	}
	});
																																						jQuery("#' . $this->tblName . '").jqGrid("navGrid", "#' . $this->pagerName . '",
																																						{ edit: false, del: false, add: false, search:false }, //options
																																						{}, // edit options
																																						{}, // add options
																																						{}, // del options
																																						{}, // search options
																																						{}  // view option
																																								);

																																								'
																																								;

																																								if (!empty($this->linkPath))
																																									$jsFormat .= $this->selecltList();

																																								return $jsFormat;
	}

	public function renderHtml($tblType = 'simple')
	{
		$tblFormat = '<div class="grid-resize"><table id="' . $this->tblName . '"></table><div id="' . $this->pagerName . '"></div></div>';
		return $tblFormat;
	}

	private function gridComplete()
	{
		if ($this->linkPath != '') {
			$script = ' window.location.href="' . $this->linkPath . '?itemid="+id;';
		}
		if ($this->jsGridComplete != '') {
			$script = $this->jsGridComplete;
		}
		return $script;
	}
	private function renderColumns($colArray)
	{
		$colStrArray = array();
		foreach($colArray as $col) {
			if ($col instanceof Emwork_Grid_TableColumn) {
				$colStrArray[] = $col->rederColumn();
			}
		}
		return implode(",", $colStrArray);
	}
	private function renderRowList($rowList)
	{
		return implode(",", $rowList);
	}

	private function selecltList()
	{
		$script = 'function SelectList(cellvalue, options, rowObject) {return "<a href=\'javascript:void(0);\' dataid=\'" + options.rowId + "\' class=\'jqselect\'>" + cellvalue + "</a>";}';
		return $script;
	}
}

?>