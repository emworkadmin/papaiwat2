<?php 

class Newsletter_IndexController extends Zend_Controller_Action
{
    protected $statusOption = array('Y' => 'ใช้งาน', 'N' => 'ไม่ใช้งาน');
    protected $lang;
    public function init()
    {
        // Get Languages
        $lang = Zend_Registry::isRegistered("lang") ? Zend_Registry::get("lang") : "";
        if (empty($lang)) {
            $defaultLanguage = System_Model_Setting::getDefaultLanguage();
            $lang = $defaultLanguage["Code"];
        }
        $this->lang = $lang;
        
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
    
    public function indexAction()
	{
		// Log Access
        System_Model_Log::access();
        
        // Zend Filter
        $filterInt = new Zend_Filter_Int();

        $defaultLanguage = System_Model_Setting::getDefaultLanguage();

        $model = new Newsletter_Model_Index();
        $categoryOption = $model->getCategoryOption($this->lang);

        $this->view->categoryOption = $categoryOption;
        $this->view->statusOption = $this->statusOption;
        
        $menuID = $this->findMenu("/newsletter/index/index");

        Zend_Registry::set("MenuID", $menuID);        

    }

    function saveAction()
    {
        // Init
		$result = array ();
		$result ['result'] = false;
		$result ['id'] = null;
        $result ['message'] = '';
                
        $params = $this->getRequest()->getParams();

        $model = new Newsletter_Model_Index();
        $checkEmail = $model->checkEmail($params['Email']);
        if($checkEmail->ID) {
//			$result['message'] = Emwork_Translate::text($this->lang, 'newsletter', 'validate_email');
// 			$this->_helper->json($result);

        	$authobj = Zend_Auth::getInstance();
        	$auth = $authobj->getIdentity();
        	$userID = isset($auth->UserID) ? $auth->UserID : '';
        	if ((!empty($userID)) && ($userID != $checkEmail->UserID)) {
        		$result['message'] = Emwork_Translate::text($this->lang, 'newsletter', 'validate_email_nomatch');
        		$this->_helper->json($result);
			}
        }

		$data = $model->save($params);
		
		// Set Result
		if ($data->result) {
			$result ['result'] = true;
			$result ['message'] = '';
			$result ['id'] = $data->id;
		} else {
			$result ['result'] = false;
			$result ['message'] = Emwork_Translate::text($this->lang, 'newsletter', 'validate_error');
		}
		$this->_helper->json ($result);
    }
    
}

?>