<?php

class Zend_View_Helper_Widget_License_License extends Zend_View_Helper_Abstract
{
	protected $params = array();
	public function Widget_License_License($params)
	{
		$this->params = $params;
		return $this;
	}
	
	public function display() {
		$db = Zend_Db_Table::getDefaultAdapter();
		$lang = Zend_Registry::get('lang');
		$defaultCommentUrl = System_Model_Setting::getValue("CommentUrl", "License");
		
		$paramDataType = (isset($this->params["datatype"]) && !empty($this->params["datatype"])) ? $this->params["datatype"] : "L";
		$paramCategory = (isset($this->params["category"]) && !empty($this->params["category"])) ? $this->params["category"] : "";
		$paramLicense = (isset($this->params["license"]) && !empty($this->params["license"])) ? $this->params["license"] : "";
		$paramSortType = (isset($this->params["sorttype"]) && !empty($this->params["sorttype"])) ? $this->params["sorttype"] : "title";
		$paramSortDirection = (isset($this->params["sortdirection"]) && !empty($this->params["sortdirection"])) ? $this->params["sortdirection"] : "asc";
		$paramLinkTarget = (isset($this->params["linkTarget"]) && !empty($this->params["linkTarget"])) ? $this->params["linkTarget"] : "_blank";
		$paramShowEndDate = (isset($this->params["showEndDate"]) && !empty($this->params["showEndDate"])) ? $this->params["showEndDate"] : "Y";
		$paramAmount = (isset($this->params["amount"]) && !empty($this->params["amount"])) ? $this->params["amount"] : "5";
		if (!is_numeric($paramAmount)) {
			$paramAmount = 5;
		}
		
		$paramLinkTarget = in_array($paramLinkTarget, array('_blank', '_self')) ? $paramLinkTarget : '_blank';
		$paramSortDirection = in_array($paramSortDirection, array('asc', 'desc')) ? $paramSortDirection : 'desc';
		$paramShowEndDate = in_array($paramShowEndDate, array('Y', 'N')) ? $paramShowEndDate : 'Y';
		
		$data = array();
		if ($paramDataType == 'L') {
			$paramSortType = in_array($paramSortType, array('title', 'created', 'modified', 'publishdate')) ? $paramSortType : 'name';
			$paramShowEndDate = 'N';
			
			switch ($paramSortType) {
				case "created":
					$paramSortType = "Created";
					break;
				case "modified":
					$paramSortType = "Modified";
					break;
				case "publishdate":
					$paramSortType = "PublishDate";
					break;
				case "title":
				default:
					$paramSortType = "Title";
					break;
			}
			
			$data = $this->getLicenses($db, $lang, $paramCategory, $paramSortType, $paramSortDirection, $paramAmount);
		} else {
			$paramSortType = in_array($paramSortType, array('title', 'created', 'modified', 'publishdate', 'startdate', 'enddate')) ? $paramSortType : 'name';
			
			switch ($paramSortType) {
				case "created":
					$paramSortType = "Created";
					break;
				case "modified":
					$paramSortType = "Modified";
					break;
				case "publishdate":
					$paramSortType = "PublishDate";
					break;
				case "startdate":
					$paramSortType = "StartDate";
					break;
				case "enddate":
					$paramSortType = "EndDate";
					break;
				case "title":
				default:
					$paramSortType = "Title";
					break;
			}
			
			$data = $this->getProcedures($db, $lang, $paramCategory, $paramLicense, $paramSortType, $paramSortDirection, $paramAmount);
		}
		
		$html = '';		
		$html .= '<div class="license">';
		$html .= '<ul class="list">';
		if (count($data) > 0) {
			foreach ($data as $item) {
				$id = $item["ID"];
				$url = $paramDataType == 'L' ? $this->view->url(array("module" => "license", "controller" => "info", "action" => "license", "id" => $id), null, true) : $item["UrlComment"];
				$title = $item["Title"];
				$endDate = empty($item["EndDate"]) ? null : new DateTime($item["EndDate"]);
				
				$html .= '<li class="text1">';
				$html .= '<span class="text2">';
				if (!empty($url) || !empty($defaultCommentUrl)) {
					$html .= '<a href="' . (empty($url) ? $defaultCommentUrl : $url) . '" target="' . $paramLinkTarget . '">' . $title . '</a>';
				} else {
					$html .= '<span>' . $title . '</span>';
				}
				if ($paramShowEndDate == 'Y' && ($endDate != null)) {
					$label = Emwork_Translate::text($lang, "license", "widget_enddate_label");
					$endDateStr = Emwork_Translate::text($lang, "license", "widget_enddate_date_format");
					$endDateStr = str_replace("{eyear}", $endDate->format("Y") + ($lang == 'th' ? 543 : 0), $endDateStr);
					$endDateStr = str_replace("{emonth}", $endDate->format("n"), $endDateStr);
					$endDateStr = str_replace("{eday}", $endDate->format("j"), $endDateStr);
					
					$html .= '<span> <span class="date"> [ ' . $label . ' : ' . $endDateStr . ' ] </span> </span></span>';
				}
				$html .= '</span>';
				$html .= '</li>';
			}
		}
		$html .= '</ul>';
		$html .= '</div>';
		
		
		echo $html;
	}
	
	private function getLicenses($db, $lang, $categoryID, $sortType, $sortDirection, $amount) {
		$data = array();
		try {
			$select = $db->select()
			->from(array("tbl" => "license_item_info"), "")
			->joinLeft(array("d" => "license_item_detail"), "tbl.ID = d.ID and d.Lang = '{$lang}' ", "")
			->joinLeft(array("i" => "license_issue_info"), "tbl.LicenseIssueID = i.ID", "")
			->joinLeft(array("id" => "license_issue_detail"), "i.ID = id.ID and id.Lang = '{$lang}' ", "")
			->joinLeft(array("c" => "license_category_info"), "i.LicenseCategoryID = c.ID", "")
			->joinLeft(array("cd" => "license_category_detail"), "c.ID = cd.ID and cd.Lang = '{$lang}' ", "")
			->columns(array(
					"ID" => "tbl.ID",
					"Title" => "d.Title",
					"tbl.PublishDate",
					"tbl.Created",
					"tbl.Modified"
			))
			->where("ifnull(d.Title, '') <> '' ")
			->where("tbl.IsEnable = 'Y' ")
			->where("tbl.IsDeleted = 'N' ")
			->where("tbl.PublishDate <= ? ", date("Y-m-d H:i"))
			->where("tbl.PublishDown is null or tbl.PublishDown >= ? ", date("Y-m-d H:i"))
			;
			
			if (!empty($categoryID)) {
				$select = $select->where("i.LicenseCategoryID = ? ", $categoryID);
			}
			
			$select = $db->select()
			->from(array("tbl" => $select), array('*'))
			;			
			
			if (in_array($sortType, array('Title'))) {
				$select = $select->order("convert($sortType USING tis620) $sortDirection");
			} else {
				$select = $select->order("$sortType $sortDirection");
			}
			$select = $select->limitPage ( 1, $amount );
			$stmt = $db->query($select);
			$data = $stmt->fetchAll();
			
		} catch (Exception $e) {
			
		}
		return $data;
	}
	
	private function getProcedures($db, $lang, $categoryID, $licenseID, $sortType, $sortDirection, $amount) {
		$data = array();
		try {
			$select = $db->select()
			->from(array("tbl" => "license_item_info"), "")
			->joinInner(array("d" => "license_item_detail"), "tbl.ID = d.ID and d.Lang = '{$lang}' ", "")
			->joinLeft(array("i" => "license_issue_info"), "tbl.LicenseIssueID = i.ID", "")
			->joinLeft(array("id" => "license_issue_detail"), "i.ID = id.ID and id.Lang = '{$lang}' ", "")
			->joinInner(array("c" => "license_category_info"), "i.LicenseCategoryID = c.ID", "")
			->joinInner(array("cd" => "license_category_detail"), "c.ID = cd.ID and cd.Lang = '{$lang}' ", "")
			->joinInner(array("p" => "license_procedure_info"), "tbl.ID = p.LicenseItemID", "")
			->joinInner(array("pd" => "license_procedure_detail"), "p.ID = pd.ID and pd.Lang = '{$lang}' ", "")
			->columns(array(
					"ID" => "p.ID",
					"Title" => "pd.Title",
					"Url" => "p.Url",
					"UrlComment" => "p.UrlComment",
					"p.StartDate",
					"p.EndDate",
					"p.PublishDate",
					"p.Created",
					"p.Modified"
			))
			->where("ifnull(pd.Title, '') <> '' ")
			->where("tbl.IsEnable = 'Y' ")
			->where("tbl.IsDeleted = 'N' ")
			->where("p.IsEnable = 'Y' ")
			->where("p.IsDeleted = 'N' ")
			->where("tbl.PublishDate <= ? ", date("Y-m-d H:i"))
			->where("tbl.PublishDown is null or tbl.PublishDown >= ? ", date("Y-m-d H:i"))
			->where("p.PublishDate <= ? ", date("Y-m-d H:i"))
			->where("p.PublishDown is null or p.PublishDown >= ? ", date("Y-m-d H:i"))			
			;
			
			if (!empty($categoryID)) {
				$select = $select->where("i.LicenseCategoryID = ? ", $categoryID);
			}
			if (!empty($licenseID)) {
				$select = $select->where("tbl.ID = ? ", $licenseID);
			}
			
			$select = $db->select()
			->from(array("tbl" => $select), array('*'))
			;			
			
			if (in_array($sortType, array('Title'))) {
				$select = $select->order("convert($sortType USING tis620) $sortDirection");
			} else {
				$select = $select->order("$sortType $sortDirection");
			}
			$select = $select->limitPage ( 1, $amount );
			
			$stmt = $db->query($select);
			$data = $stmt->fetchAll();
			
		} catch (Exception $e) {
			
		}
		return $data;
	}
	
}