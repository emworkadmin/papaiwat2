<?php

Class Emwork_Grid_Grid {

	private $tblName = "emGrid";
	private $caption = "";
	private $width = 700;
	private $height = 220;
	private $urlPath = "";
	private $linkPath = "";
	private $autowidth = 'true';
	private $colName = array();
	private $colModel = array();
	private $jsGridComplete = '';
	private $jsGridFunctionComplete = '';
	private $multiselect = 'false';
	private $paging = '10,20,30';
	private $rowNum = '10';
	private $sortName = '';
	private $sortorder = 'desc';
	private $postData = "{}";
	//private $postData

	function __construct($tblName, $caption, $colName, $colModel, $urlPath = '', $linkPath = '', $width = 700, $height = 220, $paging = '10,20,30', $rowNum = '10') {
		if ($this->checkNotNull($tblName))
			$this->tblName = $tblName;

		if ($this->checkNotNull($caption))
			$this->caption = $caption;

		if ($this->checkNotNull($width))
			$this->width = $width;

		if ($this->checkNotNull($height))
			$this->height = $height;

		if ($this->checkNotNull($paging))
			$this->paging = $paging;

		if ($this->checkNotNull($urlPath))
			$this->urlPath = $urlPath;

		if ($this->checkNotNull($rowNum))
			$this->rowNum = $rowNum;

		if ($this->checkNotNull($linkPath))
			$this->linkPath = $linkPath;

		if ($this->checkNotNull($colName)) {
			$column = implode(",", $colName);
			$this->colName = $column;
		}

		if ($this->checkNotNull($colModel)) {
			$column = implode(",", $colModel);
			$this->colModel = $column;
		}
	}

	function setHeight($height) {
		$this->height = $height;
	}

	function setPostData($postData = array()) {
		if (!is_array($postData)){

		}
		$this->postData = Zend_Json_Encoder::encode($postData);
	}

	function setUrlPath($urlPath) {
		$this->urlPath = $urlPath;
	}

	function setTableName($tblName) {
		$this->tblName = $tblName;
	}

	function setCaption($captionName) {
		$this->cation = $captionName;
	}

	function setWidth($width) {
		$this->width = $width;
	}

	function setMultiselect($multiselect) {
		$this->multiselect = $multiselect;
	}

	function setPaging($paging) {
		if (is_array($paging)) {
			$paging = implode(",", $paging);
		}
		$this->paging = $paging;
	}

	function setRowNum($rowNum) {
		$this->rowNum = $rowNum;
	}

	function setAutowidth($autowidth) {
		$this->autowidth = $autowidth;
	}

	function setGridComplete($jsScript) {
		$this->jsGridComplete = $jsScript;
	}

	function setGridFunction($jsScript) {
		$this->jsGridFunctionComplete = $jsScript;
	}

	function setSortName($sortName) {
		$this->sortName = $sortName;
	}
	function setSortOrder($sortorder = 'desc') {
		if (strtolower($sortorder) == 'desc' || strtolower($sortorder) == 'asc') {
			$this->sortorder = $sortorder;
		}
	}

	function gridComplete() {
		if ($this->linkPath != ''):
		$script = ' window.location.href="' . $this->linkPath . '?itemid="+id;';
		endif;
		if ($this->jsGridComplete != ''):
		$script = $this->jsGridComplete;
		endif;
		return $script;
	}

	function displayJSGrid() {


		$jsFormat .= 'jQuery("#tbl' . $this->tblName . '").jqGrid({datatype: "json",url: "' . $this->urlPath . '" ,
				height: ' . $this->height . ' ,width: ' . $this->width . ',autowidth:' . $this->autowidth . ',colNames: [' . $this->colName . '],
						colModel: [
						' . $this->colModel . '
								],
								multiselect: ' . $this->multiselect . ',
										caption: "' . $this->caption . '",
												pager: "#pager_' . $this->tblName . '",
														rowList: [' . $this->paging . '],
																viewrecords: true,
																rownumbers: true,
																rownumWidth: 40,
																rowNum: '.$this->rowNum.',
																		sortname: "' . $this->sortName . '",
																				sortorder: "' . $this->sortorder . '",
																						gridComplete: function() {
																						' . $this->jsGridFunctionComplete . '

																								jQuery(".jqselect", "#tbl' . $this->tblName . '").click(function() {
																										var id = jQuery(this).attr("dataid");
																										if (id) {
																										' . $this->gridComplete() . '
	}
	});
	}
																												,
																												xmlReader: {
																												page: "result>page",
																												total: "result>total",
																												records: "result>records",
																												repeatitems: false,
																												root: "rows",
																												row: "row",
																												id: "id"
	}
	});
																												jQuery("#tbl' . $this->tblName . '").jqGrid("navGrid", "#pager_' . $this->tblName . '",
																												{ edit: false, del: false, add: false, search:false }, //options
																												{}, // edit options
																												{}, // add options
																												{}, // del options
																												{}, // search options
																												{} // view option
																														);

																														$(".imbSearch").button({icons: { primary: "ui-icon-search"}});
																														$(".imbReset").button({icons: { primary: "ui-icon-arrowrefresh-1-n"}});


																														$(window).bind("resize", function() {
																														var width = $("#gbox_tbl' . $this->tblName . '").parent().width();
																																$("#tbl' . $this->tblName . '").setGridWidth(width);
	}).trigger("resize");

																																		'; //  $("#tbl'.$this->tblName.'").setGridWidth($(window).width());

		if ($this->checkNotNull($this->linkPath))
			$jsFormat .= $this->selecltList();

		return $jsFormat;
	}
	function displayJsGridMap() {

		$caption = empty($this->caption) ? "" : 'caption: "' . $this->caption . '",';

		$jsFormat = "";
		$jsFormat .= 'jQuery("#tbl' . $this->tblName . '").jqGrid({
				datatype: "json",
				postData : ' . $this->postData . ',
						url: "' . $this->urlPath . '" ,
								height: ' . $this->height . ' ,
										width: ' . $this->width . ',
												autowidth:' . $this->autowidth . ',
														colNames: [' . $this->colName . '],
																colModel: [
																' . $this->colModel . '
																		],
																		multiselect: ' . $this->multiselect . ',
																				' . $caption . '
																						pager: "#pager_' . $this->tblName . '",
																								rowList: [' . $this->paging . '],
																										viewrecords: true,
																										rownumbers: true,
																										rownumWidth: 40,
																										rowNum: '.$this->rowNum.',
																												sortname: "' . $this->sortName . '",
																														sortorder: "' . $this->sortorder . '",
																																gridComplete: function() {
																																' . $this->jsGridFunctionComplete . '

																																		jQuery(".jqselect", "#tbl' . $this->tblName . '").click(function() {
																																				var id = jQuery(this).attr("dataid");
																																				if (id) {
																																				' . $this->gridComplete() . '
	}
	});
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
																																						jQuery("#tbl' . $this->tblName . '").jqGrid("navGrid", "#pager_' . $this->tblName . '",
																																						{ edit: false, del: false, add: false, search:false }, //options
																																						{}, // edit options
																																						{}, // add options
																																						{}, // del options
																																						{}, // search options
																																						{}  // view option
																																								);

																																								$(".imbSearch").button({icons: { primary: "ui-icon-search"}});
																																								$(".imbReset").button({icons: { primary: "ui-icon-arrowrefresh-1-n"}});


																																								/*$(window).bind("resize", function() {
																																								var width = $("#gbox_tbl' . $this->tblName . '").parent().width();
																																										$("#tbl' . $this->tblName . '").setGridWidth(width);
	}).trigger("resize");*/

																																												'
																																												; //  $("#tbl'.$this->tblName.'").setGridWidth($(window).width());

																																												if ($this->checkNotNull($this->linkPath))
																																													$jsFormat .= $this->selecltList();

																																												return $jsFormat;
	}

	function displayTblGrid($tblType = 'simple') {
		$tblFormat = '';
		switch ($tblType) {
			case 'simple':
				$tblFormat = '<div class="grid-resize"><table id="tbl' . $this->tblName . '"></table><div id="pager_' . $this->tblName . '"></div></div>';
				break;
			case 'simple02':
				$tblFormat = '<div class="grid-resize"><table id="tbl' . $this->tblName . '"></table></div>';
				break;
		}
		return $tblFormat;
	}

	function selecltList() {
		$script = 'function SelectList(cellvalue, options, rowObject) {return "<a href=\'javascript:void(0);\' dataid=\'" + options.rowId + "\' class=\'jqselect\'>" + cellvalue + "</a>";}';
		return $script;
	}

	function advanceSearch($filterArray) {
		$html = "";
		if (!empty($filterArray)) {
			foreach ($filterArray as $item):
			$html .= '<div class = "search-zone-advance-filter">';
			$html .= $item;
			$html .= '</div>';
			endforeach;
		}
		return $html;
	}

	function getMethodName() {

		return $this->urlPath;
	}

	function checkNotNull($input) {
		if ($input == '' || $input == null || $input == ' ') {
			return false;
		}
		return true;
	}

}

?>