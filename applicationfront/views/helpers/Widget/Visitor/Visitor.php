<?php

class Zend_View_Helper_Widget_Visitor_Visitor extends Zend_View_Helper_Abstract
{
	protected $params = array();
	protected $recursiveIds = array();

	public function Widget_Visitor_Visitor($params)
	{
		$this->params = $params;
		return $this;
    }

	public function display() {
		$db = Zend_Db_Table::getDefaultAdapter();
		$lang = Zend_Registry::get('lang');
		$SumAmount =$this->getSumVisitor();

		// ---------------------------------------------- Present Data ---------------------------------------------- //
		$html ='<div class="visitor">'; 
		$html .='	<span class="txtvisitor">'.Emwork_Translate::text($lang,'wvisitor','viewamount').' : '.$SumAmount.'</span>'; 
		$html .='</div>'; 
		echo $html;
	}

	public function getSumVisitor(){
		$db = Zend_Db_Table::getDefaultAdapter();
		// Get Data
		$data = new stdClass();
		$data->VisitorBeginVisitor = System_Model_Setting::getValue("VisitorBeginVisitor", "Visitor");
		$data->VisitorSiteOpenDate = System_Model_Setting::getValue("VisitorSiteOpenDate", "Visitor");
		$startdate = $data->VisitorSiteOpenDate;
		$BeginAmount = $data->VisitorBeginVisitor;

		$select = $db->select()
		->from(array('tbl' => 'log_access'), "")
		->columns(array(
			"Amount" => " COUNT(tbl.ID)",
		))
		->where("tbl.SiteType = 'F' ")
		->where("tbl.IsAjax = 'N' ")
		->where("DATE_FORMAT(tbl.created,'%Y%m') >= ?",$startdate)
		->order("tbl.created ASC")
		;

		$stmt = $db->query($select);	
		$data = $stmt->fetchObject();
		
		$data =$data->Amount + $BeginAmount;

		return $data;
	}

}

?>