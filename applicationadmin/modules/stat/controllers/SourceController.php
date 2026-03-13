<?php
class Stat_SourceController extends Zend_Controller_Action
{
    public function indexAction(){
        // Log Access
        System_Model_Log::access();

        $sUser = System_Model_SUser::getInstance();
        if (!$sUser->hasData()) {
           $this->_helper->redirector('index', 'index', 'default');
        }
        if ($sUser->getInfo()->UserType != "B") {
           $this->_helper->redirector('index', 'index', 'default');
        }
        
        // Check Action Rights
        $canView = $sUser->isAllow('stat.stat.view');
        if (!$canView) {
           $this->_helper->redirector('index', 'index', 'default');
        }

        $languages = System_Model_Setting::getLanguages();
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();

        require_once APPLICATION_ROOT . DS . 'library' . DS . 'vendor' . DS . 'autoload.php';
        $analytics = $this->initializeAnalytics();
        $profile = $this->getFirstProfileId($analytics);
        
        // Check: มีการตั้งค่าแล้วหรือไม่
        // ถ้าไม่ให้แสดงหน้าให้ไปที่หน้าตั้งค่าก่อน
        // ถ้ามีแล้ว ให้แสดงข้อมูล
        if ($analytics == null) {
        	$this->view->pageName = "สถิติตามแหล่งที่มา";
        	$this->view->pageIcon = "fa fa-bar-chart";
        	$this->view->subMenus = array();
        	$this->view->breadcrumb = array(
        			array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'stat', 'controller' => 'source', 'action' => 'index'), null, true)),
        			array('name' => 'สถิติตามแหล่งที่มา')
        	);
        	
        	$this->renderScript('index/alertsetting.phtml');
        	return;
        }
        
        //Get params
        $params=$this->getRequest()->getParams();
        if($params['startdate']){
            $startdate=Emwork_Utility_DateTime::dateThaiToMySQL(base64_decode($params["startdate"]));
            $enddate=Emwork_Utility_DateTime::dateThaiToMySQL(base64_decode($params["enddate"]));
        }
        else{
            $adate = date('Y-m-d');
            $startdate=date("Y-m-d",strtotime("-7 days",strtotime($adate)));
            $enddate=date('Y-m-d');
        }
        //put data from google analytics
        $UserAll = $this->getUserAll($analytics, $profile,$startdate,$enddate);
        $source = $this->getSource($analytics, $profile,$startdate,$enddate);

        //send to view
        $this->view->source=$source;
        $this->view->startdate=	Emwork_Utility_DateTime::MySQLToDateThai($startdate);
        $this->view->enddate=Emwork_Utility_DateTime::MySQLToDateThai($enddate);
        $this->view->UserAll=$UserAll[0][0];
        // Set View
        $this->view->canAdd = $canAdd;
        $this->view->pageName = "สถิติตามแหล่งที่มา";
        $this->view->pageIcon = "fa fa-bar-chart";
        $this->view->subMenus = array();
        $this->view->breadcrumb = array(
            array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'stat', 'controller' => 'source', 'action' => 'index'), null, true)),
            array('name' => 'สถิติตามแหล่งที่มา')
            
        );
		// Log Acion
		System_Model_Log::action("stat.source", "view", null, null);
    }
    public function checkdateAction() {

		// Init
		$result = array();
		$result['result'] = false;
		$result['id'] = null;
		$result['message'] = '';

		// Param
        $params=$this->getRequest()->getParams();
        if($params['startdate']){
            $startdate=Emwork_Utility_DateTime::dateThaiToMySQL(base64_decode($params["startdate"]));
            $enddate=Emwork_Utility_DateTime::dateThaiToMySQL(base64_decode($params["enddate"]));
        }
        else{
            $adate = date('Y-m-d');
            $startdate=date("Y-m-d",strtotime("-7 days",strtotime($adate)));
            $enddate=date('Y-m-d');
        }
		// Set Result
		if ($startdate > $enddate) {				
			$result['result'] = false;
			$result['message'] = 'กรุณากรอกวันที่เริ่มต้น น้อยกว่าหรือเท่ากับ วันที่สิ้นสุด';
		} else {
			$result['result'] = true;
			$result['message'] = '';
		}
		
		$this->_helper->json($result);
	}
    function initializeAnalytics()
    {
    	//$KEY_FILE_LOCATION = APPLICATION_PATH . DS . 'configs' . DS . 'd2ms-207603-0cb6a3946021.json';
    	//$KEY_FILE_LOCATION = APPLICATION_PATH . DS . 'modules' . DS . 'stat' . DS . 'configs' . DS . 'google-analytic.json';
    	    	
    	// Get Stat Setting
    	$statServiceAccount = System_Model_Setting::getValue("ServiceAccount", "Stat");
    	if (empty($statServiceAccount)) {
    		return null;
    	} else {
    		$fileModel = new System_Model_Fileinfo();
    		$fileObj = $fileModel->item($statServiceAccount);
    		if (empty($fileObj->ID)) {
    			return null;
    		} else {
    			$KEY_FILE_LOCATION = APPLICATION_ROOT . DS . 'files' . $fileObj->Path . DS . $fileObj->HashName;
    		}
    	}

        // Create and configure a new client object.
        $client = new Google_Client();
        $client->setApplicationName("Hello Analytics Reporting");
        $client->setAuthConfig($KEY_FILE_LOCATION);
        $client->setScopes(['https://www.googleapis.com/auth/analytics.readonly']);
        $analytics = new Google_Service_Analytics($client);
        return $analytics;
    }
    function getFirstProfileId($analytics) {
    	
    	if ($analytics == null) {
    		return null;
    	}
    	
    	$profileId = "";
    	try {
    		$statViewID = System_Model_Setting::getValue("ViewID", "Stat");
    		if(!empty($statViewID)) {
    			$profileId = $statViewID;
    		}
    	} catch (Exception $e) {
    		
    	}
    	
    	if (!empty($profileId)) {
    		return $profileId;
    	}
    	
        $accounts = $analytics->management_accounts->listManagementAccounts();
        if (count($accounts->getItems()) > 0) {
            $items = $accounts->getItems();
            $firstAccountId = $items[0]->getId();
            // Get the list of properties for the authorized user.
            $properties = $analytics->management_webproperties->listManagementWebproperties($firstAccountId);
            

            if (count($properties->getItems()) > 0) {
            $items = $properties->getItems();
            $firstPropertyId = $items[0]->getId();

            // Get the list of views (profiles) for the authorized user.
            $profiles = $analytics->management_profiles->listManagementProfiles($firstAccountId, $firstPropertyId);

            $browser = new Google_Service_AnalyticsReporting_Dimension();
            $browser->setName("ga:browser");
            if (count($profiles->getItems()) > 0) {
                $items = $profiles->getItems();

                // Return the first view (profile) ID.
                return $items[0]->getId();

            } else {
                throw new Exception('No views (profiles) found for this user.');
            }
            } else {
            throw new Exception('No properties found for this user.');
            }
        } else {
            throw new Exception('No accounts found for this user.');
        }
    }
    function getUserAll($analytics, $profileId,$startdate,$enddate) {
        $rows= $analytics->data_ga->get(
            'ga:' . $profileId,
            ''.$startdate,
            ''.$enddate,
            'ga:users');
        return $rows->getRows();
    }    
    function getSource($analytics, $profileId,$startdate,$enddate) {
        $rows= $analytics->data_ga->get(
            'ga:' . $profileId,
            ''.$startdate,
            ''.$enddate,
            'ga:users',
            array(
                'dimensions' => 'ga:source',
                'metrics' => 'ga:users',
                'sort'=>'-ga:users'
            ));

        return $rows->getRows();
    }    
}
?>