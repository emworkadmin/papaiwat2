<?php 


class Content_PageController extends Zend_Controller_Action
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
		Content_Model_Article::saveView($itemID, $memberID, $userID, $this->lang, $accessFrom);
		
		// Model
		$model = new Content_Model_Article();
		$dataInfo = $model->getInfo( $itemID );
		$data = $model->getDataByLanguage( $itemID, $this->lang);
		$datatag= $model->getAllKeywordByArticle($itemID);
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
		
		// Intro Image
		$dataIntroImages = array();
		if(!empty($data->IntroImageID)) {
			$dataIntroImages[] = array('file_name'=> $data->IntroImageFileName, 'size'=> $data->IntroImageFileSize, 'type'=> $data->IntroImageFileType, 'hash_name'=> $data->IntroImageHashName);
		}
		
		$dataFiles = $model->getFiles($data->ID);
		$dataFileImages = array();
		$dataFileDocuments = array();
		$dataFileMultiMedias = array();
		if (count($dataFiles) > 0) {
			foreach ($dataFiles as $dataFile) {
				if ($dataFile["ArticleFileType"] == "image") {
					$dataFileImages[] = array('ContentArticleFileID'=>$dataFile['ContentArticleFileID'], 'DownloadAmount'=>$dataFile['DownloadAmount'], 'file_name'=> $dataFile['FileName'], 'size'=> $dataFile['FileSize'], 'type'=> $dataFile['FileType'], 'hash_name'=> $dataFile['FileHashName']);
				} elseif ($dataFile["ArticleFileType"] == "document") {
					$dataFileDocuments[] = array('ContentArticleFileID'=>$dataFile['ContentArticleFileID'], 'DownloadAmount'=>$dataFile['DownloadAmount'], 'file_name'=> $dataFile['FileName'], 'size'=> $dataFile['FileSize'], 'type'=> $dataFile['FileType'], 'hash_name'=> $dataFile['FileHashName'],'Extension'=>$dataFile['FileExtension']);
				} elseif ($dataFile["ArticleFileType"] == "multimedia") {
					$dataFileMultiMedias[] = array('ContentArticleFileID'=>$dataFile['ContentArticleFileID'], 'DownloadAmount'=>$dataFile['DownloadAmount'], 'file_name'=> $dataFile['FileName'], 'size'=> $dataFile['FileSize'], 'type'=> $dataFile['FileType'], 'hash_name'=> $dataFile['FileHashName']);
				}
			}
		}
		
		// Other Setup
		$menuName = "ข่าวประชาสัมพันธ์";
		$menuUrl = $this->view->url(array("module" => "content", "controller" => "article", "action" => "list"), null, true);
		$this->view->menuName = $menuName;
		$this->view->menuUrl = $menuUrl;
		$currentUrl = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
		$this->view->currentUrl = $currentUrl;
		
		$siteName = System_Model_Setting::getValue("SiteName", "Site");
		$this->view->currentUrlQrCodeUrl = $this->view->QrcodeCurrentUrl($currentUrl, null, $siteName);

		$cSettings = json_decode($data->Settings);
		$cArticleCanCommentSetting = $cSettings->ArticleCanCommentSetting;
		$cArticleCanComment = $cSettings->ArticleCanComment;
		$cArticlePublicCommentSetting = $cSettings->ArticlePublicCommentSetting;
		$cArticlePublicComment = $cSettings->ArticlePublicComment;

		$dArticleCanComment = System_Model_Setting::getValue("ArticleCanComment", "Content");
		$ArticleCanComment = $cArticleCanCommentSetting == "D" ? $dArticleCanComment : $cArticleCanComment;
		$dArticlePublicComment = System_Model_Setting::getValue("ArticlePublicComment", "Content");
		$ArticlePublicComment = $cArticlePublicCommentSetting == "D" ? $dArticlePublicComment : $cArticlePublicComment;

		if(in_array($data->CanComment,array('Y','N'))){
			$ArticleCanComment = $data->CanComment;
		}

		// Send To View
		$this->view->data = $data;
		$this->view->datatag = $datatag;
		$this->view->dataInfo = $dataInfo;
		$this->view->dataIntroImages = $dataIntroImages;
		$this->view->dataFileImages = $dataFileImages;
		$this->view->dataFileDocuments = $dataFileDocuments;
		$this->view->dataFileMultiMedias = $dataFileMultiMedias;

		$this->view->UserID = $this->auth->UserID;
		$this->view->ProfileImageHashName = $this->auth->ProfileImageHashName;
		$this->view->ControllerName = "page";
		$this->view->ArticleCanComment = $ArticleCanComment;
		$this->view->ArticlePublicComment = $ArticlePublicComment;
		$this->view->ReCaptchaSiteKey = System_Model_Setting::getValue("ReCaptchaSiteKey", "General");
		
		$menuID = $this->findMenu("/content/page/index/id/" . $id);
		Zend_Registry::set("MenuID", $menuID);
		
		// Send To Layout
		$this->view->pageTitle = $data->Title;
		$this->view->pageDescription = $data->IntroText;
		$this->view->pageKeyword = $dataInfo->MetaData;
		if(!empty($data->IntroImageID)) {
			$fileModel = new System_Model_Fileinfo();
			$fileObj = $fileModel->item($data->IntroImageID);
			if (!empty($fileObj->HashName)) {
				$this->view->ogImage = $this->view->url(array("module" => "default", "controller" => "file", "action" => "get", "file" => $fileObj->HashName), null, true);
			}
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