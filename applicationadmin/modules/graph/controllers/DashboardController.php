<?php
class Graph_OverviewController extends Zend_Controller_Action
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
        $BetweenDate = new stdClass();
        if (Zend_Session::namespaceIsset("GraphBetweenDate")) {
        	$infoSearchNamespace = new Zend_Session_Namespace("GraphBetweenDate");
        	$BetweenDate->txtStartDate = $infoSearchNamespace->txtEndDate;
            $BetweenDate->txtEndDate= $infoSearchNamespace->txtEndDate;
            $this->view->BetweenDate = $BetweenDate;
        	Zend_Session::namespaceUnset("GraphBetweenDate");
        }
        // Check Action Rights
        $canView = true; // $sUser->isAllow('Graph.page.view');
        $canAdd = true; // $sUser->isAllow('Graph.page.manage');
        if (!$canView) {
            $this->_helper->redirector('index', 'index', 'default');
        }
        $languages = System_Model_Setting::getLanguages();
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();

        require_once APPLICATION_ROOT . DS . 'library' . DS . 'vendor' . DS . 'autoload.php';
        $analytics = $this->initializeAnalytics();
        $profile = $this->getFirstProfileId($analytics);
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
        $userall = $this->getUserAll($analytics, $profile,$startdate,$enddate);
        foreach($userall as $item){
            //เอาเฉพาะตัวเลขจำนวน
        }
        $language = $this->getLanguage($analytics, $profile,$startdate,$enddate);
        $country = $this->getCountry($analytics, $profile,$startdate,$enddate);
        $city = $this->getCity($analytics, $profile,$startdate,$enddate);
        $browser = $this->getBrowser($analytics, $profile,$startdate,$enddate);
        $os = $this->getOS($analytics, $profile,$startdate,$enddate);
        $service = $this->getService($analytics, $profile,$startdate,$enddate);
        $resolution = $this->getResolution($analytics, $profile,$startdate,$enddate);
        $osmobile = $this->getOSMobile($analytics, $profile,$startdate,$enddate);
        $servicemobile = $this->getServiceMobile($analytics, $profile,$startdate,$enddate);
        $UserPerDay = $this->getUserPerDay($analytics, $profile,$startdate,$enddate);

        //send to view
        $this->view->language=$language;
        $this->view->country=$country;
        $this->view->city=$city;
        $this->view->browser=$browser;
        $this->view->os=$os;
        $this->view->service=$service;
        $this->view->resolution=$resolution;
        $this->view->startdate=	Emwork_Utility_DateTime::MySQLToDateThai($startdate);
        $this->view->enddate=Emwork_Utility_DateTime::MySQLToDateThai($enddate);
        $this->view->osmobile=$osmobile;
        $this->view->servicemobile=$servicemobile;
        $this->view->userall=$item[0];
        $this->view->UserPerDay=$UserPerDay;

        // Set View
        $this->view->canAdd = $canAdd;
        $this->view->pageName = "สถิติภาพรวม";
        $this->view->pageIcon = "fa fa-newspaper-o";
        $this->view->subMenus = array();
        $this->view->breadcrumb = array(
            array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'Graph', 'controller' => 'overview', 'action' => 'index'), null, true)),
            array('name' => 'สถิติภาพรวม')
            
        );
		// Log Acion
		System_Model_Log::action("Graph.overview", "view", null, null);
    }

    function initializeAnalytics()
    {
        $KEY_FILE_LOCATION = APPLICATION_PATH . DS . 'configs' . DS . 'd2ms-207603-0cb6a3946021.json';

        // Create and configure a new client object.
        $client = new Google_Client();
        $client->setApplicationName("Hello Analytics Reporting");
        $client->setAuthConfig($KEY_FILE_LOCATION);
        $client->setScopes(['https://www.googleapis.com/auth/analytics.readonly']);
        $analytics = new Google_Service_Analytics($client);
        return $analytics;
    }
    function getFirstProfileId($analytics) {
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
    function getUserPerDay($analytics, $profileId,$startdate,$enddate) {
        $rows= $analytics->data_ga->get(
            'ga:' . $profileId,
            ''.$startdate,
            ''.$enddate,
            'ga:user',      
            array(
                'dimensions' => 'ga:date',
                'metrics' => 'ga:users'
            ));
        return $rows->getRows();
    }     
    function getUserPerHour($analytics, $profileId,$startdate,$enddate) {
        $rows= $analytics->data_ga->get(
            'ga:' . $profileId,
            ''.$startdate,
            ''.$enddate,
            'ga:user',      
            array(
                'dimensions' => 'ga:hour',
                'metrics' => 'ga:users',
                'sort' => '-ga:users'
            ));
        return $rows->getRows();
    }     
    function getUserPerMonth($analytics, $profileId,$startdate,$enddate) {
        $rows= $analytics->data_ga->get(
            'ga:' . $profileId,
            ''.$startdate,
            ''.$enddate,
            'ga:user',      
            array(
                'dimensions' => 'ga:month',
                'metrics' => 'ga:users',
                'sort' => '-ga:users'
            ));
        return $rows->getRows();
    }     
    function getUserPerWeek($analytics, $profileId,$startdate,$enddate) {
        $rows= $analytics->data_ga->get(
            'ga:' . $profileId,
            ''.$startdate,
            ''.$enddate,
            'ga:user',      
            array(
                'dimensions' => 'ga:week',
                'metrics' => 'ga:users',
                'sort' => '-ga:users'
            ));
        return $rows->getRows();
    }     
    function getLanguage($analytics, $profileId,$startdate,$enddate) {
        $rows= $analytics->data_ga->get(
            'ga:' . $profileId,
            ''.$startdate,
            ''.$enddate,
            'ga:users',
            array(
                'dimensions' => 'ga:language',
                'metrics' => 'ga:users',
                'sort' => '-ga:users',
                'max-results' => '10'
            ));
        return $rows->getRows();
    }    
    function getCountry($analytics, $profileId,$startdate,$enddate) {
        // Calls the Core Reporting API and queries for the number of users
        // for the last seven days.
        $rows= $analytics->data_ga->get(
            'ga:' . $profileId,
            ''.$startdate,
            ''.$enddate,
            'ga:users',
            array(
                'dimensions' => 'ga:country',
                'metrics' => 'ga:users',
                'sort' => '-ga:users',
                'max-results' => '10'
        ));
        return $rows->getRows();
    }
    function getCity($analytics, $profileId,$startdate,$enddate) {
        // Calls the Core Reporting API and queries for the number of users
        // for the last seven days.
        $rows= $analytics->data_ga->get(
            'ga:' . $profileId,
            '7daysAgo',
            'today',
            'ga:users',
            array(
                'dimensions' => 'ga:city',
                'metrics' => 'ga:users',
                'sort' => '-ga:users',
                'max-results' => '10'
        ));
        return $rows->getRows();
    }
    function getBrowser($analytics, $profileId,$startdate,$enddate) {
    // Calls the Core Reporting API and queries for the number of users
    // for the last seven days.
        $rows= $analytics->data_ga->get(
            'ga:' . $profileId,
            ''.$startdate,
            ''.$enddate,
            'ga:users',
            array(
                'dimensions' => 'ga:browser',
                'metrics' => 'ga:users',
                'sort' => '-ga:users',
                'max-results' => '10'
        ));
        return $rows->getRows();
    }
    function getOS($analytics, $profileId,$startdate,$enddate) {
        // Calls the Core Reporting API and queries for the number of users
        // for the last seven days.
        $rows=$analytics->data_ga->get(
            'ga:' . $profileId,
            '7daysAgo',
            'today',
            'ga:users',
            array(
                'dimensions' => 'ga:operatingSystem',
                'metrics' => 'ga:users',
                'sort' => '-ga:users',
                'max-results' => '10'
        ));
        return $rows->getRows();
    }
    function getService($analytics, $profileId,$startdate,$enddate) {
        // Calls the Core Reporting API and queries for the number of users
        // for the last seven days.
        $rows= $analytics->data_ga->get(
            'ga:' . $profileId,
            '7daysAgo',
            'today',
            'ga:users',
            array(
                'dimensions' => 'ga:networkLocation',
                'metrics' => 'ga:users',
                'sort' => '-ga:users',
                'max-results' => '10'
        ));
        return $rows->getRows();
    }
    function getResolution($analytics, $profileId,$startdate,$enddate) {
        // Calls the Core Reporting API and queries for the number of users
        // for the last seven days.
        $rows= $analytics->data_ga->get(
            'ga:' . $profileId,
            '7daysAgo',
            'today',
            'ga:users',
            array(
                'dimensions' => 'ga:screenResolution',
                'metrics' => 'ga:users',
                'sort' => '-ga:users',
                'max-results' => '10'
        ));
        return $rows->getRows();
    }
    function getServiceMobile($analytics, $profileId,$startdate,$enddate) {
        $rows= $analytics->data_ga->get(
            'ga:' . $profileId,
            '7daysAgo',
            'today',
            'ga:users',
            array(
                'dimensions' => 'ga:deviceCategory,ga:networkLocation',
                'filters' =>'ga:deviceCategory==mobile',
                'metrics' => 'ga:users',
                'sort' => '-ga:users',
                'max-results' => '10'
        ));
        return $rows->getRows();
    }
    function getOSMobile($analytics, $profileId,$startdate,$enddate) {
        // Calls the Core Reporting API and queries for the number of users
        // for the last seven days.
        $rows= $analytics->data_ga->get(
            'ga:' . $profileId,
            '7daysAgo',
            'today',
            'ga:users',
            array(
                'dimensions' => 'ga:deviceCategory,ga:operatingSystem',
                'filters' =>'ga:deviceCategory==mobile',
                'metrics' => 'ga:users',
                'sort' => '-ga:users',
                'max-results' => '10'
        ));
        return $rows->getRows();
    }
}
?>