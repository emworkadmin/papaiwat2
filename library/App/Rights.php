<?php

/**
 * Description of App_Rights
 *
 * @author jon
 */
class App_Rights {

	private $actionList = array();
	private $accessLevel = 'N';
	private $accessDepartmentID = null;

	public function __construct($userGroupID, $urlPath) {

		$model = new Admin_Model_Usergroups();
		$data = $model->item($userGroupID);
		$dataList = $model->getAction($userGroupID, $urlPath);
		$model->closeConnection();
		
		if ($data) {
			if ($data->is_admin == 'Y') {
				$this->accessLevel = 'A';
			} else {
				$this->accessLevel = $data->access_level;
				if ($data->access_level == "D")
					$this->accessDepartmentID = $data->access_department_id;
				else 
					$this->accessDepartmentID = null;
			}
		}
 
		foreach ($dataList as $value) {
			$this->actionList[] = strtolower($value["code"]);
		}
		
		// Get UserID
		$authobj = Zend_Auth::getInstance();
		if ($authobj != null) {
			if ($auth = $authobj->getIdentity()) {
			  	$dataList = new stdClass();
				$modelUser = new Admin_Model_Users();
				$dataList = $modelUser->getAction($auth->UserID, $urlPath);
				
				if ($dataList) {
					$userActionList = array();
					foreach ($dataList as $value) {
						$userActionList[] = strtolower($value["code"]);
					}
					$this->actionList = array_merge($this->actionList, $userActionList);
				}
			}
		}
	}

	public function getRight($actionCode) {
		if (in_array(strtolower($actionCode), $this->actionList)) {
			return true;
		}
		return false;
	}

	public function getAccessLevel() {
		return $this->accessLevel;
	}

	public function getAccessDepartmentID() {
		return $this->accessDepartmentID;
	}

}