<?php

class Zend_View_Helper_Widget_Menu_Menu extends Zend_View_Helper_Abstract
{
	protected $params = array();
	public function Widget_Menu_Menu($params)
	{
		$this->params = $params;
		return $this;
	}
	
	public function display() {
		$db = Zend_Db_Table::getDefaultAdapter();
		$lang = Zend_Registry::get('lang');
		$menuGroupID = $this->params["menugroup"];
		$isUseLanguageCode = false;
		if (Zend_Registry::isRegistered("IsUseLanguageCode")) {
			$isUseLanguageCode = Zend_Registry::get("IsUseLanguageCode");
		}
		
		// Get data menu
		$select = $db->select()
		->from(array('tbl' => 'menu_info'), array('ID', 'MenuGroupID', 'ParentID', 'Url', 'Type', 'Params'))
		->joinLeft(array('d' => 'menu_detail'), "tbl.ID = d.ID and d.Lang = '{$lang}' ", array("Name"))
		->where("tbl.IsEnable = 'Y' ")
		->where("tbl.IsDeleted = 'N' ")
		->where("tbl.MenuGroupID = ? ", $menuGroupID)
		->order("tbl.Ordering asc")
		;
		$stmt = $db->query($select);
		$data = $stmt->fetchAll();
		
		$menus = array();
		if (count($data)>0) {
			foreach ($data as $item) {
				$parentID = empty($item["ParentID"]) ? "main" : $item["ParentID"];
				if (!isset($menus[$parentID])) {
					$menus[$parentID] = array();
				}
				$menu = array();
				$menu["ID"] = $item["ID"];
				$menu["Name"] = $item["Name"];
				$menu["Url"] = ($isUseLanguageCode ? '/' . $lang : '') . $item["Url"];
				$menu["Type"] = empty($item["Type"]) ? 'component' : $item["Type"];
				$menu["Params"] = $item["Params"];
				$menus[$parentID][] = $menu;
			}
		}
		
		if (count($menus) > 0) {
			echo "<ul>";
			
			foreach ($menus["main"] as $menu) {
				echo "<li>";
				echo "<a href=''>" . $menu["Name"] . "</a>";
				echo "</li>";				
			}
			
			echo "</ul>";
		}
	}
}