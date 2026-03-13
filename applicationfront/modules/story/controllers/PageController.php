<?php 


class Story_PageController extends Zend_Controller_Action
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
	
	// detail
	public function indexAction() {

		// Log Access
		System_Model_Log::access();

		$filterInt = new Zend_Filter_Int();
		
		$id = $this->getRequest()->getParam("id", "");
						
		if (empty($id)) {
			$this->_helper->redirector('index', 'index', 'default');
			return;
		}
		
		// $model = new Story_Model_Category();
		// $dataInfo = $model->getInfoStory($id,$this->lang);

		// $this->view->ViewAmount = $dataInfo->ViewAmount ;
		// $this->view->Created = $dataInfo->Created ;

		// Save View
		$accessFrom = "Web";
		$memberID = null;
		$userID = null;
		Story_Model_Article::saveView($iid, $memberID, $userID, $this->lang, $accessFrom);

		// Other Setup
		$menuUrl = $this->view->url(array("module" => "story", "controller" => "page", "action" => "index", "itemid" => $id), null, true);
		$this->view->menuName = $menuName;
		$this->view->menuUrl = $menuUrl;
		$currentUrl = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
		$this->view->currentUrl = $currentUrl;
		
		$siteName = System_Model_Setting::getValue("SiteName", "Site");
		$this->view->currentUrlQrCodeUrl = $this->view->QrcodeCurrentUrl($currentUrl, null, $siteName);
		
		$model = new Story_Model_Category();
		$dataInfo = $model->getInfoStory($id,$this->lang);

		// Check Data: Can Access
		if (!$dataInfo || $dataInfo->IsEnable != 'Y' || $dataInfo->IsDeleted != 'N') {
			// Show "Not found data"
			$this->renderScript('index/_alertnotfound.phtml');
			return;
		}
		
		if (empty($dataInfo->Title)) {
			// Show "Not found data in this LANG, อยู่ระหว่างจัดทำข้อมูล"
			$this->renderScript('index/_alertmaintenance.phtml');
			return;
		}
		
		$this->view->dataInfoStory = $dataInfo;
		$this->view->ViewAmount = $dataInfo->ViewAmount ;
		$this->view->Created = $dataInfo->Created ;
		
		$dataDetail = $model->getInfoDataStory($dataInfo->ID,$this->lang);
		$dataCover = $model->getCoverListByLanguage($dataInfo->ID,$this->lang);

		$cSettings = json_decode($dataInfo->Settings);
		$cArticleCanCommentSetting = $cSettings->ArticleCanCommentSetting;
		$cArticleCanComment = $cSettings->ArticleCanComment;
		$cArticlePublicCommentSetting = $cSettings->ArticlePublicCommentSetting;
		$cArticlePublicComment = $cSettings->ArticlePublicComment;

		$dArticleCanComment = System_Model_Setting::getValue("ArticleCanComment", "Story");
		$ArticleCanComment = $cArticleCanCommentSetting == "D" ? $dArticleCanComment : $cArticleCanComment;
		$dArticlePublicComment = System_Model_Setting::getValue("ArticlePublicComment", "Story");
		$ArticlePublicComment = $cArticlePublicCommentSetting == "D" ? $dArticlePublicComment : $cArticlePublicComment;

		if(in_array($dataInfo->CanComment,array('Y','N'))){
			$ArticleCanComment = $dataInfo->CanComment;
		}
		
		// File Document
		$dataFiles = $model->getFiles($dataInfo->ID);
		$dataFileImages = array();
		$dataFileDocuments = array();
		$dataFileMultiMedias = array();
		if (count($dataFiles) > 0) {
			foreach ($dataFiles as $dataFile) {
				if ($dataFile["ArticleFileType"] == "image") {
					$dataFileImages[] = array('file_name'=> $dataFile['FileName'], 'size'=> $dataFile['FileSize'], 'type'=> $dataFile['FileType'], 'hash_name'=> $dataFile['FileHashName']);
				} elseif ($dataFile["ArticleFileType"] == "document") {
					$dataFileDocuments[] = array('file_name'=> $dataFile['FileName'], 'size'=> $dataFile['FileSize'], 'type'=> $dataFile['FileType'], 'hash_name'=> $dataFile['FileHashName']);
				} elseif ($dataFile["ArticleFileType"] == "multimedia") {
					$dataFileMultiMedias[] = array('file_name'=> $dataFile['FileName'], 'size'=> $dataFile['FileSize'], 'type'=> $dataFile['FileType'], 'hash_name'=> $dataFile['FileHashName']);
				}
			}
		}
		
		$this->view->dataInfo = $dataInfo;
		$this->view->dataDetail = $dataDetail;
		$this->view->dataCover = $dataCover;

		$this->view->UserID = $this->auth->UserID;
		$this->view->ProfileImageHashName = $this->auth->ProfileImageHashName;
		$this->view->ControllerName = "page";
		$this->view->ArticleCanComment = $ArticleCanComment;
		$this->view->ArticlePublicComment = $ArticlePublicComment;
		$this->view->dataFileImages = $dataFileImages;
		$this->view->dataFileDocuments = $dataFileDocuments;
		$this->view->dataFileMultiMedias = $dataFileMultiMedias;
		$this->view->ReCaptchaSiteKey = System_Model_Setting::getValue("ReCaptchaSiteKey", "General");
		
		// Send Data to Template
		$this->view->bodyClass = "story-body-detail";
		$this->view->pageTitle = $dataInfo->Title;
		$this->view->pageDescription = $dataInfo->IntroText;
		$this->view->pageKeyword = '';
		if(!empty($dataInfo->FileHashName)) {
			$this->view->ogImage = $this->view->url(array("module" => "default", "controller" => "file", "action" => "get", "file" => $dataInfo->FileHashName), null, true);
		}

		$menuID = $this->findMenu("/story/page/index/id/" . $id);
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