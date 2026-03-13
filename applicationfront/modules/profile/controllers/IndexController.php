<?php
Class Profile_IndexController extends Zend_Controller_Action {

    protected $genderOption = array("M"=> "ชาย", "F" => "หญิง");
	protected $lang = null;

	public function init()
	{
		// Get Languages
	    $lang = Zend_Registry::isRegistered("lang") ? Zend_Registry::get("lang") : "";
	    if (empty($lang)) {
	        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
	        $lang = $defaultLanguage["Code"];
	    }
		$this->lang = $lang;
		$this->genderOption = array(
			"M" => Emwork_Translate::text($lang, 'profile', 'option_man'),
			"F" => Emwork_Translate::text($lang, 'profile', 'option_female')
		);
		// Send data to view
		$this->view->lang = $lang;
	}

	protected function findMenu($url)
	{
		$menuID = null;
		$menus = System_Model_Menu::findMenuByUrl($url);
		if (count($menus) > 0) {
			$menuID = $menus[0]["ID"];
		}
		return $menuID;
    }
        
	/**
	 * Page: 
	 */
	public function indexAction()
	{
        $menuID = $this->findMenu("/profile/index/index");
        Zend_Registry::set("MenuID", $menuID);

        $menuDefault = System_Model_Menu::getHome();
        
		// Log Access
		System_Model_Log::access();
		
		// Check Auth
		$auth = Zend_Auth::getInstance();

		if (!$auth->hasIdentity()) {
            $this->getResponse()->setRedirect($menuDefault->Url);
		}

		// Check Rights
        $authData = $auth->getIdentity();
        
		if ($authData->UserFrontType != 'F') {
            $this->getResponse()->setRedirect($menuDefault->Url);
        }
		
		$model = new Profile_Model_Profile();
        $data = $model->getInfoByID($authData->UserID);

		if($data->BirthDate) {
		  $data->Age =  date_diff(date_create($data->BirthDate), date_create('today'))->y;
        }

		// Prepare Data
		if(!empty($data->ProfileImageHashName)){
		    $data->ProfileImageUrl = $this->view->url(array("module" => "default", "controller" => "file", "action" => "get", "file" => $data->ProfileImageHashName), null, true);
		} else {
		    $data->ProfileImageUrl = '';
		}
		
		$this->view->data = $data;
		
		$this->view->genderOption = $this->genderOption;
    }
    
    /*
    * Function :
    */
	public function updateprofilepictureAction()
	{
	    // Init
	    $result = array();
	    $result['result'] = false;
	    $result['message'] = '';
	    
	    // Param
	    $HashName = $this->getRequest()->getParam('HashName');
	    
	    // Prepare Profile Picture
	    $profilePictureId= null;
	    if ($HashName) {
	        $modelFileInfo = new System_Model_Fileinfo();
	        $pictureData = $modelFileInfo->itemByHashName($HashName);
	        if ($pictureData->ID != null) {
	            $profilePictureId= $pictureData->ID;
	        }
	    }
	    $params['profilePictureId'] = $profilePictureId;
        
        // Model
        $model = new Profile_Model_Profile();
        
	    // Update
	    $data = $model->updateProfilePicture($params);
	    
	    // Set Result
	    if ($data->result) {
	        
	        $auth = Zend_Auth::getInstance();
	        $authData = $auth->getIdentity();
	        $authData->ProfileImageHashName = $HashName;
			$auth->getStorage()->write($authData);
	        
	        $result['result'] = true;
	        $result['message'] = Emwork_Translate::text($this->lang, 'profile', 'change_image_success');
	    } else {
	        $result['result'] = false;
	        $result['message'] = Emwork_Translate::text($this->lang, 'profile', 'change_image_unsuccess');
	    }
	    
	    $this->_helper->json($result);
    }

    /* 
    * Page :
    */
    function editAction()
    {
    	$menuID = $this->findMenu("/profile/index/index");
    	Zend_Registry::set("MenuID", $menuID);

        // Log Access
        System_Model_Log::access();
        
        $auth = Zend_Auth::getInstance();
        $authData = $auth->getIdentity();
        
        $model = new Profile_Model_Profile();
		$data = $model->getInfoByID($authData->UserID);
		
        if($this->lang == 'th') {
			$data->BirthDate = Emwork_Utility_DateTime::MySQLToDateThai($data->BirthDate, "D");
		} else {
			$data->BirthDate = Emwork_Utility_DateTime::MySQLToDateEng($data->BirthDate, "D");
		}
        
        require_once APPLICATION_ROOT . DS . 'library' . DS . 'Mobile_Detect.php';
        $detect = new Mobile_Detect();
        
        $this->view->isiOS = $detect->isiOS();
        
        // Send data to view
        $this->view->data = $data;
        $this->view->genderOption = $this->genderOption;
    }

    /*
    * Function :
    */
    function updateAction()
    {
		// Init
		$result = array();
		$result['result'] = false;
		$result['id'] = null;
        $result['message'] = '';	
        
        		// Param
		$params = $this->getRequest()->getParams();
		
		// Save & Update
		$model = new Profile_Model_Profile();
		$data = $model->update($params);
		
		// Set Result
		if ($data->result) {	
			$result['result'] = true;
			$result['message'] = '';
			$result['id'] = $data->id;

			$auth = Zend_Auth::getInstance();
	        $authData = $auth->getIdentity();
	        $authData->FirstName = $params['txtFirstName'];
			$authData->LastName = $params['txtFirstName'];
			$auth->getStorage()->write($authData);

		} else {
			$result['result'] = false;
			$result['message'] = Emwork_Translate::text($lang, 'profile', 'validate_error');
		}
	
		$this->_helper->json($result);
    }

    /*
    * Function :
    */
    public function updatepasswordAction()
	{
	    // Init
	    $result = array();
	    $result['result'] = false;
	    $result['message'] = '';
	    
	    // Log Access
	    System_Model_Log::access();
	    
	    $auth = Zend_Auth::getInstance();
	    $authData = $auth->getIdentity();
	    	    
	    $params = $this->getRequest()->getParams();
	    
	    $model = new Profile_Model_Profile();
	    $dataUser = $model->getInfoByID($authData->UserID);
	    
	    $oldPassword = md5($params['txtOldPassword']);

        if($dataUser->Password != $oldPassword) {
	        $result['message'] = Emwork_Translate::text($this->lang, 'profile', 'change_password_error_1');
	        $this->_helper->json($result);
        }

	    $data = $model->updatePassword($params);
	    
	    // Set Result
	    if ($data->result) {
	        $result['result'] = true;
	        $result['message'] = "";
	    } else {
	        $result['result'] = false;
	        $result['message'] = Emwork_Translate::text($this->lang, 'profile', 'change_password_error_2');
	    }
	    
	    $this->_helper->json($result);
	}
}
?>