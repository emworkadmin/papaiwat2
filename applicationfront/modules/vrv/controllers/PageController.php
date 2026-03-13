<?php 


class Vrv_PageController extends Zend_Controller_Action
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
		Vrv_Model_Item::saveView($itemID, $memberID, $userID, $this->lang, $accessFrom);
		
		// Model
		$model = new Vrv_Model_Item();
		$dataInfo = $model->getInfo( $itemID );

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