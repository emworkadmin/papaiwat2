<?php
/*
 *
*/

class Zend_View_Helper_MenuDatas extends Zend_View_Helper_Abstract
{	
	public function MenuDatas()
	{
		$datas = array();
		try {
			$db = Zend_Db_Table::getDefaultAdapter();
			
			$select = $db->select()->from(array("mg" => "sys_menugroup"), array("MenuGroupID" => "ID", "MenuGroupName" => "Name", "ModuleName", "MenuGroupIcon" => "Icon"))
			->joinInner(array("m" => "sys_menu"), "mg.ID = m.MenuGroupID", array("MenuID" => "ID", "MenuName" => "Name", "ControllerName", "ActionName", "ActionCode", "MenuIcon" => "Icon"))
			->where("mg.IsEnable = ?", "Y")
			->where("mg.IsDeleted = ?", "N")
			->where("m.IsEnable = ?", "Y")
			->where("m.IsDeleted = ?", "N")
			->order("mg.Ordering")
			->order("m.Ordering")
			;
			
			$stmt = $db->query($select);
			$datas = $stmt->fetchAll();
		} catch (Exception $e) {
			
		}
		
		return $datas;
	}
}

?>
