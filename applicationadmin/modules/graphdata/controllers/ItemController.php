<?php

class Graphdata_ItemController extends Zend_Controller_Action
{
	protected $statusOption = array('Y' => 'ใช้งาน', 'N' => 'ไม่ใช้งาน');
	protected $publishOption = array ('Y' => 'เผยแพร่', 'N' => 'ไม่เผยแพร่');
	protected $commentStatusOption = array ('Y' => 'เผยแพร่', 'N' => 'ไม่เผยแพร่', 'S' => 'สแปม');
	protected $yesNoOption = array ('Y' => 'ใช่', 'N' => 'ไม่ใช่');
    
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
        $canView = $sUser->isAllow('graphdata.item.view');
        $canAdd = $sUser->isAllow('graphdata.item.manage');
        if (!$canView) {
            $this->_helper->redirector('index', 'index', 'default');
        }
        
        $InfoSearch = new stdClass();
        if (Zend_Session::namespaceIsset("GraphdataItemSearch")) {
        	$infoSearchNamespace = new Zend_Session_Namespace("GraphdataItemSearch");
        	$InfoSearch->textsearch = $infoSearchNamespace->textsearch;
        	$InfoSearch->category= $infoSearchNamespace->category;
        	$InfoSearch->isenable= $infoSearchNamespace->isenable;
        	$InfoSearch->tblPage = $infoSearchNamespace->tblPage;
        	$InfoSearch->tblRow = $infoSearchNamespace->tblRow;
        	$InfoSearch->tblOrderCol = $infoSearchNamespace->tblOrderCol;
        	$InfoSearch->tblOrderDir = $infoSearchNamespace->tblOrderDir;
        	
        	$this->view->InfoSearch = $InfoSearch;
        	Zend_Session::namespaceUnset("GraphdataItemSearch");
        }
        
        $languages = System_Model_Setting::getLanguages();
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
        
        // Get Option
        $modelOption = new Graphdata_Model_Category();
        $graphdataCategoryOption = $modelOption->getOption($defaultLanguage["Code"]);
        
        // Send To View
        $this->view->statusOption = $this->statusOption;
        $this->view->publishOption = $this->publishOption;
        $this->view->graphdataCategoryOption = $graphdataCategoryOption;
        
        // Set View
        $this->view->canAdd = $canAdd;
        $this->view->pageName = "รายการกราฟ";
        $this->view->pageIcon = "fa fa-newspaper-o";
        $this->view->subMenus = array();
        $this->view->breadcrumb = array(
            array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
            array('name' => 'รายการกราฟ')
            
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
        
        // Check Action Rights
        $canView = $sUser->isAllow('graphdata.item.view');
        
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
        
        $infoSearchNamespace = new Zend_Session_Namespace("GraphdataItemSearch");
        $infoSearchNamespace->textsearch = $advancesearch['textsearch'];
        $infoSearchNamespace->category= $advancesearch['isCat'];
        $infoSearchNamespace->isenable= $advancesearch['isEnable'];
        $infoSearchNamespace->tblPage = $_start;
        $infoSearchNamespace->tblRow = $_length;
        $infoSearchNamespace->tblOrderCol = $_order[0]['column'];
        $infoSearchNamespace->tblOrderDir = $_order[0]['dir'];
        
        if($canView):
            $model = new Graphdata_Model_Item();
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
                $d['Title'] = Emwork_Utility::notShowNull($item["Title"]);
                $d['ViewAmount'] = $item["ViewAmount"];
                $d['Ordering'] = Emwork_Utility::notShowNull($item["Ordering"], '-');
                $d['CategoryTitle'] = Emwork_Utility::notShowNull($item["CategoryTitle"]);
                $d['AuthorName'] = Emwork_Utility::notShowNull($item["AuthorName"]);
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
        $canAdd = $sUser->isAllow('graphdata.item.manage');
        if (!$canAdd) {
            $this->_helper->redirector('index', 'item', 'graphdata');
        }
        
        $languages = System_Model_Setting::getLanguages();
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
        
        // Get Option
        $graphdataCategoryModel = new Graphdata_Model_Category();
        $graphdataCategoryOption = $graphdataCategoryModel->getRecursiveOption($defaultLanguage["Code"]);
        
        // Mobile Detect
        $detect = new Mobile_Detect();
        $this->view->isiOS = $detect->isiOS();
        
        // Send Option and lookup To View
        $this->view->defaultLanguage = $defaultLanguage['Code'];
        $this->view->defaultLanguageName = $defaultLanguage['Name'];
        $this->view->languages = $languages;
        $this->view->publishOption = $this->publishOption;
        $this->view->yesNoOption = $this->yesNoOption;
        $this->view->graphdataCategoryOption = $graphdataCategoryOption;
        
        // Set View
        $this->view->pageName = "เพิ่มกราฟ";
        $this->view->pageIcon = "fa fa-newspaper-o";
        $this->view->subMenus = array();
        $this->view->breadcrumb = array(
            array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
            array('name' => 'รายการกราฟ', 'url' => $this->view->url(array('module' => 'graphdata', 'controller' => 'item', 'action' => 'index'), null, true)),
            array('name' => 'เพิ่มกราฟ')
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
        $canAdd = $sUser->isAllow('graphdata.item.manage');
        if (!$canAdd) {
            $result['message'] = 'ไม่สามารถบันทึกได้';
            $this->_helper->json($result);
        }
        
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
        $defaultLanguageCode = $defaultLanguage["Code"];
        
        $model = new Graphdata_Model_Item();
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
            System_Model_Log::action("graphdata.item", "save", $params['params'][$defaultLanguageCode]['Title'], $data->id);
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
        
        // Check Action Rights
        $canDelete = $sUser->isAllow('graphdata.item.manage');
        $canEdit = $sUser->isAllow('graphdata.item.manage');
        $canView = $sUser->isAllow('graphdata.item.view');
        if (!$canView) {
            $this->_helper->redirector('index', 'item', 'graphdata');
        }
        
        // Get Param
        $itemID = $this->_request->getParam('itemid');
        
        // Model
        if (!$itemID) {
        	$this->_helper->redirector('index', 'item', 'graphdata');
        }
        
        $languages = System_Model_Setting::getLanguages();
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
        
        // Get Option
        $graphdataCategoryModel = new Graphdata_Model_Category();
        $graphdataCategoryOption = $graphdataCategoryModel->getRecursiveOption($defaultLanguage["Code"]);
        
        $model = new Graphdata_Model_Item();
        
        // Get Data
        $data = $model->getInfo($itemID);     
        $graphDataList = $model->getGraphDataList($data->ID);     
        
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

        $dataCategories = $model->getCategories($data->ID);
        $dataCategoryIds = array();
        if (count($dataCategories) > 0) {
        	foreach ($dataCategories as $dataCategory) {
        		$dataCategoryIds[] = $dataCategory['GraphdataCategoryID'];
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
        $this->view->dataCategoryIds = $dataCategoryIds;
        $this->view->dataDetails = $dataDetails;
        $this->view->graphDataList = $graphDataList;
        $this->view->dataIntroImages = $dataIntroImages;
        
        $this->view->languages = $languages;
        $this->view->defaultLanguage = $defaultLanguage['Code'];
        $this->view->defaultLanguageName = $defaultLanguage['Name'];
        $this->view->statusOption = $this->statusOption;
        $this->view->publishOption = $this->publishOption;
        $this->view->graphdataCategoryOption = $graphdataCategoryOption;
        $this->view->yesNoOption = $this->yesNoOption;
        
        // Set View
        $this->view->pageName = "ปรับปรุงกราฟ";
        $this->view->pageIcon= "fa fa-newspaper-o";
        $this->view->breadcrumb = array(
            array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
            array('name' => 'รายการกราฟ', 'url' => $this->view->url(array('module' => 'graphdata', 'controller' => 'item', 'action' => 'index'), null, true)),
            array('name' => 'ปรับปรุงกราฟ')
        );
        
        // Log Acion
        System_Model_Log::action("graphdata.item", "view", $dataDetails[$defaultLanguage["Code"]]->Title, $itemID);
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
        $canSave = $sUser->isAllow('graphdata.item.manage');
        if (!$canSave) {
            $result['message'] = 'ไม่สามารถบันทึกได้';
            $this->_helper->json($result);
        }
        
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
        $defaultLanguageCode = $defaultLanguage["Code"];
        
        $model = new Graphdata_Model_Item();
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
            System_Model_Log::action("graphdata.item", "update", $params['params'][$defaultLanguageCode]['Title'], $data->id);
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
        $canSave = $sUser->isAllow('graphdata.item.manage');
        if (!$canSave) {
            $result['message'] = 'ไม่สามารถลบได้';
            $this->_helper->json($result);
        }
        
        // Param
        $params = $this->getRequest()->getParams();
        
        // Update
        $model = new Graphdata_Model_Item();
        $data = $model->delete($params);
        
        // Set Result
        if ($data->result) {
            $result['result'] = true;
            $result['message'] = '';
            
            // Log Acion
            System_Model_Log::action("graphdata.item", "delete",  $params["title"], $params["id"]);
        } else {
            $result['result'] = false;
            $result['message'] = 'ลบไม่สำเร็จ';
        }
        
        $this->_helper->json($result);
    }
    
    public function graphdataformAction() {

    	$this->_helper->layout()->disableLayout();
    	
    	// Log Access
    	System_Model_Log::access();
    	
    	$languages = System_Model_Setting::getLanguages();
    	$defaultLanguage = System_Model_Setting::getDefaultLanguage();
        
    	// Get Params
        $type =  $this->getRequest()->getParam('type');
        $data =  $this->getRequest()->getParam('data');
        $dataObj = json_decode($data, true);

    	// Prepare Data
        $details = array();
        $detailsHeader = array();
    	$ID = null;
    	if (!empty($data)) {
    		$ID = $dataObj["ID"];
    	}
    	if (count($languages) > 0) {
    		foreach ($languages as $lang) {
                $langCode = $lang['Code'];
                $detailsHeader[$langCode] = new stdClass();
                if (!empty($data)) {
                    if ((count($dataObj['Header']) > 0) && (array_key_exists($langCode, $dataObj['Header']))) {
                        $detailsHeader[$langCode]->Type = $dataObj['Header'][$langCode]["Type"];
                        $detailsHeader[$langCode]->Title = $dataObj['Header'][$langCode]["Title"];
                        $detailsHeader[$langCode]->Color = $dataObj['Header'][$langCode]["Color"];
                    }
                }
    		}
        }
        if (count($languages) > 0) {
    		foreach ($languages as $lang) {
                if (count($dataObj['Order']) > 0) {
                    foreach ($dataObj['Order'] as $k=>$v) {
                        $langCode = $lang['Code'];
                        $details[$k][$langCode] = new stdClass();
                        if (!empty($data)) {
                            if ((count($v['Lang']) > 0) && (array_key_exists($langCode, $v['Lang']))) {
                                $details[$k][$langCode]->Title = $v['Lang'][$langCode]["Title"];
                                $details[$k][$langCode]->Value = $v['Lang'][$langCode]["Value"];
                                $details[$k][$langCode]->Color = $v['Lang'][$langCode]["Color"];
                            }
                        }
                    }
                }
    		}
        }
    	
    	// Send Data to view
    	$this->view->ID = $ID;
    	$this->view->Type = empty($dataObj['Type']) ? $type : $dataObj['Type'];
    	$this->view->Title = $dataObj['Title'];
    	$this->view->dataHeader = $detailsHeader;
    	$this->view->dataDetails = $details;
    	$this->view->defaultLanguage = $defaultLanguage['Code'];
        $this->view->languages = $languages;
        $this->view->dataDefault =  $dataDefault;
    }

    public function dialogimportexcelAction()
	{
		// Log Access
        System_Model_Log::access();
        
        // Get Param
        $uid = $this->getRequest()->getParam('uid');
        
        // Send data to view
        $this->view->uid = $uid;
        
		$this->_helper->layout()->disableLayout();
    }
    
    public function importexcelAction() {
        
        // Init
		$result = array();
		$result['result'] = false;
		$result['message'] = '';
		
		$sUser = System_Model_SUser::getInstance();
		if (!$sUser->hasData()) {
			$result['message'] = 'กรุณาเข้าสู่ระบบ';
			$this->_helper->json($result);
		}
		
		// Get Param
		$params = $this->getRequest()->getParams();
		
        try {
		
			// Get File
			$fileModel = new System_Model_Fileinfo();
			$fileHashName = $params['hdfImportFile'][0];
			if($fileHashName){
				$fileData = $fileModel->itemByHashName($fileHashName);
				if ($fileData->ID != null) {
					$Path = explode("/",$fileData->Path);
					$tmpFileName =  APPLICATION_ROOT.DS."files".DS.$Path[1].DS.$Path[2].DS.$Path[3].DS.$fileData->HashName;
				}
			}
			
			if(empty($tmpFileName)){
				$result['message'] = 'กรุณาอัพโหลดไฟล์';
                $this->_helper->json($result);
			}
			
			// Read File
			$inputFileType = 'Excel2007';
			
			$objReader = PHPExcel_IOFactory::createReader($inputFileType);
			$objReader->setReadDataOnly(true);
			$objPHPExcel = $objReader->load($tmpFileName);
			
			$loadedSheetNames = $objPHPExcel->getSheetNames();
                
            $k = 0;
            $objPHPExcel->setActiveSheetIndexByName($loadedSheetNames[$k]);
            
            $objSheet = $objPHPExcel->getActiveSheet();
            
            $highestColumn = $objSheet->getHighestColumn();
            $total_rows = $objSheet->getHighestRow();
            $total_cols = PHPExcel_Cell::columnIndexFromString($objSheet->getHighestColumn());
            
            $dataRow = array();
            if ($total_rows > 1) {
                $data_all = array();
                for ($row = 1; $row <= $total_rows; $row ++) {
                    $datas = array();
                    for ($col = 0; $col < $total_cols; $col ++) {
                        $colName = PHPExcel_Cell::stringFromColumnIndex($col);
                        $datas[$colName] = $objSheet->getCell($colName . $row)->getValue();
                    }
                    $data_all[$row-1] = $datas;
                }
            }
            
            $checkData = true;
            if (count($data_all)>0) {
                $dataList = array();
                $row = 1;
                foreach ($data_all as $d) {

                    if(empty($d["A"]) || empty($d["B"])){
                        continue;
                    }
                    if(empty($d["C"])){
                        $d["C"] = $this->random_color();
                    }

                    $key = 'row-'.$row;
                    if (array_key_exists($key, $dataList)) {
                        // nothing
                    } else {
                        $dataList[$key] = array(
                                "A" => $d["A"],
                                "B" => $d["B"],
                                "C" => $d["C"],
                        );
                    }
                    $row++;
                }
            }
            
			// Check Data
            if(strpos($dataList['row-1']['A'], 'ชื่อ (Labels)') === false) { $checkData=false; }
            if(strpos($dataList['row-1']['B'], 'จำนวน (Value)') === false) { $checkData=false; }
            if(strpos($dataList['row-1']['C'], 'โค๊ดสี (Color)') === false) { $checkData=false; }
            
            if(!$checkData){
                $result['message'] = 'ไฟล์ข้อมูลไม่ถูกต้อง';
                $this->_helper->json($result);
            }

            unset($dataList['row-1']);

            // Prepare Data
            $params['DataList']=$dataList;
            $data = $this->prepareDataFormat($params);

            $result['result'] = true;
            $result['message'] = '';
            $result['id'] = $params['hdfID'];
            $result['type'] = $params['Type'];
            $result['title'] = $params['Title'];
            $result['data'] = json_encode($data);
			
		} catch (Exception $e) {
			$result['message'] = 'การเชื่อมต่อข้อมูลผิดพลาด กรุณาตรวจสอบไฟล์ข้อมูลของท่าน';
		}
		
		// Return Result
		$this->_helper->json($result);
    }

    private function prepareDataFormat($params){

        $languages = System_Model_Setting::getLanguages();
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
        
        $setLineChartColor = true;

        $data = array();
        $data['ID'] = $params['hdfID'];
        $data['Header'] = array();
        if (count($languages) > 0) {
    		foreach ($languages as $lang) {
                $data['Header'][$lang['Code']] = array();
                $data['Header'][$lang['Code']]['Type'] = $params['Type'];
                if($defaultLanguage['Code']==$lang['Code']){
                    $data['Header'][$lang['Code']]['Title'] = $params['Title'];
                    $data['Header'][$lang['Code']]['Color'] = $params['Color'];
                }else{
                    $data['Header'][$lang['Code']]['Title'] = '';
                    $data['Header'][$lang['Code']]['Color'] = '';
                }
            }
        } 
        if(count($params['DataList'])>0){
            $data['Order'] = array();
            foreach($params['DataList'] as $d){
                $uid =  uniqid();
                $data['Order'][$uid] = array();
                $data['Order'][$uid]['Lang'] = array();
                if (count($languages) > 0) {
                    foreach ($languages as $lang) {
                        if($defaultLanguage['Code']==$lang['Code']){
                            // // Case Line Chart
                            // if($setLineChartColor && $params['Type']=='Line Chart'){
                            //     $data['Header'][$defaultLanguage['Code']]['Color'] = $d['C'];
                            //     $setLineChartColor = false;
                            // }
                            $data['Order'][$uid]['Lang'][$lang['Code']] = array(
                                'Title'=>$d['A'],
                                'Value'=>$d['B'],
                                'Color'=>$d['C']
                            );
                        }else{
                            $data['Order'][$uid]['Lang'][$lang['Code']] = array(
                                'Title'=>'',
                                'Value'=>'',
                                'Color'=>''
                            );
                        }
                    }
                }
            }
        }
        return $data;
    }

    private function random_color_part() {
        return str_pad( dechex( mt_rand( 0, 255 ) ), 2, '0', STR_PAD_LEFT);
    }
    
    private function random_color() {
        return random_color_part() . random_color_part() . random_color_part();
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
		$canSave = $sUser->isAllow('graphdata.item.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
    	
    	// Param
        $params = $this->getRequest()->getParams();

    	// Update
    	$model = new Graphdata_Model_Item();
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
		$canSave = $sUser->isAllow('graphdata.item.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถคัดลอกได้';
			$this->_helper->json($result);
		}
    	
    	// Param
        $params = $this->getRequest()->getParams();

		$defaultLanguage = System_Model_Setting::getDefaultLanguage();
		$params['defaultLanguage'] = $defaultLanguage['Code'];

    	// Update
    	$model = new Graphdata_Model_Item();
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
		$canSave = $sUser->isAllow('graphdata.item.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถลบได้';
			$this->_helper->json($result);
		}
    	
    	// Param
        $params = $this->getRequest()->getParams();

    	// Update
    	$model = new Graphdata_Model_Item();
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

