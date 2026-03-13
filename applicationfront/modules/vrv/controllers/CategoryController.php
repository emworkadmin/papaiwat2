<?php 


class Vrv_CategoryController extends Zend_Controller_Action
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
		$model = new Vrv_Model_Category();
		$dataDetail = $model->getDataByLanguage($id, $this->lang);
				
		// Send Data to view
		$this->view->id = $id;
		$this->view->pageTitle = $dataDetail->Title;	
		$this->view->dataDetail = $dataDetail;	
		$this->view->cssClass = $dataDetail->CssClass;	
		
		$menuID = $this->findMenu("/vrv/category/index/id/" . $id);
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
		$categoryModel = new Vrv_Model_Category();
		$categoryData = $categoryModel->getSettings($cid);
		$categorySetting = json_decode($categoryData->Settings);
		$cCategoryPerPageSetting = empty($categorySetting->CategoryPerPageSetting) ? 'D' : $categorySetting->CategoryPerPageSetting;
		$cCategoryPerPage = $categorySetting->CategoryPerPage;
		$cCategoryAmountColumnSetting = empty($categorySetting->CategoryAmountColumnSetting) ? 'D' : $categorySetting->CategoryAmountColumnSetting;
		$cCategoryAmountColumn = $categorySetting->CategoryAmountColumn;
		$cCategoryShowImageSetting = empty($categorySetting->CategoryShowImageSetting) ? 'D' : $categorySetting->CategoryShowImageSetting;
		$cCategoryShowImage = $categorySetting->CategoryShowImage;
		$cCategoryShowIntroTextSetting = empty($categorySetting->CategoryShowIntroTextSetting) ? 'D' : $categorySetting->CategoryShowIntroTextSetting;
		$cCategoryShowIntroText = $categorySetting->CategoryShowIntroText;
		
		// Setting
		$vrvSettingCategoryAmountColumn = System_Model_Setting::getValue("CategoryAmountColumn", "VRV");
		$vrvSettingCategoryAmountColumn = $cCategoryAmountColumnSetting == "D" ? $vrvSettingCategoryAmountColumn : $cCategoryAmountColumn;
		$vrvSettingCategoryAmountColumn = empty($vrvSettingCategoryAmountColumn) || $vrvSettingCategoryAmountColumn <= 1 ? 1 : ($vrvSettingCategoryAmountColumn >= 4 ? 4 : $vrvSettingCategoryAmountColumn);
		$vrvSettingCategoryPerPage = System_Model_Setting::getValue("CategoryPerPage", "VRV");
		$vrvSettingCategoryPerPage = $cCategoryPerPageSetting == "D" ? $vrvSettingCategoryPerPage : $cCategoryPerPage;
		$vrvSettingCategoryShowImage = System_Model_Setting::getValue("CategoryShowImage", "VRV");
		$vrvSettingCategoryShowImage = $cCategoryShowImageSetting == "D" ? $vrvSettingCategoryShowImage : $cCategoryShowImage;
		$vrvSettingCategoryShowIntroText = System_Model_Setting::getValue("CategoryShowIntroText", "VRV");
		$vrvSettingCategoryShowIntroText = $cCategoryShowIntroTextSetting == "D" ? $calendarSettingCategoryShowIntroText : $cCategoryShowIntroText;
		
		$sortType = 'Ordering';
		$sortDirection = 'asc';
				
		// Init
		$pageSize = empty($vrvSettingCategoryPerPage) ? 10 : $vrvSettingCategoryPerPage;
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
		
		$model = new Vrv_Model_Category();
		$items = $model->getList($sortType, $sortDirection, $pageSize, $pageIndex, $textstr, $filter, $advancesearch, $this->lang);
		
		// Set Setting to view
		$this->view->defaultImageUrl = $this->view->baseUrl("images/other/no-image.jpg");
		$this->view->vrvSettingCategoryAmountColumn = $vrvSettingCategoryAmountColumn;
		$this->view->vrvSettingCategoryShowImage = $vrvSettingCategoryShowImage == 'Y';
		$this->view->vrvSettingCategoryShowIntroText = $vrvSettingCategoryShowIntroText == 'Y';
		
		// Send Data to view
		$this->view->id = $id;
		$this->view->cid = $cid;
		$this->view->items = $items;
		if ($vrvSettingCategoryAmountColumn == 1) {
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
		$model = new Vrv_Model_Category();
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
		$this->view->cssClass = $mainCategoryData->CssClass;
		$this->view->subCssClass = $categoryData->CssClass;
		$this->view->pageTitle = $categoryData->Title;
		$this->view->dataDetail = $categoryData;
		
		// Set Global
		$menuID = $this->findMenu("/vrv/category/index/id/" . $id);
		Zend_Registry::set("MenuID", $menuID);
		
		$extraBreadcrumb = array();

		$check = 0 ;
		$num = count($parent);
		$sumparen = $num - 2 ;

		while($sumparen > -1){
			$extraBreadcrumb[] = array('url' => '/vrv/category/items/id/' . $id . '/cid/' . $parent[$sumparen-1], 'name' => $parentName[$sumparen]);
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

		$categoryModel = new Vrv_Model_Category();
		$categoryData = $categoryModel->getSettings($cid);
		$categorySetting = json_decode($categoryData->Settings);
		$cItemAmountColumnSetting = empty($categorySetting->ItemAmountColumnSetting) ? 'D' : $categorySetting->ItemAmountColumnSetting;
		$cItemAmountColumn = $categorySetting->ItemAmountColumn;
		$cItemPerPageSetting = empty($categorySetting->ItemPerPageSetting) ? 'D' : $categorySetting->ItemPerPageSetting;
		$cItemPerPage = $categorySetting->ItemPerPage;
		$cItemShowAmountSetting = empty($categorySetting->ItemShowAmountSetting) ? 'D' : $categorySetting->ItemShowAmountSetting;
		$cItemShowAmount = $categorySetting->ItemShowAmount;
		$cItemShowImageSetting = empty($categorySetting->ItemShowImageSetting) ? 'D' : $categorySetting->ItemShowImageSetting;
		$cItemShowImage = $categorySetting->ItemShowImage;
		$cItemShowIntroTextSetting = empty($categorySetting->ItemShowIntroTextSetting) ? 'D' : $categorySetting->ItemShowIntroTextSetting;
		$cItemShowIntroText = $categorySetting->ItemShowIntroText;
		$cItemSortSetting = empty($categorySetting->ItemSortSetting) ? 'D' : $categorySetting->ItemSortSetting;
		$cItemSortType = $categorySetting->ItemSortType;
		$cItemSortDirection = $categorySetting->ItemSortDirection;

		// Setting
		$vrvSettingItemAmountColumn = System_Model_Setting::getValue("ItemAmountColumn", "VRV");
		$vrvSettingItemAmountColumn = $cItemAmountColumnSetting == "D" ? $vrvSettingItemAmountColumn : $cItemAmountColumn;
		$vrvSettingItemAmountColumn = empty($vrvSettingItemAmountColumn) || $vrvSettingItemAmountColumn <= 1 ? 1 : ($vrvSettingItemAmountColumn >= 4 ? 4 : $vrvSettingItemAmountColumn);
		$vrvSettingItemPerPage = System_Model_Setting::getValue("ItemPerPage", "VRV");
		$vrvSettingItemPerPage = $cItemPerPageSetting == "D" ? $vrvSettingItemPerPage : $cItemPerPage;
		$vrvSettingItemShowAmount = System_Model_Setting::getValue("ItemShowAmount", "VRV");
		$vrvSettingItemShowAmount = $cItemShowAmountSetting == "D" ? $vrvSettingItemShowAmount : $cItemShowAmount;
		$vrvSettingItemShowImage = System_Model_Setting::getValue("ItemShowImage", "VRV");
		$vrvSettingItemShowImage = $cItemShowImageSetting == "D" ? $vrvSettingItemShowImage : $cItemShowImage;
		$vrvSettingItemShowIntroText = System_Model_Setting::getValue("ItemShowIntroText", "VRV");
		$vrvSettingItemShowIntroText = $cItemShowIntroTextSetting == "D" ? $vrvSettingItemShowIntroText : $cItemShowIntroText;
		$vrvSettingItemSortType = System_Model_Setting::getValue("ItemSortType", "VRV");
		$vrvSettingItemSortType = $cItemSortSetting == "D" ? $vrvSettingItemSortType : $cItemSortType;
		$vrvSettingItemSortDirection = System_Model_Setting::getValue("ItemSortDirection", "VRV");
		$vrvSettingItemSortDirection = $cItemSortSetting == "D" ? $vrvSettingItemSortDirection : $cItemSortDirection;

		$sortType = 'Created';
		switch ($vrvSettingItemSortType) {
			case 'created' :
				$sortType = 'Created';
				break;
			case 'publishdate' :
				$sortType = 'PublishDate';
				break;
			case 'title' :
			case 'name' :
				$sortType = 'Title';
				break;
			case 'modified' :
				$sortType = 'Modified';
				break;
		}
		$sortDirection = in_array($vrvSettingItemSortDirection, array('asc', 'desc')) ? $vrvSettingItemSortDirection : 'desc';
		
		// Init
		$pageSize = empty($vrvSettingItemPerPage) ? 10 : $vrvSettingItemPerPage;


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

		$model = new Vrv_Model_Item();
		$items = $model->getList($sortType, $sortDirection, $pageSize, $pageIndex, $textstr, $filter, $advancesearch, $this->lang);		

		// Set Setting to view
		$this->view->id = $id;
		$this->view->cid = $cid;

		$this->view->setting = $setting;
		$this->view->defaultImageUrl = $this->view->baseUrl("images/other/no-image.jpg");
		$this->view->vrvSettingItemAmountColumn = $vrvSettingItemAmountColumn;
		$this->view->vrvSettingItemShowImage = $vrvSettingItemShowImage == 'Y';
		$this->view->vrvSettingItemShowIntroText = $vrvSettingItemShowIntroText == 'Y';

		// Send Data to view
		$this->view->items = $items;
		if ($vrvSettingItemAmountColumn == 1) {
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
		Vrv_Model_Item::saveView($iID, $memberID, $userID, $this->lang, $accessFrom);
		
		// Model
		$model = new Vrv_Model_Item();
		$dataInfo = $model->getInfo( $iID );

		// Check Data: Can Access
		if (!$dataInfo || $dataInfo->IsEnable != 'Y' || $dataInfo->IsDeleted != 'N') {
			// Show "Not found data"
			$this->renderScript('index/_alertnotfound.phtml');
			return;
		}
		
		// if (empty ( $data->Title)) {
		// 	// Show "Not found data in this LANG, อยู่ระหว่างจัดทำข้อมูล"
		// 	$this->renderScript('index/_alertmaintenance.phtml');
		// 	return;
		// }

		$config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/application.ini', 'init');
        $this->view->appSystemName = empty(System_Model_Setting::getValue("SiteName", "Site")) ? $config->app->name : System_Model_Setting::getValue("SiteName", "Site");

		// Get Favicon
		$objTemplate = $model->getDefaultTemplate();
		if($objTemplate->FaviconImageHashName){
			$appFaviconPath = $this->view->url(array('module' => 'default', 'controller' => 'file', 'action' => 'get', 'file' => $objTemplate->FaviconImageHashName), null, true);
		}else{
			$faviconID = System_Model_Setting::getValue("Favicon", "Site");
			if($faviconID){
				$fileModel = new System_Model_Fileinfo();
				$fileData = $fileModel->item($faviconID);
				if ($fileData->ID != null) {
					$appFaviconPath = $this->view->url(array('module' => 'default', 'controller' => 'file', 'action' => 'get', 'file' => $fileData->HashName), null, true);
				}
			}else{
				$appFaviconPath = $this->view->baseUrl($config->app->faviconpath);
			}
		}
		$this->view->appFaviconPath = $appFaviconPath;
		
		if($dataInfo->VirtualRealityHashName){
            $folder = str_replace('.zip','',$dataInfo->VirtualRealityHashName);
            $path = '/vrv/'.$folder.'/index.html';
            if(file_exists(APPLICATION_ROOT.'/public'.$path)){
                $this->view->path = $path;
                $this->_helper->layout()->disableLayout();
            }else{
				$this->renderScript('index/_alertnotfound.phtml');
				return;
            }
        }else{
            $this->renderScript('index/_alertnotfound.phtml');
            return;
        }
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