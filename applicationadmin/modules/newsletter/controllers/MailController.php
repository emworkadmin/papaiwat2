<?php

class Newsletter_MailController extends Zend_Controller_Action
{
    protected $statusOption = array('Y' => 'ใช้งาน', 'N' => 'ไม่ใช้งาน');
    protected $sendOption = array('A' => 'สมาชิกทุกคน', 'C' => 'สมาชิกตามหมวดหมู่');

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
        $canView = $sUser->isAllow('newsletter.mail.view');
        $canAdd = $sUser->isAllow('newsletter.mail.manage');
        if (!$canView) {
           $this->_helper->redirector('index', 'index', 'default');
        }

        $InfoSearch = new stdClass();
        if (Zend_Session::namespaceIsset("NewsletterMailSearch")) {
        	$infoSearchNamespace = new Zend_Session_Namespace("NewsletterMailSearch");
            $InfoSearch->textsearch = $infoSearchNamespace->textsearch;
            $InfoSearch->iscat = $infoSearchNamespace->iscat;
        	$InfoSearch->isenable = $infoSearchNamespace->isenable;
        	$InfoSearch->tblPage = $infoSearchNamespace->tblPage;
        	$InfoSearch->tblRow = $infoSearchNamespace->tblRow;
        	$InfoSearch->tblOrderCol = $infoSearchNamespace->tblOrderCol;
        	$InfoSearch->tblOrderDir = $infoSearchNamespace->tblOrderDir;
        	
        	$this->view->InfoSearch = $InfoSearch;
        	Zend_Session::namespaceUnset("NewsletterMailSearch");
        }
        
        $languages = System_Model_Setting::getLanguages();
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();

        $model = new Newsletter_Model_Category();
        $categoryOption = $model->getOption($defaultLanguage['Code']);
        
        // Send To View
        $this->view->statusOption = $this->statusOption;
        $this->view->categoryOption = $categoryOption;
        
        // Set View
        $this->view->canAdd = $canAdd;
        $this->view->pageName = "รายการจดหมายข่าว";
        $this->view->pageIcon = "fa fa-newspaper-o";
        $this->view->subMenus = array();
        $this->view->breadcrumb = array(
            array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
            array('name' => 'รายการจดหมายข่าว')
        );
    }

    public function getlistAction()
    {
        $filterInt = new Zend_Filter_Int();
        $canView = true;
        $responce = array();
        $responce['draw'] = $_draw;
        $responce['recordsTotal'] = 0;
        $responce['recordsFiltered'] = 0;
        $responce['data'] = array();
        
        $sUser = System_Model_SUser::getInstance();
        if (!$sUser->hasData()) {
            $this->_helper->json($responce);
        }
        if ($sUser->getInfo()->UserType != "B") {
            $this->_helper->json($responce);
        }
        $canView = $sUser->isAllow('newsletter.mail.view');
        
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
        $textstr = $this->getRequest()->getParam('textstr');
        $textstr = $textstr;
        $filter = ''; //$this->getRequest()->getParam('filter');
        
        //Advance Search
        $advancesearch['textsearch'] = $textstr;
        $advancesearch['type'] = $this->getRequest()->getParam('type');
        $advancesearch['isEnable'] = $this->getRequest()->getParam('isEnable');
        $advancesearch['isCat'] = $this->getRequest()->getParam('isCat');
        
        $infoSearchNamespace = new Zend_Session_Namespace("NewsletterMailSearch");
        $infoSearchNamespace->textsearch = $advancesearch['textsearch'];
        $infoSearchNamespace->iscat = $advancesearch['isCat'];
        $infoSearchNamespace->isenable= $advancesearch['isEnable'];
        $infoSearchNamespace->tblPage = $_start;
        $infoSearchNamespace->tblRow = $_length;
        $infoSearchNamespace->tblOrderCol = $_order[0]['column'];
        $infoSearchNamespace->tblOrderDir = $_order[0]['dir'];

        $languages = System_Model_Setting::getLanguages();
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();

        $model = new Newsletter_Model_Category();
        $categoryOption = $model->getOption($defaultLanguage['Code']);
        
        $model = new Newsletter_Model_Mail();
        $data = $model->getList($sidx, $sord, $rows, $page, $textstr, $filter, $advancesearch);
        
        $i=$_start;
        if ($canView && $data) {
            $responce['recordsTotal'] = $data[0]["RECORD"];
            $responce['recordsFiltered'] = $data[0]["RECORD"];
            
            foreach($data as $item){
                $i++;
                $d = array();
                $d['DT_RowId'] = $item["ID"];
                $d['NO'] = $i;
                $d['Subject'] = Emwork_Utility::notShowNull($item["Subject"]);
                $d['EnewsletterCategoryID'] = Emwork_Utility::notShowNull($categoryOption[$item["EnewsletterCategoryID"]]);
                $d['Created'] = Emwork_Utility::notShowNull(Emwork_Utility_DateTime::MySqlToDateThai($item["Created"], 'DT'));
                $d['IsEnable'] = Emwork_Utility::notShowNull($this->statusOption[$item["IsEnable"]]);
                $d['IsEnableCode'] = $item["IsEnable"];
                $responce['data'][] = $d;
            }
        }
        $this->_helper->json($responce);
    }

    public function addAction()
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
        $canAdd = $sUser->isAllow('newsletter.mail.manage');
        
        if (!$canAdd) {
           $this->_helper->redirector('index', 'mail', 'newsletter');
        }
        
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
        
        $model = new Newsletter_Model_Category();
        $categoryOption = $model->getOption($defaultLanguage['Code']);

        // Mobile Detect
        $detect = new Mobile_Detect();
        $this->view->isiOS = $detect->isiOS();
        
        $this->view->categoryOption = $categoryOption;
        $this->view->statusOption = $this->statusOption;
        $this->view->sendOption = $this->sendOption;
        
        // Set View
        $this->view->canAdd = $canAdd;
        $this->view->pageName = "เพิ่มจดหมายข่าว";
        $this->view->pageIcon = "fa fa-newspaper-o";
        $this->view->subMenus = array();
        $this->view->breadcrumb = array(
            array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
            array('name' => 'รายการจดหมายข่าว', 'url' => $this->view->url(array('module' => 'newsletter', 'controller' => 'mail', 'action' => 'index'), null, true)),
            array('name' => 'เพิ่มจดหมายข่าว')
        );
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
        $canDelete = $sUser->isAllow('newsletter.mail.manage');
        $canEdit = $sUser->isAllow('newsletter.mail.manage');
        $canView = $sUser->isAllow('newsletter.mail.view');
        if (!$canView) {
            $this->_helper->redirector('index', 'mail', 'newsletter');
        }
		
		// Get Param
		$itemID = $this->_request->getParam ('itemid');
		
		if (!$itemID) {
			$this->_helper->redirector('index', 'mail', 'newsletter');
        }

        $defaultLanguage = System_Model_Setting::getDefaultLanguage();

        $model = new Newsletter_Model_Mail();
        $data = $model->getInfo($itemID);

        // File
        $dataFile = array();
        if(!empty($data->FileID)) {
        	$dataFile[] = array(
                'file_name'=> $data->FileName, 
                'size'=> $data->FileSize, 
                'type'=> $data->FileType, 
                'hash_name'=> $data->HashName
            );
        }        
        $data->File = $dataFile;

        $modelCategory = new Newsletter_Model_Category();
        $categoryOption = $modelCategory->getOption($defaultLanguage['Code']);

        // Mobile Detect
        $detect = new Mobile_Detect();
        $this->view->isiOS = $detect->isiOS();

        $this->view->data = $data;

        $this->view->categoryOption = $categoryOption;
        $this->view->statusOption = $this->statusOption;
        $this->view->sendOption = $this->sendOption;

        // Set View
        $this->view->canEdit = $canEdit;
        $this->view->pageName = "ปรับปรุงจดหมายข่าว";
        $this->view->pageIcon = "fa fa-newspaper-o";
        $this->view->subMenus = array();
        $this->view->breadcrumb = array(
            array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
            array('name' => 'รายการจดหมายข่าว', 'url' => $this->view->url(array('module' => 'newsletter', 'controller' => 'mail', 'action' => 'index'), null, true)),
            array('name' => 'ปรับปรุงจดหมายข่าว')
        );

        System_Model_Log::action("newsletter.mail", "view", $data->Subject, $itemID);        
    }

    public function previewAction()
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
        $canDelete = $sUser->isAllow('newsletter.mail.manage');
        $canEdit = $sUser->isAllow('newsletter.mail.manage');
        $canView = $sUser->isAllow('newsletter.mail.view');
        if (!$canView) {
            $this->_helper->redirector('index', 'mail', 'newsletter');
        }
		
		// Get Param
		$itemID = $this->_request->getParam ('itemid');
		
		if (!$itemID) {
			$this->_helper->redirector('index', 'mail', 'newsletter');
        }

        $defaultLanguage = System_Model_Setting::getDefaultLanguage();

        $model = new Newsletter_Model_Mail();
        $data = $model->getInfo($itemID);

        // File
        $dataFile = array();
        if(!empty($data->FileID)) {
        	$dataFile[] = array(
                'file_name'=> $data->FileName, 
                'size'=> $data->FileSize, 
                'type'=> $data->FileType, 
                'hash_name'=> $data->HashName
            );
        }        
        $data->File = $dataFile;

        $modelCategory = new Newsletter_Model_Category();
        $categoryOption = $modelCategory->getOption($defaultLanguage['Code']);

        // Mobile Detect
        $detect = new Mobile_Detect();
        $this->view->isiOS = $detect->isiOS();

        $this->view->data = $data;

        $this->view->categoryOption = $categoryOption;
        $this->view->statusOption = $this->statusOption;
        $this->view->sendOption = $this->sendOption;
        $this->view->sendPreviewOption = array("Y" => "ส่งทั้งหมด", "N" => "ส่งเฉพาะผู้ที่ยังไม่เคยได้รับ");

        // Set View
        $this->view->canAdd = $canAdd;
        $this->view->pageName = "จดหมายข่าว";
        $this->view->pageIcon = "fa fa-newspaper-o";
        $this->view->subMenus = array();
        $this->view->breadcrumb = array(
            array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
            array('name' => 'รายการจดหมายข่าว', 'url' => $this->view->url(array('module' => 'newsletter', 'controller' => 'mail', 'action' => 'index'), null, true)),
            array('name' => 'จดหมายข่าว')
        );

        System_Model_Log::action("newsletter.mail", "preview", $data->Subject, $itemID);                
    }

    public function saveAction()
    {
        // Init
		$result = array ();
		$result ['result'] = false;
		$result ['id'] = null;
		$result ['message'] = '';
		
		$sUser = System_Model_SUser::getInstance();
        if (!$sUser->hasData()) {
            $result['message'] = 'ไม่สามารถบันทึกได้';
            $this->_helper->json($result);
        }
        if ($sUser->getInfo()->UserType != "B") {
            $result['message'] = 'ไม่สามารถบันทึกได้';
            $this->_helper->json($result);
        }
        $canAdd = $sUser->isAllow('newsletter.mail.manage');
        if (!$canAdd) {
            $result['message'] = 'ไม่สามารถบันทึกได้';
            $this->_helper->json($result);
        }

       
        $params = $this->getRequest()->getParams();

        if(!$params['Body']) {
            $result['message'] = 'กรุณากรอกข้อมูลเนื้อหา';
			$this->_helper->json($result);
        }
		
		$defaultLanguage = System_Model_Setting::getDefaultLanguage();
        $params['DefaultLanguageCode'] = $defaultLanguage['Code'];

        // Prepare Intro Image
        $fileModel = new System_Model_Fileinfo();
		$fileID = null;
		if (count($params['hdfFile'])) {
			unset($fileData);
			$fileData = $fileModel->itemByHashName($params['hdfFile'][0]);
			if ($fileData->ID != null) {
				$fileID = $fileData->ID;
			}
		}
        $params['FileID'] = $fileID;        
        
        		
        $model = new Newsletter_Model_Mail();
		$data = $model->save($params);
		
		// Set Result
		if ($data->result) {
			$result ['result'] = true;
			$result ['message'] = '';
			$result ['id'] = $data->id;
			
			// Log Acion
			System_Model_Log::action("newsletter.mail", "save", $params['Subject'], $data->id);
		} else {
			$result ['result'] = false;
			$result ['message'] = 'บันทึกไม่สำเร็จ';
		}
		
		$this->_helper->json ($result);        
    }

    public function updateAction()
    {
                // Init
		$result = array ();
		$result ['result'] = false;
		$result ['id'] = null;
		$result ['message'] = '';
		
		$sUser = System_Model_SUser::getInstance();
        if (!$sUser->hasData()) {
            $result['message'] = 'ไม่สามารถบันทึกได้';
            $this->_helper->json($result);
        }
        if ($sUser->getInfo()->UserType != "B") {
            $result['message'] = 'ไม่สามารถบันทึกได้';
            $this->_helper->json($result);
        }
        $canSave = $sUser->isAllow('newsletter.mail.manage');
        if (!$canSave) {
            $result['message'] = 'ไม่สามารถบันทึกได้';
            $this->_helper->json($result);
        }
        
        $params = $this->getRequest()->getParams();

        if(!$params['Body']) {
            $result['message'] = 'กรุณากรอกข้อมูลเนื้อหา';
			$this->_helper->json($result);
        }
		
		$defaultLanguage = System_Model_Setting::getDefaultLanguage();
        $params['DefaultLanguageCode'] = $defaultLanguage['Code'];

        // Prepare Intro Image
        $fileModel = new System_Model_Fileinfo();
		$fileID = null;
		if (count($params['hdfFile'])) {
			unset($fileData);
			$fileData = $fileModel->itemByHashName($params['hdfFile'][0]);
			if ($fileData->ID != null) {
				$fileID = $fileData->ID;
			}
		}
        $params['FileID'] = $fileID;        
        
        		
        $model = new Newsletter_Model_Mail();
		$data = $model->update($params);
		
		// Set Result
		if ($data->result) {
			$result ['result'] = true;
			$result ['message'] = '';
			$result ['id'] = $data->id;
			
			// Log Acion
			System_Model_Log::action("newsletter.mail", "update", $params['Subject'], $data->id);
		} else {
			$result ['result'] = false;
			$result ['message'] = 'บันทึกไม่สำเร็จ';
		}
		
		$this->_helper->json ($result);   
    }

    public function deleteAction()
    {
        // Init
        $result = array();
        $result['result'] = false;
        $result['message'] = '';
        
        $sUser = System_Model_SUser::getInstance();
        if (!$sUser->hasData()) {
            $result['message'] = 'ไม่สามารถลบได้';
            $this->_helper->json($result);
        }
        if ($sUser->getInfo()->UserType != "B") {
            $result['message'] = 'ไม่สามารถลบได้';
            $this->_helper->json($result);
        }
        $canSave = $sUser->isAllow('newsletter.mail.manage');
        if (!$canSave) {
            $result['message'] = 'ไม่สามารถลบได้';
            $this->_helper->json($result);
        }
        
        // Param
		$params = $this->getRequest()->getParams();

        // Update
        $model = new Newsletter_Model_mail();
        $data = $model->delete($params);
        
        // Set Result
        if ($data->result) {
            $result['result'] = true;
            $result['message'] = '';
            
            // Log Acion
            System_Model_Log::action("newsletter.mail", "delete", $params["Subject"], $params["id"]);
        } else {
            $result['result'] = false;
            $result['message'] = 'ลบไม่สำเร็จ';
        }
        
        $this->_helper->json($result);
    }

	public function sendmailAction() {
        
        // Init
        $result = array();
        $result['result'] = false;
        $result['message'] = '';

        $sUser = System_Model_SUser::getInstance();
        if (!$sUser->hasData()) {
           $result['message'] = 'ไม่สามารถส่งจดหมายได้';
           $this->_helper->json($result);
        }
        if ($sUser->getInfo()->UserType != "B") {
           $result['message'] = 'ไม่สามารถส่งจดหมายได้';
           $this->_helper->json($result);
        }
        $canSave = $sUser->isAllow('newsletter.mail.manage');
        if (!$canSave) {
           $result['message'] = 'ไม่สามารถส่งจดหมายได้';
           $this->_helper->json($result);
        }
        
        // Get Params
        $params = $this->getRequest()->getParams();

        // Get Info Data
        $model = new Newsletter_Model_Mail();
        $data = $model->getInfo($params['hdfID']);
        $data->SendAll = $params['SendAll'];

        // Set File Path
        if($data->FileID) {
            $filePath = APPLICATION_ROOT . DS . "files" . $data->FilePath . DS . $data->HashName;
            $filePath = str_replace('/', DS, $filePath);
            $filePath = str_replace('\\', DS, $filePath);
        }
        $data->FilePath = empty($filePath) ? null : $filePath;

        // Get Data User
        $dataUser = $model->getDataEmail($data);

        $tmp_dataUser = 0;

        if(count($dataUser)>0) {

            // Mail Config
            $siteUrl = System_Model_Setting::getValue("SiteUrl", "General");
            $smtpHost = System_Model_Setting::getValue("Host", "SMTP");
            $smtpPort = System_Model_Setting::getValue("Port", "SMTP");
            $smtpFromEmail = empty($data->FromEmail) ? System_Model_Setting::getValue("FromEmail", "SMTP") : $data->FromEmail;
            $smtpFromName = empty($data->FromName) ? System_Model_Setting::getValue("FromName", "SMTP") : $data->FromName;
            $smtpIsSsl = System_Model_Setting::getValue("IsSsl", "SMTP");
            $smtpSslType = System_Model_Setting::getValue("SslType", "SMTP");
            $smtpIsAuth = System_Model_Setting::getValue("IsAuth", "SMTP");
            $smtpAuthType = System_Model_Setting::getValue("AuthType", "SMTP");
            $smtpAuthUsername = System_Model_Setting::getValue("AuthUsername", "SMTP");
            $smtpAuthPassword = System_Model_Setting::getValue("AuthPassword", "SMTP");
            
            $headerImageUrl =  $siteUrl . '/admin/images/other/mail-header.jpg';
            $footerImageUrl =  $siteUrl . '/admin/images/other/mail-footer.jpg';
            
            // Send Mail
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
          
            $SiteUrl = System_Model_Setting::getValue("SiteUrl", "General");

            if(substr($SiteUrl,-1)  == '/') {
                $SiteUrl = substr($SiteUrl, 0, -1);
            }

            $dom = new DOMDocument;
            @$dom->loadHTML($data->Body);
            $images = $dom->getElementsByTagName('img');
            for ($i = 0; $i < $images->length; $i++) {
                $img = $images->item($i);
                $url = $img->getAttribute('src');
                if(substr($url, 0, 4) != 'http') {
                    if(substr($url, 0, 1) != '/') {
                        $SiteUrl = $SiteUrl . "/";
                    }
                    $replaceUrl = $SiteUrl . $url;
                    $data->Body = str_replace($url, $replaceUrl, $data->Body);
                }
            }
            
            foreach($dataUser as $user) {

                // เตรียมข้อมูลในการส่งอีเมล์
                //$mail = new Zend_Mail_InlineImages("UTF-8");
                $mail = new Zend_Mail("UTF-8");
                $mail->setHeaderEncoding(Zend_Mime::ENCODING_BASE64);
                $mail->setBodyHtml($data->Body,"UTF-8")
                ->setFrom($smtpFromEmail, $smtpFromName)
                ->addTo($user['Email'])
                ->setSubject($data->Subject);
             
                if($data->FileID) {
                    $content = file_get_contents($data->FilePath);
                    $attachment = new Zend_Mime_Part($content);
                    $attachment->type = $data->FileType;
                    $attachment->disposition = Zend_Mime::DISPOSITION_ATTACHMENT;
                    $attachment->encoding = Zend_Mime::ENCODING_BASE64;
                    $attachment->filename = $data->FileName;
                    $mail->addAttachment($attachment);
                }
        
                // ทำการส่งอีเมล์
                $mail->send($transport);
                $tmp_dataUser++;
            }
        }
        
        if($tmp_dataUser == count($dataUser)) {
            $result['result'] = true;
            $result['message'] = '';
            $result['id'] = $data->ID;
            $params['IsSend'] = 'Y';
            $dataUpdate = $model->updateMail($data, $dataUser);

            System_Model_Log::action("newsletter.mail", "sendmail", null, $data->ID);
        } else {
            $result['result'] = false;
            $result['message'] = 'ส่งจดหมายไม่สำเร็จ';
        }
        $this->_helper->json($result);
    }
    
    public function listpublishAction()
    {
    	// Init
    	$result = array();
    	$result['result'] = false;
    	$result['message'] = '';
    	
		$sUser = System_Model_SUser::getInstance();
		if (!$sUser->hasData()) {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
		if ($sUser->getInfo()->UserType != "B") {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
		$canSave = $sUser->isAllow('newsletter.mail.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
    	
    	// Param
        $params = $this->getRequest()->getParams();

    	// Update
    	$model = new Newsletter_Model_Mail();
    	$data = $model->listPublish($params);
    	
    	// Set Result
    	if ($data->result) {
    		$result['result'] = true;
			$result['message'] = '';
    	} else {
    		$result['result'] = false;
    		$result['message'] = 'ปรับปรุงสถานะไม่สำเร็จ';
    	}
    	
    	$this->_helper->json($result);
    }

	public function listcopyAction()
    {
    	// Init
    	$result = array();
    	$result['result'] = false;
    	$result['message'] = '';
    	
		$sUser = System_Model_SUser::getInstance();
		if (!$sUser->hasData()) {
			$result['message'] = 'ไม่สามารถคัดลอกได้';
			$this->_helper->json($result);
		}
		if ($sUser->getInfo()->UserType != "B") {
			$result['message'] = 'ไม่สามารถคัดลอกได้';
			$this->_helper->json($result);
		}
		$canSave = $sUser->isAllow('newsletter.mail.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถคัดลอกได้';
			$this->_helper->json($result);
		}
    	
    	// Param
        $params = $this->getRequest()->getParams();

		$defaultLanguage = System_Model_Setting::getDefaultLanguage();
		$params['defaultLanguage'] = $defaultLanguage['Code'];

    	// Update
    	$model = new Newsletter_Model_Mail();
    	$data = $model->listCopy($params);
    	
    	// Set Result
    	if ($data->result) {
    		$result['result'] = true;
			$result['message'] = '';
    	} else {
    		$result['result'] = false;
    		$result['message'] = 'คัดลอกข้อมูลไม่สำเร็จ';
    	}
    	
    	$this->_helper->json($result);
    }
	
	public function listdeleteAction() 
    {
    	// Init
    	$result = array();
    	$result['result'] = false;
    	$result['message'] = '';
    	
		$sUser = System_Model_SUser::getInstance();
		if (!$sUser->hasData()) {
			$result['message'] = 'ไม่สามารถลบได้';
			$this->_helper->json($result);
		}
		if ($sUser->getInfo()->UserType != "B") {
			$result['message'] = 'ไม่สามารถลบได้';
			$this->_helper->json($result);
		}
		$canSave = $sUser->isAllow('newsletter.mail.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถลบได้';
			$this->_helper->json($result);
		}
    	
    	// Param
        $params = $this->getRequest()->getParams();

    	// Update
    	$model = new Newsletter_Model_Mail();
    	$data = $model->listDelete($params);
    	
    	// Set Result
    	if ($data->result) {
    		$result['result'] = true;
			$result['message'] = '';
    	} else {
    		$result['result'] = false;
    		$result['message'] = 'ลบข้อมูลไม่สำเร็จ';
    	}
    	
    	$this->_helper->json($result);
    }
}

?>