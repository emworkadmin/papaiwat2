<?php
/*
 *
*/

class Zend_View_Helper_Rights extends Zend_View_Helper_Abstract
{
	private $userID;
	private $actions = array();
	
	public function rights($userGroupID)
	{
		$this->userGroupID = $userGroupID;
		
		$usergroupActionList = System_Model_Usergroup::getActions($this->userGroupID);
		if (count($usergroupActionList)) {
			foreach ($usergroupActionList as $usergropAction) {
				$this->actions[] = strtolower($usergropAction["action_code"]);
			}
		}
		
		return $this;
	}
	
	public function isAllow($actionCode) {
		if (in_array(strtolower($actionCode), $this->actions)) {
			return true;
		}
		return false;
	}
}

?>
