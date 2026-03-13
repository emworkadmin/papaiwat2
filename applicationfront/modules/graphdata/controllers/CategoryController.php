<?php 


class Graphdata_CategoryController extends Zend_Controller_Action
{
	protected $lang;
	protected $mainCategoryID = 2;

	public function init()
	{
		// Auth
		$auth = Zend_Auth::getInstance();
		$authData = $auth->getIdentity();
		$this->auth = $authData;

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
	
	public function indexAction()
	{
		// Log Access
		System_Model_Log::access();
		
		// Zend Filter
        $filterInt = new Zend_Filter_Int();
        
        // Get Param
		$id = $filterInt->filter($this->_request->getParam('id'));

		// Get Info
		$model = new Graphdata_Model_Category();
		$dataDetail = $model->getDataByLanguage($id, $this->lang);
				
		// Send Data to view
		$this->view->id = $id;
		$this->view->pageTitle = $dataDetail->Title;	
		
		$menuID = $this->findMenu("/graphdata/category/index/id/" . $id);
		Zend_Registry::set("MenuID", $menuID);
	}
	
	public function getlistAction() {
		$filterInt = new Zend_Filter_Int();
		
		$responce = array();
		$responce['total'] = 0;
		$responce['totalPage'] = 0;
		$responce['pageIndex'] = 0;
		$responce['html'] = '';

		// Category Setting
		$id = $filterInt->filter($this->getRequest()->getParam('id'));
		$cid = $filterInt->filter($this->getRequest()->getParam('cid'));
		if(empty($cid)){
			$cid = $id;
		}
		$categoryModel = new Graphdata_Model_Category();
		$categoryData = $categoryModel->getSettings($cid);
		$categorySetting = json_decode($categoryData->Settings);

		// Graph data category setting
		$gc = new stdClass();
		$gc->CategoryPerPageSetting = $categorySetting->CategoryPerPageSetting;
		$gc->CategoryPerPage = $categorySetting->CategoryPerPage;
		$gc->CategoryAmountColumnSetting = $categorySetting->CategoryAmountColumnSetting;
		$gc->CategoryAmountColumn = $categorySetting->CategoryAmountColumn;
		$gc->CategoryShowImageSetting = $categorySetting->CategoryShowImageSetting;
		$gc->CategoryShowImage = $categorySetting->CategoryShowImage;
		$gc->CategoryShowTitleSetting = $categorySetting->CategoryShowTitleSetting;
		$gc->CategoryShowTitle = $categorySetting->CategoryShowTitle;
		$gc->CategoryShowIntroTextSetting = $categorySetting->CategoryShowIntroTextSetting;
		$gc->CategoryShowIntroText = $categorySetting->CategoryShowIntroText;

		// Graph data default setting
		$g = new stdClass();
		$g->CategoryPerPage = System_Model_Setting::getValue("CategoryPerPage", "Graphdata");
		$g->CategoryPerPage = $gc->CategoryPerPageSetting == "D" ? $g->CategoryPerPage : $gc->CategoryPerPage;
		$g->CategoryAmountColumn = System_Model_Setting::getValue("CategoryAmountColumn", "Graphdata");
		$g->CategoryAmountColumn = $gc->CategoryAmountColumnSetting == "D" ? $g->CategoryAmountColumn : $gc->CategoryAmountColumn;
		$g->CategoryAmountColumn = empty($g->CategoryAmountColumn) || $g->CategoryAmountColumn <= 1 ? 1 : ($g->CategoryAmountColumn >= 4 ? 4 : $g->CategoryAmountColumn);
		$g->CategoryShowImage = System_Model_Setting::getValue("CategoryShowImage", "Graphdata");
		$g->CategoryShowImage = $gc->CategoryShowImageSetting == "D" ? $g->CategoryShowImage : $gc->CategoryShowImage;
		$g->CategoryShowTitle = System_Model_Setting::getValue("CategoryShowTitle", "Graphdata");
		$g->CategoryShowTitle = $gc->CategoryShowTitleSetting == "D" ? $g->CategoryShowTitle : $gc->CategoryShowTitle;
		$g->CategoryShowIntroText = System_Model_Setting::getValue("CategoryShowIntroText", "Graphdata");
		$g->CategoryShowIntroText = $gc->CategoryShowIntroTextSetting == "D" ? $g->CategoryShowIntroText : $gc->CategoryShowIntroText;

		$sortType = 'Ordering';
		$sortDirection = 'asc';
				
		// Init
		$pageSize = empty($g->CategoryPerPage) ? 10 : $g->CategoryPerPage;
		$pageIndex = $this->getRequest()->getParam('pageIndex', 1);
		
		// Search
		$textstr = $this->getRequest()->getParam('search');
		$id = $filterInt->filter($this->getrequest()->getParam('id'));
		$cid = $filterInt->filter($this->getRequest()->getParam('cid'));
		
		$advancesearch = array();
		if(!empty($cid)){
			$advancesearch["parent_id"] = $cid;
		}else{
			$advancesearch["parent_id"] = $id;
		}
		
		$model = new Graphdata_Model_Category();
		$items = $model->getList($sortType, $sortDirection, $pageSize, $pageIndex, $textstr, $filter, $advancesearch, $this->lang);
		
		// Set Setting to view
		$this->view->defaultImageUrl = $this->view->baseUrl("images/other/no-image.jpg");
		$this->view->graphdataSettingCategoryAmountColumn = $g->CategoryAmountColumn;
		$this->view->graphdataSettingCategoryShowImage = $g->CategoryShowImage == 'Y';
		$this->view->graphdataSettingCategoryShowTitle = $g->CategoryShowTitle == 'Y';
		$this->view->graphdataSettingCategoryShowIntroText = $g->CategoryShowIntroText == 'Y';
		
		// Send Data to view
		$this->view->id = $id;
		$this->view->cid = $cid;
		$this->view->items = $items;
		if ($g->CategoryAmountColumn == 1) {
			$responce["html"] = $this->view->render('category/_crows.phtml');
		} else {
			$responce["html"] = $this->view->render('category/_ccards.phtml');
		}
		if (count($items) > 0) {
			$responce["total"] = $items[0]["RECORD"];
			$responce['totalPage'] = ceil($responce["total"] / $pageSize);
		}
		$responce['pageIndex'] = $pageIndex;
		$this->_helper->json($responce);
	}
	
	// Item List	
	public function itemsAction()
	{	
		// Log Access
		System_Model_Log::access();
		
		// Zend Filter
        $filterInt = new Zend_Filter_Int();
        
        // Get Param
		$id = $filterInt->filter($this->getRequest()->getParam('id'));
		$cid = $filterInt->filter($this->getRequest()->getParam('cid'));
		// id = cid when id = 0
		if (empty($id) || empty($cid)) {
			// Show "Not found data"
			$this->renderScript('index/_alertnotfound.phtml');
			return;
		}
		
		$model = new Graphdata_Model_Category();
		$mainCategoryData = $model->getDataByLanguage($id, $this->lang);
		$categoryData = $model->getDataByLanguage($cid, $this->lang);

		$parent[0] =  $categoryData->ParentID;
		$parentName[0] = $categoryData->Title;

		$checkParen = 1 ;

		while($checkParen != null){
			$dataParen = $model->getDataByLanguage($parent[$checkParen-1], $this->lang);
			$parent[$checkParen] = $dataParen->ParentID;
			$parentName[$checkParen] = $dataParen->Title;
			if($parent[$checkParen] != null){
				$checkParen++ ;
			}else{
				$checkParen = null ;
			}
		}

		// Send Data to view
		$this->view->id = $id;
		$this->view->cid = $cid;
		$this->view->parentMenu = $mainCategoryData->Title;
		$this->view->pageTitle = $categoryData->Title;
		
		// Set Global
		$menuID = $this->findMenu("/graphdata/category/index/id/" . $id);
		Zend_Registry::set("MenuID", $menuID);
		
		$extraBreadcrumb = array();

		$check = 0 ;
		$num = count($parent);
		$sumparen = $num - 2 ;

		while($sumparen > -1){
			$extraBreadcrumb[] = array('url' => '/graphdata/category/items/id/' . $id . '/cid/' . $parent[$sumparen-1], 'name' => $parentName[$sumparen]);
			$sumparen-- ;
			if($sumparen == -1){
				$sumparen = null ;
			}
		}

		Zend_Registry::set("ExtraBreadcrumb", $extraBreadcrumb);
	}
	
	public function getitemsAction() {
		$mainCategoryID = $this->mainCategoryID;
		$filterInt = new Zend_Filter_Int();
		
		$responce = array();
		$responce['total'] = 0;
		$responce['totalPage'] = 0;
		$responce['pageIndex'] = 0;
		$responce['html'] = '';

		// Category
		$id = $filterInt->filter($this->getRequest()->getParam('id'));
		$cid = $filterInt->filter($this->getRequest()->getParam('cid'));
		if(empty($cid)){
			$cid = $id;
		}

		$categoryModel = new Graphdata_Model_Category();
		$categoryData = $categoryModel->getSettings($cid);
		$categorySetting = json_decode($categoryData->Settings);
		
		// Graph data category setting
		$gc = new stdClass();
		$gc->ItemPerPageSetting = $categorySetting->ItemPerPageSetting;
		$gc->ItemPerPage = $categorySetting->ItemPerPage;
		$gc->ItemSortSetting = $categorySetting->ItemSortSetting;
		$gc->ItemSortType = $categorySetting->ItemSortType;
		$gc->ItemSortDirection = $categorySetting->ItemSortDirection;
		$gc->ItemAmountColumnSetting = $categorySetting->ItemAmountColumnSetting;
		$gc->ItemAmountColumn = $categorySetting->ItemAmountColumn;
		$gc->ItemShowImageSetting = $categorySetting->ItemShowImageSetting;
		$gc->ItemShowImage = $categorySetting->ItemShowImage;
		$gc->ItemShowTitleSetting = $categorySetting->ItemShowTitleSetting;
		$gc->ItemShowTitle = $categorySetting->ItemShowTitle;
		$gc->ItemShowIntroTextSetting = $categorySetting->ItemShowIntroTextSetting;
		$gc->ItemShowIntroText = $categorySetting->ItemShowIntroText;
		
		// Graph data default setting
		$g = new stdClass();
		$g->ItemPerPage = System_Model_Setting::getValue("ItemPerPage", "Graphdata");
		$g->ItemPerPage = $gc->ItemPerPageSetting == "D" ? $g->ItemPerPage : $gc->ItemPerPage;
		$g->ItemSortType = System_Model_Setting::getValue("ItemSortType", "Graphdata");
		$g->ItemSortType = $gc->ItemSortSetting == "D" ? $g->ItemSortType : $gc->ItemSortType;
		$g->ItemSortDirection = System_Model_Setting::getValue("ItemSortDirection", "Graphdata");
		$g->ItemSortDirection = $gc->ItemSortSetting == "D" ? $g->ItemSortDirection : $gc->ItemSortDirection;
		$g->ItemAmountColumn = System_Model_Setting::getValue("ItemAmountColumn", "Graphdata");
		$g->ItemAmountColumn = $gc->ItemAmountColumnSetting == "D" ? $g->ItemAmountColumn : $gc->ItemAmountColumn;
		$g->ItemAmountColumn = empty($g->ItemAmountColumn) || $g->ItemAmountColumn <= 1 ? 1 : ($g->ItemAmountColumn >= 4 ? 4 : $g->ItemAmountColumn);
		$g->ItemShowImage = System_Model_Setting::getValue("ItemShowImage", "Graphdata");
		$g->ItemShowImage = $gc->ItemShowImageSetting == "D" ? $g->ItemShowImage : $gc->ItemShowImage;
		$g->ItemShowTitle = System_Model_Setting::getValue("ItemShowTitle", "Graphdata");
		$g->ItemShowTitle = $gc->ItemShowTitleSetting == "D" ? $g->ItemShowTitle : $gc->ItemShowTitle;
		$g->ItemShowIntroText = System_Model_Setting::getValue("ItemShowIntroText", "Graphdata");
		$g->ItemShowIntroText = $gc->ItemShowIntroTextSetting == "D" ? $g->ItemShowIntroText : $gc->ItemShowIntroText;
		
		$sortType = 'Created';
		switch ($g->ItemSortType) {
			case 'created' :
				$sortType = 'Created';
				break;
			case 'title' :
			case 'name' :
				$sortType = 'Title';
				break;
			case 'modified' :
				$sortType = 'Modified';
				break;
		}
		$sortDirection = in_array($g->ItemSortDirection, array('asc', 'desc')) ? $g->ItemSortDirection : 'desc';
		
		// Init
		$pageSize = empty($g->ItemPerPage) ? 10 : $g->ItemPerPage;


		// Search
		$pageIndex = $this->getRequest()->getParam('pageIndex', 1);
		$textstr = $this->getRequest()->getParam('search');
		$id = $filterInt->filter($this->getRequest()->getParam('id'));
		$cid = $filterInt->filter($this->getRequest()->getParam('cid'));
		
		$advancesearch = array();
		$advancesearch['isCat'] = $id;
		if($cid){
			$advancesearch['isCat'] = $cid;
		}

		$model = new Graphdata_Model_Item();
		$items = $model->getList($sortType, $sortDirection, $pageSize, $pageIndex, $textstr, $filter, $advancesearch, $this->lang);
		
		// Set Setting to view
		$this->view->id = $id;
		$this->view->cid = $cid;

		$this->view->setting = $setting;
		$this->view->defaultImageUrl = $this->view->baseUrl("images/other/no-image.jpg");
		$this->view->graphdataSettingItemAmountColumn = $g->ItemAmountColumn;
		$this->view->graphdataSettingItemShowImage = $g->ItemShowImage == 'Y';
		$this->view->graphdataSettingItemShowTitle = $g->ItemShowTitle == 'Y';
		$this->view->graphdataSettingItemShowIntroText = $g->ItemShowIntroText == 'Y';

		// Send Data to view
		$this->view->items = $items;
		if ($g->ItemAmountColumn == 1) {
			$responce["html"] = $this->view->render('category/_arows.phtml');
		} else {
			$responce["html"] = $this->view->render('category/_acards.phtml');
		}
		if (count($items) > 0) {
			$responce["total"] = $items[0]["RECORD"];
			$responce['totalPage'] = ceil($responce["total"] / $pageSize);
		}
		$responce['pageIndex'] = $pageIndex;
		$this->_helper->json($responce);
	}
	
	// Item Detail
	public function detailAction() {
		// Log Access
		System_Model_Log::access ();
		
		$filterInt = new Zend_Filter_Int ();
		$id = $this->getRequest ()->getParam ( "id", "" );
		$iID = $this->getRequest ()->getParam ( "iid", "" );
		$cid = $this->getRequest ()->getParam ( "cid", "" );
		$id = $filterInt->filter ( $id );
		$iID = $filterInt->filter ( $iID );
		
		if (empty ( $id ) || empty ( $iID )) {
			// Show "Not found data"
			$this->renderScript('index/_alertnotfound.phtml');
			return;
		}
		
		// Save View
		$accessFrom = "Web";
		$memberID = null;
		$userID = null;
		Graphdata_Model_Item::saveView($iID, $memberID, $userID, $this->lang, $accessFrom);
		
		// Model
		$model = new Graphdata_Model_Item();
		$dataInfo = $model->getInfo( $iID );
		$data = $model->getDataByLanguage( $iID, $this->lang);
		$graphData = $model->getItemData( $iID );
				
		$categoryModel = new Graphdata_Model_Category();
		$dataDetailCategory = $categoryModel->getDataByLanguage($id, $this->lang);
		
		// Check Data: Can Access
		if (!$dataInfo || $dataInfo->IsEnable != 'Y' || $dataInfo->IsDeleted != 'N') {
			// Show "Not found data"
			$this->renderScript('index/_alertnotfound.phtml');
			return;
		}
		
		if (empty ( $data->Title)) {
			// Show "Not found data in this LANG, อยู่ระหว่างจัดทำข้อมูล"
			$this->renderScript('index/_alertmaintenance.phtml');
			return;
		}
		
		// Intro Image
		$dataIntroImages = array();
		if(!empty($data->IntroImageID)) {
			$dataIntroImages[] = array('file_name'=> $data->IntroImageFileName, 'size'=> $data->IntroImageFileSize, 'type'=> $data->IntroImageFileType, 'hash_name'=> $data->IntroImageHashName);
		}
		
		$Model = new Graphdata_Model_Category();
		$categoryData = $Model->getDataByLanguage($cid, $this->lang);
		
		$parent[0] =  $categoryData->ParentID;
		$parentName[0] = $categoryData->Title;
		$checkParen = 1 ;

		while($checkParen != null){
			$dataParen = $model->getDataByLanguage($parent[$checkParen-1], $this->lang);
			$parent[$checkParen] = $dataParen->ParentID;
			$parentName[$checkParen] = $dataParen->Title;
			if($parent[$checkParen] != null){
				$checkParen++ ;
			}else{
				$checkParen = null ;
			}
		}

		$defaultLanguage = System_Model_Setting::getDefaultLanguage();
		$this->view->defaultLanguage = $defaultLanguage['Code'];

		// Send To View
		$this->view->data = $data;
		$this->view->graphData = $graphData;
		$this->view->dataInfo = $dataInfo;
		$this->view->dataIntroImages = $dataIntroImages;

		$this->view->UserID = $this->auth->UserID;
		$this->view->ProfileImageHashName = $this->auth->ProfileImageHashName;
		$this->view->ControllerName = "category";

		$dataParam = new stdClass();
		$dataParam->id = $id;
		$dataParam->cid = $cid;
		$dataParam->iid = $iID;
		$this->view->dataParam = $dataParam;

		// Set Global
		$menuID = $this->findMenu("/graphdata/category/index/id/" . $id);
		Zend_Registry::set("MenuID", $menuID);
		
		$extraBreadcrumb = array();

		$check = 0 ;
		$num = count($parent);
		$sumparen = $num - 2 ;

		while($sumparen > -1){
			if($sumparen <= 0){
				$extraBreadcrumb[] = array('url' => '/graphdata/category/items/id/' . $id . '/cid/' . $cid, 'name' => $parentName[$sumparen] );
				$sumparen = null ;
			}else{
				$extraBreadcrumb[] = array('url' => '/graphdata/category/items/id/' . $id . '/cid/' . $parent[$sumparen-1], 'name' => $parentName[$sumparen]);
				$sumparen-- ;
			}
		}
		$extraBreadcrumb[] = array('url' => '/graphdata/category/detail/id/' . $id . '/cid/' . $cid . '/iid/' . $iID, 'name' => $data->Title);
		Zend_Registry::set("ExtraBreadcrumb", $extraBreadcrumb);
	}
	
	protected function findMenu($url) {
		$menuID = null;
		$menus = System_Model_Menu::findMenuByUrl($url);
		if (count($menus) > 0) {
			$menuID = $menus[0]["ID"];
		}
		return $menuID;
	}
}
?>