<?php 


class Graphdata_PageController extends Zend_Controller_Action
{
	protected $lang;

	public function init()
	{
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
	
	// Index / Detail : Start Page
	public function indexAction() {
		// Log Access
		System_Model_Log::access ();
		
		$filterInt = new Zend_Filter_Int ();
		$id = $this->getRequest ()->getParam ( "id", "" );
		$itemID = $filterInt->filter ( $id );
		
		if (empty ( $itemID )) {
			// Show "Not found data"
			$this->renderScript('index/_alertnotfound.phtml');
			return;
		}
		
		// Save View
		$accessFrom = "Web";
		$memberID = null;
		$userID = null;
		Graphdata_Model_Item::saveView($itemID, $memberID, $userID, $this->lang, $accessFrom);
		
		// Model
		$model = new Graphdata_Model_Item();
		$dataInfo = $model->getInfo( $itemID );
		$data = $model->getDataByLanguage( $itemID, $this->lang);
		$graphData = $model->getItemData( $itemID );
		
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
		$this->view->ControllerName = "page";

		$menuID = $this->findMenu("/graphdata/page/index/id/" . $id);
		Zend_Registry::set("MenuID", $menuID);
		
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