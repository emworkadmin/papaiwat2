<?php

class Faq_QuestionController extends Zend_Controller_Action
{
	protected $statusOption = array('Y' => 'ใช้งาน', 'N' => 'ไม่ใช้งาน');
	protected $publishOption = array ('Y' => 'เผยแพร่', 'N' => 'ไม่เผยแพร่');
	protected $commentStatusOption = array ('Y' => 'เผยแพร่', 'N' => 'ไม่เผยแพร่', 'S' => 'สแปม');
	protected $canCommentTypeOption = array ('D' => 'ค่าพื้นฐาน', 'C' => 'กำหนดเอง');
    protected $yesNoOption = array ('Y' => 'ใช่', 'N' => 'ไม่ใช่');
    protected $questionStatusOption = array('W' => 'รอตอบคำถาม', 'A' => 'ตอบคำถามแล้ว');
    
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
        $canView = $sUser->isAllow('faq.question.view');
        $canAdd = $sUser->isAllow('faq.question.manage');
        if (!$canView) {
            $this->_helper->redirector('index', 'index', 'default');
        }
        
        $InfoSearch = new stdClass();
        if (Zend_Session::namespaceIsset("FaqQuestionSearch")) {
        	$infoSearchNamespace = new Zend_Session_Namespace("FaqQuestionSearch");
            $InfoSearch->textsearch = $infoSearchNamespace->textsearch;
        	$InfoSearch->status= $infoSearchNamespace->status;
        	$InfoSearch->tblPage = $infoSearchNamespace->tblPage;
        	$InfoSearch->tblRow = $infoSearchNamespace->tblRow;
        	$InfoSearch->tblOrderCol = $infoSearchNamespace->tblOrderCol;
        	$InfoSearch->tblOrderDir = $infoSearchNamespace->tblOrderDir;
        	
        	$this->view->InfoSearch = $InfoSearch;
        	Zend_Session::namespaceUnset("FaqQuestionSearch");
        }
        
        $languages = System_Model_Setting::getLanguages();
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
        
        // Get Option
        $modelOption = new Faq_Model_Category();
        $FaqCategoryOption = $modelOption->getOption($defaultLanguage["Code"]);
        
        // Send To View
        $this->view->statusOption = $this->statusOption;
        $this->view->questionStatusOption = Emwork_Utility::addEmptyOption($this->questionStatusOption);
        $this->view->FaqCategoryOption = Emwork_Utility::addEmptyOption($FaqCategoryOption);
        $this->view->FaqSubcategoryOption = array();
        
        // Set View
        $this->view->canAdd = $canAdd;
        $this->view->pageName = "รายการ Q&A";
        $this->view->pageIcon = "fa fa-file-image-o";
        $this->view->subMenus = array();
        $this->view->breadcrumb = array(
            array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
            array('name' => 'รายการ Q&A')
            
        );
    }
    
    public function getlistAction()
    {
        $filterInt = new Zend_Filter_Int();
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
        $canView = $sUser->isAllow('faq.question.view');
        
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
        $advancesearch['searchText'] = $textstr;
        $advancesearch['status'] = $this->getRequest()->getParam('status');
        
        $infoSearchNamespace = new Zend_Session_Namespace("FaqQuestionSearch");
        $infoSearchNamespace->textsearch = $advancesearch['textsearch'];
        $infoSearchNamespace->status= $advancesearch['status'];
        $infoSearchNamespace->tblPage = $_start;
        $infoSearchNamespace->tblRow = $_length;
        $infoSearchNamespace->tblOrderCol = $_order[0]['column'];
        $infoSearchNamespace->tblOrderDir = $_order[0]['dir'];

        if($canView):
            $model = new Faq_Model_Question();
            $data = $model->getList($sidx, $sord, $rows, $page, $textstr, $filter, $advancesearch);
        endif;
        
        $i=$_start;
        if ($canView && $data) {
            $responce['recordsTotal'] = $data[0]["RECORD"];
            $responce['recordsFiltered'] = $data[0]["RECORD"];
            
            foreach($data as $item){
                $i++;
                $d = array();
                $d['DT_RowId'] = $item["ID"];
                $d['NO'] = $i;
                $d['Question'] = Emwork_Utility::notShowNull($item["Question"]);
                $d['FullName'] = Emwork_Utility::notShowNull($item["FullName"]);
                $d['QuestionDate'] = Emwork_Utility::notShowNull(Emwork_Utility_DateTime::MySQLToDateThai($item["QuestionDate"], 'DT'));
                $d['StatusCode'] = $item["Status"];
                $d['Status'] = Emwork_Utility::notShowNull($this->questionStatusOption[$item["Status"]]);
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
        $canDelete = $sUser->isAllow('faq.question.manage');
        $canEdit = $sUser->isAllow('faq.question.manage');
        $canView = $sUser->isAllow('faq.question.view');
        if (!$canView) {
            $this->_helper->redirector('index', 'question', 'faq');
        }
        
        // Zend Filter
        $filterInt = new Zend_Filter_Int();
        
        // Get Param
        $itemID = $filterInt->filter($this->_request->getParam('itemid'));
        
        if (!$itemID) {
        	$this->_helper->redirector('index', 'question', 'faq');
        }
        
        $languages = System_Model_Setting::getLanguages();
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
        
        // Get Data
        $model = new Faq_Model_Question();
        $data = $model->getInfo($itemID);  

        if (!$data->ID) {
        	$this->_helper->redirector('index', 'question', 'faq');
        }
        if($data->Status == 'A'){
            $canEdit = false;
            $canDelete = false;
        }
        
        // Send Rights to View
        $this->view->canDelete = $canDelete;
        $this->view->canEdit = $canEdit;
        
        // Mobile Detect
        $detect = new Mobile_Detect();
        $this->view->isiOS = $detect->isiOS();
        
        // Send To View
        $this->view->data = $data;
        
        $this->view->languages = $languages;
        $this->view->defaultLanguage = $defaultLanguage['Code'];
        $this->view->defaultLanguageName = $defaultLanguage['Name'];
        $this->view->questionStatusOption = $this->questionStatusOption;
        
        // Set View
        $this->view->pageName = "ปรับปรุง Q&A";
        $this->view->pageIcon= "fa fa-file-image-o";
        $this->view->subMenus = array();
        $this->view->breadcrumb = array(
            array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
            array('name' => 'รายการ Q&A', 'url' => $this->view->url(array('module' => 'faq', 'controller' => 'question', 'action' => 'index'), null, true)),
            array('name' => 'ปรับปรุง Q&A')
        );
        
        // Log Acion
        System_Model_Log::action("faq.question", "view", $data->Question, $itemID);
    }
    
    public function updateAction()
    {
        // Init
        $result = array();
        $result['result'] = false;
        $result['id'] = null;
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
        $canSave = $sUser->isAllow('faq.question.manage');
        if (!$canSave) {
            $result['message'] = 'ไม่สามารถบันทึกได้';
            $this->_helper->json($result);
        }
        
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
        $defaultLanguageCode = $defaultLanguage["Code"];
        
        $model = new Faq_Model_Question();
        $params = $this->getRequest()->getParams();
        $params['DefaultLanguageCode'] = $defaultLanguage['Code'];
        
        $data = $model->update($params);
        
        // Set Result
        if ($data->result) {
            $result['result'] = true;
            $result['message'] = '';
            $result['id'] = $data->id;

            // Send Mail
            // if($params['Status']=='A'){
            //     $jobConfig = new Zend_Config_Ini(APPLICATION_PATH . '/configs/job.ini', 'access');
            //     $url = $this->getRequest()->getScheme() . '://' . $this->getRequest()->getHttpHost() . "/admin/faq/question/sendmail";
            //     $sendParams = array();
            //     $sendParams['id'] = $data->id;
            //     $sendParams['key'] = $jobConfig->key;
            //     $sendParams['secret'] = $jobConfig->secret;
            //     Emwork_Utility::sendAsynchronous($url, $sendParams);
            // }

            // Log Acion
            System_Model_Log::action("faq.question", "update", $this->questionStatusOption[$params['Status']], $data->id);
        } else {
            $result['result'] = false;
            $result['message'] = 'บันทึกไม่สำเร็จ';
        }
        
        $this->_helper->json($result);
    }

    /*
	 * Send Mail
	 */
	public function sendmailAction() {
	    
	    // Set View
	    $this->_helper->layout()->disableLayout();
	    $this->_helper->viewRenderer->setNoRender();
	    
	    if ($this->_request->isPost()) {
	        $_key = $this->getRequest()->getParam('key', '');
	        $_secret = $this->getRequest()->getParam('secret', '');
	        $_id = $this->getRequest()->getParam('id', null);
	        
	        $jobConfig = new Zend_Config_Ini(APPLICATION_PATH . '/configs/job.ini', 'access');
	        
	        if ($jobConfig->key == $_key && $jobConfig->secret == $_secret) {
	            
	            $templateMailPath = APPLICATION_PATH . '/configs/templateMail.ini';
	            $defaultLanguage = System_Model_Setting::getDefaultLanguage();
	            
	            // Get Data
                $model = new Faq_Model_Question();
                $data = $model->getInfo($_id);  
                
                if (empty($data->Email) || empty($data->Answer)) {
                    exit;
                }
	            
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
                
                switch($data->Lang){
                    case 'th':
                        $headerImageUrl =  $siteUrl . '/admin/images/other/mail-header-th.jpg';
                        $footerImageUrl =  $siteUrl . '/admin/assets/faq/mail-header-th.jpg';
                        break;
                    case 'zh':
                        $headerImageUrl =  $siteUrl . '/admin/images/other/mail-header-zh.jpg';
                        $footerImageUrl =  $siteUrl . '/admin/assets/faq/mail-header-zh.jpg';
                        break;
                    case 'en':
                    default:
                        $headerImageUrl =  $siteUrl . '/admin/images/other/mail-header-en.jpg';
                        $footerImageUrl =  $siteUrl . '/admin/assets/faq/mail-header-en.jpg';
                        break;
                }
                
                // Set Content
                $templateConfig = new Zend_Config_Ini($templateMailPath, 'faq_question_'.$data->Lang);

                $subject = $templateConfig->subject;
                //$subject= str_replace("{Question}", $data->Question, $subject);
                //$subject= str_replace("{QuestionBy}", $data->FullName, $subject);

                $body = $templateConfig->body;
                $body = str_replace("{Question}", $data->Question, $body);
                $body = str_replace("{QuestionBy}", $data->FullName, $body);
                $body = str_replace("{Answer}", $data->Answer, $body);
                $body = str_replace("{AnswerBy}", $data->AnswerName, $body);
                $body = str_replace("{header_image_url}", $headerImageUrl, $body);
                $body = str_replace("{footer_image_url}", $footerImageUrl, $body);
	            
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
	            
	            // เตรียมข้อมูลในการส่งอีเมล์
	            $mail = new Zend_Mail_InlineImages("UTF-8");
	            $mail->setHeaderEncoding(Zend_Mime::ENCODING_BASE64);
	            $mail->setBodyHtml($body ,"UTF-8")
	            ->setFrom($smtpFromEmail, $smtpFromName)
	            ->addTo($data->UserEmail)
	            ->setSubject($subject);
	            
	            // ทำการส่งอีเมล์
	            $mail->send($transport);
	        }
	    }
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
        $canSave = $sUser->isAllow('faq.question.manage');
        if (!$canSave) {
            $result['message'] = 'ไม่สามารถลบได้';
            $this->_helper->json($result);
        }
        
        // Param
        $params = $this->getRequest()->getParams();
        
        // Update
        $model = new Faq_Model_Question();
        $data = $model->delete($params);
        
        // Set Result
        if ($data->result) {
            $result['result'] = true;
            $result['message'] = '';

            // Log Acion
            $data = $model->getInfo($params["id"]);
            System_Model_Log::action("faq.question", "delete", $data->Question, $params["id"]);
        } else {
            $result['result'] = false;
            $result['message'] = 'ลบไม่สำเร็จ';
        }
        
        $this->_helper->json($result);
    }

}

