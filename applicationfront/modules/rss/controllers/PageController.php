<?php 


class Rss_PageController extends Zend_Controller_Action
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
		// Rss_Model_Article::saveView($itemID, $memberID, $userID, $this->lang, $accessFrom);

		// Model
		$model = new Rss_Model_Page();
		$data = $model->getCategoryByLanguage($itemID, $this->lang);

		// Setting
		$dSetting = new stdClass();
		$dSetting->ItemPerPage = System_Model_Setting::getValue("ItemPerPage", "Rss");
		$dSetting->ItemSortType = System_Model_Setting::getValue("ItemSortType", "Rss");
		$dSetting->ItemSortDirection = System_Model_Setting::getValue("ItemSortDirection", "Rss");
		$dSetting->ItemHtmlClass = System_Model_Setting::getValue("ItemHtmlClass", "Rss");
		$dSetting->ItemCustomCSS = System_Model_Setting::getValue("ItemCustomCSS", "Rss");

		$cSetting = json_decode($data->Settings);
		$setting = new stdClass();
		$setting->ItemPerPage = $cSetting->ItemPerPageSetting == 'C' ? $cSetting->ItemPerPage : $dSetting->ItemPerPage;
		$setting->ItemSortType = $cSetting->ItemSortSetting == 'C' ? $cSetting->ItemSortType : $dSetting->ItemSortType;
		$setting->ItemSortDirection = $cSetting->ItemSortSetting == 'C' ? $cSetting->ItemSortDirection : $dSetting->ItemSortDirection;
		$setting->ItemHtmlClass = $cSetting->ItemHtmlClassSetting == 'C' ? $cSetting->ItemHtmlClass : $dSetting->ItemHtmlClass;
		$setting->ItemCustomCSS = $cSetting->ItemCSSSetting == 'C' ? $cSetting->ItemCustomCSS : $dSetting->ItemCustomCSS;

		$mainSiteUrl = System_Model_Setting::getValue("MainSiteUrl", "General");
		
		// Check Data: Can Access
		if (!$data || $data->IsEnable != 'Y' || $data->IsDeleted != 'N') {
			// Show "Not found data"
			$this->renderScript('index/_alertnotfound.phtml');
			return;
		}
		
		// Check Data : Rss in This Lang
		if (empty($data->Title)) {
			// Show "Not found data in this LANG, อยู่ระหว่างจัดทำข้อมูล"
			$this->renderScript('index/_alertmaintenance.phtml');
			return;
		}
		
		$dataItem = $model->getItemListByCategory($itemID, $this->lang, $setting);

		// Send To View
		$this->view->data = $data;
		$this->view->dataItem = $dataItem;
		$this->view->setting = $setting;
		
		$menuID = $this->findMenu("/rss/page/index/id/" . $id);
		Zend_Registry::set("MenuID", $menuID);
		
		// Send To Layout
		$this->view->pageDescription = $dataInfo->MetaData;
		
	}

	public function contentAction() {
		// Log Access
		System_Model_Log::access ();

		$filterInt = new Zend_Filter_Int ();
		$_id = $this->getRequest ()->getParam ( "id", "" );
		$_cid = $this->getRequest ()->getParam ( "cid", "" );
		$id = $filterInt->filter ( $_id );
		$cid = $filterInt->filter ( $_cid );
		
		if (empty($id) || empty($cid)) {
			// Show "Not found data"
			$this->renderScript('index/_alertnotfound.phtml');
			return;
		}

		// Save View
		$accessFrom = "Web";
		$memberID = null;
		$userID = null;
		// Rss_Model_Article::saveView($_id, $memberID, $userID, $this->lang, $accessFrom);

		// Model
		$model = new Rss_Model_Page();

		// Get RSS Item
		$dataItem = $model->getItemSetting($_id, $this->lang);

		// Check Data: Can Access
		if (!$dataItem || $dataItem->IsEnable != 'Y' || $dataItem->IsDeleted != 'N') {
			// Show "Not found data"
			$this->renderScript('index/_alertnotfound.phtml');
			return;
		}

		// Setting
		$cSetting = json_decode($dataItem->Settings);
		$setting = new stdClass();
		$setting->Limit = $cSetting->Limit;
		$setting->Sidx = $cSetting->Sidx;
		$setting->Sord = $cSetting->Sord;

		// Get Data
		$data = $model->getContent($_cid, $this->lang);

		// Check Data: Can Access
		if (!$data || $data->IsEnable != 'Y' || $data->IsDeleted != 'N') {
			// Show "Not found data"
			$this->renderScript('index/_alertnotfound.phtml');
			return;
		}
		
		// Check Data : Rss in This Lang
		if (empty($data->Title)) {
			// Show "Not found data in this LANG, อยู่ระหว่างจัดทำข้อมูล"
			$this->renderScript('index/_alertmaintenance.phtml');
			return;
		}

		$SiteUrl = System_Model_Setting::getValue("SiteUrl", "General");

		$dataItemList = $model->getContentList($_cid, $this->lang, $setting);

		$entries = array();
		$itunes = array();
		if(count($dataItemList)>0){
			foreach($dataItemList as $k=>$v){
				$date = DateTime::createFromFormat('Y-m-d H:i:s', $v['PublishDate']);
				$entry = array(
						'title' => $v['Title'],
						'description' => empty($v['Detail']) ? '-' : $v['Detail'],
						'link' => $SiteUrl . $this->view->url(array('module' => 'content', 'controller' => 'category', 'action' => 'detail', 'id' => $data->ID, 'iid' => $v['ID']), null, true),
						'guid' => uniqid() . $v['ID'],
						'lastUpdate' => $date->getTimestamp()
				);
				if (!empty($v['IntroImageHashName'])) {
					$entry['enclosure'] =  array(array(
							'url' => $SiteUrl . $this->view->url(array('module' => 'default', 'controller' => 'file', 'action' => 'get', 'file' => $v['IntroImageHashName']), null, true),
							'type' => $v['IntroImageFileType'],
							'length' => null
					));
				}
				$entries[] = $entry;
			}
		}

		$feedData = array(
			'title' => $data->Title,
			'description' => $data->IntroText,
			'link' => $SiteUrl . $this->view->url(array('module' => 'content', 'controller' => 'category', 'action' => 'index', 'id' => $data->ID), null, true),
			'charset' => 'utf8',
			'entries' => $entries
		);


		// create our feed object and import the data
		$feed = Zend_Feed::importArray ( $feedData, 'rss' );
			
		// set the Content Type of the document
		header ( 'Content-type: text/xml' );
			
		// echo the contents of the RSS xml document
		echo $feed->send();
		exit;
	}

	public function galleryAction() {
		// Log Access
		System_Model_Log::access ();

		$filterInt = new Zend_Filter_Int ();
		$_id = $this->getRequest ()->getParam ( "id", "" );
		$_cid = $this->getRequest ()->getParam ( "cid", "" );
		$id = $filterInt->filter ( $_id );
		$cid = $filterInt->filter ( $_cid );
		
		if (empty($id) || empty($cid)) {
			// Show "Not found data"
			$this->renderScript('index/_alertnotfound.phtml');
			return;
		}

		// Save View
		$accessFrom = "Web";
		$memberID = null;
		$userID = null;
		// Rss_Model_Article::saveView($itemID, $memberID, $userID, $this->lang, $accessFrom);

		// Model
		$model = new Rss_Model_Page();

		// Get RSS Item
		$dataItem = $model->getItemSetting($_id, $this->lang);

		// Check Data: Can Access
		if (!$dataItem || $dataItem->IsEnable != 'Y' || $dataItem->IsDeleted != 'N') {
			// Show "Not found data"
			$this->renderScript('index/_alertnotfound.phtml');
			return;
		}

		// Setting
		$cSetting = json_decode($dataItem->Settings);
		$setting = new stdClass();
		$setting->Limit = $cSetting->Limit;
		$setting->Sidx = $cSetting->Sidx;
		$setting->Sord = $cSetting->Sord;

		// Get Data
		$data = $model->getGallery($_cid, $this->lang);

		// Check Data: Can Access
		if (!$data || $data->IsEnable != 'Y' || $data->IsDeleted != 'N') {
			// Show "Not found data"
			$this->renderScript('index/_alertnotfound.phtml');
			return;
		}
		
		// Check Data : Rss in This Lang
		if (empty($data->Title)) {
			// Show "Not found data in this LANG, อยู่ระหว่างจัดทำข้อมูล"
			$this->renderScript('index/_alertmaintenance.phtml');
			return;
		}

		$SiteUrl = System_Model_Setting::getValue("SiteUrl", "General");

		$dataItemList = $model->getGalleryList($_cid, $this->lang, $setting);

		$entries = array();
		$itunes = array();
		if(count($dataItemList)>0){
			foreach($dataItemList as $k=>$v){
				$date = DateTime::createFromFormat('Y-m-d H:i:s', $v['PublishDate']);
				$entry = array(
					'title' => $v['Title'],
					'description' => empty($v['Detail']) ? '-' : $v['Detail'],
					'link' => $SiteUrl . $this->view->url(array('module' => 'gallery', 'controller' => 'category', 'action' => 'detail', 'id' => $data->ID, 'iid' => $v['ID']), null, true),
					'guid' => uniqid() . $v['ID'],
					'lastUpdate' => $date->getTimestamp()
				);
				if (!empty($v['IntroImageHashName'])) {
					$entry['enclosure'] = array(array(
							'url' => $SiteUrl . $this->view->url(array('module' => 'default', 'controller' => 'file', 'action' => 'get', 'file' => $v['IntroImageHashName']), null, true),
							'type' => $v['IntroImageFileType'],
							'length' => null
					));
				}
				$entries[] = $entry;
			}
		}

		$feedData = array(
			'title' => $data->Title,
			'description' => $data->IntroText,
			'link' => $SiteUrl . $this->view->url(array('module' => 'gallery', 'controller' => 'category', 'action' => 'index', 'id' => $data->ID), null, true),
			'charset' => 'utf8',
			'entries' => $entries
		);


		// create our feed object and import the data
		$feed = Zend_Feed::importArray ( $feedData, 'rss' );
			
		// set the Content Type of the document
		header ( 'Content-type: text/xml' );
			
		// echo the contents of the RSS xml document
		echo $feed->send();
		exit;
	}

	public function calendarAction() {
		// Log Access
		System_Model_Log::access ();

		$filterInt = new Zend_Filter_Int ();
		$_id = $this->getRequest ()->getParam ( "id", "" );
		$_cid = $this->getRequest ()->getParam ( "cid", "" );
		$id = $filterInt->filter ( $_id );
		$cid = $filterInt->filter ( $_cid );
		
		if (empty($id) || empty($cid)) {
			// Show "Not found data"
			$this->renderScript('index/_alertnotfound.phtml');
			return;
		}

		// Save View
		$accessFrom = "Web";
		$memberID = null;
		$userID = null;
		// Rss_Model_Article::saveView($itemID, $memberID, $userID, $this->lang, $accessFrom);

		// Model
		$model = new Rss_Model_Page();

		// Get RSS Item
		$dataItem = $model->getItemSetting($_id, $this->lang);

		// Check Data: Can Access
		if (!$dataItem || $dataItem->IsEnable != 'Y' || $dataItem->IsDeleted != 'N') {
			// Show "Not found data"
			$this->renderScript('index/_alertnotfound.phtml');
			return;
		}

		// Setting
		$cSetting = json_decode($dataItem->Settings);
		$setting = new stdClass();
		$setting->Limit = $cSetting->Limit;
		$setting->Sidx = $cSetting->Sidx;
		$setting->Sord = $cSetting->Sord;

		// Get Data
		$data = $model->getCalendar($_cid, $this->lang);

		// Check Data: Can Access
		if (!$data || $data->IsEnable != 'Y' || $data->IsDeleted != 'N') {
			// Show "Not found data"
			$this->renderScript('index/_alertnotfound.phtml');
			return;
		}
		
		// Check Data : Rss in This Lang
		if (empty($data->Title)) {
			// Show "Not found data in this LANG, อยู่ระหว่างจัดทำข้อมูล"
			$this->renderScript('index/_alertmaintenance.phtml');
			return;
		}

		$SiteUrl = System_Model_Setting::getValue("SiteUrl", "General");

		$dataItemList = $model->getCalendarList($_cid, $this->lang, $setting);

		$entries = array();
		$itunes = array();
		if(count($dataItemList)>0){
			foreach($dataItemList as $k=>$v){
				$date = DateTime::createFromFormat('Y-m-d H:i:s', $v['PublishDate']);
				$entry = array(
					'title' => $v['Title'],
					'description' => empty($v['Detail']) ? '-' : $v['Detail'],
					'link' => $SiteUrl . $this->view->url(array('module' => 'calendar', 'controller' => 'category', 'action' => 'detail', 'id' => $data->ID, 'iid' => $v['ID']), null, true),
					'guid' => uniqid() . $v['ID'],
					'lastUpdate' => $date->getTimestamp()
				);
				if (!empty($v['IntroImageHashName'])) {
					$entry['enclosure'] = array(array(
							'url' => $SiteUrl . $this->view->url(array('module' => 'default', 'controller' => 'file', 'action' => 'get', 'file' => $v['IntroImageHashName']), null, true),
							'type' => $v['IntroImageFileType'],
							'length' => null
					));
				}
				$entries[] = $entry;
			}
		}

		$feedData = array(
			'title' => $data->Title,
			'description' => $data->IntroText,
			'link' => $SiteUrl . $this->view->url(array('module' => 'calendar', 'controller' => 'category', 'action' => 'index', 'id' => $data->ID), null, true),
			'charset' => 'utf8',
			'entries' => $entries
		);


		// create our feed object and import the data
		$feed = Zend_Feed::importArray ( $feedData, 'rss' );
			
		// set the Content Type of the document
		header ( 'Content-type: text/xml' );
			
		// echo the contents of the RSS xml document
		echo $feed->send();
		exit;
	}

	public function storyAction() {
		// Log Access
		System_Model_Log::access ();

		$filterInt = new Zend_Filter_Int ();
		$_id = $this->getRequest ()->getParam ( "id", "" );
		$_cid = $this->getRequest ()->getParam ( "cid", "" );
		$id = $filterInt->filter ( $_id );
		$cid = $filterInt->filter ( $_cid );
		
		if (empty($id) || empty($cid)) {
			// Show "Not found data"
			$this->renderScript('index/_alertnotfound.phtml');
			return;
		}

		// Save View
		$accessFrom = "Web";
		$memberID = null;
		$userID = null;
		// Rss_Model_Article::saveView($itemID, $memberID, $userID, $this->lang, $accessFrom);

		// Model
		$model = new Rss_Model_Page();

		// Get RSS Item
		$dataItem = $model->getItemSetting($_id, $this->lang);

		// Check Data: Can Access
		if (!$dataItem || $dataItem->IsEnable != 'Y' || $dataItem->IsDeleted != 'N') {
			// Show "Not found data"
			$this->renderScript('index/_alertnotfound.phtml');
			return;
		}

		// Setting
		$cSetting = json_decode($dataItem->Settings);
		$setting = new stdClass();
		$setting->Limit = $cSetting->Limit;
		$setting->Sidx = $cSetting->Sidx;
		$setting->Sord = $cSetting->Sord;

		// Get Data
		$data = $model->getStory($_cid, $this->lang);

		// Check Data: Can Access
		if (!$data || $data->IsEnable != 'Y' || $data->IsDeleted != 'N') {
			// Show "Not found data"
			$this->renderScript('index/_alertnotfound.phtml');
			return;
		}
		
		// Check Data : Rss in This Lang
		if (empty($data->Title)) {
			// Show "Not found data in this LANG, อยู่ระหว่างจัดทำข้อมูล"
			$this->renderScript('index/_alertmaintenance.phtml');
			return;
		}

		$SiteUrl = System_Model_Setting::getValue("SiteUrl", "General");

		$dataItemList = $model->getStoryList($_cid, $this->lang, $setting);

		$entries = array();
		$itunes = array();
		if(count($dataItemList)>0){
			foreach($dataItemList as $k=>$v){
				$date = DateTime::createFromFormat('Y-m-d H:i:s', $v['PublishDate']);
				$entry = array(
					'title' => $v['Title'],
					'description' => empty($v['Detail']) ? '-' : $v['Detail'],
					'link' => $SiteUrl . $this->view->url(array('module' => 'story', 'controller' => 'category', 'action' => 'detail', 'id' => $data->ID, 'iid' => $v['ID']), null, true),
					'guid' => uniqid() . $v['ID'],
					'lastUpdate' => $date->getTimestamp()
				);
				if (!empty($v['IntroImageHashName'])) {
					$entry['enclosure'] = array(array(
							'url' => $SiteUrl . $this->view->url(array('module' => 'default', 'controller' => 'file', 'action' => 'get', 'file' => $v['IntroImageHashName']), null, true),
							'type' => $v['IntroImageFileType'],
							'length' => null
					));
				}
				$entries[] = $entry;
			}
		}

		$feedData = array(
			'title' => $data->Title,
			'description' => $data->IntroText,
			'link' => $SiteUrl . $this->view->url(array('module' => 'story', 'controller' => 'category', 'action' => 'index', 'id' => $data->ID), null, true),
			'charset' => 'utf8',
			'entries' => $entries
		);

		// create our feed object and import the data
		$feed = Zend_Feed::importArray ( $feedData, 'rss' );
			
		// set the Content Type of the document
		header ( 'Content-type: text/xml' );
			
		// echo the contents of the RSS xml document
		echo $feed->send();
		exit;
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