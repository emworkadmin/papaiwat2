<?php

class Stat_OverviewController extends Zend_Controller_Action
{

    protected $analytics;
    protected $profile;
    protected $startdate;
    protected $enddate;

    function init()
    {

        $params = $this->getRequest()->getParams();

        // Get params
        if ($params['startdate']) {
            $this->startdate = Emwork_Utility_DateTime::dateThaiToMySQL(base64_decode($params["startdate"]));
            $this->enddate = Emwork_Utility_DateTime::dateThaiToMySQL(base64_decode($params["enddate"]));
        } else {
            $adate = date('Y-m-d');
            $this->startdate = date("Y-m-d", strtotime("-7 days", strtotime($adate)));
            $this->enddate = date('Y-m-d');
        }

        if ($this->startdate > $this->enddate) {
            $result = array();
            $result['result'] = false;
            $result['message'] = 'กรุณากรอกวันที่เริ่มต้น น้อยกว่าหรือเท่ากับ วันที่สิ้นสุด';
            $this->_helper->json($result);
            // $this->_helper->redirector('index', 'index', 'default');
        }

        $this->view->startdate = Emwork_Utility_DateTime::MySQLToDateThai($this->startdate);
        $this->view->enddate = Emwork_Utility_DateTime::MySQLToDateThai($this->enddate);

        $BetweenDate = new stdClass();
        if (Zend_Session::namespaceIsset("StatBetweenDate")) {
            $infoSearchNamespace = new Zend_Session_Namespace("StatBetweenDate");
            $BetweenDate->txtStartDate = $infoSearchNamespace->txtEndDate;
            $BetweenDate->txtEndDate = $infoSearchNamespace->txtEndDate;
            $this->view->BetweenDate = $BetweenDate;
            Zend_Session::namespaceUnset("StatBetweenDate");
        }

        require_once APPLICATION_ROOT . DS . 'library' . DS . 'vendor' . DS . 'autoload.php';
        $this->analytics = $this->initializeAnalytics();
        $this->profile = $this->getFirstProfileId($this->analytics);

        $userall = $this->getUserAll($this->analytics, $this->profile, $this->startdate, $this->enddate);
        $this->view->userall = $userall[0][0];
        
        $userMobile = $this->getUserMobileAll($this->analytics, $this->profile, $this->startdate, $this->enddate);
        $this->view->userMobile = $userMobile[0][0];
    }

    public function indexAction()
    {
        // Log Access
        System_Model_Log::access();

        $sUser = System_Model_SUser::getInstance();
        if (! $sUser->hasData()) {
            $this->_helper->redirector('index', 'index', 'default');
        }
        if ($sUser->getInfo()->UserType != "B") {
            $this->_helper->redirector('index', 'index', 'default');
        }
        
        // Check Action Rights
        $canView = $sUser->isAllow('stat.stat.view');
        if (! $canView) {
            $this->_helper->redirector('index', 'index', 'default');
        }
        
        // Check: มีการตั้งค่าแล้วหรือไม่
        // ถ้าไม่ให้แสดงหน้าให้ไปที่หน้าตั้งค่าก่อน
        // ถ้ามีแล้ว ให้แสดงข้อมูล
        if ($this->analytics == null) {
        	
        	$this->view->pageName = "สถิติภาพรวม";
        	$this->view->pageIcon = "fa fa-bar-chart";
        	$this->view->subMenus = array();
        	$this->view->breadcrumb = array(
        			array('name' => 'หน้าแรก','url' => $this->view->url(array('module' => 'stat','controller' => 'overview','action' => 'index'), null, true)),
        			array('name' => 'สถิติภาพรวม')
        	);
        	
        	$this->renderScript('index/alertsetting.phtml');
        	return;
        }

        $languages = System_Model_Setting::getLanguages();
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
        $model = new Stat_Model_Stat();

        // put data from google analytics
        $uservisit = $model->getvisiter($this->startdate, $this->enddate);
        $amountpage = $model->getlist();
        $session = $this->getUserSesstion($this->analytics, $this->profile, $this->startdate, $this->enddate);
        $UserPerDay = $this->getUserPerDay($this->analytics, $this->profile, $this->startdate, $this->enddate);
        
        // send to view
        $this->view->uservisit = $uservisit;
        $this->view->amountpage = count($amountpage);
        $this->view->session = $session;
        $this->view->UserPerDay = $UserPerDay;

        // Set View
        $this->view->canAdd = $canAdd;
        $this->view->pageName = "สถิติภาพรวม";
        $this->view->pageIcon = "fa fa-bar-chart";
        $this->view->subMenus = array();
        $this->view->breadcrumb = array(
        		array('name' => 'หน้าแรก','url' => $this->view->url(array('module' => 'stat','controller' => 'overview','action' => 'index'), null, true)),
        		array('name' => 'สถิติภาพรวม')
        );
        
        // Log Acion
        System_Model_Log::action("stat.overview", "view", null, null);
    }

    public function checkdateAction()
    {
        
        // Init
        $result = array();
        $result['result'] = false;
        $result['id'] = null;
        $result['message'] = '';
        
        // Param
        $params = $this->getRequest()->getParams();

        if ($params['startdate']) {
            $startdate = Emwork_Utility_DateTime::dateThaiToMySQL(base64_decode($params["startdate"]));
            $enddate = Emwork_Utility_DateTime::dateThaiToMySQL(base64_decode($params["enddate"]));
        } else {
            $adate = date('Y-m-d');
            $startdate = date("Y-m-d", strtotime("-7 days", strtotime($adate)));
            $enddate = date('Y-m-d');
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
        // $KEY_FILE_LOCATION = APPLICATION_PATH . DS . 'configs' . DS . 'd2ms-207603-0cb6a3946021.json';
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
        $client->setApplicationName("Mobile Applicatoin");
        $client->setAuthConfig($KEY_FILE_LOCATION);
        $client->setScopes([
            'https://www.googleapis.com/auth/analytics.readonly'
        ]);
        $analytics = new Google_Service_Analytics($client);
        return $analytics;
    }

    function getFirstProfileId($analytics)
    {
    	if ($analytics == null) {
    		return null;
    	}
    	
    	$profileId = "";
    	try {
    		$statViewID = System_Model_Setting::getValue("ViewID", "Stat");
    		if(!empty($statViewID)) {
    			$profileId = $statViewID;
    		}
    		//$config = new Zend_Config_Ini(APPLICATION_PATH . DS . 'modules' . DS . 'stat' . DS . 'configs' . DS . 'config.ini', 'analytic');
    		//$profileId = $config->viewID;
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

    function getUserAll($analytics, $profileId, $startdate, $enddate)
    {
    	if ($analytics == null) {
    		return array();
    	}
    	
        $rows = $analytics->data_ga->get('ga:' . $profileId, '' . $startdate, '' . $enddate, 'ga:users');
        return $rows->getRows();
    }

    function getUserMobileAll($analytics, $profileId, $startdate, $enddate)
    {
    	if ($analytics == null) {
    		return array();
    	}
    	
    	$rows = $analytics->data_ga->get('ga:' . $profileId, '' . $startdate, '' . $enddate, 'ga:users', array('filters' => 'ga:deviceCategory==mobile'));
        return $rows->getRows();
    }

    function getUserSesstion($analytics, $profileId, $startdate, $enddate)
    {
    	if ($analytics == null) {
    		return '';
    	}
    	
    	$rows = $analytics->data_ga->get('ga:' . $profileId, '' . $startdate, '' . $enddate, 'ga:avgSessionDuration');
        $seconds = array_sum(array_column($rows->getRows(),0));
        $hours = floor($seconds / 3600);
        $mins = floor($seconds / 60 % 60);
        $secs = floor($seconds % 60);

        $timeFormat = '';
        
        if($hours)
            $timeFormat .= $hours . ' ชั่วโมง ';

        if($mins)
            $timeFormat .= $mins . ' นาที ';
        
        if($secs)
            $timeFormat .= $secs . ' วินาที';

        // $timeFormat = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);
        return $timeFormat;
    }

    function getUserPerDay($analytics, $profileId, $startdate, $enddate)
    {
    	if ($analytics == null) {
    		return array();
    	}
    	
        $rows = $analytics->data_ga->get('ga:' . $profileId, '' . $startdate, '' . $enddate, 'ga:user', array(
            'dimensions' => 'ga:date',
            'metrics' => 'ga:users'
        ));
        return $rows->getRows();
    }

    function getlanguageAction()
    {
        $rows = $this->analytics->data_ga->get('ga:' . $this->profile, '' . $this->startdate, '' . $this->enddate, 'ga:users', array(
            'dimensions' => 'ga:language',
            'metrics' => 'ga:users',
            'sort' => '-ga:users',
            'max-results' => '10'
        ));
        $this->view->language = $rows->getRows();
		Zend_Layout::resetMvcInstance();
    }

    function getcontryAction()
    {
        $rows = $this->analytics->data_ga->get('ga:' . $this->profile, '' . $this->startdate, '' . $this->enddate, 'ga:users', array(
            'dimensions' => 'ga:country',
            'metrics' => 'ga:users',
            'sort' => '-ga:users',
            'max-results' => '10'
        ));
        $this->view->country = $rows->getRows();
		Zend_Layout::resetMvcInstance();
    }

    function getcityAction()
    {
        // $rows = $this->analytics->data_ga->get('ga:' . $this->profile, '7daysAgo', 'today', 'ga:users', array(
        $rows = $this->analytics->data_ga->get('ga:' . $this->profile, '' . $this->startdate, '' . $this->enddate, 'ga:users', array(
            'dimensions' => 'ga:city',
            'metrics' => 'ga:users',
            'sort' => '-ga:users',
            'max-results' => '10'
        ));
        $this->view->city = $rows->getRows();
		Zend_Layout::resetMvcInstance();
    }

    function getbrowserAction()
    {
        // $rows = $this->analytics->data_ga->get('ga:' . $this->profile, '7daysAgo', 'today', 'ga:users', array(
        $rows = $this->analytics->data_ga->get('ga:' . $this->profile, '' . $this->startdate, '' . $this->enddate, 'ga:users', array(
            'dimensions' => 'ga:browser',
            'metrics' => 'ga:users',
            'sort' => '-ga:users',
            'max-results' => '10'
        ));
        $this->view->browser = $rows->getRows();
		Zend_Layout::resetMvcInstance();
    }

    function getosAction()
    {
        // $rows = $this->analytics->data_ga->get('ga:' . $this->profile, '7daysAgo', 'today', 'ga:users', array(
        $rows = $this->analytics->data_ga->get('ga:' . $this->profile, '' . $this->startdate, '' . $this->enddate, 'ga:users', array(
            'dimensions' => 'ga:operatingSystem',
            'metrics' => 'ga:users',
            'sort' => '-ga:users',
            'max-results' => '10'
        ));
        $this->view->os = $rows->getRows();
		Zend_Layout::resetMvcInstance();
    }

    function getserviceAction()
    {
        // $rows = $this->analytics->data_ga->get('ga:' . $this->profile, '7daysAgo', 'today', 'ga:users', array(
        $rows = $this->analytics->data_ga->get('ga:' . $this->profile, '' . $this->startdate, '' . $this->enddate, 'ga:users', array(
            'dimensions' => 'ga:networkLocation',
            'metrics' => 'ga:users',
            'sort' => '-ga:users',
            'max-results' => '10'
        ));
        $this->view->service = $rows->getRows();
		Zend_Layout::resetMvcInstance();
    }

    function getosmobileAction()
    {
        // $rows = $this->analytics->data_ga->get('ga:' . $this->profile, '7daysAgo', 'today', 'ga:users', array(
        $rows = $this->analytics->data_ga->get('ga:' . $this->profile, '' . $this->startdate, '' . $this->enddate, 'ga:users', array(
            'dimensions' => 'ga:deviceCategory,ga:operatingSystem',
            'filters' => 'ga:deviceCategory==mobile',
            'metrics' => 'ga:users',
            'sort' => '-ga:users',
            'max-results' => '10'
        ));
        $this->view->osmobile = $rows->getRows();
		Zend_Layout::resetMvcInstance();
    }

    function getservicemobileAction()
    {
        // $rows = $this->analytics->data_ga->get('ga:' . $this->profile, '7daysAgo', 'today', 'ga:users', array(
        $rows = $this->analytics->data_ga->get('ga:' . $this->profile, '' . $this->startdate, '' . $this->enddate, 'ga:users', array(
            'dimensions' => 'ga:deviceCategory,ga:networkLocation',
            'filters' => 'ga:deviceCategory==mobile',
            'metrics' => 'ga:users',
            'sort' => '-ga:users',
            'max-results' => '10'
        ));
        $this->view->servicemobile = $rows->getRows();
		Zend_Layout::resetMvcInstance();
    }

    function getresolutionAction()
    {
        // $rows = $this->analytics->data_ga->get('ga:' . $this->profile, '7daysAgo', 'today', 'ga:users', array(
        $rows = $this->analytics->data_ga->get('ga:' . $this->profile, '' . $this->startdate, '' . $this->enddate, 'ga:users', array(
        	'dimensions' => 'ga:screenResolution',
        	'filters' => 'ga:deviceCategory==mobile',
            'metrics' => 'ga:users',
            'sort' => '-ga:users',
            'max-results' => '10'
        ));
        $this->view->resolution = $rows->getRows();
		Zend_Layout::resetMvcInstance();
    }

}
?>