<?php

class Poll_ItemController extends Zend_Controller_Action
{
	protected $statusOption = array('Y' => 'ใช้งาน', 'N' => 'ไม่ใช้งาน');
	protected $publishOption = array ('Y' => 'เผยแพร่', 'N' => 'ไม่เผยแพร่');
	protected $commentStatusOption = array ('Y' => 'เผยแพร่', 'N' => 'ไม่เผยแพร่', 'S' => 'สแปม');
	protected $canCommentTypeOption = array ('D' => 'ค่าพื้นฐาน', 'C' => 'กำหนดเอง');
    protected $yesNoOption = array ('Y' => 'ใช่', 'N' => 'ไม่ใช่');
    protected $resultDisplayOprion = array ('pie' => 'กราฟวงกลม', 'bar' => 'กราฟแท่ง');
    protected $answerTypeOption = array ('A' => 'Answer', 'V' => 'View');
    protected $colorCode = array('#E63935', '#D81A62', '#3F4EA2', '#863B96', '#2899D5', '#0D8A7C', '#7CB342', '#FCD836', '#F68C1E', '#F05323');
    
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
        $canView = $sUser->isAllow('poll.item.view');
        $canAdd = $sUser->isAllow('poll.item.manage');
        if (!$canView) {
            $this->_helper->redirector('index', 'index', 'default');
        }
        
        $InfoSearch = new stdClass();
        if (Zend_Session::namespaceIsset("PollItemSearch")) {
        	$infoSearchNamespace = new Zend_Session_Namespace("PollItemSearch");
        	$InfoSearch->textsearch = $infoSearchNamespace->textsearch;
        	$InfoSearch->category= $infoSearchNamespace->category;
        	$InfoSearch->isenable= $infoSearchNamespace->isenable;
        	$InfoSearch->tblPage = $infoSearchNamespace->tblPage;
        	$InfoSearch->tblRow = $infoSearchNamespace->tblRow;
        	$InfoSearch->tblOrderCol = $infoSearchNamespace->tblOrderCol;
        	$InfoSearch->tblOrderDir = $infoSearchNamespace->tblOrderDir;
        	
        	$this->view->InfoSearch = $InfoSearch;
        	Zend_Session::namespaceUnset("PollItemSearch");
        }
        
        $languages = System_Model_Setting::getLanguages();
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
        
        // Get Option
        $modelOption = new Poll_Model_Item();
        $pollCategoryOption = $modelOption->getOption($defaultLanguage["Code"]);
        
        // Send To View
        $this->view->statusOption = $this->statusOption;
        $this->view->publishOption = $this->publishOption;
        $this->view->pollCategoryOption = $pollCategoryOption;
        
        // Set View
        $this->view->canAdd = $canAdd;
        $this->view->pageName = "รายการแบบสำรวจ";
        $this->view->pageIcon = "fa fa-pie-chart";
        $this->view->subMenus = array();
        $this->view->breadcrumb = array(
            array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
            array('name' => 'รายการแบบสำรวจ')
            
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
        $canView = $sUser->isAllow('poll.item.view');
        
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
        $advancesearch['type'] = $this->getRequest()->getParam('type');
        $advancesearch['isEnable'] = $this->getRequest()->getParam('isEnable');
        $advancesearch['isCat'] = $this->getRequest()->getParam('isCat');
        
        $infoSearchNamespace = new Zend_Session_Namespace("PollItemSearch");
        $infoSearchNamespace->textsearch = $advancesearch['textsearch'];
        $infoSearchNamespace->category= $advancesearch['isCat'];
        $infoSearchNamespace->isenable= $advancesearch['isEnable'];
        $infoSearchNamespace->tblPage = $_start;
        $infoSearchNamespace->tblRow = $_length;
        $infoSearchNamespace->tblOrderCol = $_order[0]['column'];
        $infoSearchNamespace->tblOrderDir = $_order[0]['dir'];
        
        $model = new Poll_Model_Item();
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
                $d['Title'] = Emwork_Utility::notShowNull($item["Title"]);
                $d['CategoryTitle'] = Emwork_Utility::notShowNull($item["CategoryTitle"]);
                $d['Created'] = Emwork_Utility::notShowNull(Emwork_Utility_DateTime::MSSqlToDateThai($item["Created"], 'DT'));
                $d['IsEnableCode'] = $item["IsEnable"];
                $d['IsEnable'] = Emwork_Utility::notShowNull($this->publishOption[$item["IsEnable"]]);
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
        $canAdd = $sUser->isAllow('poll.item.manage');

        if (!$canAdd) {
            $this->_helper->redirector('index', 'item', 'poll');
        }
        
        $languages = System_Model_Setting::getLanguages();
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
        
        // Get Option
        $pollCategoryModel = new Poll_Model_Item();
        $pollCategoryOption = $pollCategoryModel->getOption($defaultLanguage["Code"]);
        
        // Mobile Detect
        $detect = new Mobile_Detect();
        $this->view->isiOS = $detect->isiOS();
        
        // Send Option and lookup To View
        $this->view->defaultLanguage = $defaultLanguage['Code'];
        $this->view->defaultLanguageName = $defaultLanguage['Name'];
        $this->view->languages = $languages;
        $this->view->publishOption = $this->publishOption;
        $this->view->yesNoOption = $this->yesNoOption;
        $this->view->pollCategoryOption = $pollCategoryOption;
        $this->view->resultDisplayOprion = $this->resultDisplayOprion;
        
        // Set View
        $this->view->pageName = "เพิ่มแบบสำรวจ";
        $this->view->pageIcon = "fa fa-pie-chart";
        $this->view->subMenus = array();
        $this->view->breadcrumb = array(
            array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
            array('name' => 'รายการแบบสำรวจ', 'url' => $this->view->url(array('module' => 'poll', 'controller' => 'item', 'action' => 'index'), null, true)),
            array('name' => 'เพิ่มแบบสำรวจ')
        );
    }
    
    public function saveAction()
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
        $canAdd = $sUser->isAllow('poll.item.manage');
        if (!$canAdd) {
           $result['message'] = 'ไม่สามารถบันทึกได้';
           $this->_helper->json($result);
        }
        
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
        $defaultLanguageCode = $defaultLanguage["Code"];
        
        $model = new Poll_Model_Item();
        $fileModel = new System_Model_Fileinfo();
        $params = $this->getRequest()->getParams();
        $params['DefaultLanguageCode'] = $defaultLanguage['Code'];
        
        // IntroImage
        $introImageID = null;
        if (count($params['hdfIntroImage'])>0) {
        	$fileData = $fileModel->itemByHashName($params['hdfIntroImage'][0]);
        	if ($fileData->ID != null) {
        		$introImageID = $fileData->ID;
            }
        }
        $params['IntroImageID'] = $introImageID;
        
        // Save
        $data = $model->save($params);
        
        // Set Result
        if ($data->result) {
            $result['result'] = true;
            $result['message'] = '';
            $result['id'] = $data->id;
            
            // Log Acion
            System_Model_Log::action("poll.item", "save", $params['params'][$defaultLanguage['Code']]['Title'], $data->id);
        } else {
            $result['result'] = false;
            $result['message'] = 'บันทึกไม่สำเร็จ';
        }
        
        $this->_helper->json($result);
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
        $canDelete = $sUser->isAllow('poll.item.manage');
        $canEdit = $sUser->isAllow('poll.item.manage');
        $canView = $sUser->isAllow('poll.item.view');
        if (!$canView) {
           $this->_helper->redirector('index', 'item', 'poll');
        }
        
        // Get Param
        $itemID = $this->_request->getParam('itemid');
        
        // Model
        if (!$itemID) {
        	$this->_helper->redirector('index', 'item', 'poll');
        }
        
        $languages = System_Model_Setting::getLanguages();
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
        
        // Get Option
        $pollCategoryModel = new Poll_Model_Item();
        $pollCategoryOption = $pollCategoryModel->getOption($defaultLanguage["Code"]);
        
        // Model
        $model = new Poll_Model_Item();
        
        // Get Data
        $data = $model->getInfo($itemID);

        if ($data->IsDeleted=='Y') {
        	$this->_helper->redirector('index', 'item', 'poll');
        }
        
        $dataDetails = array();
        if (count($languages) > 0) {
        	foreach ($languages as $lang) {
        		$langCode = $lang['Code'];
        		$detail = $model->getDataByLanguage($data->ID, $langCode);
        		if (!empty($detail->Lang)) {
        			$dataDetails[$langCode] = $detail;
        		}
        	}
        }
        
        // Intro Image
        $dataIntroImages = array();
        if(!empty($data->IntroImageID)) {
        	$dataIntroImages[] = array('file_name'=> $data->IntroImageFileName, 'size'=> $data->IntroImageFileSize, 'type'=> $data->IntroImageFileType, 'hash_name'=> $data->IntroImageHashName);
        }
        
        // Send Rights to View
        $this->view->canDelete = $canDelete;
        $this->view->canEdit = $canEdit;
        
        // Mobile Detect
        $detect = new Mobile_Detect();
        $this->view->isiOS = $detect->isiOS();
        
        // Send To View
        $this->view->data = $data;
        $this->view->dataDetails = $dataDetails;
        $this->view->dataIntroImages = $dataIntroImages;
        
        $this->view->languages = $languages;
        $this->view->defaultLanguage = $defaultLanguage['Code'];
        $this->view->defaultLanguageName = $defaultLanguage['Name'];
        $this->view->statusOption = $this->statusOption;
        $this->view->publishOption = $this->publishOption;
        $this->view->pollCategoryOption = $pollCategoryOption;
        $this->view->yesNoOption = $this->yesNoOption;
        $this->view->resultDisplayOprion = $this->resultDisplayOprion;
        
        // Set View
        $this->view->pageName = "ปรับปรุงแบบสำรวจ";
        $this->view->pageIcon= "fa fa-pie-chart";
        $this->view->subMenus = array(
        		array('name' => 'ปรับปรุงแบบสำรวจ', 'active' => true),
        		array('name' => 'รายการคำถาม', 'active' => false, 'url' => $this->view->url(array('module' => 'poll', 'controller' => 'item', 'action' => 'question', 'itemid' => $itemID), null, true)),
                array('name' => 'รายการตอบกลับ', 'active' => false, 'url' => $this->view->url(array('module' => 'poll', 'controller' => 'item', 'action' => 'submission', 'itemid' => $itemID), null, true)),
                array('name' => 'รายงาน', 'active' => false, 'url' => $this->view->url(array('module' => 'poll', 'controller' => 'item', 'action' => 'report', 'itemid' => $itemID), null, true)),
        );
        $this->view->breadcrumb = array(
            array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
            array('name' => 'รายการแบบสำรวจ', 'url' => $this->view->url(array('module' => 'poll', 'controller' => 'item', 'action' => 'index'), null, true)),
            array('name' => 'ปรับปรุงแบบสำรวจ')
        );

		// Log Acion
        System_Model_Log::action("poll.item", "view", $dataDetails[$defaultLanguage['Code']]->Title, $itemID);
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
        $canSave = $sUser->isAllow('poll.item.manage');
        if (!$canSave) {
            $result['message'] = 'ไม่สามารถบันทึกได้';
            $this->_helper->json($result);
        }
        
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
        $defaultLanguageCode = $defaultLanguage["Code"];
        
        $model = new Poll_Model_Item();
        $fileModel = new System_Model_Fileinfo();
        $params = $this->getRequest()->getParams();
        $params['DefaultLanguageCode'] = $defaultLanguage['Code'];
        
        // IntroImage
        $introImageID = null;
        if (count($params['hdfIntroImage'])>0) {
        	$fileData = $fileModel->itemByHashName($params['hdfIntroImage'][0]);
        	if ($fileData->ID != null) {
        		$introImageID = $fileData->ID;
        	}
        }
        $params['IntroImageID'] = $introImageID;
        
        // Update
        $data = $model->update($params);
        
        // Set Result
        if ($data->result) {
            $result['result'] = true;
            $result['message'] = '';
            $result['id'] = $data->id;
            
            // Log Acion
            System_Model_Log::action("poll.item", "update", $params['params'][$defaultLanguage['Code']]['Title'], $data->id);
        } else {
            $result['result'] = false;
            $result['message'] = 'บันทึกไม่สำเร็จ';
        }
        
        $this->_helper->json($result);
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
        $canSave = $sUser->isAllow('poll.item.manage');
        if (!$canSave) {
            $result['message'] = 'ไม่สามารถลบได้';
            $this->_helper->json($result);
        }

        
        // Param
        $params = $this->getRequest()->getParams();
        
        // Update
        $model = new Poll_Model_Item();
        $data = $model->delete($params);
        
        // Set Result
        if ($data->result) {
            $result['result'] = true;
            $result['message'] = '';
            
            // Log Acion
            System_Model_Log::action("poll.item", "delete", $params["title"], $params["id"]);
        } else {
            $result['result'] = false;
            $result['message'] = 'ลบไม่สำเร็จ';
        }
        
        $this->_helper->json($result);
    }

    public function questionAction()
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
        $canView = $sUser->isAllow('poll.item.view');
        $canAdd = $sUser->isAllow('poll.item.manage');
        if (!$canView) {
           $this->_helper->redirector('index', 'index', 'default');
        }
        
        // Get Param
        $itemID = $this->_request->getParam('itemid');
        
        // Model
        if (!$itemID) {
        	$this->_helper->redirector('index', 'item', 'poll');
        }
        
        $languages = System_Model_Setting::getLanguages();
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
        
        // Model
        $model = new Poll_Model_Item();
        
        // Get Data
        $data = $model->getInfo($itemID);

        if ($data->IsDeleted=='Y') {
        	$this->_helper->redirector('index', 'item', 'poll');
        }

        if($data->ID){
            $questionList = array();
            $dataQuestion = $model->getQuestion($data->ID);
            if(count($dataQuestion)>0){
                foreach($dataQuestion as $k1=>$v1){
                    $questionList[$v1['ID']]['ID'] = $v1['ID'];
                    $questionList[$v1['ID']]['Lang'][$v1['Lang']]['Question'] = $v1['Question'];
                    $questionList[$v1['ID']]['Lang'][$v1['Lang']]['Description'] = $v1['Description'];
                    $questionList[$v1['ID']]['OptionList'] = array();

                    $dataOption = $model->getQuestionOption($v1['ID']);
                    if(count($dataOption)>0){
                        foreach($dataOption as $k2=>$v2){
                            $questionList[$v1['ID']]['OptionList'][$v2['ID']]['ID'] = $v2['ID'];
                            $questionList[$v1['ID']]['OptionList'][$v2['ID']]['Lang'][$v2['Lang']]['Name'] = $v2['Name'];
                        }
                    }
                }
            }
            if(count($questionList)>0){
                foreach($questionList as $k1=>$v1){
                    $questionList[$k1]['setting'] = json_encode($v1['Lang']);
                    if(count($v1['OptionList'])>0){
                        foreach($v1['OptionList'] as $k2=>$v2){
                            $questionList[$k1]['OptionList'][$k2]['setting'] = json_encode($v2['Lang']);
                        }
                    }
                }
            }
        }
        
        // Send Rights to View
        $this->view->canAdd = $canAdd;
        
        // Mobile Detect
        $detect = new Mobile_Detect();
        $this->view->isiOS = $detect->isiOS();
        
        // Send To View
        $this->view->data = $data;
        $this->view->questionList = $questionList;
        
        $this->view->languages = $languages;
        $this->view->defaultLanguage = $defaultLanguage['Code'];
        $this->view->defaultLanguageName = $defaultLanguage['Name'];
        
        // Set View
        $this->view->pageName = "รายการคำถาม";
        $this->view->pageIcon= "fa fa-pie-chart";
        $this->view->subMenus = array(
            array('name' => 'ปรับปรุงแบบสำรวจ', 'active' => false, 'url' => $this->view->url(array('module' => 'poll', 'controller' => 'item', 'action' => 'edit', 'itemid' => $itemID), null, true)),
            array('name' => 'รายการคำถาม', 'active' => true),
            array('name' => 'รายการตอบกลับ', 'active' => false, 'url' => $this->view->url(array('module' => 'poll', 'controller' => 'item', 'action' => 'submission', 'itemid' => $itemID), null, true)),
            array('name' => 'รายงาน', 'active' => false, 'url' => $this->view->url(array('module' => 'poll', 'controller' => 'item', 'action' => 'report', 'itemid' => $itemID), null, true)),
        );
        $this->view->breadcrumb = array(
            array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
            array('name' => 'รายการแบบสำรวจ', 'url' => $this->view->url(array('module' => 'poll', 'controller' => 'item', 'action' => 'index'), null, true)),
            array('name' => 'รายการคำถาม')
        );

        // Log Acion
        $detail = $model->getDataByLanguage($data->ID, $defaultLanguage['Code']);
        System_Model_Log::action("poll.item", "view", "รายการคำถาม : ".$detail->Title, $detail->ID);
    }

    public function savequestionAction()
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
        $canAdd = $sUser->isAllow('poll.item.manage');
        if (!$canAdd) {
           $result['message'] = 'ไม่สามารถบันทึกได้';
           $this->_helper->json($result);
        }
        
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
        $defaultLanguageCode = $defaultLanguage["Code"];
        
        $params = $this->getRequest()->getParams();
        $params['DefaultLanguageCode'] = $defaultLanguage['Code'];
        
        // Update
        $model = new Poll_Model_Item();
        $data = $model->saveQuestion($params);
        
        // Set Result
        if ($data->result) {
            $result['result'] = true;
            $result['message'] = '';
            
            // Log Acion
            $detail = $model->getDataByLanguage($params["hdfID"], $defaultLanguage['Code']);
            System_Model_Log::action("poll.item", "update", "รายการคำถาม : ".$detail->Title, $detail->ID);
        } else {
            $result['result'] = false;
            $result['message'] = 'บันทึกข้อมูลไม่สำเร็จ';
        }
        
        $this->_helper->json($result);
    }

    public function submissionAction()
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
        $canView = $sUser->isAllow('poll.item.view');
        $canAdd = $sUser->isAllow('poll.item.manage');
        if (!$canView) {
            $this->_helper->redirector('index', 'index', 'default');
        }

        // Get Param
        $itemID = $this->_request->getParam('itemid');
        
        // Model
        if (!$itemID) {
        	$this->_helper->redirector('index', 'item', 'poll');
        }

        $InfoSearch = new stdClass();
        if (Zend_Session::namespaceIsset("PollSubmissionSearch")) {
        	$infoSearchNamespace = new Zend_Session_Namespace("PollSubmissionSearch");
        	$InfoSearch->tblPage = $infoSearchNamespace->tblPage;
        	$InfoSearch->tblRow = $infoSearchNamespace->tblRow;
        	$InfoSearch->tblOrderCol = $infoSearchNamespace->tblOrderCol;
        	$InfoSearch->tblOrderDir = $infoSearchNamespace->tblOrderDir;
        	
        	$this->view->InfoSearch = $InfoSearch;
        	Zend_Session::namespaceUnset("PollSubmissionSearch");
        }
        
        $languages = System_Model_Setting::getLanguages();
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
        
        // Model
        $model = new Poll_Model_Item();
        
        // Get Data
        $data = $model->getInfo($itemID);

        if ($data->IsDeleted=='Y') {
        	$this->_helper->redirector('index', 'item', 'poll');
        }

        if($data->ID){
            $dataQuestion = $model->getQuestionByLanguage($data->ID, $defaultLanguage['Code']);
        }
        
        // Send Rights to View
        $this->view->canManage = $canManage;
        
        // Mobile Detect
        $detect = new Mobile_Detect();
        $this->view->isiOS = $detect->isiOS();
        
        // Send To View
        $this->view->data = $data;
        $this->view->dataQuestion = $dataQuestion;
        
        $this->view->languages = $languages;
        $this->view->defaultLanguage = $defaultLanguage['Code'];
        $this->view->defaultLanguageName = $defaultLanguage['Name'];
        
        // Set View
        $this->view->pageName = "รายการตอบกลับ";
        $this->view->pageIcon= "fa fa-pie-chart";
        $this->view->subMenus = array(
            array('name' => 'ปรับปรุงแบบสำรวจ', 'active' => false, 'url' => $this->view->url(array('module' => 'poll', 'controller' => 'item', 'action' => 'edit', 'itemid' => $itemID), null, true)),
            array('name' => 'รายการคำถาม', 'active' => false, 'url' => $this->view->url(array('module' => 'poll', 'controller' => 'item', 'action' => 'question', 'itemid' => $itemID), null, true)),
            array('name' => 'รายการตอบกลับ', 'active' => true),
            array('name' => 'รายงาน', 'active' => false, 'url' => $this->view->url(array('module' => 'poll', 'controller' => 'item', 'action' => 'report', 'itemid' => $itemID), null, true)),
        );
        $this->view->breadcrumb = array(
            array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
            array('name' => 'รายการแบบสำรวจ', 'url' => $this->view->url(array('module' => 'poll', 'controller' => 'item', 'action' => 'index'), null, true)),
            array('name' => 'รายการตอบกลับ')
        );

        // Log Acion
        $detail = $model->getDataByLanguage($itemID, $defaultLanguage['Code']);
        System_Model_Log::action("poll.item", "view", "รายการตอบกลับ : ".$detail->Title, $detail->ID);
    }

    public function getsubmissionlistAction()
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
        $canView = $sUser->isAllow('poll.item.view');
        
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
        $textstr = ''; // $this->getRequest()->getParam('textstr');
        $filter = ''; // $this->getRequest()->getParam('filter');
        $id = $this->getRequest()->getParam('id');
        $advancesearch['PollItemID'] = $id;
        
        $infoSearchNamespace = new Zend_Session_Namespace("PollSubmissionSearch");
        $infoSearchNamespace->tblPage = $_start;
        $infoSearchNamespace->tblRow = $_length;
        $infoSearchNamespace->tblOrderCol = $_order[0]['column'];
        $infoSearchNamespace->tblOrderDir = $_order[0]['dir'];
        
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();

        $model = new Poll_Model_Item();
        $data = $model->getSubmissionList($sidx, $sord, $rows, $page, $textstr, $filter, $advancesearch);

        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
        $dataQuestion = $model->getQuestionByLanguage($id, $defaultLanguage['Code']);
        
        $i=$_start;
        if ($canView && $data) {
            $responce['recordsTotal'] = $data[0]["RECORD"];
            $responce['recordsFiltered'] = $data[0]["RECORD"];
            
            foreach($data as $item){
                $i++;
                $d = array();
                $d['DT_RowId'] = $item["ID"];
                $d['NO'] = $i;
                $d['ActionDate'] = Emwork_Utility::notShowNull(Emwork_Utility_DateTime::MSSqlToDateThai($item["ActionDate"], 'DT'));
                $d['RemoteIP'] = Emwork_Utility::notShowNull($item["RemoteIP"]);
                $d['AnswerType'] = Emwork_Utility::notShowNull($this->answerTypeOption[$item["AnswerType"]]);
                if(count($dataQuestion)>0){
                    foreach($dataQuestion as $value){
                        $submissionValues = $model->getSubmissionValues($item["ID"], $value['ID'], $defaultLanguage['Code']);
                        $d[$value["ID"]] = Emwork_Utility::notShowNull($submissionValues->PollOptionName);
                    }
                }
                $responce['data'][] = $d;
            }
        }
        $this->_helper->json($responce);
    }

    public function reportAction()
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
        $canView = $sUser->isAllow('poll.item.view');
        $canAdd = $sUser->isAllow('poll.item.manage');
        if (!$canView) {
           $this->_helper->redirector('index', 'index', 'default');
        }
        
        // Get Param
        $itemID = $this->_request->getParam('itemid');
        
        // Model
        if (!$itemID) {
        	$this->_helper->redirector('index', 'item', 'poll');
        }

        $languages = System_Model_Setting::getLanguages();
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
        
        // Model
        $model = new Poll_Model_Item();
        $data = $model->getDataByLanguage($itemID, $defaultLanguage['Code']);

        if ($data->IsDeleted=='Y') {
        	$this->_helper->redirector('index', 'item', 'poll');
        }
        
		if($data->ID){

			$dataSubmission = $model->getReportSubmissionList($data->ID);

			$dataQuestion = $model->getQuestionListByLanguage($data->ID, $defaultLanguage['Code']);
			if(count($dataQuestion)>0){
				foreach($dataQuestion as $k=>$v){
					$dataOption = $model->getOptionListByLanguage($v['ID'], $defaultLanguage['Code']);
					if(count($dataOption)>0){
						$optionAmount = count($dataOption);
						$index=0;
						foreach($dataOption as $k2=>$v2){
							if(count($dataSubmission)>0){
								foreach($dataSubmission as $k3=>$v3){
									if($v['ID']==$v3['PollQuestionID'] && $v2['ID']==$v3['PollOptionID']){
										$dataOption[$k2]['Amount'] = $v3['Amount'];
										$dataOption[$k2]['Percentage'] = $v3['Percentage'];
										if($index<=9){
											$dataOption[$k2]['Color'] = $this->colorCode[$index];
											$index++;	
										}else{
											$dataOption[$k2]['Color'] = '#'.$this->random_color();
										}
									}
								}
							}
						}
					}
					$dataQuestion[$k]['OptionList'] = $dataOption;
				}
			}

			$this->view->dataQuestion = $dataQuestion;

			// Setting
			$this->view->ShowIntroText = $data->ShowIntroText == 'Y';
            $this->view->ResultDisplay = $data->ResultDisplay;
            $this->view->pageTitle = $data->Title;
            $this->view->ID = $data->ID;
        }
        
        // Send Rights to View
        $this->view->canManage = $canManage;
        
        // Mobile Detect
        $detect = new Mobile_Detect();
        $this->view->isiOS = $detect->isiOS();
        
        // Send To View
        $this->view->languages = $languages;
        $this->view->defaultLanguage = $defaultLanguage['Code'];
        $this->view->defaultLanguageName = $defaultLanguage['Name'];
        
        // Set View
        $this->view->pageName = "รายงาน";
        $this->view->pageIcon= "fa fa-pie-chart";
        $this->view->subMenus = array(
            array('name' => 'ปรับปรุงแบบสำรวจ', 'active' => false, 'url' => $this->view->url(array('module' => 'poll', 'controller' => 'item', 'action' => 'edit', 'itemid' => $itemID), null, true)),
            array('name' => 'รายการคำถาม', 'active' => false, 'url' => $this->view->url(array('module' => 'poll', 'controller' => 'item', 'action' => 'question', 'itemid' => $itemID), null, true)),
            array('name' => 'รายการตอบกลับ', 'active' => false, 'url' => $this->view->url(array('module' => 'poll', 'controller' => 'item', 'action' => 'submission', 'itemid' => $itemID), null, true)),
            array('name' => 'รายงาน', 'active' => true),
        );
        $this->view->breadcrumb = array(
            array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
            array('name' => 'รายการแบบสำรวจ', 'url' => $this->view->url(array('module' => 'poll', 'controller' => 'item', 'action' => 'index'), null, true)),
            array('name' => 'รายงาน')
        );

        // Log Acion
        $detail = $data; //$model->getDataByLanguage($itemID, $defaultLanguage['Code']);
        System_Model_Log::action("poll.item", "view", "รายงาน : ".$detail->Title, $detail->ID);
    }

    public function exportAction() {
        
        // Get Param
        $itemID = $this->_request->getParam('itemid');
        
        $languages = System_Model_Setting::getLanguages();
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
        
        // Model
        $model = new Poll_Model_Item();
        $data = $model->getDataByLanguage($itemID, $defaultLanguage['Code']);

        if ($data->IsDeleted=='Y') {
        	$this->_helper->redirector('index', 'item', 'poll');
        }
        
		if($data->ID){

			$dataSubmission = $model->getReportSubmissionList($data->ID);

			$dataQuestion = $model->getQuestionListByLanguage($data->ID, $defaultLanguage['Code']);
			if(count($dataQuestion)>0){
				foreach($dataQuestion as $k=>$v){
					$dataOption = $model->getOptionListByLanguage($v['ID'], $defaultLanguage['Code']);
					if(count($dataOption)>0){
						$optionAmount = count($dataOption);
						$index=0;
						foreach($dataOption as $k2=>$v2){
							if(count($dataSubmission)>0){
								foreach($dataSubmission as $k3=>$v3){
									if($v['ID']==$v3['PollQuestionID'] && $v2['ID']==$v3['PollOptionID']){
										$dataOption[$k2]['Amount'] = $v3['Amount'];
										$dataOption[$k2]['Percentage'] = $v3['Percentage'];
										if($index<=9){
											$dataOption[$k2]['Color'] = $this->colorCode[$index];
											$index++;	
										}else{
											$dataOption[$k2]['Color'] = '#'.$this->random_color();
										}
									}
								}
							}
						}
					}
					$dataQuestion[$k]['OptionList'] = $dataOption;
				}
			}

			$this->view->dataQuestion = $dataQuestion;

			// Setting
			$this->view->ShowIntroText = $data->ShowIntroText == 'Y';
            $this->view->ResultDisplay = $data->ResultDisplay;
            $this->view->pageTitle = $data->Title;
        }
    	
    	// Set View
    	$this->_helper->layout->disableLayout();
    	 
    	// Set Response
    	header("Content-Type: application/vnd.ms-excel");
    	header("Expires: 0");
    	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    	header("content-disposition: attachment;filename={$data->Title}.xls");
    }

    private function random_color_part() {
		return str_pad( dechex( mt_rand( 0, 255 ) ), 2, '0', STR_PAD_LEFT);
	}
	
	private function random_color() {
		return $this->random_color_part() . $this->random_color_part() . $this->random_color_part();
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
		$canSave = $sUser->isAllow('poll.item.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
    	
    	// Param
        $params = $this->getRequest()->getParams();

    	// Update
    	$model = new Poll_Model_Item();
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
		$canSave = $sUser->isAllow('poll.item.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถคัดลอกได้';
			$this->_helper->json($result);
		}
    	
    	// Param
        $params = $this->getRequest()->getParams();

		$defaultLanguage = System_Model_Setting::getDefaultLanguage();
		$params['defaultLanguage'] = $defaultLanguage['Code'];

    	// Update
    	$model = new Poll_Model_Item();
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
		$canSave = $sUser->isAllow('poll.item.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถลบได้';
			$this->_helper->json($result);
		}
    	
    	// Param
        $params = $this->getRequest()->getParams();

    	// Update
    	$model = new Poll_Model_Item();
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

