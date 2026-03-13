<?php
/*
 *
*/

class Zend_View_Helper_MenuData extends Zend_View_Helper_Abstract
{
	protected $db = null;
	
	public function MenuData($menuGroupID, $lang)
	{
		$menus = array();
		$this->db = Zend_Db_Table::getDefaultAdapter();
		
		if (empty($menuGroupID)) {
			return $menus;
		}
		
		if (empty($lang)) {
			$defaultLanguage = System_Model_Setting::getDefaultLanguage();
			$lang = $defaultLanguage["Code"];
		}
		
		$data = array();
		try {
		    
		    // Get data menu
			$select = $this->db->select()
			->from(array('tbl' => 'menu_info'), array('ID', 'MenuGroupID', 'ParentID', 'Url', 'Type', 'Params'))
			->joinLeft(array('d' => 'menu_detail'), "tbl.ID = d.ID and d.Lang = '{$lang}' ", array("Name"))
			->where("tbl.IsEnable = 'Y' ")
			->where("tbl.IsDeleted = 'N' ")
			->where("tbl.MenuGroupID = ? ", $menuGroupID)
			->order("tbl.Ordering asc")
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();
			
		} catch (Exception $e) {
			
		}
		
		if (count($data)>0) {
			foreach ($data as $item) {
				$parentID = empty($item["ParentID"]) ? "main" : $item["ParentID"];
				if (!isset($menus[$parentID])) {
					$menus[$parentID] = array();
				}
				$menu = array();
				$menu["ID"] = $item["ID"];
				$menu["Name"] = $item["Name"];
				$menu["Url"] = $item["Url"];
				$menu["Type"] = empty($item["Type"]) ? 'component' : $item["Type"];
				$menu["Params"] = $item["Params"];
				$menus[$parentID][] = $menu;
			}
		}

		return $menus;
	}

}

?>
