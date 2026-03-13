<?php 


class Graphdata_ItemController extends Zend_Controller_Action
{
	protected $auth;
	protected $lang;
	protected $mainCategoryID = '1';

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
		
		$model = new Graphdata_Model_Category();
		$dataDetail = $model->getDataByLanguage($id, $this->lang);
		
		// Send Data to view
		$this->view->id = $id;
		$this->view->pageTitle = $dataDetail->Title;
		
		$menuID = $this->findMenu("/graphdata/item/index/id/" . $id);
		Zend_Registry::set("MenuID", $menuID);
	}
	
	public function getlistAction() {
		
		$filterInt = new Zend_Filter_Int();
		
		$responce = array();
		$responce['total'] = 0;
		$responce['totalPage'] = 0;
		$responce['pageIndex'] = 0;
		$responce['html'] = '';
		
		// Category
		$id = $filterInt->filter($this->getRequest()->getParam('id'));

		$categoryModel = new Graphdata_Model_Category();
		$categoryData = $categoryModel->getSettings($id);
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
		$pageIndex = $this->getRequest()->getParam('pageIndex', 1);
		
		// Search
		$textstr = $this->getRequest()->getParam('search');
		$id = $filterInt->filter($this->getRequest()->getParam('id'));
		$advancesearch = array();
		$advancesearch["isCat"] = $id;
		
		$model = new Graphdata_Model_Item();
		$items = $model->getList($sortType, $sortDirection, $pageSize, $pageIndex, $textstr, $filter, $advancesearch, $this->lang);
		
		// Set Setting to view
		$this->view->defaultImageUrl = $this->view->baseUrl("images/other/no-image.jpg");
		$this->view->graphdataSettingItemAmountColumn = $g->ItemAmountColumn;
		$this->view->graphdataSettingItemShowImage = $g->ItemShowImage == 'Y';
		$this->view->graphdataSettingItemShowTitle = $g->ItemShowTitle == 'Y';
		$this->view->graphdataSettingItemShowIntroText = $g->ItemShowIntroText == 'Y';
		
		// Send Data to view
		$this->view->id = $id;
		$this->view->items = $items;
		if ($g->ItemAmountColumn == 1) {
			$responce["html"] = $this->view->render('index/_rows.phtml');
		} else {
			$responce["html"] = $this->view->render('index/_cards.phtml');
		}
		if (count($items) > 0) {
			$responce["total"] = $items[0]["RECORD"];
			$responce['totalPage'] = ceil($responce["total"] / $pageSize);
		}
		$responce['pageIndex'] = $pageIndex;
		$this->_helper->json($responce);
	}
	
	// detail
	public function detailAction() {

		// Log Access
		System_Model_Log::access ();

		$filterInt = new Zend_Filter_Int ();
		$id = $this->getRequest ()->getParam ( "id", "" );
		$iID = $this->getRequest ()->getParam ( "iid", "" );
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
		
		$defaultLanguage = System_Model_Setting::getDefaultLanguage();
		$this->view->defaultLanguage = $defaultLanguage['Code'];
		
		// Send To View
		$this->view->data = $data;
		$this->view->graphData = $graphData;
		$this->view->dataInfo = $dataInfo;
		$this->view->dataIntroImages = $dataIntroImages;

		$this->view->UserID = $this->auth->UserID;
		$this->view->ProfileImageHashName = $this->auth->ProfileImageHashName;
		$this->view->ControllerName = "item";
		
		// Set Global
		$menuID = $this->findMenu("/graphdata/item/index/id/" . $id);
		Zend_Registry::set("MenuID", $menuID);
		
		$extraBreadcrumb = array();
		$extraBreadcrumb[] = array('url' => '/graphdata/item/detail/id/' . $id . '/iid/' . $iID, 'name' => $data->Title);
		Zend_Registry::set("ExtraBreadcrumb", $extraBreadcrumb);
		
		// Send To Layout
		$this->view->pageDescription = $dataInfo->MetaData;
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