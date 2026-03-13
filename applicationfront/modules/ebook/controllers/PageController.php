<?php 


class Ebook_PageController extends Zend_Controller_Action
{

    //default Language 
	protected $lang;
	protected $auth;

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
	
	// Index / Detail : Start Page
	public function indexAction() {
		// Log Access
		System_Model_Log::access ();
		
		$filterInt = new Zend_Filter_Int ();
		$id = $this->getRequest ()->getParam ( "id", "" );
		$itemID = $filterInt->filter ( $id );
			
		// Save View
		$accessFrom = "Web";
		$memberID = null;
		$userID = null;
		Ebook_Model_Book::saveView($itemID, $memberID, $userID, $this->lang, $accessFrom);
		
		// Model
        $model = new Ebook_Model_Book();
		$dataInfo = $model->getInfo( $itemID );
		$data = $model->getDataByLanguage( $itemID, $this->lang);

		if($data->SizeType == 'C'){
			$this->view->Height = $data->Height;
			$this->view->Width = $data->Width; 
		}

		$mainSiteUrl = System_Model_Setting::getValue("MainSiteUrl", "General");
		
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
		
        $dataFiles = $model->getFiles($data->ID);
		$dataFileImages = array();
        
		if (count($dataFiles) > 0) {
			foreach ($dataFiles as $dataFile) {
				$dataFileImages[] = array('file_name'=> $dataFile['FileName'], 'size'=> $dataFile['FileSize'], 'type'=> $dataFile['FileType'], 'hash_name'=> $dataFile['FileHashName']);
            }
		}
		
		// Other Setup
		$this->view->SizeType = $data->SizeType;
		$menuName = "ข่าวประชาสัมพันธ์";
		$menuUrl = $this->view->url(array("module" => "ebook", "controller" => "book", "action" => "list"), null, true);
		$this->view->menuName = $menuName;
		$this->view->menuUrl = $menuUrl;
		$currentUrl = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
		$this->view->currentUrl = $currentUrl;
		
		$siteName = System_Model_Setting::getValue("SiteName", "Site");
		$this->view->currentUrlQrCodeUrl = $this->view->QrcodeCurrentUrl($currentUrl, null, $siteName);

		$modelCat = new Ebook_Model_Category();
		$settingData = $modelCat->getSettings($data->EbookCategoryID);

		$cSettings = json_decode($settingData->Settings);
		$cEbookCanCommentSetting = $cSettings->EbookCanCommentSetting;
		$cEbookCanComment = $cSettings->EbookCanComment;
		$cEbookPublicCommentSetting = $cSettings->EbookPublicCommentSetting;
		$cEbookPublicComment = $cSettings->EbookPublicComment;

		$dEbookCanComment = System_Model_Setting::getValue("EbookCanComment", "Ebook");
		$EbookCanComment = $cEbookCanCommentSetting == "D" ? $dEbookCanComment : $cEbookCanComment;
		$dEbookPublicComment = System_Model_Setting::getValue("EbookPublicComment", "Ebook");
		$EbookPublicComment = $cEbookPublicCommentSetting == "D" ? $dEbookPublicComment : $cEbookPublicComment;

		if(in_array($data->CanComment,array('Y','N'))){
			$CanComment = $data->CanComment;
		}

		$this->view->CanComment = $CanComment;
		$this->view->EbookPublicComment = $EbookPublicComment;
		$this->view->UserID = $this->auth->UserID;
		$this->view->ProfileImageHashName = $this->auth->ProfileImageHashName;
		$this->view->ReCaptchaSiteKey = System_Model_Setting::getValue("ReCaptchaSiteKey", "General");
		
		// Send To View
		$this->view->dataInfo = $dataInfo;
		$this->view->dataFileImages = $dataFileImages;
		$this->view->data = $data;

		$menuID = $this->findMenu("/ebook/page/index/id/" . $id);
		Zend_Registry::set("MenuID", $menuID);
		
		// Send To Layout
		// $this->view->pageDescription = $dataInfo->MetaData;
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