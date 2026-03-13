<?php

class Complain_IndexController extends Zend_Controller_Action
{
    protected $statusOption = array(
        "W" => "รอตรวจสอบ", 
        "C" => "เสร็จสิ้น"
    );
  
    public function init()
    {
        
    }

    public function indexAction()
    {
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
        $canView = $sUser->isAllow('complain.item.view');
        $canAdd = $sUser->isAllow('complain.item.manage');
        if (!$canView) {
            $this->_helper->redirector('index', 'index', 'default');
        }

        $InfoSearch = new stdClass();
        if (Zend_Session::namespaceIsset("ComplainIndexSearch")) {
        	$infoSearchNamespace = new Zend_Session_Namespace("ComplainIndexSearch");
        	$InfoSearch->textsearch = $infoSearchNamespace->textsearch;
            $InfoSearch->sendto = $infoSearchNamespace->sendto;
            $InfoSearch->sendfrom = $infoSearchNamespace->sendfrom;
        	$InfoSearch->status = $infoSearchNamespace->status;
        	$InfoSearch->tblPage = $infoSearchNamespace->tblPage;
        	$InfoSearch->tblRow = $infoSearchNamespace->tblRow;
        	$InfoSearch->tblOrderCol = $infoSearchNamespace->tblOrderCol;
        	$InfoSearch->tblOrderDir = $infoSearchNamespace->tblOrderDir;
        	
        	$this->view->InfoSearch = $InfoSearch;
        	Zend_Session::namespaceUnset("ComplainIndexSearch");
        }

        $languages = System_Model_Setting::getLanguages();
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();

        $this->view->statusOption = $this->statusOption;

        $this->view->canAdd = $canAdd;
        $this->view->pageName = "รายการเรื่องร้องเรียน";
        $this->view->pageIcon= "fa fa-newspaper-o";
	    $this->view->subMenus = array();
	    $this->view->breadcrumb = array(
	        array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
	        array('name' => 'รายการเรื่องร้องเรียน')
	    );

    }

    public function getlistAction()
    {

        $sUser = System_Model_SUser::getInstance();
        if (!$sUser->hasData()) {
            $this->_helper->json($responce);
        }
        if ($sUser->getInfo()->UserType != "B") {
            $this->_helper->json($responce);
        }
        $canView = $sUser->isAllow('complain.item.view');

        // Init
	    $_draw = $this->getRequest()->getParam('draw');
	    $_start = $this->getRequest()->getParam('start'); // Rowindex
	    $_length = $this->getRequest()->getParam('length'); // Page Size
	    $_order = $this->getRequest()->getParam('order'); // Ordering
	    $_columns = $this->getRequest()->getParam('columns'); // Columns
	    $orderIndex = $_order[0]['column']; // Find Ordering Index
	    $sidx = $_columns[$orderIndex]['data']; // Find Ordering Column
	    $sord = $_order[0]['dir']; // Find Ordering Direction
	    $rows = $_length;	// Assign to Page Size (Row)
	    $page = ($_start / $rows) + 1; // Assign to Page Index
	    
	    // Basic Search
	    $textstr = $this->getRequest()->getParam('textsearch');
	    $textstr = $textstr;
        $filter = '';
        
	    //Advance Search
	    $advancesearch['textsearch'] = $textstr;
	    $advancesearch['status'] = $this->getRequest()->getParam('status');
	    $advancesearch['sendfrom'] = $this->getRequest()->getParam('sendfrom');
	    $advancesearch['sendto'] = $this->getRequest()->getParam('sendto');

        $searchNamespace = new Zend_Session_Namespace("ComplainIndexSearch");
        $searchNamespace->searchText = $advancesearch['searchText'];
        $searchNamespace->sendfrom = $advancesearch['sendfrom'];
        $searchNamespace->sendto = $advancesearch['sendto'];
		$searchNamespace->status = $advancesearch['status'];
		$searchNamespace->tblPage = $_start;
		$searchNamespace->tblRow = $_length;
		$searchNamespace->tblOrderCol = $_order[0]['column'];
        $searchNamespace->tblOrderDir = $_order[0]['dir'];

        if($canView):
            $model = new Complain_Model_Index();
            $data = $model->getList($sidx, $sord, $rows, $page, $textstr, $filter, $advancesearch);
        endif;

        $i=$_start;
        $responce = array();
        $responce['draw'] = $_draw;
        $responce['recordsTotal'] = 0;
        $responce['recordsFiltered'] = 0;
        $responce['data'] = array();

        if ($canView && $data) {
	        $responce['recordsTotal'] = $data[0]["RECORD"];
	        $responce['recordsFiltered'] = $data[0]["RECORD"];
	        foreach($data as $item) {
	            $i++;
	            $d = array();
	            $d['DT_RowId'] = $item["ID"];
	            $d['ID'] = empty($item["ID"]) ? "" : $item["ID"];
	            $d['NO'] = $i;
	            $d['DateComplain'] = Emwork_Utility::notShowNull(Emwork_Utility_DateTime::MySQLToDateThai($item["DateComplain"], "DT"));
	            $d['Topic'] = Emwork_Utility::notShowNull($item["Topic"]);
	            $d['FullName'] = Emwork_Utility::notShowNull($item["FullName"]);
	            $d['Status'] = Emwork_Utility::notShowNull($this->statusOption[$item['Status']]);
	            $responce['data'][] = $d;
	        }
        }

        $this->_helper->json($responce);
    }

	public function editAction()
    {
        // Log Access
        System_Model_Log::access();

        $sUser = System_Model_SUser::getInstance();
        if (!$sUser->hasData()) {
            $this->_helper->redirector('index', 'index', 'default');
        }
        if ($sUser->getInfo()->UserType != "B") {
            $this->_helper->redirector('index', 'index', 'default');
        }
        $canDelete = $sUser->isAllow('complain.item.manage');
        $canEdit = $sUser->isAllow('complain.item.manage');
        $canView = $sUser->isAllow('complain.item.view');
        if (!$canView) {
            $this->_helper->redirector('index', 'index', 'complain');
        }
        
        $languages = System_Model_Setting::getLanguages();
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();

        $langOption = array();
        foreach($languages as $key => $val) {
            $langOption[$val['Code']] = $val['Name'];
        }

        // Get Param
        $params = $this->getRequest()->getParams();
        $itemID = $params['itemid'];
        
        // Get Data
        $model = new Complain_Model_Index();
        $data = $model->getData($itemID);

        $modelFile = new System_Model_Fileinfo();
        $dataImages = $modelFile->item($data->FileInfoID);

	    $images = new stdClass();
	    if ($dataImages) {
			$images->file_name = $dataImages->Name;
			$images->size = $dataImages->FileSize;
			$images->type = $dataImages->FileType;
			$images->hash_name = $dataImages->HashName;
			$images->path = $dataImages->Path;
	    }
	    $data->ImageIntro = $images;

        $this->view->canEdit = $canEdit;
        $this->view->canDelete = $canDelete;
        $this->view->data = $data;
        $this->view->statusOption = $this->statusOption;
        $this->view->langOption = $langOption;

	    $this->view->pageName = "รายละเอียดเรื่องร้องเรียน";
	    $this->view->pageIcon= "fa fa-newspaper-o";
	    $this->view->subMenus = array(
            array('name' => 'รายละเอียดเรื่องร้องเรียน', 'active' => true),
            array('name' => 'ประวัติการตอบกลับเรื่องร้องเรียน', 'active' => false, 'url' => $this->view->url(array("module" => "complain", "controller" => "index", "action" => "responselog", "itemid" => $data->ID), null, true))
        );
	    $this->view->breadcrumb = array(
	        array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
	        array('name' => 'รายการเรื่องร้องเรียน', 'url' => $this->view->url(array('module' => 'complain', 'controller' => 'index', 'action' => 'index'), null, true)),
	        array('name' => 'รายละเอียดเรื่องร้องเรียน')
	    );
		// Log Acion
	    System_Model_Log::action("complain.index", "view", $data->ComplainTopic, $itemID);
    }

	function updateAction()
	{
        // Init
		$result = array();
		$result['result'] = false;
		$result['id'] = null;
		$result['message'] = '';
	
		// Param
		$params = $this->getRequest()->getParams();
		
        $sUser = System_Model_SUser::getInstance();
        if (!$sUser->hasData()) {
            $result['message'] = 'ไม่สามารถบันทึกได้';
            $this->_helper->json($result);
        }
        if ($sUser->getInfo()->UserType != "B") {
            $result['message'] = 'ไม่สามารถบันทึกได้';
            $this->_helper->json($result);
        }
        $canSave = $sUser->isAllow('complain.item.manage');
        if (!$canSave) {
            $result['message'] = 'ไม่สามารถบันทึกได้';
            $this->_helper->json($result);
        }
        
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
        
        // Prepare Intro Image
        $modelFile = new System_Model_Fileinfo();
	    $introImageID= null;
	    if (count($params['hdfIntroImage'])) {
	        unset($fileData);
	        $fileData = $modelFile->itemByHashName($params['hdfIntroImage'][0]);
	        if ($fileData->ID != null) {
	            $introImageID = $fileData->ID;
	        }
	    }
        $params['IntroImageID'] = $introImageID;

        $model = new Complain_Model_Index();
        $data = $model->update($params);

        if ($data->result) {
	        $result['result'] = true;
	        $result['message'] = '';
            $result['id'] = $data->ID;
            
            $model->savelog($params);
            $this->sendmail($params);
            
	        // Log Acion
	        System_Model_Log::action("complain.index", "update",  $params['ResponseDetail'], $data->ID);
	    } else {
	        $result['result'] = false;
	        $result['message'] = 'บันทึกไม่สำเร็จ';
        }

        $this->_helper->json($result);
    }

    private function sendmail($params)
    {

        $itemID = $params['hdfID'];
        $body = $params['ResponseDetail'];
        $model = new Complain_Model_Index();
        $data = $model->getData($itemID);

		// Mail Config
		$siteUrl = System_Model_Setting::getValue("SiteUrl", "General");
		$smtpHost = System_Model_Setting::getValue("Host", "SMTP");
		$smtpPort = System_Model_Setting::getValue("Port", "SMTP");
		$smtpFromEmail = System_Model_Setting::getValue("FromEmail", "SMTP");
		$smtpFromName = System_Model_Setting::getValue("FromName", "SMTP");
		$smtpIsSsl = System_Model_Setting::getValue("IsSsl", "SMTP");
		$smtpSslType = System_Model_Setting::getValue("SslType", "SMTP");
		$smtpIsAuth = System_Model_Setting::getValue("IsAuth", "SMTP");
		$smtpAuthType = System_Model_Setting::getValue("AuthType", "SMTP");
		$smtpAuthUsername = System_Model_Setting::getValue("AuthUsername", "SMTP");
		$smtpAuthPassword = System_Model_Setting::getValue("AuthPassword", "SMTP");		

		// Config Mail
		$config = array(
			'port' => $smtpPort
		);
		if ($smtpIsAuth == 'Y') {
			$config["auth"] = $smtpAuthType;
			$config["username"] = $smtpAuthUsername;
			$config["password"] = $smtpAuthPassword;
		}
		if ($smtpIsSsl == 'Y') {
			$config["ssl"] = $smtpSslType;
		}
			
        $transport = new Zend_Mail_Transport_Smtp($smtpHost, $config);

        $subject = $data->ComplainTopic;
        
        $mail = new Zend_Mail_InlineImages("UTF-8");
        $mail->setHeaderEncoding(Zend_Mime::ENCODING_BASE64);
        $mail->setBodyHtml(nl2br($body) ,"UTF-8")
        ->setFrom($smtpFromEmail, $smtpFromName)
        ->addTo($data->Email)
        ->setSubject($subject);
        
        // ทำการส่งอีเมล์
        $mail->send($transport);
    }

    function responselogAction()
    {
        // Log Access
        System_Model_Log::access();

        $sUser = System_Model_SUser::getInstance();
        if (!$sUser->hasData()) {
            $this->_helper->redirector('index', 'index', 'default');
        }
        if ($sUser->getInfo()->UserType != "B") {
            $this->_helper->redirector('index', 'index', 'default');
        }
        $canView = $sUser->isAllow('complain.item.view');
        if (!$canView) {
            $this->_helper->redirector('index', 'index', 'complain');
        }
        
        $languages = System_Model_Setting::getLanguages();
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();

        $params = $this->getRequest()->getParams();
        $itemID = $params['itemid'];

        $this->view->itemID = $itemID;

	    $this->view->pageName = "ประวัติการตอบกลับเรื่องร้องเรียน";
	    $this->view->pageIcon= "fa fa-newspaper-o";
	    $this->view->subMenus = array(
            array('name' => 'รายละเอียดเรื่องร้องเรียน', 'active' => false, 'url' => $this->view->url(array("module" => "complain", "controller" => "index", "action" => "edit", "itemid" => $itemID), null, true)),
            array('name' => 'ประวัติการตอบกลับเรื่องร้องเรียน', 'active' => true)
        );
	    $this->view->breadcrumb = array(
	        array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
	        array('name' => 'รายการเรื่องร้องเรียน', 'url' => $this->view->url(array('module' => 'complain', 'controller' => 'index', 'action' => 'index'), null, true)),
	        array('name' => 'รายละเอียดเรื่องร้องเรียน')
        );
    }

    public function getresponselistAction()
    {

        $sUser = System_Model_SUser::getInstance();
        if (!$sUser->hasData()) {
            $this->_helper->json($responce);
        }
        if ($sUser->getInfo()->UserType != "B") {
            $this->_helper->json($responce);
        }
        $canView = $sUser->isAllow('complain.item.view');

        // Init
	    $_draw = $this->getRequest()->getParam('draw');
	    $_start = $this->getRequest()->getParam('start'); // Rowindex
	    $_length = $this->getRequest()->getParam('length'); // Page Size
	    $_order = $this->getRequest()->getParam('order'); // Ordering
	    $_columns = $this->getRequest()->getParam('columns'); // Columns
	    $orderIndex = $_order[0]['column']; // Find Ordering Index
	    $sidx = $_columns[$orderIndex]['data']; // Find Ordering Column
	    $sord = $_order[0]['dir']; // Find Ordering Direction
	    $rows = $_length;	// Assign to Page Size (Row)
	    $page = ($_start / $rows) + 1; // Assign to Page Index
	    
	    // Basic Search
	    $textstr = $this->getRequest()->getParam('textsearch');
	    $textstr = $textstr;
        $filter = '';
        
	    //Advance Search
	    $advancesearch['textsearch'] = $textstr;
	    $advancesearch['ComplainID'] = $this->getRequest()->getParam('ComplainID');

        if($canView):
            $model = new Complain_Model_Index();
            $data = $model->getresponseList($sidx, $sord, $rows, $page, $textstr, $filter, $advancesearch);
        endif;

        $i=$_start;
        $responce = array();
        $responce['draw'] = $_draw;
        $responce['recordsTotal'] = 0;
        $responce['recordsFiltered'] = 0;
        $responce['data'] = array();

        if ($canView && $data) {
	        $responce['recordsTotal'] = $data[0]["RECORD"];
	        $responce['recordsFiltered'] = $data[0]["RECORD"];
	        foreach($data as $item) {
	            $i++;
	            $d = array();
	            $d['DT_RowId'] = $item["ID"];
	            $d['ID'] = empty($item["ID"]) ? "" : $item["ID"];
	            $d['NO'] = $i;
	            $d['Created'] = Emwork_Utility::notShowNull(Emwork_Utility_DateTime::MySQLToDateThai($item["Created"], "DT"));
	            $d['ResponseDetail'] = nl2br(Emwork_Utility::notShowNull($item["ResponseDetail"]));
	            $d['FullName'] = Emwork_Utility::notShowNull($item["FullName"]);
	            $d['Status'] = Emwork_Utility::notShowNull($this->statusOption[$item['Status']]);
	            $responce['data'][] = $d;
	        }
        }

        $this->_helper->json($responce);
    }

}

?>