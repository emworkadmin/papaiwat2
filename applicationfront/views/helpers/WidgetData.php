<?php
/*
 *
*/

class Zend_View_Helper_WidgetData extends Zend_View_Helper_Abstract
{
	protected $db = null;
	
	public function WidgetData($menuID, $lang)
	{
		$menuID = empty($menuID) ? '0' : $menuID;
		$defaultLang = System_Model_Setting::getDefaultLanguage();
		$lang = empty($lang) ? $defaultLang['Code'] : $lang;
		$this->db = Zend_Db_Table::getDefaultAdapter();
		
		$data = array();
		$result = array();
		try {
		    
		    // Get data menu
// 			$select = $this->db->select()->distinct()
// 			->from(array('tbl' => 'sys_widget'), array('ID', 'Title', 'Ordering', 'Position', 'Widget', 'ShowTitle', 'Params'))
// 			//->joinInner(array('wm' => 'sys_widget_menu'), "tbl.ID = wm.WidgetID", array("MenuID"))
// 			//->joinInner(array('wmm' => $this->db->select()->from(array('wm' => 'sys_widget_menu'), array("WidgetID", "Amount" => "sum(wm.MenuID)"))->group("wm.WidgetID") ), "tbl.ID = wmm.WidgetID", array("CaseType" => "(case when wmm.Amount > 0 then 'selected' when wmm.Amount < 0 then 'unselected' else 'all' end)") )
// 			->where("tbl.IsEnable = 'Y' ")
// 			->where("tbl.IsDeleted = 'N' ")
// 			//->where("(wm.MenuID = '0') or (wm.MenuID = '{$menuID}') or (wm.MenuID <> '-{$menuID}' and exists (select * from sys_widget_menu ccc where tbl.) ) ")
// 			->where("tbl.Lang = '*' or tbl.Lang = ? ", $lang)
// 			->order("tbl.Ordering asc")
// 			;
			$select = $this->db->select()->distinct()
			->from(array('tbl' => 'sys_widget'), array('ID', 'Title', 'Ordering', 'Position', 'Widget', 'ShowTitle', 'Params'))
			->where("tbl.IsEnable = 'Y' ")
			->where("tbl.IsDeleted = 'N' ")
			->where("(exists(select * from sys_widget_menu ccc where tbl.ID = ccc.WidgetID and ccc.MenuID = '0')) or (exists(select * from sys_widget_menu ccc where tbl.ID = ccc.WidgetID and ccc.MenuID > 0 and ccc.MenuID = '{$menuID}')) or (exists (select * from sys_widget_menu ccc where tbl.ID = ccc.WidgetID and ccc.MenuID < 0 and ccc.MenuID <> '-{$menuID}') )")
			->where("tbl.Lang = '*' or tbl.Lang = ? ", $lang)
			->order("tbl.Ordering asc")
			;//echo $select; exit;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();
			
			if (count($data) > 0) {
				foreach ($data as $item) {
// 					if ($item["MenuID"] >= 0) {
// 						$result[] = $item;
// 					}
					$result[] = $item;
				}
			}
		} catch (Exception $e) {
			print_r($e);
		}
		
		return $result;
	}

}

?>
