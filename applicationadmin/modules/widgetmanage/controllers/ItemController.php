<?php

class Widgetmanage_ItemController extends Zend_Controller_Action
{
	protected $statusOption = array('Y' => 'ใช้งาน', 'N' => 'ไม่ใช้งาน');
	protected $publishOption = array ('Y' => 'เผยแพร่', 'N' => 'ไม่เผยแพร่');
    protected $yesNoOption = array ('Y' => 'ใช่', 'N' => 'ไม่ใช่');
	protected $columnAmountOption = array ('1' => '1 คอลัมน์', '2' => '2 คอลัมน์', '3' => '3 คอลัมน์', '4' => '4 คอลัมน์', '6' => '6 คอลัมน์');
    protected $sortTypeOption = array ('name' => 'ชื่อ', 'created' => 'วันที่สร้าง', 'publishdate' => 'วันที่เผยแพร่', 'viewamount' => 'ความนิยม', 'ordering' => 'ลำดับการแสดงผล');
	protected $sortDirectionOption = array ('asc' => 'น้อยไปมาก', 'desc' => 'มากไปน้อย');
	protected $widgetTypeOption = array (
        'grid' => 'grid',
        'slideshow' => 'slideshow',
        'slideset' => 'slideset',
        'kenburns' => 'kenburns',
        'marquee' => 'marquee',
        'tab' => 'tab',
        'collapse' => 'collapse',
        'calendar' => 'calendar'
    );
	protected $dataTypeOption = array (
        'content' => 'content',
        'gallery' => 'gallery',
        'calendar' => 'calendar',
        'story' => 'story',
        'banner' => 'banner',
        'weblink' => 'weblink',
        'event' => 'event',
        'vrv' => 'virtual reality',
        'custom' => 'custom'
    );
    protected $aspectRatioOption = array (
        'ratio-1-1' => '1:1 Aspect Ratio',
        'ratio-4-3' => '4:3 Aspect Ratio',
        'ratio-3-2' => '3:2 Aspect Ratio',
        'ratio-8-5' => '8:5 Aspect Ratio',
        'ratio-16-9' => '16:9 Aspect Ratio',
        'ratio-2-1' => '2:1 Aspect Ratio',
        'ratio-3-1' => '3:1 Aspect Ratio'
    );
    protected $targetOption = array ('_blank' => '_blank', '_self' => '_self');
    protected $titleSizeOption = array ('h1' => 'h1', 'h2' => 'h2', 'h3' => 'h3', 'h4' => 'h4');
    protected $textAlignOption = array ('left' => 'left', 'right' => 'right', 'center' => 'center');
    protected $directionOption = array ('left' => 'จากขวาไปซ้าย', 'right' => 'จากซ้ายไปขวา', 'up' => 'จากล่างขึ้นบน', 'down' => 'จากบนลงล่าง');

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
		$canView = $sUser->isAllow('widget.item.view');
		$canAdd = $sUser->isAllow('widget.item.manage');
		if (!$canView) {
			$this->_helper->redirector('index', 'index', 'default');
		}
        
        $InfoSearch = new stdClass();
        if (Zend_Session::namespaceIsset("WidgetmanageItemSearch")) {
        	$infoSearchNamespace = new Zend_Session_Namespace("WidgetmanageItemSearch");
            $InfoSearch->textsearch = $infoSearchNamespace->textsearch;
        	$InfoSearch->widgettype= $infoSearchNamespace->widgettype;
        	$InfoSearch->datatype= $infoSearchNamespace->datatype;
        	$InfoSearch->isenable= $infoSearchNamespace->isenable;
        	$InfoSearch->tblPage = $infoSearchNamespace->tblPage;
        	$InfoSearch->tblRow = $infoSearchNamespace->tblRow;
        	$InfoSearch->tblOrderCol = $infoSearchNamespace->tblOrderCol;
        	$InfoSearch->tblOrderDir = $infoSearchNamespace->tblOrderDir;
        	
        	$this->view->InfoSearch = $InfoSearch;
        	Zend_Session::namespaceUnset("WidgetmanageItemSearch");
        }
        
        $languages = System_Model_Setting::getLanguages();
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
        
        // Send To View
        $this->view->widgetTypeOption = $this->widgetTypeOption;
        $this->view->dataTypeOption = $this->dataTypeOption;
        $this->view->statusOption = $this->statusOption;
        
        // Set View
        $this->view->canAdd = $canAdd;
        $this->view->pageName = "รายการ Widget";
        $this->view->pageIcon = "fa fa-newspaper-o";
        $this->view->subMenus = array();
        $this->view->breadcrumb = array(
            array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
            array('name' => 'รายการ Widget')
            
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
		$canView = $sUser->isAllow('widget.item.view');
        
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
        $advancesearch['isEnable'] = $this->getRequest()->getParam('isEnable');
        $advancesearch['widgetType'] = $this->getRequest()->getParam('widgetType');
        $advancesearch['dataType'] = $this->getRequest()->getParam('dataType');
        
        $infoSearchNamespace = new Zend_Session_Namespace("WidgetmanageItemSearch");
        $infoSearchNamespace->textsearch = $advancesearch['searchText'];
        $infoSearchNamespace->widgettype= $advancesearch['widgetType'];
        $infoSearchNamespace->datatype= $advancesearch['dataType'];
        $infoSearchNamespace->isenable= $advancesearch['isEnable'];
        $infoSearchNamespace->tblPage = $_start;
        $infoSearchNamespace->tblRow = $_length;
        $infoSearchNamespace->tblOrderCol = $_order[0]['column'];
        $infoSearchNamespace->tblOrderDir = $_order[0]['dir'];
        
        $model = new Widgetmanage_Model_Item();
        $data = $model->getList($sidx, $sord, $rows, $page, $textstr, $filter, $advancesearch);
        
        $i=$_start;
        if ($canView && $data) {
            $responce['recordsTotal'] = $data[0]["RECORD"];
            $responce['recordsFiltered'] = $data[0]["RECORD"];
            
            foreach($data as $item){
                $i++;
                $d = array();
                $d['DT_RowId'] = $item["ID"];
                $d['NO'] = $i;
                $d['Name'] = Emwork_Utility::notShowNull($item["Name"]);
                $d['DataType'] = $item["DataType"];
                $d['WidgetType'] = $item["WidgetType"];
                $d['IsEnableCode'] = $item["IsEnable"];
                $d['IsEnable'] = Emwork_Utility::notShowNull($this->statusOption[$item["IsEnable"]]);
                $responce['data'][] = $d;
            }
        }
        $this->_helper->json($responce);
    }

    public function selectAction()
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
		$canAdd = $sUser->isAllow('widget.item.manage');

		if (!$canAdd) {
		$this->_helper->redirector('index', 'item', 'banner');
		}

        // Send Option and lookup To View
        $this->view->widgetTypeOption = $this->widgetTypeOption;
        $this->view->dataTypeOption = $this->dataTypeOption;
        
        // Set View
        $this->view->pageName = "เลือกประเภท";
        $this->view->pageIcon = "fa fa-gear";
        $this->view->subMenus = array();
        $this->view->breadcrumb = array(
            array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
            array('name' => 'รายการ Widget', 'url' => $this->view->url(array('module' => 'widgetmanage', 'controller' => 'item', 'action' => 'index'), null, true)),
            array('name' => 'เลือกประเภท')
        );
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
		$canAdd = $sUser->isAllow('widget.item.manage');

		if (!$canAdd) {
		$this->_helper->redirector('index', 'item', 'widget');
		}
        
        $languages = System_Model_Setting::getLanguages();
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
        
        // Mobile Detect
        $detect = new Mobile_Detect();
        $this->view->isiOS = $detect->isiOS();

        // Get Param
        $dataType = $this->getRequest()->getParam('dt');
        $widgetType = $this->getRequest()->getParam('wt');

        if(!in_array($widgetType, array('grid','slideshow','slideset','kenburns','marquee','tab','collapse','calendar'))){
            $this->_helper->redirector('index', 'Item', 'widgetmanage');
        }
        if(!in_array($dataType, array('content','vrv','gallery','calendar','banner','weblink','story','event','custom'))){
            $this->_helper->redirector('index', 'Item', 'widgetmanage');
        }

        $languages = System_Model_Setting::getLanguages();
		$defaultLanguage = System_Model_Setting::getDefaultLanguage();
		
        // Get Option
        switch($dataType){
            case 'content':
                $model = new Content_Model_Category();
                $categoryOption = $model->getRecursiveOption($defaultLanguage['Code']);
                break;
            case 'vrv':
                $model = new Vrv_Model_Category();
                $categoryOption = $model->getRecursiveOption($defaultLanguage['Code']);
                break;
            case 'gallery':
                $model = new Gallery_Model_Category();
                $categoryOption = $model->getRecursiveOption($defaultLanguage['Code']);
                break;
            case 'calendar':
                $model = new Calendar_Model_Category();
                $categoryOption = $model->getRecursiveOption($defaultLanguage['Code']);
                break;
            case 'story':
                $model = new Story_Model_Category();
                $categoryOption = $model->getRecursiveOption($defaultLanguage['Code']);
                break;
            case 'banner':
                $model = new Banner_Model_Category();
                $categoryOption = $model->getOption($defaultLanguage['Code']);
                break;
            case 'weblink':
                $model = new Weblink_Model_Category();
                $categoryOption = $model->getOption($defaultLanguage['Code']);
                break;
            case 'event' : 
                $model = new Event_Model_Category();
                $categoryOption = $model->getOption($defaultLanguage['Code']);
                break;
            case 'custom':
            default:
                $categoryOption = array();
                break;
        }

        $sortTypeOption = $this->sortTypeOption;
        if(!in_array($dataType,array('banner','weblink'))){
            unset($sortTypeOption['ordering']);
        }
        
        // Send Option and lookup To View
        $this->view->canAdd = $canAdd;
        $this->view->dataType = $dataType;
        $this->view->widgetType = $widgetType;
        $this->view->categoryOption = $categoryOption;
        $this->view->sortTypeOption = $sortTypeOption;
        $this->view->sortDirectionOption = $this->sortDirectionOption;
        $this->view->publishOption = $this->publishOption;
        
        // Set View
        $this->view->pageName = "เพิ่ม Widget";
        $this->view->pageIcon = "fa fa-newspaper-o";
        $this->view->subMenus = array();
        $this->view->breadcrumb = array(
            array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
            array('name' => 'รายการ Widget', 'url' => $this->view->url(array('module' => 'widgetmanage', 'controller' => 'item', 'action' => 'index'), null, true)),
            array('name' => 'เลือกประเภท', 'url' => $this->view->url(array('module' => 'widgetmanage', 'controller' => 'item', 'action' => 'select'), null, true)),
            array('name' => 'เพิ่ม Widget')
        );

        if(in_array($widgetType, array('tab','collapse'))){
            $widgetmanageItemModel = new Widgetmanage_Model_Item();
            $widgetOption = $widgetmanageItemModel->getOptionWidgetSystem();
            $widgetToolOption = $widgetmanageItemModel->getOption();

            $this->view->widgetOption = $widgetOption;
            $this->view->widgetToolOption = $widgetToolOption;
            
            $this->renderScript('item/_addcustomtab.phtml');
        }
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
		$canAdd = $sUser->isAllow('widget.item.manage');
		if (!$canAdd) {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
        
        // Get Params
        $params = $this->getRequest()->getParams();

        // Prepare Data
        $parameter = array();
        $parameter['Name'] = $params['Name'];
        $parameter['DataType'] = $params['hdfDataType'];
        $parameter['WidgetType'] = $params['hdfWidgetType'];

        $parameter['ContentSettings']['settingCategory'] = $params['settingCategory'];
        $parameter['ContentSettings']['settingUseCategoryParent'] = empty($params['settingUseCategoryParent']) ? 'N' : 'Y';
        $parameter['ContentSettings']['settingLimit'] = $params['settingLimit'];
        $parameter['ContentSettings']['settingSord'] = $params['settingSord'];
        $parameter['ContentSettings']['settingSidx'] = $params['settingSidx'];

        if($params['hdfWidgetType']=='grid'){
            $parameter['DisplaySettings']['settingShowColumn'] = '6';
            $parameter['DisplaySettings']['settingShowImage'] = 'Y';
            $parameter['DisplaySettings']['settingImageShowLink'] = 'Y';
            $parameter['DisplaySettings']['settingImageTarget'] = '_self';
            $parameter['DisplaySettings']['settingShowTitle'] = 'Y';
            $parameter['DisplaySettings']['settingTitleShowLink'] = 'Y';
            $parameter['DisplaySettings']['settingTitleTarget'] = '_self';
            $parameter['DisplaySettings']['settingShowIntroText'] = 'Y';
            $parameter['DisplaySettings']['settingIntroTextShowLink'] = 'Y';
            $parameter['DisplaySettings']['settingIntroTextTarget'] = '_self';
            $parameter['DisplaySettings']['settingImageAspectRatio'] = 'ratio-1-1';
            $parameter['DisplaySettings']['settingShowOverlay'] = 'Y';
            $parameter['DisplaySettings']['settingHtmlClass'] = null;
            $parameter['DisplaySettings']['settingCustomCSS'] = null;
        }
        if($params['hdfWidgetType']=='slideshow'){
            $parameter['DisplaySettings']['settingNavigationArrowShow'] = 'Y';
            $parameter['DisplaySettings']['settingNavigationBulletShow'] = 'Y';
            $parameter['DisplaySettings']['settingNavigationDuration'] = null;
            $parameter['DisplaySettings']['settingAnimation'] = null;
            $parameter['DisplaySettings']['settingAutoPlay'] = 'Y';
            $parameter['DisplaySettings']['settingSlideDuration'] = null;
            $parameter['DisplaySettings']['settingTitleShow'] = 'Y';
            $parameter['DisplaySettings']['settingTitleSize'] = 'h1';
            $parameter['DisplaySettings']['settingTextAlign'] = 'left';
            $parameter['DisplaySettings']['settingIntroTextShow'] = 'Y';
            $parameter['DisplaySettings']['settingIntroTextSize'] = 'h4';
            $parameter['DisplaySettings']['settingIntroTextTextAlign'] = 'left';
            $parameter['DisplaySettings']['settingShowLink'] = 'Y';
            $parameter['DisplaySettings']['settingTarget'] = '_blank';
            $parameter['DisplaySettings']['settingHtmlClass'] = null;
            $parameter['DisplaySettings']['settingCustomCSS'] = null;
        }
        if($params['hdfWidgetType']=='slideset'){
            $parameter['DisplaySettings']['settingNavigationArrowShow'] = 'Y';
            $parameter['DisplaySettings']['settingNavigationBulletShow'] = 'Y';
            $parameter['DisplaySettings']['settingAutoPlay'] = 'Y';
            $parameter['DisplaySettings']['settingSlideDuration'] = null;
            $parameter['DisplaySettings']['settingAnimation'] = null;
            $parameter['DisplaySettings']['settingAnimationDuration'] = null;
            $parameter['DisplaySettings']['settingShowImage'] = 'Y';
            $parameter['DisplaySettings']['settingImageShowLink'] = 'Y';
            $parameter['DisplaySettings']['settingImageTarget'] = '_blank';
            $parameter['DisplaySettings']['settingShowTitle'] = 'Y';
            $parameter['DisplaySettings']['settingTitleShowLink'] = 'Y';
            $parameter['DisplaySettings']['settingTitleTarget'] = '_blank';
            $parameter['DisplaySettings']['settingShowIntroText'] = 'Y';
            $parameter['DisplaySettings']['settingIntroTextShowLink'] = 'Y';
            $parameter['DisplaySettings']['settingIntroTextTarget'] = '_blank';
            $parameter['DisplaySettings']['settingImageAspectRatio'] = 'ratio-1-1';
            $parameter['DisplaySettings']['settingSlidesToShowExtraLarge'] = '6';
            $parameter['DisplaySettings']['settingSlidesToShowLarge'] = '4';
            $parameter['DisplaySettings']['settingSlidesToShowMedium'] = '2';
            $parameter['DisplaySettings']['settingSlidesToShowSmall'] = '2';
            $parameter['DisplaySettings']['settingSlidesToShowExtraSmall'] = '1';
            $parameter['DisplaySettings']['settingHtmlClass'] = null;
            $parameter['DisplaySettings']['settingCustomCSS'] = null;
        }
        if($params['hdfWidgetType']=='marquee'){
            $parameter['DisplaySettings']['settingShowTitle'] = 'N';
            $parameter['DisplaySettings']['settingTitle'] = null;
            $parameter['DisplaySettings']['settingTitleBackgroundColor'] = null;
            $parameter['DisplaySettings']['settingTitleFontColor'] = null;
            $parameter['DisplaySettings']['settingShowLink'] = 'Y';
            $parameter['DisplaySettings']['settingTarget'] = '_blank';
            $parameter['DisplaySettings']['settingBackgroundColor'] = null;
            $parameter['DisplaySettings']['settingFontColor'] = null;
            $parameter['DisplaySettings']['settingDirection'] = 'left';
            $parameter['DisplaySettings']['settingDuration'] = '20000';
            // $parameter['DisplaySettings']['settingDuplicated'] = 'Y';
            $parameter['DisplaySettings']['settingPauseOnHover'] = 'Y';
            $parameter['DisplaySettings']['settingHtmlClass'] = null;
            $parameter['DisplaySettings']['settingCustomCSS'] = null;
        }
        
        $model = new Widgetmanage_Model_Item();
        $data = $model->save($parameter);
        
        // Set Result
        if ($data->result) {
            $result['result'] = true;
            $result['message'] = '';
            $result['id'] = $data->id;
            
            // Log Acion
            System_Model_Log::action("widgetmanage.item", "save", $params['Name'], $data->id);
        } else {
            $result['result'] = false;
            $result['message'] = 'บันทึกไม่สำเร็จ';
        }
        
        $this->_helper->json($result);
    }

    public function savecustomAction()
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
		$canAdd = $sUser->isAllow('widget.item.manage');
		if (!$canAdd) {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
        
        // Get Params
        $params = $this->getRequest()->getParams();

        // Prepare Data
        $parameter = array();
        $parameter['Name'] = $params['Name'];
        $parameter['DataType'] = $params['hdfDataType'];
        $parameter['WidgetType'] = $params['hdfWidgetType'];
        $parameter['ContentSettings'] = count($params['ContentList']) > 0 ? $params['ContentList'] : array();

        if($params['hdfWidgetType']=='grid'){
            $parameter['DisplaySettings']['settingShowColumn'] = '6';
            $parameter['DisplaySettings']['settingShowImage'] = 'Y';
            $parameter['DisplaySettings']['settingImageShowLink'] = 'Y';
            $parameter['DisplaySettings']['settingImageTarget'] = '_self';
            $parameter['DisplaySettings']['settingShowTitle'] = 'Y';
            $parameter['DisplaySettings']['settingTitleShowLink'] = 'Y';
            $parameter['DisplaySettings']['settingTitleTarget'] = '_self';
            $parameter['DisplaySettings']['settingShowIntroText'] = 'Y';
            $parameter['DisplaySettings']['settingIntroTextShowLink'] = 'Y';
            $parameter['DisplaySettings']['settingIntroTextTarget'] = '_self';
            $parameter['DisplaySettings']['settingImageAspectRatio'] = 'ratio-1-1';
            $parameter['DisplaySettings']['settingShowOverlay'] = 'Y';
            $parameter['DisplaySettings']['settingHtmlClass'] = null;
            $parameter['DisplaySettings']['settingCustomCSS'] = null;
        }
        if($params['hdfWidgetType']=='slideshow'){
            $parameter['DisplaySettings']['settingNavigationArrowShow'] = 'Y';
            $parameter['DisplaySettings']['settingNavigationBulletShow'] = 'Y';
            $parameter['DisplaySettings']['settingNavigationDuration'] = null;
            $parameter['DisplaySettings']['settingAnimation'] = null;
            $parameter['DisplaySettings']['settingAutoPlay'] = 'Y';
            $parameter['DisplaySettings']['settingSlideDuration'] = null;
            $parameter['DisplaySettings']['settingTitleShow'] = 'Y';
            $parameter['DisplaySettings']['settingTitleSize'] = 'h1';
            $parameter['DisplaySettings']['settingTextAlign'] = 'left';
            $parameter['DisplaySettings']['settingIntroTextShow'] = 'Y';
            $parameter['DisplaySettings']['settingIntroTextSize'] = 'h4';
            $parameter['DisplaySettings']['settingIntroTextTextAlign'] = 'left';
            $parameter['DisplaySettings']['settingShowLink'] = 'Y';
            $parameter['DisplaySettings']['settingTarget'] = '_blank';
            $parameter['DisplaySettings']['settingHtmlClass'] = null;
            $parameter['DisplaySettings']['settingCustomCSS'] = null;
        }
        if($params['hdfWidgetType']=='slideset'){
            $parameter['DisplaySettings']['settingNavigationArrowShow'] = 'Y';
            $parameter['DisplaySettings']['settingNavigationBulletShow'] = 'Y';
            $parameter['DisplaySettings']['settingAutoPlay'] = 'Y';
            $parameter['DisplaySettings']['settingSlideDuration'] = null;
            $parameter['DisplaySettings']['settingAnimation'] = null;
            $parameter['DisplaySettings']['settingAnimationDuration'] = null;
            $parameter['DisplaySettings']['settingShowImage'] = 'Y';
            $parameter['DisplaySettings']['settingImageShowLink'] = 'Y';
            $parameter['DisplaySettings']['settingImageTarget'] = '_blank';
            $parameter['DisplaySettings']['settingShowTitle'] = 'Y';
            $parameter['DisplaySettings']['settingTitleShowLink'] = 'Y';
            $parameter['DisplaySettings']['settingTitleTarget'] = '_blank';
            $parameter['DisplaySettings']['settingShowIntroText'] = 'Y';
            $parameter['DisplaySettings']['settingIntroTextShowLink'] = 'Y';
            $parameter['DisplaySettings']['settingIntroTextTarget'] = '_blank';
            $parameter['DisplaySettings']['settingImageAspectRatio'] = 'ratio-1-1';
            $parameter['DisplaySettings']['settingSlidesToShowExtraLarge'] = '6';
            $parameter['DisplaySettings']['settingSlidesToShowLarge'] = '4';
            $parameter['DisplaySettings']['settingSlidesToShowMedium'] = '2';
            $parameter['DisplaySettings']['settingSlidesToShowSmall'] = '2';
            $parameter['DisplaySettings']['settingSlidesToShowExtraSmall'] = '1';
            $parameter['DisplaySettings']['settingHtmlClass'] = null;
            $parameter['DisplaySettings']['settingCustomCSS'] = null;
        }
        if($params['hdfWidgetType'] == 'kenburns') {
            $parameter['DisplaySettings']['settingKenburnDuration'] = '20';
            $parameter['DisplaySettings']['settingHeight'] = '20vw';
            $parameter['DisplaySettings']['settingAnimation'] = 'left';
            $parameter['DisplaySettings']['settingSlideDuration'] = '4000';
            $parameter['DisplaySettings']['settingTitleShow'] = 'Y';
            $parameter['DisplaySettings']['settingTitleSize'] = 'h1';
            $parameter['DisplaySettings']['settingTextAlign'] = 'left';
            $parameter['DisplaySettings']['settingIntroTextShow'] = 'Y';
            $parameter['DisplaySettings']['settingIntroTextSize'] = 'h4';
            $parameter['DisplaySettings']['settingIntroTextTextAlign'] = 'left';
            $parameter['DisplaySettings']['settingShowLink'] = 'Y';
            $parameter['DisplaySettings']['settingTarget'] = '_blank';
            $parameter['DisplaySettings']['settingHtmlClass'] = null;
            $parameter['DisplaySettings']['settingCustomCSS'] = null;
        }
        if($params['hdfWidgetType'] == 'marquee') {
            $parameter['DisplaySettings']['settingShowTitle'] = 'N';
            $parameter['DisplaySettings']['settingTitle'] = null;
            $parameter['DisplaySettings']['settingTitleBackgroundColor'] = null;
            $parameter['DisplaySettings']['settingTitleFontColor'] = null;
            $parameter['DisplaySettings']['settingShowLink'] = 'Y';
            $parameter['DisplaySettings']['settingTarget'] = '_blank';
            $parameter['DisplaySettings']['settingBackgroundColor'] = null;
            $parameter['DisplaySettings']['settingFontColor'] = null;
            $parameter['DisplaySettings']['settingDirection'] = 'left';
            $parameter['DisplaySettings']['settingDuration'] = '20000';
            // $parameter['DisplaySettings']['settingDuplicated'] = 'Y';
            $parameter['DisplaySettings']['settingPauseOnHover'] = 'Y';
            $parameter['DisplaySettings']['settingHtmlClass'] = null;
            $parameter['DisplaySettings']['settingCustomCSS'] = null;
        }
        if($params['hdfWidgetType']=='tab'){
            $parameter['DisplaySettings']['settingAnimation'] = null;
            $parameter['DisplaySettings']['settingShowImage'] = 'Y';
            $parameter['DisplaySettings']['settingImageAlign'] = $params['settingImageAlign'];
            $parameter['DisplaySettings']['settingShowTitle'] = 'Y';
            $parameter['DisplaySettings']['settingTitleAlign'] = $params['settingTitleAlign'];
            $parameter['DisplaySettings']['settingShowIntroText'] = 'Y';
            $parameter['DisplaySettings']['settingIntroTextAlign'] = $params['settingIntroTextAlign'];
            $parameter['DisplaySettings']['settingShowLink'] = 'Y';
            $parameter['DisplaySettings']['settingLinkTitle'] = 'Read More';
            $parameter['settingLinkAlign']['settingLinkAlign'] = $params['settingLinkAlign'];
            $parameter['DisplaySettings']['settingLinkTarget'] = '_blank';
            $parameter['DisplaySettings']['settingHtmlClass'] = null;
            $parameter['DisplaySettings']['settingCustomCSS'] = null;
        }
        if($params['hdfWidgetType']=='collapse'){
            $parameter['DisplaySettings']['settingAnimation'] = null;
            $parameter['DisplaySettings']['settingAllowMultipleOpened'] = 'N';
            $parameter['DisplaySettings']['settingShowFirstItem'] = 'Y';
            $parameter['DisplaySettings']['settingShowImage'] = 'Y';
            $parameter['DisplaySettings']['settingImageAlign'] = $params['settingImageAlign'];
            $parameter['DisplaySettings']['settingShowTitle'] = 'Y';
            $parameter['DisplaySettings']['settingTitleAlign'] = $params['settingTitleAlign'];
            $parameter['DisplaySettings']['settingShowIntroText'] = 'Y';
            $parameter['DisplaySettings']['settingIntroTextAlign'] = $params['settingIntroTextAlign'];
            $parameter['DisplaySettings']['settingShowLink'] = 'Y';
            $parameter['DisplaySettings']['settingLinkTitle'] = 'Read More';
            $parameter['settingLinkAlign']['settingLinkAlign'] = $params['settingLinkAlign'];
            $parameter['DisplaySettings']['settingLinkTarget'] = '_blank';
            $parameter['DisplaySettings']['settingHtmlClass'] = null;
            $parameter['DisplaySettings']['settingCustomCSS'] = null;
        }
        if($params['hdfWidgetType']=='calendar'){
            $parameter['DisplaySettings']['settingTemplate'] = 'C';
            $parameter['DisplaySettings']['settingStartDate'] = null;
            $parameter['DisplaySettings']['settingEndDate'] = null;
            $parameter['DisplaySettings']['settingHtmlClass'] = $params['settingHtmlClass'];
            $parameter['DisplaySettings']['settingCustomCSS'] = $params['settingCustomCSS'];
        }

        $model = new Widgetmanage_Model_Item();
        $data = $model->save($parameter);
        
        // Set Result
        if ($data->result) {
            $result['result'] = true;
            $result['message'] = '';
            $result['id'] = $data->id;
            
            // Log Acion
            System_Model_Log::action("widgetmanage.item", "save", $params['Name'], $data->id);
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
		$canDelete = $sUser->isAllow('widget.item.manage');
		$canEdit = $sUser->isAllow('widget.item.manage');
		$canView = $sUser->isAllow('widget.item.view');
		if (!$canView) {
		   $this->_helper->redirector('index', 'item', 'widget');
		}
        
        // Get Param
        $itemID = $this->_request->getParam('itemid');
        
        // Model
        if (!$itemID) {
        	$this->_helper->redirector('index', 'Item', 'widgetmanage');
        }
        
        $languages = System_Model_Setting::getLanguages();
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
        
        $model = new Widgetmanage_Model_Item();
        
        // Get Data
        $data = $model->getInfo($itemID);
        $dataContentSettings = empty($data->ContentSettings) ? null : json_decode($data->ContentSettings);
        $dataDisplaySettings = empty($data->DisplaySettings) ? null : json_decode($data->DisplaySettings);
        // echo '<pre>'; print_r($dataContentSettings); echo '</pre>';
        
        $languages = System_Model_Setting::getLanguages();
		$defaultLanguage = System_Model_Setting::getDefaultLanguage();
        
        // Get Option
        switch($data->DataType){
            case 'content':
                $model = new Content_Model_Category();
                $categoryOption = $model->getRecursiveOption($defaultLanguage['Code']);
                break;
            case 'vrv':
                $model = new Vrv_Model_Category();
                $categoryOption = $model->getRecursiveOption($defaultLanguage['Code']);
                break;
            case 'gallery':
                $model = new Gallery_Model_Category();
                $categoryOption = $model->getRecursiveOption($defaultLanguage['Code']);
                break;
            case 'calendar':
                $model = new Calendar_Model_Category();
                $categoryOption = $model->getRecursiveOption($defaultLanguage['Code']);
                break;
            case 'story':
                $model = new Story_Model_Category();
                $categoryOption = $model->getRecursiveOption($defaultLanguage['Code']);
                break;
            case 'banner':
                $model = new Banner_Model_Category();
                $categoryOption = $model->getOption($defaultLanguage['Code']);
                break;
            case 'weblink':
                $model = new Weblink_Model_Category();
                $categoryOption = $model->getOption($defaultLanguage['Code']);
                break;
            case 'event' :
                $model = new Event_Model_Category();
                $categoryOption = $model->getOption($defaultLanguage['Code']);
                break;
            case 'custom':
            default:
                $categoryOption = array();
                break;
        }

        // Send Rights to View
        $this->view->canDelete = $canDelete;
        $this->view->canEdit = $canEdit;
        
        // Mobile Detect
        $detect = new Mobile_Detect();
        $this->view->isiOS = $detect->isiOS();

        $sortTypeOption = $this->sortTypeOption;
        if(!in_array($data->DataType,array('banner','weblink'))){
            unset($sortTypeOption['ordering']);
        }

        // Send To View
        $this->view->data = $data;
        $this->view->dataContent = $dataContentSettings;
        $this->view->dataDisplay = $dataDisplaySettings;
        $this->view->categoryOption = $categoryOption;
        $this->view->sortTypeOption = $sortTypeOption;
        $this->view->sortDirectionOption = $this->sortDirectionOption;
        $this->view->publishOption = $this->publishOption;
        
        // Set View
        $this->view->pageName = "ปรับปรุง Widget";
        $this->view->pageIcon= "fa fa-newspaper-o";
        $this->view->subMenus = array(
        		array('name' => 'ตั้งค่าข้อมูล', 'active' => true),
        		array('name' => 'ตั้งค่าการแสดงผล', 'active' => false, 'url' => $this->view->url(array('module' => 'widgetmanage', 'controller' => 'item', 'action' => 'displaysetting', 'itemid' => $itemID), null, true)),
        );
        $this->view->breadcrumb = array(
            array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
            array('name' => 'รายการ Widget', 'url' => $this->view->url(array('module' => 'widgetmanage', 'controller' => 'item', 'action' => 'index'), null, true)),
            array('name' => 'ปรับปรุง Widget')
        );

        if(in_array($data->WidgetType, array('tab','collapse'))){
            $widgetmanageItemModel = new Widgetmanage_Model_Item();
            $widgetOption = $widgetmanageItemModel->getOptionWidgetSystem();
            $widgetToolOption = $widgetmanageItemModel->getOption(null, $data->ID);

            $this->view->widgetOption = $widgetOption;
            $this->view->widgetToolOption = $widgetToolOption;
            
            $this->renderScript('item/_editcustomtab.phtml');
        }
        
        // Log Acion
        System_Model_Log::action("widgetmanage.item", "view", $data->Name, $itemID);
    }

    public function displaysettingAction()
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
		$canDelete = $sUser->isAllow('widget.item.manage');
		$canEdit = $sUser->isAllow('widget.item.manage');
		$canView = $sUser->isAllow('widget.item.view');
		if (!$canView) {
		   $this->_helper->redirector('index', 'item', 'widget');
		}
        
        // Get Param
        $itemID = $this->_request->getParam('itemid');
        
        // Model
        if (!$itemID) {
        	$this->_helper->redirector('index', 'Item', 'widgetmanage');
        }
        
        $languages = System_Model_Setting::getLanguages();
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
        
        $model = new Widgetmanage_Model_Item();
        
        // Get Data
        $data = $model->getInfo($itemID);
        $dataContentSettings = empty($data->ContentSettings) ? null : json_decode($data->ContentSettings);
        $dataDisplaySettings = empty($data->DisplaySettings) ? null : json_decode($data->DisplaySettings);
        
        $languages = System_Model_Setting::getLanguages();
		$defaultLanguage = System_Model_Setting::getDefaultLanguage();

        // Send Rights to View
        $this->view->canDelete = $canDelete;
        $this->view->canEdit = $canEdit;
        
        // Mobile Detect
        $detect = new Mobile_Detect();
        $this->view->isiOS = $detect->isiOS();

        // Send To View
        $this->view->data = $data;
        $this->view->dataContent = $dataContentSettings;
        $this->view->dataDisplay = $dataDisplaySettings;
        $this->view->statusOption = $this->statusOption;
        $this->view->aspectRatioOption = $this->aspectRatioOption;
        $this->view->targetOption = $this->targetOption;
        $this->view->columnAmountOption = $this->columnAmountOption;
        $this->view->animationOption = $this->getAnimationOption();
        $this->view->animationKenburnsOption = $this->getAnimationKenburnsOption();
        $this->view->titleSizeOption = $this->titleSizeOption;
        $this->view->textAlignOption = $this->textAlignOption;
        $this->view->directionOption = $this->directionOption;
        
        // Set View
        $this->view->pageName = "ปรับปรุง Widget";
        $this->view->pageIcon= "fa fa-newspaper-o";
        $this->view->subMenus = array(
                array('name' => 'ตั้งค่าข้อมูล', 'active' => false, 'url' => $this->view->url(array('module' => 'widgetmanage', 'controller' => 'item', 'action' => 'edit', 'itemid' => $itemID), null, true)),
        		array('name' => 'ตั้งค่าการแสดงผล', 'active' => true),
        );
        $this->view->breadcrumb = array(
            array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
            array('name' => 'รายการ Widget', 'url' => $this->view->url(array('module' => 'widgetmanage', 'controller' => 'item', 'action' => 'index'), null, true)),
            array('name' => 'ปรับปรุง Widget')
        );
        
        // Log Acion
        System_Model_Log::action("widgetmanage.item", "view", "ตั้งค่า : ".$data->Name, $itemID);
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
		$canSave = $sUser->isAllow('widget.item.manage');
		if (!$canSave) {
		   $result['message'] = 'ไม่สามารถบันทึกได้';
		   $this->_helper->json($result);
		}
        
        // Get Params
        $params = $this->getRequest()->getParams();

        // Prepare Data
        $parameter = array();
        $parameter['ID'] = $params['hdfID'];
        $parameter['UpdateType'] = $params['hdfUpdateType'];

        if($params['hdfUpdateType']=='C'){
            $parameter['Name'] = $params['Name'];
            $parameter['DataType'] = $params['hdfDataType'];
            $parameter['WidgetType'] = $params['hdfWidgetType'];
            $parameter['ContentSettings']['settingCategory'] = $params['settingCategory'];
            $parameter['ContentSettings']['settingUseCategoryParent'] = empty($params['settingUseCategoryParent']) ? 'N' : 'Y';
            $parameter['ContentSettings']['settingLimit'] = $params['settingLimit'];
            $parameter['ContentSettings']['settingSord'] = $params['settingSord'];
            $parameter['ContentSettings']['settingSidx'] = $params['settingSidx'];
        }
        if($params['hdfUpdateType']=='D'){
            if($params['hdfWidgetType']=='grid'){
                $parameter['DisplaySettings']['settingShowColumn'] = $params['settingShowColumn'];
                $parameter['DisplaySettings']['settingShowImage'] = $params['settingShowImage'];
                $parameter['DisplaySettings']['settingImageShowLink'] = $params['settingImageShowLink'];
                $parameter['DisplaySettings']['settingImageTarget'] = $params['settingImageTarget'];
                $parameter['DisplaySettings']['settingShowTitle'] = $params['settingShowTitle'];
                $parameter['DisplaySettings']['settingTitleShowLink'] = $params['settingTitleShowLink'];
                $parameter['DisplaySettings']['settingTitleTarget'] = $params['settingTitleTarget'];
                $parameter['DisplaySettings']['settingShowIntroText'] = $params['settingShowIntroText'];
                $parameter['DisplaySettings']['settingIntroTextShowLink'] = $params['settingIntroTextShowLink'];
                $parameter['DisplaySettings']['settingIntroTextTarget'] = $params['settingIntroTextTarget'];
                $parameter['DisplaySettings']['settingImageAspectRatio'] = $params['settingImageAspectRatio'];
                $parameter['DisplaySettings']['settingShowOverlay'] = $params['settingShowOverlay'];
                $parameter['DisplaySettings']['settingHtmlClass'] = $params['settingHtmlClass'];
                $parameter['DisplaySettings']['settingCustomCSS'] = $params['settingCustomCSS'];
                $parameter['DisplaySettings']['settingWeblinkbuttonShowLink'] = empty($params['settingWeblinkbuttonShowLink']) ? 'Y' : $params['settingWeblinkbuttonShowLink'];
            }
            if($params['hdfWidgetType']=='slideshow'){
                $parameter['DisplaySettings']['settingHeight'] = $params['settingHeight'];
                $parameter['DisplaySettings']['settingNavigationArrowShow'] = $params['settingNavigationArrowShow'];
                $parameter['DisplaySettings']['settingNavigationBulletShow'] = $params['settingNavigationBulletShow'];
                $parameter['DisplaySettings']['settingNavigationDuration'] = empty($params['settingNavigationDuration']) ? null : ($params['settingNavigationDuration']*1000);
                $parameter['DisplaySettings']['settingAnimation'] = $params['settingAnimation'];
                $parameter['DisplaySettings']['settingAutoPlay'] = $params['settingAutoPlay'];
                $parameter['DisplaySettings']['settingSlideDuration'] = empty($params['settingSlideDuration']) ? null : ($params['settingSlideDuration']*1000);
                $parameter['DisplaySettings']['settingTitleShow'] = $params['settingTitleShow'];
                $parameter['DisplaySettings']['settingTitleSize'] = $params['settingTitleSize'];
                $parameter['DisplaySettings']['settingTextAlign'] = $params['settingTextAlign'];
                $parameter['DisplaySettings']['settingIntroTextShow'] = $params['settingIntroTextShow'];
                $parameter['DisplaySettings']['settingIntroTextSize'] = $params['settingIntroTextSize'];
                $parameter['DisplaySettings']['settingIntroTextTextAlign'] = $params['settingIntroTextTextAlign'];
                $parameter['DisplaySettings']['settingShowLink'] = $params['settingShowLink'];
                $parameter['DisplaySettings']['settingTarget'] = $params['settingTarget'];
                $parameter['DisplaySettings']['settingHtmlClass'] = $params['settingHtmlClass'];
                $parameter['DisplaySettings']['settingCustomCSS'] = $params['settingCustomCSS'];
            }
            if($params['hdfWidgetType']=='slideset'){
                $parameter['DisplaySettings']['settingNavigationArrowShow'] = $params['settingNavigationArrowShow'];
                $parameter['DisplaySettings']['settingNavigationBulletShow'] = $params['settingNavigationBulletShow'];
                $parameter['DisplaySettings']['settingAutoPlay'] = $params['settingAutoPlay'];
                $parameter['DisplaySettings']['settingSlideDuration'] = empty($params['settingSlideDuration']) ? null : ($params['settingSlideDuration']*1000);
                $parameter['DisplaySettings']['settingAnimation'] = $params['settingAnimation'];
                $parameter['DisplaySettings']['settingAnimationDuration'] = empty($params['settingAnimationDuration']) ? null : ($params['settingAnimationDuration']*1000);
                $parameter['DisplaySettings']['settingShowImage'] = $params['settingShowImage'];
                $parameter['DisplaySettings']['settingImageShowLink'] = $params['settingImageShowLink'];
                $parameter['DisplaySettings']['settingImageTarget'] = $params['settingImageTarget'];
                $parameter['DisplaySettings']['settingShowTitle'] = $params['settingShowTitle'];
                $parameter['DisplaySettings']['settingTitleShowLink'] = $params['settingTitleShowLink'];
                $parameter['DisplaySettings']['settingTitleTarget'] = $params['settingTitleTarget'];
                $parameter['DisplaySettings']['settingShowIntroText'] = $params['settingShowIntroText'];
                $parameter['DisplaySettings']['settingIntroTextShowLink'] = $params['settingIntroTextShowLink'];
                $parameter['DisplaySettings']['settingIntroTextTarget'] = $params['settingIntroTextTarget'];
                $parameter['DisplaySettings']['settingImageAspectRatio'] = $params['settingImageAspectRatio'];
                $parameter['DisplaySettings']['settingSlidesToShowExtraLarge'] = $params['settingSlidesToShowExtraLarge'];
                $parameter['DisplaySettings']['settingSlidesToShowLarge'] = $params['settingSlidesToShowLarge'];
                $parameter['DisplaySettings']['settingSlidesToShowMedium'] = $params['settingSlidesToShowMedium'];
                $parameter['DisplaySettings']['settingSlidesToShowSmall'] = $params['settingSlidesToShowSmall'];
                $parameter['DisplaySettings']['settingSlidesToShowExtraSmall'] = $params['settingSlidesToShowExtraSmall'];
                $parameter['DisplaySettings']['settingHtmlClass'] = $params['settingHtmlClass'];
                $parameter['DisplaySettings']['settingCustomCSS'] = $params['settingCustomCSS'];
                $parameter['DisplaySettings']['settingWeblinkbuttonShowLink'] = empty($params['settingWeblinkbuttonShowLink']) ? 'Y' : $params['settingWeblinkbuttonShowLink'];
            }
            if($params['hdfWidgetType']=='tab'){
                $parameter['DisplaySettings']['settingAnimation'] = $params['settingAnimation'];
                $parameter['DisplaySettings']['settingShowImage'] = $params['settingShowImage'];
                $parameter['DisplaySettings']['settingImageAlign'] = $params['settingImageAlign'];
                $parameter['DisplaySettings']['settingShowTitle'] = $params['settingShowTitle'];
                $parameter['DisplaySettings']['settingTitleAlign'] = $params['settingTitleAlign'];
                $parameter['DisplaySettings']['settingShowIntroText'] = $params['settingShowIntroText'];
                $parameter['DisplaySettings']['settingIntroTextAlign'] = $params['settingIntroTextAlign'];
                $parameter['DisplaySettings']['settingShowLink'] = $params['settingShowLink'];
                $parameter['DisplaySettings']['settingLinkTitle'] = $params['settingLinkTitle'];
                $parameter['DisplaySettings']['settingLinkAlign'] = $params['settingLinkAlign'];
                $parameter['DisplaySettings']['settingLinkTarget'] = $params['settingLinkTarget'];
                $parameter['DisplaySettings']['settingHtmlClass'] = $params['settingHtmlClass'];
                $parameter['DisplaySettings']['settingCustomCSS'] = $params['settingCustomCSS'];
            }
            if($params['hdfWidgetType']=='collapse'){
                $parameter['DisplaySettings']['settingAnimation'] = $params['settingAnimation'];
                $parameter['DisplaySettings']['settingAllowMultipleOpened'] = $params['settingAllowMultipleOpened'];
                $parameter['DisplaySettings']['settingShowFirstItem'] = $params['settingShowFirstItem'];
                $parameter['DisplaySettings']['settingShowImage'] = $params['settingShowImage'];
                $parameter['DisplaySettings']['settingImageAlign'] = $params['settingImageAlign'];
                $parameter['DisplaySettings']['settingShowTitle'] = $params['settingShowTitle'];
                $parameter['DisplaySettings']['settingTitleAlign'] = $params['settingTitleAlign'];
                $parameter['DisplaySettings']['settingShowIntroText'] = $params['settingShowIntroText'];
                $parameter['DisplaySettings']['settingIntroTextAlign'] = $params['settingIntroTextAlign'];
                $parameter['DisplaySettings']['settingShowLink'] = $params['settingShowLink'];
                $parameter['DisplaySettings']['settingLinkTitle'] = $params['settingLinkTitle'];
                $parameter['DisplaySettings']['settingLinkAlign'] = $params['settingLinkAlign'];
                $parameter['DisplaySettings']['settingLinkTarget'] = $params['settingLinkTarget'];
                $parameter['DisplaySettings']['settingHtmlClass'] = $params['settingHtmlClass'];
                $parameter['DisplaySettings']['settingCustomCSS'] = $params['settingCustomCSS'];
            }
            if($params['hdfWidgetType']=='kenburns'){
                $parameter['DisplaySettings']['settingKenburnDuration'] = $params['settingKenburnDuration'];
                $parameter['DisplaySettings']['settingHeight'] = $params['settingHeight'];
                $parameter['DisplaySettings']['settingAnimation'] = $params['settingAnimation'];
                $parameter['DisplaySettings']['settingSlideDuration'] = empty($params['settingSlideDuration']) ? null : ($params['settingSlideDuration']*1000);
                $parameter['DisplaySettings']['settingTitleShow'] = $params['settingTitleShow'];
                $parameter['DisplaySettings']['settingTitleSize'] = $params['settingTitleSize'];
                $parameter['DisplaySettings']['settingTextAlign'] = $params['settingTextAlign'];
                $parameter['DisplaySettings']['settingIntroTextShow'] = $params['settingIntroTextShow'];
                $parameter['DisplaySettings']['settingIntroTextSize'] = $params['settingIntroTextSize'];
                $parameter['DisplaySettings']['settingIntroTextTextAlign'] = $params['settingIntroTextTextAlign'];
                $parameter['DisplaySettings']['settingShowLink'] = $params['settingShowLink'];
                $parameter['DisplaySettings']['settingTarget'] = $params['settingTarget'];
                $parameter['DisplaySettings']['settingHtmlClass'] = $params['settingHtmlClass'];
                $parameter['DisplaySettings']['settingCustomCSS'] = $params['settingCustomCSS'];
            }
            if($params['hdfWidgetType']=='marquee'){
                $parameter['DisplaySettings']['settingShowTitle'] = $params['settingShowTitle'];
                $parameter['DisplaySettings']['settingTitle'] = $params['settingTitle'];
                $parameter['DisplaySettings']['settingTitleBackgroundColor'] = $params['settingTitleBackgroundColor'];
                $parameter['DisplaySettings']['settingTitleFontColor'] = $params['settingTitleFontColor'];
                $parameter['DisplaySettings']['settingShowLink'] = $params['settingShowLink'];
                $parameter['DisplaySettings']['settingTarget'] = $params['settingTarget'];
                $parameter['DisplaySettings']['settingBackgroundColor'] = $params['settingBackgroundColor'];
                $parameter['DisplaySettings']['settingFontColor'] = $params['settingFontColor'];
                $parameter['DisplaySettings']['settingDirection'] = $params['settingDirection'];
                $parameter['DisplaySettings']['settingDuration'] = empty($params['settingDuration']) ? null : ($params['settingDuration']*1000);
                // $parameter['DisplaySettings']['settingDuplicated'] = $params['settingDuplicated'];
                $parameter['DisplaySettings']['settingPauseOnHover'] = $params['settingPauseOnHover'];
                $parameter['DisplaySettings']['settingHtmlClass'] = $params['settingHtmlClass'];
                $parameter['DisplaySettings']['settingCustomCSS'] = $params['settingCustomCSS'];
            }
            if($params['hdfWidgetType']=='calendar'){
                $parameter['DisplaySettings']['settingTemplate'] = $params['settingTemplate'];
                $parameter['DisplaySettings']['settingStartDate'] = empty($params['settingStartDate']) ? null : (Emwork_Utility_DateTime::dateThaiToMySQL($params['settingStartDate']));
                $parameter['DisplaySettings']['settingEndDate'] = empty($params['settingEndDate']) ? null : (Emwork_Utility_DateTime::dateThaiToMySQL($params['settingEndDate']).' 23:59');
                $parameter['DisplaySettings']['settingHtmlClass'] = $params['settingHtmlClass'];
                $parameter['DisplaySettings']['settingCustomCSS'] = $params['settingCustomCSS'];
            }
        }

        $parameter['IsEnable'] = $params['IsEnable'];
        
        // Update
        $model = new Widgetmanage_Model_Item();
        $data = $model->update($parameter);
        
        // Set Result
        if ($data->result) {
            $result['result'] = true;
            $result['message'] = '';
            $result['id'] = $data->id;
            
            // Log Acion
            System_Model_Log::action("widgetmanage.item", "update", $params['Name'], $data->id);
        } else {
            $result['result'] = false;
            $result['message'] = 'บันทึกไม่สำเร็จ';
        }
        
        $this->_helper->json($result);
    }
    
    public function updatecustomAction()
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
		$canSave = $sUser->isAllow('widget.item.manage');
		if (!$canSave) {
		   $result['message'] = 'ไม่สามารถบันทึกได้';
		   $this->_helper->json($result);
		}
        
        // Get Params
        $params = $this->getRequest()->getParams();

        // Prepare Data
        $parameter = array();
        $parameter['ID'] = $params['hdfID'];
        $parameter['Name'] = $params['Name'];
        $parameter['DataType'] = $params['hdfDataType'];
        $parameter['WidgetType'] = $params['hdfWidgetType'];
        $parameter['ContentSettings'] = count($params['ContentList']) > 0 ? $params['ContentList'] : array();
        
        // Update
        $model = new Widgetmanage_Model_Item();
        $data = $model->updateCustom($parameter);
        
        // Set Result
        if ($data->result) {
            $result['result'] = true;
            $result['message'] = '';
            $result['id'] = $data->id;
            
            // Log Acion
            System_Model_Log::action("widgetmanage.item", "update", $params['Name'], $data->id);
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
		$canSave = $sUser->isAllow('widget.item.manage');
		if (!$canSave) {
		   $result['message'] = 'ไม่สามารถลบได้';
		   $this->_helper->json($result);
		}
        
        // Param
        $params = $this->getRequest()->getParams();
        
        // Update
        $model = new Widgetmanage_Model_Item();
        $data = $model->delete($params);
        
        // Set Result
        if ($data->result) {
            $result['result'] = true;
            $result['message'] = '';
            
            // Log Acion
            $model = new Widgetmanage_Model_Item();
            $data = $model->getInfo($params['id']);
            System_Model_Log::action("widgetmanage.item", "delete", $data->Name, $data->ID);
        } else {
            $result['result'] = false;
            $result['message'] = 'ลบไม่สำเร็จ';
        }
        
        $this->_helper->json($result);
    }

    private function getAnimationOption(){
        $settingAnimation = array(
            // 0 => array( 'value' => '', 'name' => '----- Fade Transitions -----' ),
            1 => array( 'value' => '{$Duration:800,$Opacity:2}', 'name' => 'Fade' ),
            2 =>array( 'value' => '{$Duration:800,x:0.3,$During:{$Left:[0.3,0.7]},$Easing:{$Left:$Jease$.$InCubic,$Opacity:$Jease$.$Linear},$Opacity:2}', 'name' => 'Fade in L' ),
            3 =>array( 'value' => '{$Duration:800,x:-0.3,$During:{$Left:[0.3,0.7]},$Easing:{$Left:$Jease$.$InCubic,$Opacity:$Jease$.$Linear},$Opacity:2}', 'name' => 'Fade in R' ),
            4 =>array( 'value' => '{$Duration:800,y:0.3,$During:{$Top:[0.3,0.7]},$Easing:{$Top:$Jease$.$InCubic,$Opacity:$Jease$.$Linear},$Opacity:2}', 'name' => 'Fade in T' ),
            5 =>array( 'value' => '{$Duration:800,y:-0.3,$During:{$Top:[0.3,0.7]},$Easing:{$Top:$Jease$.$InCubic,$Opacity:$Jease$.$Linear},$Opacity:2}', 'name' => 'Fade in B' ),
            6 =>array( 'value' => '{$Duration:800,x:0.3,$Cols:2,$During:{$Left:[0.3,0.7]},$ChessMode:{$Column:3},$Easing:{$Left:$Jease$.$InCubic,$Opacity:$Jease$.$Linear},$Opacity:2}', 'name' => 'Fade in LR' ),
            7 =>array( 'value' => '{$Duration:800,y:0.3,$Cols:2,$During:{$Top:[0.3,0.7]},$ChessMode:{$Column:12},$Easing:{$Top:$Jease$.$InCubic,$Opacity:$Jease$.$Linear},$Opacity:2}', 'name' => 'Fade in LR Chess' ),
            8 =>array( 'value' => '{$Duration:800,y:0.3,$Rows:2,$During:{$Top:[0.3,0.7]},$ChessMode:{$Row:12},$Easing:{$Top:$Jease$.$InCubic,$Opacity:$Jease$.$Linear},$Opacity:2}', 'name' => 'Fade in TB' ),
            9 =>array( 'value' => '{$Duration:800,x:0.3,$Rows:2,$During:{$Left:[0.3,0.7]},$ChessMode:{$Row:3},$Easing:{$Left:$Jease$.$InCubic,$Opacity:$Jease$.$Linear},$Opacity:2}', 'name' => 'Fade in TB Chess' ),
            10 =>array( 'value' => '{$Duration:800,x:0.3,y:0.3,$Cols:2,$Rows:2,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$ChessMode:{$Column:3,$Row:12},$Easing:{$Left:$Jease$.$InCubic,$Top:$Jease$.$InCubic,$Opacity:$Jease$.$Linear},$Opacity:2}', 'name' => 'Fade in Corners' ),
            11 =>array( 'value' => '{$Duration:800,x:0.3,$SlideOut:true,$Easing:{$Left:$Jease$.$InCubic,$Opacity:$Jease$.$Linear},$Opacity:2}', 'name' => 'Fade out L' ),
            12 =>array( 'value' => '{$Duration:800,x:-0.3,$SlideOut:true,$Easing:{$Left:$Jease$.$InCubic,$Opacity:$Jease$.$Linear},$Opacity:2}', 'name' => 'Fade out R' ),
            13 =>array( 'value' => '{$Duration:800,y:0.3,$SlideOut:true,$Easing:{$Top:$Jease$.$InCubic,$Opacity:$Jease$.$Linear},$Opacity:2}', 'name' => 'Fade out T' ),
            14 =>array( 'value' => '{$Duration:800,y:-0.3,$SlideOut:true,$Easing:{$Top:$Jease$.$InCubic,$Opacity:$Jease$.$Linear},$Opacity:2}', 'name' => 'Fade out B' ),
            15 =>array( 'value' => '{$Duration:800,x:0.3,$Cols:2,$SlideOut:true,$ChessMode:{$Column:3},$Easing:{$Left:$Jease$.$InCubic,$Opacity:$Jease$.$Linear},$Opacity:2}', 'name' => 'Fade out LR' ),
            16 =>array( 'value' => '{$Duration:800,y:-0.3,$Cols:2,$SlideOut:true,$ChessMode:{$Column:12},$Easing:{$Top:$Jease$.$InCubic,$Opacity:$Jease$.$Linear},$Opacity:2}', 'name' => 'Fade out LR Chess' ),
            17 =>array( 'value' => '{$Duration:800,y:0.3,$Rows:2,$SlideOut:true,$ChessMode:{$Row:12},$Easing:{$Top:$Jease$.$InCubic,$Opacity:$Jease$.$Linear},$Opacity:2}', 'name' => 'Fade out TB' ),
            18 =>array( 'value' => '{$Duration:800,x:-0.3,$Rows:2,$SlideOut:true,$ChessMode:{$Row:3},$Easing:{$Left:$Jease$.$InCubic,$Opacity:$Jease$.$Linear},$Opacity:2}', 'name' => 'Fade out TB Chess' ),
            19 =>array( 'value' => '{$Duration:800,x:0.3,y:0.3,$Cols:2,$Rows:2,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$SlideOut:true,$ChessMode:{$Column:3,$Row:12},$Easing:{$Left:$Jease$.$InCubic,$Top:$Jease$.$InCubic,$Opacity:$Jease$.$Linear},$Opacity:2}', 'name' => 'Fade out Corners' ),
            20 =>array( 'value' => '{$Duration:800,x:0.3,$During:{$Left:[0.3,0.7]},$Easing:{$Left:$Jease$.$InCubic,$Opacity:$Jease$.$Linear},$Opacity:2,$Outside:true}', 'name' => 'Fade Fly in L' ),
            21 =>array( 'value' => '{$Duration:800,x:-0.3,$During:{$Left:[0.3,0.7]},$Easing:{$Left:$Jease$.$InCubic,$Opacity:$Jease$.$Linear},$Opacity:2,$Outside:true}', 'name' => 'Fade Fly in R' ),
            22 =>array( 'value' => '{$Duration:800,y:0.3,$During:{$Top:[0.3,0.7]},$Easing:{$Top:$Jease$.$InCubic,$Opacity:$Jease$.$Linear},$Opacity:2,$Outside:true}', 'name' => 'Fade Fly in T' ),
            23 =>array( 'value' => '{$Duration:800,y:-0.3,$During:{$Top:[0.3,0.7]},$Easing:{$Top:$Jease$.$InCubic,$Opacity:$Jease$.$Linear},$Opacity:2,$Outside:true}', 'name' => 'Fade Fly in B' ),
            24 =>array( 'value' => '{$Duration:800,x:0.3,$Cols:2,$During:{$Left:[0.3,0.7]},$ChessMode:{$Column:3},$Easing:{$Left:$Jease$.$InCubic,$Opacity:$Jease$.$Linear},$Opacity:2,$Outside:true}', 'name' => 'Fade Fly in LR' ),
            25 =>array( 'value' => '{$Duration:800,y:0.3,$Cols:2,$During:{$Top:[0.3,0.7]},$ChessMode:{$Column:12},$Easing:{$Top:$Jease$.$InCubic,$Opacity:$Jease$.$Linear},$Opacity:2,$Outside:true}', 'name' => 'Fade Fly in LR Chess' ),
            26 =>array( 'value' => '{$Duration:800,y:0.3,$Rows:2,$During:{$Top:[0.3,0.7]},$ChessMode:{$Row:12},$Easing:{$Top:$Jease$.$InCubic,$Opacity:$Jease$.$Linear},$Opacity:2,$Outside:true}', 'name' => 'Fade Fly in TB' ),
            27 =>array( 'value' => '{$Duration:800,x:0.3,$Rows:2,$During:{$Left:[0.3,0.7]},$ChessMode:{$Row:3},$Easing:{$Left:$Jease$.$InCubic,$Opacity:$Jease$.$Linear},$Opacity:2,$Outside:true}', 'name' => 'Fade Fly in TB Chess' ),
            28 =>array( 'value' => '{$Duration:800,x:0.3,y:0.3,$Cols:2,$Rows:2,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$ChessMode:{$Column:3,$Row:12},$Easing:{$Left:$Jease$.$InCubic,$Top:$Jease$.$InCubic,$Opacity:$Jease$.$Linear},$Opacity:2,$Outside:true}', 'name' => 'Fade Fly in Corners' ),
            29 =>array( 'value' => '{$Duration:800,x:0.3,$SlideOut:true,$Easing:{$Left:$Jease$.$InCubic,$Opacity:$Jease$.$Linear},$Opacity:2,$Outside:true}', 'name' => 'Fade Fly out L' ),
            30 =>array( 'value' => '{$Duration:800,x:-0.3,$SlideOut:true,$Easing:{$Left:$Jease$.$InCubic,$Opacity:$Jease$.$Linear},$Opacity:2,$Outside:true}', 'name' => 'Fade Fly out R' ),
            31 =>array( 'value' => '{$Duration:800,y:0.3,$SlideOut:true,$Easing:{$Top:$Jease$.$InCubic,$Opacity:$Jease$.$Linear},$Opacity:2,$Outside:true}', 'name' => 'Fade Fly out T' ),
            32 =>array( 'value' => '{$Duration:800,y:-0.3,$SlideOut:true,$Easing:{$Top:$Jease$.$InCubic,$Opacity:$Jease$.$Linear},$Opacity:2,$Outside:true}', 'name' => 'Fade Fly out B' ),
            33 =>array( 'value' => '{$Duration:800,x:0.3,$Cols:2,$SlideOut:true,$ChessMode:{$Column:3},$Easing:{$Left:$Jease$.$InCubic,$Opacity:$Jease$.$Linear},$Opacity:2,$Outside:true}', 'name' => 'Fade Fly out LR' ),
            34 =>array( 'value' => '{$Duration:800,y:0.3,$Cols:2,$SlideOut:true,$ChessMode:{$Column:12},$Easing:{$Top:$Jease$.$InCubic,$Opacity:$Jease$.$Linear},$Opacity:2,$Outside:true}', 'name' => 'Fade Fly out LR Chess' ),
            35 =>array( 'value' => '{$Duration:800,y:0.3,$Rows:2,$SlideOut:true,$ChessMode:{$Row:12},$Easing:{$Top:$Jease$.$InCubic,$Opacity:$Jease$.$Linear},$Opacity:2,$Outside:true}', 'name' => 'Fade Fly out TB' ),
            36 =>array( 'value' => '{$Duration:800,x:0.3,$Rows:2,$SlideOut:true,$ChessMode:{$Row:3},$Easing:{$Left:$Jease$.$InCubic,$Opacity:$Jease$.$Linear},$Opacity:2,$Outside:true}', 'name' => 'Fade Fly out TB Chess' ),
            37 =>array( 'value' => '{$Duration:800,x:0.3,y:0.3,$Cols:2,$Rows:2,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$SlideOut:true,$ChessMode:{$Column:3,$Row:12},$Easing:{$Left:$Jease$.$InCubic,$Top:$Jease$.$InCubic,$Opacity:$Jease$.$Linear},$Opacity:2,$Outside:true}', 'name' => 'Fade Fly out Corners' ),
            38 =>array( 'value' => '{$Duration:800,$Delay:20,$Clip:3,$Assembly:260,$Easing:{$Clip:$Jease$.$InCubic,$Opacity:$Jease$.$Linear},$Opacity:2}', 'name' => 'Fade Clip in H' ),
            39 =>array( 'value' => '{$Duration:800,$Delay:20,$Clip:12,$Assembly:260,$Easing:{$Clip:$Jease$.$InCubic,$Opacity:$Jease$.$Linear},$Opacity:2}', 'name' => 'Fade Clip in V' ),
            40 =>array( 'value' => '{$Duration:800,$Delay:20,$Clip:3,$SlideOut:true,$Assembly:260,$Easing:{$Clip:$Jease$.$OutCubic,$Opacity:$Jease$.$Linear},$Opacity:2}', 'name' => 'Fade Clip out H' ),
            41 =>array( 'value' => '{$Duration:800,$Delay:20,$Clip:12,$SlideOut:true,$Assembly:260,$Easing:{$Clip:$Jease$.$OutCubic,$Opacity:$Jease$.$Linear},$Opacity:2}', 'name' => 'Fade Clip out V' ),
            42 =>array( 'value' => '{$Duration:600,$Delay:20,$Cols:8,$Rows:4,$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Assembly:2050,$Opacity:2}', 'name' => 'Fade Stairs' ),
            43 =>array( 'value' => '{$Duration:600,$Delay:60,$Cols:8,$Rows:4,$Opacity:2}', 'name' => 'Fade Random' ),
            44 =>array( 'value' => '{$Duration:600,$Delay:20,$Cols:8,$Rows:4,$Formation:$JssorSlideshowFormations$.$FormationSwirl,$Opacity:2}', 'name' => 'Fade Swirl' ),
            45 =>array( 'value' => '{$Duration:600,$Delay:20,$Cols:8,$Rows:4,$Formation:$JssorSlideshowFormations$.$FormationZigZag,$Assembly:260,$Opacity:2}', 'name' => 'Fade ZigZag' ),
            // 46 =>array( 'value' => '', 'name' => '----- Twins Transitions -----' ),
            47 =>array( 'value' => '{$Duration:700,$Opacity:2,$Brother:{$Duration:700,$Opacity:2}}', 'name' => 'Fade Twins' ),
            48 =>array( 'value' => '{$Duration:800,$Zoom:1,$Rotate:0.5,$Easing:{$Rotate:$Jease$.$InSine,$Zoom:$Jease$.$Swing},$Opacity:2,$Brother:{$Duration:800,$Zoom:11,$Rotate:-0.5,$Easing:{$Rotate:$Jease$.$InSine,$Zoom:$Jease$.$Swing},$Opacity:2,$Shift:200}}', 'name' => 'Rotate away' ),
            49 =>array( 'value' => '{$Duration:800,$Zoom:1,$Rotate:-0.5,$Easing:{$Rotate:$Jease$.$InSine,$Zoom:$Jease$.$Swing},$Opacity:2,$Brother:{$Duration:800,$Zoom:11,$Rotate:0.5,$Easing:{$Rotate:$Jease$.$InSine,$Zoom:$Jease$.$Swing},$Opacity:2,$Shift:200}}', 'name' => 'Rotate away acw' ),
            50 =>array( 'value' => '{$Duration:800,$Zoom:11,$Rotate:0.5,$Easing:{$Rotate:$Jease$.$InSine,$Zoom:$Jease$.$Swing},$Opacity:2,$Brother:{$Duration:800,$Zoom:1,$Rotate:-0.5,$Easing:{$Rotate:$Jease$.$InSine,$Zoom:$Jease$.$Swing},$Opacity:2,$Shift:200}}', 'name' => 'Rotate back' ),
            51 =>array( 'value' => '{$Duration:800,$Zoom:11,$Rotate:-0.5,$Easing:{$Rotate:$Jease$.$InSine,$Zoom:$Jease$.$Swing},$Opacity:2,$Brother:{$Duration:800,$Zoom:1,$Rotate:0.5,$Easing:{$Rotate:$Jease$.$InSine,$Zoom:$Jease$.$Swing},$Opacity:2,$Shift:200}}', 'name' => 'Rotate back acw' ),
            52 =>array( 'value' => '{$Duration:800,x:0.25,$Zoom:1.5,$Easing:{$Left:$Jease$.$InWave,$Zoom:$Jease$.$InCubic},$Opacity:2,$ZIndex:-10,$Brother:{$Duration:800,x:-0.25,$Zoom:1.5,$Easing:{$Left:$Jease$.$InWave,$Zoom:$Jease$.$InCubic},$Opacity:2,$ZIndex:-10}}', 'name' => 'Switch' ),
            53 =>array( 'value' => '{$Duration:800,$Zoom:11,$Rotate:1,$Easing:{$Opacity:$Jease$.$Linear,$Rotate:$Jease$.$InQuad,$Zoom:$Jease$.$Swing},$Opacity:2,$Round:{$Rotate:1},$ZIndex:-10,$Brother:{$Duration:800,$Zoom:11,$Rotate:-1,$Easing:{$Opacity:$Jease$.$Linear,$Rotate:$Jease$.$InQuad,$Zoom:$Jease$.$Swing},$Opacity:2,$Round:{$Rotate:1},$ZIndex:-10,$Shift:400}}', 'name' => 'Rotate Relay' ),
            54 =>array( 'value' => '{$Duration:800,$Zoom:11,$Rotate:-1,$Easing:{$Opacity:$Jease$.$Linear,$Rotate:$Jease$.$InQuad,$Zoom:$Jease$.$Swing},$Opacity:2,$Round:{$Rotate:1},$ZIndex:-10,$Brother:{$Duration:800,$Zoom:11,$Rotate:1,$Easing:{$Opacity:$Jease$.$Linear,$Rotate:$Jease$.$InQuad,$Zoom:$Jease$.$Swing},$Opacity:2,$Round:{$Rotate:1},$ZIndex:-10,$Shift:400}}', 'name' => 'Rotate Relay acw' ),
            55 =>array( 'value' => '{$Duration:1200,x:0.5,$Cols:2,$ChessMode:{$Column:3},$Easing:{$Left:$Jease$.$InOutCubic},$Opacity:2,$Brother:{$Duration:1200,$Opacity:2}}', 'name' => 'Doors' ),
            56 =>array( 'value' => '{$Duration:1200,$Opacity:2,$Brother:{$Duration:1200,x:0.5,$Cols:2,$ChessMode:{$Column:3},$Easing:{$Left:$Jease$.$InOutCubic},$Opacity:2}}', 'name' => 'Doors close' ),
            57 =>array( 'value' => '{$Duration:1000,x:-0.3,y:0.5,$Zoom:1,$Rotate:0.1,$During:{$Left:[0.6,0.4],$Top:[0.6,0.4],$Rotate:[0.6,0.4],$Zoom:[0.6,0.4]},$Easing:{$Left:$Jease$.$InSine,$Top:$Jease$.$InSine,$Rotate:$Jease$.$InSine,$Zoom:$Jease$.$InSine},$Opacity:2,$Brother:{$Duration:600,$Zoom:11,$Rotate:-0.5,$Easing:{$Rotate:$Jease$.$InSine,$Zoom:$Jease$.$InSine},$Opacity:2}}', 'name' => 'Rotate in+ out-' ),
            58 =>array( 'value' => '{$Duration:1000,x:-0.6,y:1,$Zoom:11,$Rotate:0.1,$During:{$Left:[0.6,0.4],$Top:[0.6,0.4],$Rotate:[0.6,0.4],$Zoom:[0.6,0.4]},$Easing:{$Left:$Jease$.$InSine,$Top:$Jease$.$InSine,$Rotate:$Jease$.$InSine,$Zoom:$Jease$.$InSine},$Opacity:2,$Brother:{$Duration:600,$Zoom:1,$Rotate:-0.5,$Easing:{$Rotate:$Jease$.$InCubic,$Zoom:$Jease$.$InSine},$Opacity:2}}', 'name' => 'Rotate in- ou+' ),
            59 =>array( 'value' => '{$Duration:600,x:0.3,$During:{$Left:[0.6,0.4]},$Easing:{$Left:$Jease$.$InCubic,$Opacity:$Jease$.$Linear},$Opacity:2,$Brother:{$Duration:600,x:-0.3,$Easing:{$Left:$Jease$.$InCubic,$Opacity:$Jease$.$Linear},$Opacity:2}}', 'name' => 'Fly Twins' ),
            60 =>array( 'value' => '{$Duration:1000,x:1,$Rows:2,$ChessMode:{$Row:3},$Easing:{$Left:$Jease$.$InOutQuart,$Opacity:$Jease$.$Linear},$Opacity:2,$Brother:{$Duration:1000,x:-1,$Rows:2,$ChessMode:{$Row:3},$Easing:{$Left:$Jease$.$InOutQuart,$Opacity:$Jease$.$Linear},$Opacity:2}}', 'name' => 'Chess Replace TB' ),
            61 =>array( 'value' => '{$Duration:1000,y:-1,$Cols:2,$ChessMode:{$Column:12},$Easing:{$Top:$Jease$.$InOutQuart,$Opacity:$Jease$.$Linear},$Opacity:2,$Brother:{$Duration:1000,y:1,$Cols:2,$ChessMode:{$Column:12},$Easing:{$Top:$Jease$.$InOutQuart,$Opacity:$Jease$.$Linear},$Opacity:2}}', 'name' => 'Chess Replace LR' ),
            62 =>array( 'value' => '{$Duration:800,$Zoom:11,$Easing:{$Zoom:$Jease$.$InOutExpo},$Opacity:2,$Brother:{$Duration:600,$Zoom:1.5,$Easing:{$Zoom:$Jease$.$InOutExpo},$Opacity:2,$Shift:-100}}', 'name' => 'Zoom back' ),
            63 =>array( 'value' => '{$Duration:800,$Zoom:1.9,$Easing:{$Zoom:$Jease$.$InOutExpo},$Opacity:2,$Brother:{$Duration:600,$Zoom:11,$Easing:{$Zoom:$Jease$.$InOutExpo},$Opacity:2,$Shift:-100}}', 'name' => 'Zoom away' ),
            64 =>array( 'value' => '{$Duration:800,$Zoom:11,$Easing:{$Zoom:$Jease$.$InOutExpo},$Opacity:2,$Brother:{$Duration:600,$Zoom:11,$Easing:{$Zoom:$Jease$.$InOutExpo},$Opacity:2,$Shift:-100}}', 'name' => 'Zoom return' ),
            65 =>array( 'value' => '{$Duration:800,y:1,$Easing:{$Top:$Jease$.$InOutQuart,$Opacity:$Jease$.$Linear},$Opacity:2,$Brother:{$Duration:800,y:-1,$Easing:{$Top:$Jease$.$InOutQuart,$Opacity:$Jease$.$Linear},$Opacity:2}}', 'name' => 'Shift TB' ),
            66 =>array( 'value' => '{$Duration:800,x:1,$Easing:{$Left:$Jease$.$InOutQuart,$Opacity:$Jease$.$Linear},$Opacity:2,$Brother:{$Duration:800,x:-1,$Easing:{$Left:$Jease$.$InOutQuart,$Opacity:$Jease$.$Linear},$Opacity:2}}', 'name' => 'Shift LR' ),
            67 =>array( 'value' => '{$Duration:800,y:-1,$Easing:{$Top:$Jease$.$InOutQuart,$Opacity:$Jease$.$Linear},$Opacity:2,$ZIndex:-10,$Brother:{$Duration:800,y:-1,$Easing:{$Top:$Jease$.$InOutQuart,$Opacity:$Jease$.$Linear},$Opacity:2,$ZIndex:-10,$Shift:-100}}', 'name' => 'Return TB' ),
            68 =>array( 'value' => '{$Duration:800,x:1,$Delay:40,$Cols:6,$Formation:$JssorSlideshowFormations$.$FormationStraight,$Easing:{$Left:$Jease$.$InOutQuart,$Opacity:$Jease$.$Linear},$Opacity:2,$ZIndex:-10,$Brother:{$Duration:800,x:1,$Delay:40,$Cols:6,$Formation:$JssorSlideshowFormations$.$FormationStraight,$Easing:{$Left:$Jease$.$InOutQuart,$Opacity:$Jease$.$Linear},$Opacity:2,$ZIndex:-10,$Shift:-60}}', 'name' => 'Return LR' ),
            69 =>array( 'value' => '{$Duration:800,x:0.25,y:0.5,$Rotate:-0.1,$Easing:{$Left:$Jease$.$InQuad,$Top:$Jease$.$InQuad,$Opacity:$Jease$.$Linear,$Rotate:$Jease$.$InQuad},$Opacity:2,$Brother:{$Duration:800,x:-0.1,y:-0.7,$Rotate:0.1,$Easing:{$Left:$Jease$.$InQuad,$Top:$Jease$.$InQuad,$Opacity:$Jease$.$Linear,$Rotate:$Jease$.$InQuad},$Opacity:2}}', 'name' => 'Rotate Axis up' ),
            70 =>array( 'value' => '{$Duration:1000,x:-0.1,y:-0.7,$Rotate:0.1,$During:{$Left:[0.6,0.4],$Top:[0.6,0.4],$Rotate:[0.6,0.4]},$Easing:{$Left:$Jease$.$InQuad,$Top:$Jease$.$InQuad,$Opacity:$Jease$.$Linear,$Rotate:$Jease$.$InQuad},$Opacity:2,$Brother:{$Duration:1000,x:0.2,y:0.5,$Rotate:-0.1,$Easing:{$Left:$Jease$.$InQuad,$Top:$Jease$.$InQuad,$Opacity:$Jease$.$Linear,$Rotate:$Jease$.$InQuad},$Opacity:2}}', 'name' => 'Rotate Axis down' ),
            71 =>array( 'value' => '{$Duration:800,x:-0.2,$Delay:40,$Cols:12,$During:{$Left:[0.4,0.6]},$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationStraight,$Assembly:260,$Easing:{$Left:$Jease$.$InOutExpo,$Opacity:$Jease$.$InOutQuad},$Opacity:2,$Outside:true,$Round:{$Top:0.5},$Brother:{$Duration:800,x:0.2,$Delay:40,$Cols:12,$Formation:$JssorSlideshowFormations$.$FormationStraight,$Assembly:1028,$Easing:{$Left:$Jease$.$InOutExpo,$Opacity:$Jease$.$InOutQuad},$Opacity:2,$Round:{$Top:0.5},$Shift:-200}}', 'name' => 'Extrude Replace' ),
            72 =>array( 'value' => '{$Duration:800,x:0.2,$Delay:40,$Cols:12,$During:{$Left:[0.4,0.6]},$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationStraight,$Easing:{$Left:$Jease$.$InOutExpo,$Opacity:$Jease$.$InOutQuad},$Opacity:2,$Outside:true,$Round:{$Top:0.5},$Brother:{$Duration:800,x:0.2,$Delay:40,$Cols:12,$Formation:$JssorSlideshowFormations$.$FormationStraight,$Assembly:1028,$Easing:{$Left:$Jease$.$InOutExpo,$Opacity:$Jease$.$InOutQuad},$Opacity:2,$Round:{$Top:0.5},$Shift:-200}}', 'name' => 'Extrude Return' ),
            // 73 =>array( 'value' => '', 'name' => '----- Rotate Transitions -----' ),
            74 =>array( 'value' => '{$Duration:1200,x:-1,y:2,$Rows:2,$Zoom:11,$Rotate:1,$Assembly:2049,$ChessMode:{$Row:15},$Easing:{$Left:$Jease$.$InCubic,$Top:$Jease$.$InCubic,$Zoom:$Jease$.$InCubic,$Opacity:$Jease$.$OutQuad,$Rotate:$Jease$.$InCubic},$Opacity:2,$Round:{$Rotate:0.8}}', 'name' => 'Rotate VDouble+ in' ),
            75 =>array( 'value' => '{$Duration:1200,x:2,y:1,$Cols:2,$Zoom:11,$Rotate:1,$Assembly:2049,$ChessMode:{$Column:15},$Easing:{$Left:$Jease$.$InCubic,$Top:$Jease$.$InCubic,$Zoom:$Jease$.$InCubic,$Opacity:$Jease$.$OutQuad,$Rotate:$Jease$.$InCubic},$Opacity:2,$Round:{$Rotate:0.7}}', 'name' => 'Rotate HDouble+ in' ),
            76 =>array( 'value' => '{$Duration:1200,x:-0.5,y:1,$Rows:2,$Zoom:1,$Rotate:1,$Assembly:2049,$ChessMode:{$Row:15},$Easing:{$Left:$Jease$.$InCubic,$Top:$Jease$.$InCubic,$Zoom:$Jease$.$InCubic,$Opacity:$Jease$.$OutQuad,$Rotate:$Jease$.$InCubic},$Opacity:2,$Round:{$Rotate:0.7}}', 'name' => 'Rotate VDouble- in' ),
            77 =>array( 'value' => '{$Duration:1200,x:0.5,y:0.3,$Cols:2,$Zoom:1,$Rotate:1,$Assembly:2049,$ChessMode:{$Column:15},$Easing:{$Left:$Jease$.$InCubic,$Top:$Jease$.$InCubic,$Zoom:$Jease$.$InCubic,$Opacity:$Jease$.$OutQuad,$Rotate:$Jease$.$InCubic},$Opacity:2,$Round:{$Rotate:0.7}}', 'name' => 'Rotate HDouble- in' ),
            78 =>array( 'value' => '{$Duration:1000,x:-1,y:2,$Rows:2,$Zoom:11,$Rotate:1,$SlideOut:true,$Assembly:2049,$ChessMode:{$Row:15},$Easing:{$Left:$Jease$.$InExpo,$Top:$Jease$.$InExpo,$Zoom:$Jease$.$InExpo,$Opacity:$Jease$.$Linear,$Rotate:$Jease$.$InExpo},$Opacity:2,$Round:{$Rotate:0.85}}', 'name' => 'Rotate VDouble+ out' ),
            79 =>array( 'value' => '{$Duration:1000,x:4,y:2,$Cols:2,$Zoom:11,$Rotate:1,$SlideOut:true,$Assembly:2049,$ChessMode:{$Column:15},$Easing:{$Left:$Jease$.$InExpo,$Top:$Jease$.$InExpo,$Zoom:$Jease$.$InExpo,$Opacity:$Jease$.$Linear,$Rotate:$Jease$.$InExpo},$Opacity:2,$Round:{$Rotate:0.8}}', 'name' => 'Rotate HDouble+ out' ),
            80 =>array( 'value' => '{$Duration:1000,x:-0.5,y:1,$Rows:2,$Zoom:1,$Rotate:1,$SlideOut:true,$Assembly:2049,$ChessMode:{$Row:15},$Easing:{$Left:$Jease$.$InExpo,$Top:$Jease$.$InExpo,$Zoom:$Jease$.$InExpo,$Opacity:$Jease$.$Linear,$Rotate:$Jease$.$InExpo},$Opacity:2,$Round:{$Rotate:0.7}}', 'name' => 'Rotate VDouble- out' ),
            81 =>array( 'value' => '{$Duration:1000,x:0.5,y:0.3,$Cols:2,$Zoom:1,$Rotate:1,$SlideOut:true,$Assembly:2049,$ChessMode:{$Column:15},$Easing:{$Left:$Jease$.$InExpo,$Top:$Jease$.$InExpo,$Zoom:$Jease$.$InExpo,$Opacity:$Jease$.$Linear,$Rotate:$Jease$.$InExpo},$Opacity:2,$Round:{$Rotate:0.7}}', 'name' => 'Rotate HDouble- out' ),
            82 =>array( 'value' => '{$Duration:1200,x:-4,y:2,$Rows:2,$Zoom:11,$Rotate:1,$Assembly:2049,$ChessMode:{$Row:28},$Easing:{$Left:$Jease$.$InCubic,$Top:$Jease$.$InCubic,$Zoom:$Jease$.$InCubic,$Opacity:$Jease$.$OutQuad,$Rotate:$Jease$.$InCubic},$Opacity:2,$Round:{$Rotate:0.7}}', 'name' => 'Rotate VFork+ in' ),
            83 =>array( 'value' => '{$Duration:1200,x:1,y:2,$Cols:2,$Zoom:11,$Rotate:1,$Assembly:2049,$ChessMode:{$Column:19},$Easing:{$Left:$Jease$.$InCubic,$Top:$Jease$.$InCubic,$Zoom:$Jease$.$InCubic,$Opacity:$Jease$.$OutQuad,$Rotate:$Jease$.$InCubic},$Opacity:2,$Round:{$Rotate:0.8}}', 'name' => 'Rotate HFork+ in' ),
            84 =>array( 'value' => '{$Duration:1000,x:-3,y:1,$Rows:2,$Zoom:11,$Rotate:1,$SlideOut:true,$Assembly:2049,$ChessMode:{$Row:28},$Easing:{$Left:$Jease$.$InExpo,$Top:$Jease$.$InExpo,$Zoom:$Jease$.$InExpo,$Opacity:$Jease$.$Linear,$Rotate:$Jease$.$InExpo},$Opacity:2,$Round:{$Rotate:0.7}}', 'name' => 'Rotate VFork+ out' ),
            85 =>array( 'value' => '{$Duration:1000,x:1,y:2,$Cols:2,$Zoom:11,$Rotate:1,$SlideOut:true,$Assembly:2049,$ChessMode:{$Column:19},$Easing:{$Left:$Jease$.$InExpo,$Top:$Jease$.$InExpo,$Zoom:$Jease$.$InExpo,$Opacity:$Jease$.$Linear,$Rotate:$Jease$.$InExpo},$Opacity:2,$Round:{$Rotate:0.8}}', 'name' => 'Rotate HFork+ out' ),
            86 =>array( 'value' => '{$Duration:1200,$Zoom:11,$Rotate:1,$Easing:{$Zoom:$Jease$.$InCubic,$Opacity:$Jease$.$OutQuad,$Rotate:$Jease$.$InCubic},$Opacity:2,$Round:{$Rotate:0.7}}', 'name' => 'Rotate Zoom+ in' ),
            87 =>array( 'value' => '{$Duration:1200,x:4,$Zoom:11,$Rotate:1,$Easing:{$Left:$Jease$.$InCubic,$Zoom:$Jease$.$InCubic,$Opacity:$Jease$.$OutQuad,$Rotate:$Jease$.$InCubic},$Opacity:2,$Round:{$Rotate:0.7}}', 'name' => 'Rotate Zoom+ in L' ),
            88 =>array( 'value' => '{$Duration:1200,x:-4,$Zoom:11,$Rotate:1,$Easing:{$Left:$Jease$.$InCubic,$Zoom:$Jease$.$InCubic,$Opacity:$Jease$.$OutQuad,$Rotate:$Jease$.$InCubic},$Opacity:2,$Round:{$Rotate:0.7}}', 'name' => 'Rotate Zoom+ in R' ),
            89 =>array( 'value' => '{$Duration:1200,y:4,$Zoom:11,$Rotate:1,$Easing:{$Top:$Jease$.$InCubic,$Zoom:$Jease$.$InCubic,$Opacity:$Jease$.$OutQuad,$Rotate:$Jease$.$InCubic},$Opacity:2,$Round:{$Rotate:0.7}}', 'name' => 'Rotate Zoom+ in T' ),
            90 =>array( 'value' => '{$Duration:1200,y:-4,$Zoom:11,$Rotate:1,$Easing:{$Top:$Jease$.$InCubic,$Zoom:$Jease$.$InCubic,$Opacity:$Jease$.$OutQuad,$Rotate:$Jease$.$InCubic},$Opacity:2,$Round:{$Rotate:0.7}}', 'name' => 'Rotate Zoom+ in B' ),
            91 =>array( 'value' => '{$Duration:1200,x:4,y:4,$Zoom:11,$Rotate:1,$Easing:{$Left:$Jease$.$InCubic,$Top:$Jease$.$InCubic,$Zoom:$Jease$.$InCubic,$Opacity:$Jease$.$OutQuad,$Rotate:$Jease$.$InCubic},$Opacity:2,$Round:{$Rotate:0.7}}', 'name' => 'Rotate Zoom+ in TL' ),
            92 =>array( 'value' => '{$Duration:1200,x:-4,y:4,$Zoom:11,$Rotate:1,$Easing:{$Left:$Jease$.$InCubic,$Top:$Jease$.$InCubic,$Zoom:$Jease$.$InCubic,$Opacity:$Jease$.$OutQuad,$Rotate:$Jease$.$InCubic},$Opacity:2,$Round:{$Rotate:0.7}}', 'name' => 'Rotate Zoom+ in TR' ),
            93 =>array( 'value' => '{$Duration:1200,x:4,y:-4,$Zoom:11,$Rotate:1,$Easing:{$Left:$Jease$.$InCubic,$Top:$Jease$.$InCubic,$Zoom:$Jease$.$InCubic,$Opacity:$Jease$.$OutQuad,$Rotate:$Jease$.$InCubic},$Opacity:2,$Round:{$Rotate:0.7}}', 'name' => 'Rotate Zoom+ in BL' ),
            94 =>array( 'value' => '{$Duration:1200,x:-4,y:-4,$Zoom:11,$Rotate:1,$Easing:{$Left:$Jease$.$InCubic,$Top:$Jease$.$InCubic,$Zoom:$Jease$.$InCubic,$Opacity:$Jease$.$OutQuad,$Rotate:$Jease$.$InCubic},$Opacity:2,$Round:{$Rotate:0.7}}', 'name' => 'Rotate Zoom+ in BR' ),
            95 =>array( 'value' => '{$Duration:1000,$Zoom:11,$Rotate:1,$SlideOut:true,$Easing:{$Zoom:$Jease$.$InQuint,$Opacity:$Jease$.$Linear,$Rotate:$Jease$.$InQuint},$Opacity:2,$Round:{$Rotate:0.8}}', 'name' => 'Rotate Zoom+ out' ),
            96 =>array( 'value' => '{$Duration:1000,x:4,$Zoom:11,$Rotate:1,$SlideOut:true,$Easing:{$Left:$Jease$.$InQuint,$Zoom:$Jease$.$InQuart,$Opacity:$Jease$.$Linear,$Rotate:$Jease$.$InQuint},$Opacity:2,$Round:{$Rotate:0.8}}', 'name' => 'Rotate Zoom+ out L' ),
            97 =>array( 'value' => '{$Duration:1000,x:-4,$Zoom:11,$Rotate:1,$SlideOut:true,$Easing:{$Left:$Jease$.$InQuint,$Zoom:$Jease$.$InQuart,$Opacity:$Jease$.$Linear,$Rotate:$Jease$.$InQuint},$Opacity:2,$Round:{$Rotate:0.8}}', 'name' => 'Rotate Zoom+ out R' ),
            98 =>array( 'value' => '{$Duration:1000,y:4,$Zoom:11,$Rotate:1,$SlideOut:true,$Easing:{$Top:$Jease$.$InQuint,$Zoom:$Jease$.$InQuart,$Opacity:$Jease$.$Linear,$Rotate:$Jease$.$InQuint},$Opacity:2,$Round:{$Rotate:0.8}}', 'name' => 'Rotate Zoom+ out T' ),
            99 =>array( 'value' => '{$Duration:1000,y:-4,$Zoom:11,$Rotate:1,$SlideOut:true,$Easing:{$Top:$Jease$.$InQuint,$Zoom:$Jease$.$InQuart,$Opacity:$Jease$.$Linear,$Rotate:$Jease$.$InQuint},$Opacity:2,$Round:{$Rotate:0.8}}', 'name' => 'Rotate Zoom+ out B' ),
            100 =>array( 'value' => '{$Duration:1000,x:4,y:4,$Zoom:11,$Rotate:1,$SlideOut:true,$Easing:{$Left:$Jease$.$InQuint,$Top:$Jease$.$InQuint,$Zoom:$Jease$.$InQuart,$Opacity:$Jease$.$Linear,$Rotate:$Jease$.$InQuint},$Opacity:2,$Round:{$Rotate:0.8}}', 'name' => 'Rotate Zoom+ out TL' ),
            101 =>array( 'value' => '{$Duration:1000,x:-4,y:4,$Zoom:11,$Rotate:1,$SlideOut:true,$Easing:{$Left:$Jease$.$InQuint,$Top:$Jease$.$InQuint,$Zoom:$Jease$.$InQuart,$Opacity:$Jease$.$Linear,$Rotate:$Jease$.$InQuint},$Opacity:2,$Round:{$Rotate:0.8}}', 'name' => 'Rotate Zoom+ out TR' ),
            102 =>array( 'value' => '{$Duration:1000,x:4,y:-4,$Zoom:11,$Rotate:1,$SlideOut:true,$Easing:{$Left:$Jease$.$InQuint,$Top:$Jease$.$InQuint,$Zoom:$Jease$.$InQuart,$Opacity:$Jease$.$Linear,$Rotate:$Jease$.$InQuint},$Opacity:2,$Round:{$Rotate:0.8}}', 'name' => 'Rotate Zoom+ out BL' ),
            103 =>array( 'value' => '{$Duration:1000,x:-4,y:-4,$Zoom:11,$Rotate:1,$SlideOut:true,$Easing:{$Left:$Jease$.$InQuint,$Top:$Jease$.$InQuint,$Zoom:$Jease$.$InQuart,$Opacity:$Jease$.$Linear,$Rotate:$Jease$.$InQuint},$Opacity:2,$Round:{$Rotate:0.8}}', 'name' => 'Rotate Zoom+ out BR' ),
            104 =>array( 'value' => '{$Duration:1200,$Zoom:1,$Rotate:1,$During:{$Zoom:[0.2,0.8],$Rotate:[0.2,0.8]},$Easing:{$Zoom:$Jease$.$Swing,$Opacity:$Jease$.$Linear,$Rotate:$Jease$.$Swing},$Opacity:2,$Round:{$Rotate:0.5}}', 'name' => 'Rotate Zoom- in' ),
            105 =>array( 'value' => '{$Duration:1200,x:0.6,$Zoom:1,$Rotate:1,$During:{$Left:[0.2,0.8],$Zoom:[0.2,0.8],$Rotate:[0.2,0.8]},$Easing:$Jease$.$Swing,$Opacity:2,$Round:{$Rotate:0.5}}', 'name' => 'Rotate Zoom- in L' ),
            106 =>array( 'value' => '{$Duration:1200,x:-0.6,$Zoom:1,$Rotate:1,$During:{$Left:[0.2,0.8],$Zoom:[0.2,0.8],$Rotate:[0.2,0.8]},$Easing:$Jease$.$Swing,$Opacity:2,$Round:{$Rotate:0.5}}', 'name' => 'Rotate Zoom- in R' ),
            107 =>array( 'value' => '{$Duration:1200,y:0.6,$Zoom:1,$Rotate:1,$During:{$Top:[0.2,0.8],$Zoom:[0.2,0.8],$Rotate:[0.2,0.8]},$Easing:$Jease$.$Swing,$Opacity:2,$Round:{$Rotate:0.5}}', 'name' => 'Rotate Zoom- in T' ),
            108 =>array( 'value' => '{$Duration:1200,y:-0.6,$Zoom:1,$Rotate:1,$During:{$Top:[0.2,0.8],$Zoom:[0.2,0.8],$Rotate:[0.2,0.8]},$Easing:$Jease$.$Swing,$Opacity:2,$Round:{$Rotate:0.5}}', 'name' => 'Rotate Zoom- in B' ),
            109 =>array( 'value' => '{$Duration:1200,x:0.6,y:0.6,$Zoom:1,$Rotate:1,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8],$Zoom:[0.2,0.8],$Rotate:[0.2,0.8]},$Easing:$Jease$.$Swing,$Opacity:2,$Round:{$Rotate:0.5}}', 'name' => 'Rotate Zoom- in TL' ),
            110 =>array( 'value' => '{$Duration:1200,x:-0.6,y:0.6,$Zoom:1,$Rotate:1,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8],$Zoom:[0.2,0.8],$Rotate:[0.2,0.8]},$Easing:$Jease$.$Swing,$Opacity:2,$Round:{$Rotate:0.5}}', 'name' => 'Rotate Zoom- in TR' ),
            111 =>array( 'value' => '{$Duration:1200,x:0.6,y:-0.6,$Zoom:1,$Rotate:1,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8],$Zoom:[0.2,0.8],$Rotate:[0.2,0.8]},$Easing:$Jease$.$Swing,$Opacity:2,$Round:{$Rotate:0.5}}', 'name' => 'Rotate Zoom- in BL' ),
            112 =>array( 'value' => '{$Duration:1200,x:-0.6,y:-0.6,$Zoom:1,$Rotate:1,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8],$Zoom:[0.2,0.8],$Rotate:[0.2,0.8]},$Easing:$Jease$.$Swing,$Opacity:2,$Round:{$Rotate:0.5}}', 'name' => 'Rotate Zoom- in BR' ),
            113 =>array( 'value' => '{$Duration:1000,$Zoom:1,$Rotate:1,$SlideOut:true,$Easing:{$Opacity:$Jease$.$Linear},$Opacity:2,$Round:{$Rotate:0.5}}', 'name' => 'Rotate Zoom- out' ),
            114 =>array( 'value' => '{$Duration:1000,x:0.5,$Zoom:1,$Rotate:1,$SlideOut:true,$Easing:{$Left:$Jease$.$InCubic,$Zoom:$Jease$.$InCubic,$Opacity:$Jease$.$Linear,$Rotate:$Jease$.$InCubic},$Opacity:2,$Round:{$Rotate:0.5}}', 'name' => 'Rotate Zoom- out L' ),
            115 =>array( 'value' => '{$Duration:1000,x:-0.5,$Zoom:1,$Rotate:1,$SlideOut:true,$Easing:{$Left:$Jease$.$InCubic,$Zoom:$Jease$.$InCubic,$Opacity:$Jease$.$Linear,$Rotate:$Jease$.$InCubic},$Opacity:2,$Round:{$Rotate:0.5}}', 'name' => 'Rotate Zoom- out R' ),
            116 =>array( 'value' => '{$Duration:1000,y:0.5,$Zoom:1,$Rotate:1,$SlideOut:true,$Easing:{$Top:$Jease$.$InCubic,$Zoom:$Jease$.$InCubic,$Opacity:$Jease$.$Linear,$Rotate:$Jease$.$InCubic},$Opacity:2,$Round:{$Rotate:0.5}}', 'name' => 'Rotate Zoom- out T' ),
            117 =>array( 'value' => '{$Duration:1000,y:-0.5,$Zoom:1,$Rotate:1,$SlideOut:true,$Easing:{$Top:$Jease$.$InCubic,$Zoom:$Jease$.$InCubic,$Opacity:$Jease$.$Linear,$Rotate:$Jease$.$InCubic},$Opacity:2,$Round:{$Rotate:0.5}}', 'name' => 'Rotate Zoom- out B' ),
            118 =>array( 'value' => '{$Duration:1000,x:0.5,y:0.5,$Zoom:1,$Rotate:1,$SlideOut:true,$Easing:{$Left:$Jease$.$InCubic,$Top:$Jease$.$InCubic,$Zoom:$Jease$.$InCubic,$Opacity:$Jease$.$Linear,$Rotate:$Jease$.$InCubic},$Opacity:2,$Round:{$Rotate:0.5}}', 'name' => 'Rotate Zoom- out TL' ),
            119 =>array( 'value' => '{$Duration:1000,x:-0.5,y:0.5,$Zoom:1,$Rotate:1,$SlideOut:true,$Easing:{$Left:$Jease$.$InCubic,$Top:$Jease$.$InCubic,$Zoom:$Jease$.$InCubic,$Opacity:$Jease$.$Linear,$Rotate:$Jease$.$InCubic},$Opacity:2,$Round:{$Rotate:0.5}}', 'name' => 'Rotate Zoom- out TR' ),
            120 =>array( 'value' => '{$Duration:1000,x:0.5,y:-0.5,$Zoom:1,$Rotate:1,$SlideOut:true,$Easing:{$Left:$Jease$.$InCubic,$Top:$Jease$.$InCubic,$Zoom:$Jease$.$InCubic,$Opacity:$Jease$.$Linear,$Rotate:$Jease$.$InCubic},$Opacity:2,$Round:{$Rotate:0.5}}', 'name' => 'Rotate Zoom- out BL' ),
            121 =>array( 'value' => '{$Duration:1000,x:-0.5,y:-0.5,$Zoom:1,$Rotate:1,$SlideOut:true,$Easing:{$Left:$Jease$.$InCubic,$Top:$Jease$.$InCubic,$Zoom:$Jease$.$InCubic,$Opacity:$Jease$.$Linear,$Rotate:$Jease$.$InCubic},$Opacity:2,$Round:{$Rotate:0.5}}', 'name' => 'Rotate Zoom- out BR' ),
            // 122 =>array( 'value' => '', 'name' => '----- Zoom Transitions -----' ),
            123 =>array( 'value' => '{$Duration:1200,y:2,$Rows:2,$Zoom:11,$Assembly:2049,$ChessMode:{$Row:15},$Easing:{$Top:$Jease$.$InCubic,$Zoom:$Jease$.$InCubic,$Opacity:$Jease$.$OutQuad},$Opacity:2}', 'name' => 'Zoom VDouble+ in' ),
            124 =>array( 'value' => '{$Duration:1200,x:4,$Cols:2,$Zoom:11,$Assembly:2049,$ChessMode:{$Column:15},$Easing:{$Left:$Jease$.$InCubic,$Zoom:$Jease$.$InCubic,$Opacity:$Jease$.$OutQuad},$Opacity:2}', 'name' => 'Zoom HDouble+ in' ),
            125 =>array( 'value' => '{$Duration:1200,y:1,$Rows:2,$Zoom:1,$Assembly:2049,$ChessMode:{$Row:15},$Easing:{$Top:$Jease$.$InCubic,$Zoom:$Jease$.$InCubic,$Opacity:$Jease$.$Linear},$Opacity:2}', 'name' => 'Zoom VDouble- in' ),
            126 =>array( 'value' => '{$Duration:1200,x:0.5,$Cols:2,$Zoom:1,$Assembly:2049,$ChessMode:{$Column:15},$Easing:{$Left:$Jease$.$InCubic,$Zoom:$Jease$.$InCubic,$Opacity:$Jease$.$Linear},$Opacity:2}', 'name' => 'Zoom HDouble- in' ),
            127 =>array( 'value' => '{$Duration:1200,y:2,$Rows:2,$Zoom:11,$SlideOut:true,$Assembly:2049,$ChessMode:{$Row:15},$Easing:{$Top:$Jease$.$InExpo,$Zoom:$Jease$.$InExpo,$Opacity:$Jease$.$Linear},$Opacity:2}', 'name' => 'Zoom VDouble+ out' ),
            128 =>array( 'value' => '{$Duration:1200,x:4,$Cols:2,$Zoom:11,$SlideOut:true,$Assembly:2049,$ChessMode:{$Column:15},$Easing:{$Left:$Jease$.$InExpo,$Zoom:$Jease$.$InExpo,$Opacity:$Jease$.$Linear},$Opacity:2}', 'name' => 'Zoom HDouble+ out' ),
            129 =>array( 'value' => '{$Duration:1200,y:1,$Rows:2,$Zoom:1,$SlideOut:true,$Assembly:2049,$ChessMode:{$Row:15},$Easing:{$Top:$Jease$.$InExpo,$Zoom:$Jease$.$InExpo,$Opacity:$Jease$.$Linear},$Opacity:2}', 'name' => 'Zoom VDouble- out' ),
            130 =>array( 'value' => '{$Duration:1200,x:0.5,$Cols:2,$Zoom:1,$SlideOut:true,$Assembly:2049,$ChessMode:{$Column:15},$Easing:{$Left:$Jease$.$InExpo,$Zoom:$Jease$.$InExpo,$Opacity:$Jease$.$Linear},$Opacity:2}', 'name' => 'Zoom HDouble- out' ),
            131 =>array( 'value' => '{$Duration:1000,$Zoom:11,$Easing:{$Zoom:$Jease$.$InCubic,$Opacity:$Jease$.$OutQuad},$Opacity:2}', 'name' => 'Zoom+ in' ),
            132 =>array( 'value' => '{$Duration:1000,x:4,$Zoom:11,$Easing:{$Left:$Jease$.$InCubic,$Zoom:$Jease$.$InCubic,$Opacity:$Jease$.$OutQuad},$Opacity:2}', 'name' => 'Zoom+ in L' ),
            133 =>array( 'value' => '{$Duration:1000,x:-4,$Zoom:11,$Easing:{$Left:$Jease$.$InCubic,$Zoom:$Jease$.$InCubic,$Opacity:$Jease$.$OutQuad},$Opacity:2,$Round:{$Top:2.5}}', 'name' => 'Zoom+ in R' ),
            134 =>array( 'value' => '{$Duration:1000,y:4,$Zoom:11,$Easing:{$Top:$Jease$.$InCubic,$Zoom:$Jease$.$InCubic,$Opacity:$Jease$.$OutQuad},$Opacity:2}', 'name' => 'Zoom+ in T' ),
            135 =>array( 'value' => '{$Duration:1000,y:-4,$Zoom:11,$Easing:{$Top:$Jease$.$InCubic,$Zoom:$Jease$.$InCubic,$Opacity:$Jease$.$OutQuad},$Opacity:2}', 'name' => 'Zoom+ in B' ),
            136 =>array( 'value' => '{$Duration:1000,x:4,y:4,$Zoom:11,$Easing:{$Left:$Jease$.$InCubic,$Top:$Jease$.$InCubic,$Zoom:$Jease$.$InCubic,$Opacity:$Jease$.$OutQuad},$Opacity:2}', 'name' => 'Zoom+ in TL' ),
            137 =>array( 'value' => '{$Duration:1000,x:-4,y:4,$Zoom:11,$Easing:{$Left:$Jease$.$InCubic,$Top:$Jease$.$InCubic,$Zoom:$Jease$.$InCubic,$Opacity:$Jease$.$OutQuad},$Opacity:2}', 'name' => 'Zoom+ in TR' ),
            138 =>array( 'value' => '{$Duration:1000,x:4,y:-4,$Zoom:11,$Easing:{$Left:$Jease$.$InCubic,$Top:$Jease$.$InCubic,$Zoom:$Jease$.$InCubic,$Opacity:$Jease$.$OutQuad},$Opacity:2}', 'name' => 'Zoom+ in BL' ),
            139 =>array( 'value' => '{$Duration:1000,x:-4,y:-4,$Zoom:11,$Easing:{$Left:$Jease$.$InCubic,$Top:$Jease$.$InCubic,$Zoom:$Jease$.$InCubic,$Opacity:$Jease$.$OutQuad},$Opacity:2}', 'name' => 'Zoom+ in BR' ),
            140 =>array( 'value' => '{$Duration:1000,$Zoom:11,$SlideOut:true,$Easing:{$Zoom:$Jease$.$InExpo,$Opacity:$Jease$.$Linear},$Opacity:2}', 'name' => 'Zoom+ out' ),
            141 =>array( 'value' => '{$Duration:1000,x:4,$Zoom:11,$SlideOut:true,$Easing:{$Left:$Jease$.$InExpo,$Zoom:$Jease$.$InExpo,$Opacity:$Jease$.$Linear},$Opacity:2}', 'name' => 'Zoom+ out L' ),
            142 =>array( 'value' => '{$Duration:1000,x:-4,$Zoom:11,$SlideOut:true,$Easing:{$Left:$Jease$.$InExpo,$Zoom:$Jease$.$InExpo,$Opacity:$Jease$.$Linear},$Opacity:2}', 'name' => 'Zoom+ out R' ),
            143 =>array( 'value' => '{$Duration:1000,y:4,$Zoom:11,$SlideOut:true,$Easing:{$Top:$Jease$.$InExpo,$Zoom:$Jease$.$InExpo,$Opacity:$Jease$.$Linear},$Opacity:2}', 'name' => 'Zoom+ out T' ),
            144 =>array( 'value' => '{$Duration:1000,y:-4,$Zoom:11,$SlideOut:true,$Easing:{$Top:$Jease$.$InExpo,$Zoom:$Jease$.$InExpo,$Opacity:$Jease$.$Linear},$Opacity:2}', 'name' => 'Zoom+ out B' ),
            145 =>array( 'value' => '{$Duration:1000,x:4,y:4,$Zoom:11,$SlideOut:true,$Easing:{$Left:$Jease$.$InExpo,$Top:$Jease$.$InExpo,$Zoom:$Jease$.$InExpo,$Opacity:$Jease$.$Linear},$Opacity:2}', 'name' => 'Zoom+ out TL' ),
            146 =>array( 'value' => '{$Duration:1000,x:-4,y:4,$Zoom:11,$SlideOut:true,$Easing:{$Left:$Jease$.$InExpo,$Top:$Jease$.$InExpo,$Zoom:$Jease$.$InExpo,$Opacity:$Jease$.$Linear},$Opacity:2}', 'name' => 'Zoom+ out TR' ),
            147 =>array( 'value' => '{$Duration:1000,x:4,y:-4,$Zoom:11,$SlideOut:true,$Easing:{$Left:$Jease$.$InExpo,$Top:$Jease$.$InExpo,$Zoom:$Jease$.$InExpo,$Opacity:$Jease$.$Linear},$Opacity:2}', 'name' => 'Zoom+ out BL' ),
            148 =>array( 'value' => '{$Duration:1000,x:-4,y:-4,$Zoom:11,$SlideOut:true,$Easing:{$Left:$Jease$.$InExpo,$Top:$Jease$.$InExpo,$Zoom:$Jease$.$InExpo,$Opacity:$Jease$.$Linear},$Opacity:2}', 'name' => 'Zoom+ out BR' ),
            149 =>array( 'value' => '{$Duration:1200,$Zoom:1,$Easing:{$Zoom:$Jease$.$InCubic,$Opacity:$Jease$.$OutQuad},$Opacity:2}', 'name' => 'Zoom- in' ),
            150 =>array( 'value' => '{$Duration:1200,x:0.6,$Zoom:1,$Easing:{$Left:$Jease$.$InCubic,$Zoom:$Jease$.$InCubic,$Opacity:$Jease$.$OutQuad},$Opacity:2}', 'name' => 'Zoom- in L' ),
            151 =>array( 'value' => '{$Duration:1200,x:-0.6,$Zoom:1,$Easing:{$Left:$Jease$.$InCubic,$Zoom:$Jease$.$InCubic,$Opacity:$Jease$.$OutQuad},$Opacity:2}', 'name' => 'Zoom- in R' ),
            152 =>array( 'value' => '{$Duration:1200,y:0.6,$Zoom:1,$Easing:{$Top:$Jease$.$InCubic,$Zoom:$Jease$.$InCubic,$Opacity:$Jease$.$OutQuad},$Opacity:2}', 'name' => 'Zoom- in T' ),
            153 =>array( 'value' => '{$Duration:1200,y:-0.6,$Zoom:1,$Easing:{$Top:$Jease$.$InCubic,$Zoom:$Jease$.$InCubic,$Opacity:$Jease$.$OutQuad},$Opacity:2}', 'name' => 'Zoom- in B' ),
            154 =>array( 'value' => '{$Duration:1200,x:0.6,y:0.6,$Zoom:1,$Easing:{$Left:$Jease$.$InCubic,$Top:$Jease$.$InCubic,$Zoom:$Jease$.$InCubic,$Opacity:$Jease$.$OutQuad},$Opacity:2}', 'name' => 'Zoom- in TL' ),
            155 =>array( 'value' => '{$Duration:1200,x:-0.6,y:0.6,$Zoom:1,$Easing:{$Left:$Jease$.$InCubic,$Top:$Jease$.$InCubic,$Zoom:$Jease$.$InCubic,$Opacity:$Jease$.$OutQuad},$Opacity:2}', 'name' => 'Zoom- in TR' ),
            156 =>array( 'value' => '{$Duration:1200,x:0.6,y:-0.6,$Zoom:1,$Easing:{$Left:$Jease$.$InCubic,$Top:$Jease$.$InCubic,$Zoom:$Jease$.$InCubic,$Opacity:$Jease$.$OutQuad},$Opacity:2}', 'name' => 'Zoom- in BL' ),
            157 =>array( 'value' => '{$Duration:1200,x:-0.6,y:-0.6,$Zoom:1,$Easing:{$Left:$Jease$.$InCubic,$Top:$Jease$.$InCubic,$Zoom:$Jease$.$InCubic,$Opacity:$Jease$.$OutQuad},$Opacity:2}', 'name' => 'Zoom- in BR' ),
            158 =>array( 'value' => '{$Duration:1000,$Zoom:1,$SlideOut:true,$Easing:{$Zoom:$Jease$.$InExpo,$Opacity:$Jease$.$Linear},$Opacity:2}', 'name' => 'Zoom- out' ),
            159 =>array( 'value' => '{$Duration:1000,x:1,$Zoom:1,$SlideOut:true,$Easing:{$Left:$Jease$.$InExpo,$Zoom:$Jease$.$InExpo,$Opacity:$Jease$.$Linear},$Opacity:2}', 'name' => 'Zoom- out L' ),
            160 =>array( 'value' => '{$Duration:1000,x:-1,$Zoom:1,$SlideOut:true,$Easing:{$Left:$Jease$.$InExpo,$Zoom:$Jease$.$InExpo,$Opacity:$Jease$.$Linear},$Opacity:2}', 'name' => 'Zoom- out R' ),
            161 =>array( 'value' => '{$Duration:1000,y:1,$Zoom:1,$SlideOut:true,$Easing:{$Top:$Jease$.$InExpo,$Zoom:$Jease$.$InExpo,$Opacity:$Jease$.$Linear},$Opacity:2}', 'name' => 'Zoom- out T' ),
            162 =>array( 'value' => '{$Duration:1000,y:-1,$Zoom:1,$SlideOut:true,$Easing:{$Top:$Jease$.$InExpo,$Zoom:$Jease$.$InExpo,$Opacity:$Jease$.$Linear},$Opacity:2}', 'name' => 'Zoom- out B' ),
            163 =>array( 'value' => '{$Duration:1000,x:1,y:1,$Zoom:1,$SlideOut:true,$Easing:{$Left:$Jease$.$InExpo,$Top:$Jease$.$InExpo,$Zoom:$Jease$.$InExpo,$Opacity:$Jease$.$Linear},$Opacity:2}', 'name' => 'Zoom- out TL' ),
            164 =>array( 'value' => '{$Duration:1000,x:-1,y:1,$Zoom:1,$SlideOut:true,$Easing:{$Left:$Jease$.$InExpo,$Top:$Jease$.$InExpo,$Zoom:$Jease$.$InExpo,$Opacity:$Jease$.$Linear},$Opacity:2}', 'name' => 'Zoom- out TR' ),
            165 =>array( 'value' => '{$Duration:1000,x:1,y:-1,$Zoom:1,$SlideOut:true,$Easing:{$Left:$Jease$.$InExpo,$Top:$Jease$.$InExpo,$Zoom:$Jease$.$InExpo,$Opacity:$Jease$.$Linear},$Opacity:2}', 'name' => 'Zoom- out BL' ),
            166 =>array( 'value' => '{$Duration:1000,x:-1,y:-1,$Zoom:1,$SlideOut:true,$Easing:{$Left:$Jease$.$InExpo,$Top:$Jease$.$InExpo,$Zoom:$Jease$.$InExpo,$Opacity:$Jease$.$Linear},$Opacity:2}', 'name' => 'Zoom- out BR' ),
            // 167 =>array( 'value' => '', 'name' => '----- Collapse Transitions -----' ),
            168 =>array( 'value' => '{$Duration:500,$Delay:12,$Cols:10,$Rows:5,$Opacity:2,$Clip:15,$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Assembly:2049,$Easing:$Jease$.$OutQuad}', 'name' => 'Collapse Stairs' ),
            169 =>array( 'value' => '{$Duration:500,$Delay:12,$Cols:10,$Rows:5,$Opacity:2,$Clip:15,$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationSwirl,$Easing:$Jease$.$OutQuad}', 'name' => 'Collapse Swirl' ),
            170 =>array( 'value' => '{$Duration:500,$Delay:50,$Cols:10,$Rows:5,$Opacity:2,$Clip:15,$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationRectangleCross,$Easing:$Jease$.$OutQuad}', 'name' => 'Collapse Rectangle Cross' ),
            171 =>array( 'value' => '{$Duration:500,$Delay:50,$Cols:10,$Rows:5,$Opacity:2,$Clip:15,$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationRectangle,$Easing:$Jease$.$OutQuad}', 'name' => 'Collapse Rectangle' ),
            172 =>array( 'value' => '{$Duration:500,$Delay:80,$Cols:10,$Rows:5,$Opacity:2,$Clip:15,$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationCross,$Easing:$Jease$.$OutQuad}', 'name' => 'Collapse Cross' ),
            173 =>array( 'value' => '{$Duration:500,$Delay:50,$Cols:10,$Rows:5,$Opacity:2,$Clip:15,$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationCircle,$Assembly:2049}', 'name' => 'Collapse Circle' ),
            174 =>array( 'value' => '{$Duration:500,$Delay:12,$Cols:10,$Rows:5,$Opacity:2,$Clip:15,$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationZigZag,$Easing:$Jease$.$OutQuad}', 'name' => 'Collapse ZigZag' ),
            175 =>array( 'value' => '{$Duration:500,$Delay:40,$Cols:10,$Rows:5,$Opacity:2,$Clip:15,$SlideOut:true,$Easing:$Jease$.$OutQuad}', 'name' => 'Collapse Random' ),
            // 176 =>array( 'value' => '', 'name' => '----- Expand Transitions -----' ),
            177 =>array( 'value' => '{$Duration:500,$Delay:12,$Cols:10,$Rows:5,$Opacity:2,$Clip:15,$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Assembly:2050,$Easing:{$Clip:$Jease$.$InSine}}', 'name' => 'Expand Stairs' ),
            178 =>array( 'value' => '{$Duration:500,$Delay:12,$Cols:10,$Rows:5,$Opacity:2,$Clip:15,$Formation:$JssorSlideshowFormations$.$FormationSwirl,$Easing:{$Clip:$Jease$.$InSine}}', 'name' => 'Expand Swirl' ),
            179 =>array( 'value' => '{$Duration:500,$Delay:50,$Cols:10,$Rows:5,$Opacity:2,$Clip:15,$Formation:$JssorSlideshowFormations$.$FormationRectangleCross,$Easing:{$Clip:$Jease$.$InSine}}', 'name' => 'Expand Rectangle Cross' ),
            180 =>array( 'value' => '{$Duration:500,$Delay:50,$Cols:10,$Rows:5,$Opacity:2,$Clip:15,$Formation:$JssorSlideshowFormations$.$FormationRectangle,$Easing:{$Clip:$Jease$.$InSine}}', 'name' => 'Expand Rectangle' ),
            181 =>array( 'value' => '{$Duration:500,$Delay:80,$Cols:10,$Rows:5,$Opacity:2,$Clip:15,$Formation:$JssorSlideshowFormations$.$FormationCross,$Easing:{$Clip:$Jease$.$InSine}}', 'name' => 'Expand Cross' ),
            182 =>array( 'value' => '{$Duration:500,$Delay:12,$Cols:10,$Rows:5,$Opacity:2,$Clip:15,$Formation:$JssorSlideshowFormations$.$FormationZigZag,$Assembly:260,$Easing:{$Clip:$Jease$.$InSine}}', 'name' => 'Expand ZigZag' ),
            183 =>array( 'value' => '{$Duration:500,$Delay:40,$Cols:10,$Rows:5,$Opacity:2,$Clip:15,$Easing:$Jease$.$InSine}', 'name' => 'Expand Random' ),
            // 184 =>array( 'value' => '', 'name' => '----- Float Transitions -----' ),
            185 =>array( 'value' => '{$Duration:500,x:-1,$Delay:40,$Cols:10,$Rows:5,$SlideOut:true,$Easing:{$Left:$Jease$.$InCubic,$Opacity:$Jease$.$OutQuad},$Opacity:2}', 'name' => 'Float Right Random' ),
            186 =>array( 'value' => '{$Duration:500,y:1,$Delay:40,$Cols:10,$Rows:5,$SlideOut:true,$Easing:{$Top:$Jease$.$InCubic,$Opacity:$Jease$.$OutQuad},$Opacity:2}', 'name' => 'Float up Random' ),
            187 =>array( 'value' => '{$Duration:500,x:1,y:-1,$Delay:40,$Cols:10,$Rows:5,$SlideOut:true,$ChessMode:{$Column:3,$Row:12},$Easing:{$Left:$Jease$.$InCubic,$Top:$Jease$.$InCubic,$Opacity:$Jease$.$OutQuad},$Opacity:2}', 'name' => 'Float up Random with Chess' ),
            188 =>array( 'value' => '{$Duration:600,x:-1,$Delay:12,$Cols:10,$Rows:5,$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationZigZag,$Assembly:513,$Easing:{$Left:$Jease$.$InCubic,$Opacity:$Jease$.$OutQuad},$Opacity:2}', 'name' => 'Float Right ZigZag' ),
            189 =>array( 'value' => '{$Duration:600,y:1,$Delay:12,$Cols:10,$Rows:5,$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationZigZag,$Assembly:264,$Easing:{$Top:$Jease$.$InCubic,$Opacity:$Jease$.$OutQuad},$Opacity:2}', 'name' => 'Float up ZigZag' ),
            190 =>array( 'value' => '{$Duration:600,x:-1,y:-1,$Delay:12,$Cols:10,$Rows:5,$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationZigZag,$Assembly:1028,$ChessMode:{$Column:3,$Row:12},$Easing:{$Left:$Jease$.$InCubic,$Top:$Jease$.$InCubic,$Opacity:$Jease$.$OutQuad},$Opacity:2}', 'name' => 'Float up ZigZag with Chess' ),
            191 =>array( 'value' => '{$Duration:600,x:-1,$Delay:12,$Cols:10,$Rows:5,$SlideOut:true,$Reverse:true,$Formation:$JssorSlideshowFormations$.$FormationSwirl,$Assembly:513,$Easing:{$Left:$Jease$.$InCubic,$Opacity:$Jease$.$OutQuad},$Opacity:2}', 'name' => 'Float Right Swirl' ),
            192 =>array( 'value' => '{$Duration:600,y:1,$Delay:12,$Cols:10,$Rows:5,$SlideOut:true,$Reverse:true,$Formation:$JssorSlideshowFormations$.$FormationSwirl,$Assembly:2049,$Easing:{$Top:$Jease$.$InCubic,$Opacity:$Jease$.$OutQuad},$Opacity:2}', 'name' => 'Float up Swirl' ),
            193 =>array( 'value' => '{$Duration:600,x:1,y:1,$Delay:12,$Cols:10,$Rows:5,$SlideOut:true,$Reverse:true,$Formation:$JssorSlideshowFormations$.$FormationSwirl,$Assembly:513,$ChessMode:{$Column:3,$Row:12},$Easing:{$Left:$Jease$.$InCubic,$Top:$Jease$.$InCubic,$Opacity:$Jease$.$OutQuad},$Opacity:2}', 'name' => 'Float up Swirl with Chess' ),
            // 194 =>array( 'value' => '', 'name' => '----- Fly Transitions -----' ),
            195 =>array( 'value' => '{$Duration:500,x:1,$Delay:40,$Cols:10,$Rows:5,$Easing:{$Left:$Jease$.$InCubic,$Opacity:$Jease$.$OutQuad},$Opacity:2}', 'name' => 'Fly Right Random' ),
            196 =>array( 'value' => '{$Duration:500,y:-1,$Delay:40,$Cols:10,$Rows:5,$Easing:{$Top:$Jease$.$InCubic,$Opacity:$Jease$.$OutQuad},$Opacity:2}', 'name' => 'Fly up Random' ),
            197 =>array( 'value' => '{$Duration:600,x:-1,y:1,$Delay:40,$Cols:10,$Rows:5,$ChessMode:{$Column:3,$Row:12},$Easing:{$Left:$Jease$.$InCubic,$Top:$Jease$.$InCubic,$Opacity:$Jease$.$OutQuad},$Opacity:2}', 'name' => 'Fly up Random with Chess' ),
            198 =>array( 'value' => '{$Duration:600,x:1,$Delay:12,$Cols:10,$Rows:5,$Formation:$JssorSlideshowFormations$.$FormationZigZag,$Assembly:514,$Easing:{$Left:$Jease$.$InCubic,$Opacity:$Jease$.$OutQuad},$Opacity:2}', 'name' => 'Fly Right ZigZag' ),
            199 =>array( 'value' => '{$Duration:600,y:-1,$Delay:12,$Cols:10,$Rows:5,$Formation:$JssorSlideshowFormations$.$FormationZigZag,$Assembly:260,$Easing:{$Top:$Jease$.$InCubic,$Opacity:$Jease$.$OutQuad},$Opacity:2}', 'name' => 'Fly up ZigZag' ),
            200 =>array( 'value' => '{$Duration:600,x:1,y:1,$Delay:12,$Cols:10,$Rows:5,$Reverse:true,$Formation:$JssorSlideshowFormations$.$FormationZigZag,$Assembly:260,$ChessMode:{$Column:3,$Row:12},$Easing:{$Left:$Jease$.$InCubic,$Top:$Jease$.$InCubic,$Opacity:$Jease$.$OutQuad},$Opacity:2}', 'name' => 'Fly up ZigZag with Chess' ),
            201 =>array( 'value' => '{$Duration:600,x:1,$Delay:12,$Cols:10,$Rows:5,$Reverse:true,$Formation:$JssorSlideshowFormations$.$FormationSwirl,$Assembly:513,$Easing:{$Left:$Jease$.$InCubic,$Opacity:$Jease$.$OutQuad},$Opacity:2}', 'name' => 'Fly Right Swirl' ),
            202 =>array( 'value' => '{$Duration:600,y:-1,$Delay:12,$Cols:10,$Rows:5,$Reverse:true,$Formation:$JssorSlideshowFormations$.$FormationSwirl,$Assembly:2049,$Easing:{$Top:$Jease$.$InCubic,$Opacity:$Jease$.$OutQuad},$Opacity:2}', 'name' => 'Fly up Swirl' ),
            203 =>array( 'value' => '{$Duration:600,x:-1,y:-1,$Delay:12,$Cols:10,$Rows:5,$Reverse:true,$Formation:$JssorSlideshowFormations$.$FormationSwirl,$Assembly:513,$ChessMode:{$Column:3,$Row:12},$Easing:{$Left:$Jease$.$InCubic,$Top:$Jease$.$InCubic,$Opacity:$Jease$.$OutQuad},$Opacity:2}', 'name' => 'Fly up Swirl with Chess' ),
            // 204 =>array( 'value' => '', 'name' => '----- Stripe Transitions -----' ),
            205 =>array( 'value' => '{$Duration:1600,y:-1,$Delay:40,$Cols:24,$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationStraight,$Easing:$Jease$.$OutJump,$Round:{$Top:1.5}}', 'name' => 'Dominoes Stripe' ),
            206 =>array( 'value' => '{$Duration:1000,x:-0.2,$Delay:20,$Cols:16,$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationStraight,$Assembly:260,$Easing:{$Left:$Jease$.$InOutExpo,$Opacity:$Jease$.$InOutQuad},$Opacity:2,$Outside:true,$Round:{$Top:0.5}}', 'name' => 'Extrude out Stripe' ),
            207 =>array( 'value' => '{$Duration:1000,x:0.2,$Delay:20,$Cols:16,$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Assembly:260,$Easing:{$Left:$Jease$.$InOutExpo,$Opacity:$Jease$.$InOutQuad},$Opacity:2,$Outside:true,$Round:{$Top:0.5}}', 'name' => 'Extrude in Stripe' ),
            208 =>array( 'value' => '{$Duration:400,$Delay:50,$Rows:7,$Clip:4,$Formation:$JssorSlideshowFormations$.$FormationStraight}', 'name' => 'Horizontal Blind Stripe' ),
            209 =>array( 'value' => '{$Duration:400,$Delay:50,$Cols:10,$Clip:2,$Formation:$JssorSlideshowFormations$.$FormationStraight}', 'name' => 'Vertical Blind Stripe' ),
            210 =>array( 'value' => '{$Duration:400,$Rows:6,$Clip:4}', 'name' => 'Horizontal Stripe' ),
            211 =>array( 'value' => '{$Duration:400,$Cols:8,$Clip:1}', 'name' => 'Vertical Stripe' ),
            212 =>array( 'value' => '{$Duration:400,$Rows:6,$Clip:4,$Move:true}', 'name' => 'Horizontal Moving Stripe' ),
            213 =>array( 'value' => '{$Duration:400,$Cols:8,$Clip:1,$Move:true}', 'name' => 'Vertical Moving Stripe' ),
            214 =>array( 'value' => '{$Duration:400,$Delay:40,$Rows:10,$Formation:$JssorSlideshowFormations$.$FormationStraight,$Opacity:2,$Assembly:260}', 'name' => 'Horizontal Fade Stripe' ),
            215 =>array( 'value' => '{$Duration:400,$Delay:40,$Rows:10,$Formation:$JssorSlideshowFormations$.$FormationStraight,$Opacity:2}', 'name' => 'Horizontal Fade Stripe Reverse' ),
            216 =>array( 'value' => '{$Duration:400,$Delay:40,$Cols:16,$Formation:$JssorSlideshowFormations$.$FormationStraight,$Opacity:2,$Assembly:260}', 'name' => 'Vertical Fade Stripe' ),
            217 =>array( 'value' => '{$Duration:400,$Delay:40,$Cols:16,$Formation:$JssorSlideshowFormations$.$FormationStraight,$Opacity:2}', 'name' => 'Vertical Fade Stripe Reverse' ),
            218 =>array( 'value' => '{$Duration:600,x:1,$Delay:50,$Rows:8,$Formation:$JssorSlideshowFormations$.$FormationStraight,$Assembly:513,$Easing:{$Left:$Jease$.$InCubic,$Opacity:$Jease$.$OutQuad},$Opacity:2}', 'name' => 'Horizontal Fly Stripe' ),
            219 =>array( 'value' => '{$Duration:600,y:1,$Delay:50,$Cols:12,$Formation:$JssorSlideshowFormations$.$FormationStraight,$Assembly:513,$Easing:{$Top:$Jease$.$InCubic,$Opacity:$Jease$.$OutQuad},$Opacity:2}', 'name' => 'Vertical Fly Stripe' ),
            220 =>array( 'value' => '{$Duration:600,x:-1,$Rows:10,$Opacity:2,$Formation:$JssorSlideshowFormations$.$FormationStraight,$ChessMode:{$Row:3},$Easing:$Jease$.$InCubic}', 'name' => 'Horizontal Chess Stripe' ),
            221 =>array( 'value' => '{$Duration:600,y:-1,$Cols:12,$Opacity:2,$Formation:$JssorSlideshowFormations$.$FormationStraight,$ChessMode:{$Column:12},$Easing:$Jease$.$InCubic}', 'name' => 'Vertical Chess Stripe' ),
            222 =>array( 'value' => '{$Duration:600,$Delay:40,$Rows:10,$Opacity:2}', 'name' => 'Horizontal Random Fade Stripe' ),
            223 =>array( 'value' => '{$Duration:600,$Delay:40,$Cols:16,$Opacity:2}', 'name' => 'Vertical Random Fade Stripe' ),
            224 =>array( 'value' => '{$Duration:600,$Delay:40,$Rows:10,$Clip:8,$Move:true,$Formation:$JssorSlideshowFormations$.$FormationCircle,$Assembly:264,$Easing:$Jease$.$InBounce}', 'name' => 'Horizontal Bounce Stripe' ),
            225 =>array( 'value' => '{$Duration:600,$Delay:40,$Cols:16,$Clip:1,$Move:true,$Formation:$JssorSlideshowFormations$.$FormationCircle,$Assembly:264,$Easing:$Jease$.$InBounce}', 'name' => 'Vertical Bounce Stripe' ),
            // 226 =>array( 'value' => '', 'name' => '----- Parabola Transitions -----' ),
            227 =>array( 'value' => '{$Duration:600,x:-1,y:1,$Delay:50,$Cols:10,$Rows:5,$Opacity:2,$Formation:$JssorSlideshowFormations$.$FormationSwirl,$Assembly:264,$Easing:{$Top:$Jease$.$InQuart,$Opacity:$Jease$.$Linear}}', 'name' => 'Parabola Swirl in' ),
            228 =>array( 'value' => '{$Duration:600,x:-1,y:1,$Delay:50,$Cols:10,$Rows:5,$Opacity:2,$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationSwirl,$Assembly:264,$Easing:{$Top:$Jease$.$InQuart,$Opacity:$Jease$.$Linear}}', 'name' => 'Parabola Swirl out' ),
            229 =>array( 'value' => '{$Duration:600,x:-1,y:1,$Delay:50,$Cols:10,$Rows:5,$Opacity:2,$Formation:$JssorSlideshowFormations$.$FormationZigZag,$Assembly:260,$ChessMode:{$Row:3},$Easing:{$Top:$Jease$.$InQuart,$Opacity:$Jease$.$Linear}}', 'name' => 'Parabola ZigZag in' ),
            230 =>array( 'value' => '{$Duration:600,x:-1,y:1,$Delay:50,$Cols:10,$Rows:5,$Opacity:2,$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationZigZag,$Assembly:260,$ChessMode:{$Row:3},$Easing:{$Top:$Jease$.$InQuart,$Opacity:$Jease$.$Linear}}', 'name' => 'Parabola ZigZag out' ),
            231 =>array( 'value' => '{$Duration:600,x:-1,y:1,$Delay:50,$Cols:10,$Rows:5,$Opacity:2,$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Easing:{$Left:$Jease$.$InQuart,$Top:$Jease$.$InQuart,$Opacity:$Jease$.$Linear}}', 'name' => 'Parabola Stairs in' ),
            232 =>array( 'value' => '{$Duration:600,x:-1,y:1,$Delay:50,$Cols:10,$Rows:5,$Opacity:2,$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Easing:{$Left:$Jease$.$InQuart,$Top:$Jease$.$InQuart,$Opacity:$Jease$.$Linear}}', 'name' => 'Parabola Stairs out' ),
            // 233 =>array( 'value' => '', 'name' => '----- Swing Inside Transitions -----' ),
            234 =>array( 'value' => '{$Duration:1200,x:0.2,y:-0.1,$Delay:16,$Cols:10,$Rows:5,$Opacity:2,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Assembly:260,$Easing:{$Left:$Jease$.$InWave,$Top:$Jease$.$InWave,$Clip:$Jease$.$OutQuad},$Round:{$Left:1.3,$Top:2.5}}', 'name' => 'Swing Inside in Stairs' ),
            235 =>array( 'value' => '{$Duration:1200,x:0.2,y:-0.1,$Delay:12,$Cols:10,$Rows:5,$Opacity:2,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$Formation:$JssorSlideshowFormations$.$FormationZigZag,$Assembly:260,$Easing:{$Left:$Jease$.$InWave,$Top:$Jease$.$InWave,$Clip:$Jease$.$OutQuad},$Round:{$Left:1.3,$Top:2.5}}', 'name' => 'Swing Inside in ZigZag' ),
            236 =>array( 'value' => '{$Duration:1200,x:0.2,y:-0.1,$Delay:12,$Cols:10,$Rows:5,$Opacity:2,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$Formation:$JssorSlideshowFormations$.$FormationSwirl,$Assembly:260,$Easing:{$Left:$Jease$.$InWave,$Top:$Jease$.$InWave,$Clip:$Jease$.$OutQuad},$Round:{$Left:1.3,$Top:2.5}}', 'name' => 'Swing Inside in Swirl' ),
            237 =>array( 'value' => '{$Duration:1200,x:0.2,y:-0.1,$Delay:40,$Cols:10,$Rows:5,$Opacity:2,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$Easing:{$Left:$Jease$.$InWave,$Top:$Jease$.$InWave,$Clip:$Jease$.$OutQuad},$Round:{$Left:1.3,$Top:2.5}}', 'name' => 'Swing Inside in Random' ),
            238 =>array( 'value' => '{$Duration:1200,x:0.2,y:-0.1,$Delay:40,$Cols:10,$Rows:5,$Opacity:2,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$ChessMode:{$Column:3,$Row:3},$Easing:{$Left:$Jease$.$InWave,$Top:$Jease$.$InWave,$Clip:$Jease$.$OutQuad},$Round:{$Left:1.3,$Top:2.5}}', 'name' => 'Swing Inside in Random Chess' ),
            239 =>array( 'value' => '{$Duration:1200,x:0.2,y:-0.1,$Delay:12,$Cols:10,$Rows:5,$Opacity:2,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationZigZag,$Assembly:260,$Easing:{$Left:$Jease$.$InWave,$Top:$Jease$.$InWave,$Clip:$Jease$.$OutQuad},$Round:{$Left:1.3,$Top:2.5}}', 'name' => 'Swing Inside out ZigZag' ),
            240 =>array( 'value' => '{$Duration:1200,x:0.2,y:-0.1,$Delay:12,$Cols:10,$Rows:5,$Opacity:2,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationSwirl,$Assembly:260,$Easing:{$Left:$Jease$.$InWave,$Top:$Jease$.$InWave,$Clip:$Jease$.$OutQuad},$Round:{$Left:1.3,$Top:2.5}}', 'name' => 'Swing Inside out Swirl' ),
            // 241 =>array( 'value' => '', 'name' => '----- Dodge Dance Inside Transitions -----' ),
            242 =>array( 'value' => '{$Duration:1500,x:0.3,y:-0.3,$Delay:20,$Cols:10,$Rows:5,$Opacity:2,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8]},$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Assembly:260,$Easing:{$Left:$Jease$.$InJump,$Top:$Jease$.$InJump,$Clip:$Jease$.$OutQuad},$Round:{$Left:0.8,$Top:2.5}}', 'name' => 'Dodge Dance Inside in Stairs' ),
            243 =>array( 'value' => '{$Duration:1500,x:0.3,y:-0.3,$Delay:20,$Cols:10,$Rows:5,$Opacity:2,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8]},$Formation:$JssorSlideshowFormations$.$FormationSwirl,$Assembly:260,$Easing:{$Left:$Jease$.$InJump,$Top:$Jease$.$InJump,$Clip:$Jease$.$OutQuad},$Round:{$Left:0.8,$Top:2.5}}', 'name' => 'Dodge Dance Inside in Swirl' ),
            244 =>array( 'value' => '{$Duration:1500,x:0.3,y:-0.3,$Delay:20,$Cols:10,$Rows:5,$Opacity:2,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8]},$Formation:$JssorSlideshowFormations$.$FormationZigZag,$Assembly:260,$Easing:{$Left:$Jease$.$InJump,$Top:$Jease$.$InJump,$Clip:$Jease$.$OutQuad},$Round:{$Left:0.8,$Top:2.5}}', 'name' => 'Dodge Dance Inside in ZigZag' ),
            245 =>array( 'value' => '{$Duration:1500,x:0.3,y:-0.3,$Delay:80,$Cols:10,$Rows:5,$Opacity:2,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$Easing:{$Left:$Jease$.$InJump,$Top:$Jease$.$InJump,$Clip:$Jease$.$OutQuad},$Round:{$Left:0.8,$Top:2.5}}', 'name' => 'Dodge Dance Inside in Random' ),
            246 =>array( 'value' => '{$Duration:1500,x:0.3,y:-0.3,$Delay:80,$Cols:10,$Rows:5,$Opacity:2,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$ChessMode:{$Column:15,$Row:15},$Easing:{$Left:$Jease$.$InJump,$Top:$Jease$.$InJump,$Clip:$Jease$.$OutQuad},$Round:{$Left:0.8,$Top:2.5}}', 'name' => 'Dodge Dance Inside in Random Chess' ),
            247 =>array( 'value' => '{$Duration:1500,x:0.3,y:-0.3,$Delay:20,$Cols:10,$Rows:5,$Opacity:2,$Clip:15,$During:{$Left:[0.1,0.9],$Top:[0.1,0.9]},$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Assembly:260,$Easing:{$Left:$Jease$.$InJump,$Top:$Jease$.$InJump,$Clip:$Jease$.$OutQuad},$Round:{$Left:0.8,$Top:2.5}}', 'name' => 'Dodge Dance Inside out Stairs' ),
            248 =>array( 'value' => '{$Duration:1500,x:0.3,y:-0.3,$Delay:20,$Cols:10,$Rows:5,$Opacity:2,$Clip:15,$During:{$Left:[0.1,0.9],$Top:[0.1,0.9]},$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationSwirl,$Assembly:260,$Easing:{$Left:$Jease$.$InJump,$Top:$Jease$.$InJump,$Clip:$Jease$.$OutQuad},$Round:{$Left:0.8,$Top:2.5}}', 'name' => 'Dodge Dance Inside out Swirl' ),
            249 =>array( 'value' => '{$Duration:1500,x:0.3,y:-0.3,$Delay:20,$Cols:10,$Rows:5,$Opacity:2,$Clip:15,$During:{$Left:[0.1,0.9],$Top:[0.1,0.9]},$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationZigZag,$Assembly:260,$Easing:{$Left:$Jease$.$InJump,$Top:$Jease$.$InJump,$Clip:$Jease$.$OutQuad},$Round:{$Left:0.8,$Top:2.5}}', 'name' => 'Dodge Dance Inside out ZigZag' ),
            250 =>array( 'value' => '{$Duration:1500,x:0.3,y:-0.3,$Delay:80,$Cols:10,$Rows:5,$Opacity:2,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$SlideOut:true,$Easing:{$Left:$Jease$.$InJump,$Top:$Jease$.$InJump,$Clip:$Jease$.$OutQuad},$Round:{$Left:0.8,$Top:2.5}}', 'name' => 'Dodge Dance Inside out Random' ),
            251 =>array( 'value' => '{$Duration:1500,x:0.3,y:-0.3,$Delay:80,$Cols:10,$Rows:5,$Opacity:2,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$SlideOut:true,$ChessMode:{$Column:15,$Row:15},$Easing:{$Left:$Jease$.$InJump,$Top:$Jease$.$InJump,$Clip:$Jease$.$OutQuad},$Round:{$Left:0.8,$Top:2.5}}', 'name' => 'Dodge Dance Inside out Random Chess' ),
            // 252 =>array( 'value' => '', 'name' => '----- Dodge Pet Inside Transitions -----' ),
            253 =>array( 'value' => '{$Duration:1500,x:0.2,y:-0.1,$Delay:20,$Cols:10,$Rows:5,$Opacity:2,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Assembly:260,$Easing:{$Left:$Jease$.$InWave,$Top:$Jease$.$InWave,$Clip:$Jease$.$OutQuad},$Round:{$Left:0.8,$Top:2.5}}', 'name' => 'Dodge Pet Inside in Stairs' ),
            254 =>array( 'value' => '{$Duration:1500,x:0.2,y:-0.1,$Delay:20,$Cols:10,$Rows:5,$Opacity:2,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$Formation:$JssorSlideshowFormations$.$FormationSwirl,$Assembly:260,$Easing:{$Left:$Jease$.$InWave,$Top:$Jease$.$InWave,$Clip:$Jease$.$OutQuad},$Round:{$Left:0.8,$Top:2.5}}', 'name' => 'Dodge Pet Inside in Swirl' ),
            255 =>array( 'value' => '{$Duration:1500,x:0.2,y:-0.1,$Delay:20,$Cols:10,$Rows:5,$Opacity:2,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$Formation:$JssorSlideshowFormations$.$FormationZigZag,$Assembly:260,$Easing:{$Left:$Jease$.$InWave,$Top:$Jease$.$InWave,$Clip:$Jease$.$OutQuad},$Round:{$Left:0.8,$Top:2.5}}', 'name' => 'Dodge Pet Inside in ZigZag' ),
            256 =>array( 'value' => '{$Duration:1500,x:0.2,y:-0.1,$Delay:80,$Cols:10,$Rows:5,$Opacity:2,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8]},$Easing:{$Left:$Jease$.$InWave,$Top:$Jease$.$InWave,$Clip:$Jease$.$Linear},$Round:{$Left:0.8,$Top:2.5}}', 'name' => 'Dodge Pet Inside in Random' ),
            257 =>array( 'value' => '{$Duration:1500,x:0.2,y:-0.1,$Delay:80,$Cols:10,$Rows:5,$Opacity:2,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8]},$ChessMode:{$Column:15,$Row:15},$Easing:{$Left:$Jease$.$InWave,$Top:$Jease$.$InWave,$Clip:$Jease$.$Linear},$Round:{$Left:0.8,$Top:2.5}}', 'name' => 'Dodge Pet Inside in Random Chess' ),
            258 =>array( 'value' => '{$Duration:1500,x:0.2,y:-0.1,$Delay:20,$Cols:10,$Rows:5,$Opacity:2,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Assembly:260,$Easing:{$Left:$Jease$.$InWave,$Top:$Jease$.$InWave,$Clip:$Jease$.$OutQuad},$Round:{$Left:0.8,$Top:2.5}}', 'name' => 'Dodge Pet Inside out Stairs' ),
            259 =>array( 'value' => '{$Duration:1500,x:0.2,y:-0.1,$Delay:20,$Cols:10,$Rows:5,$Opacity:2,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationSwirl,$Assembly:260,$Easing:{$Left:$Jease$.$InWave,$Top:$Jease$.$InWave,$Clip:$Jease$.$OutQuad},$Round:{$Left:0.8,$Top:2.5}}', 'name' => 'Dodge Pet Inside out Swirl' ),
            260 =>array( 'value' => '{$Duration:1500,x:0.2,y:-0.1,$Delay:20,$Cols:10,$Rows:5,$Opacity:2,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationZigZag,$Assembly:260,$Easing:{$Left:$Jease$.$InWave,$Top:$Jease$.$InWave,$Clip:$Jease$.$OutQuad},$Round:{$Left:0.8,$Top:2.5}}', 'name' => 'Dodge Pet Inside out ZigZag' ),
            261 =>array( 'value' => '{$Duration:1500,x:0.2,y:-0.1,$Delay:80,$Cols:10,$Rows:5,$Opacity:2,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8]},$SlideOut:true,$Easing:{$Left:$Jease$.$InWave,$Top:$Jease$.$InWave,$Clip:$Jease$.$Linear},$Round:{$Left:0.8,$Top:2.5}}', 'name' => 'Dodge Pet Inside out Random' ),
            262 =>array( 'value' => '{$Duration:1500,x:0.2,y:-0.1,$Delay:80,$Cols:10,$Rows:5,$Opacity:2,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8]},$SlideOut:true,$ChessMode:{$Column:15,$Row:15},$Easing:{$Left:$Jease$.$InWave,$Top:$Jease$.$InWave,$Clip:$Jease$.$Linear},$Round:{$Left:0.8,$Top:2.5}}', 'name' => 'Dodge Pet Inside out Random Chess' ),
            // 263 =>array( 'value' => '', 'name' => '----- Dodge Inside Transitions -----' ),
            264 =>array( 'value' => '{$Duration:1200,x:0.3,y:-0.3,$Delay:20,$Cols:10,$Rows:5,$Opacity:2,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8]},$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Assembly:260,$Easing:{$Left:$Jease$.$InJump,$Top:$Jease$.$InJump,$Clip:$Jease$.$Swing},$Round:{$Left:0.8,$Top:0.8}}', 'name' => 'Dodge Inside out Stairs' ),
            265 =>array( 'value' => '{$Duration:1200,x:0.3,y:-0.3,$Delay:20,$Cols:10,$Rows:5,$Opacity:2,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8]},$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationSwirl,$Assembly:260,$Easing:{$Left:$Jease$.$InJump,$Top:$Jease$.$InJump,$Clip:$Jease$.$Swing},$Round:{$Left:0.8,$Top:0.8}}', 'name' => 'Dodge Inside out Swirl' ),
            266 =>array( 'value' => '{$Duration:1200,x:0.3,y:-0.3,$Delay:20,$Cols:10,$Rows:5,$Opacity:2,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8]},$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationZigZag,$Assembly:260,$Easing:{$Left:$Jease$.$InJump,$Top:$Jease$.$InJump,$Clip:$Jease$.$Swing},$Round:{$Left:0.8,$Top:0.8}}', 'name' => 'Dodge Inside out ZigZag' ),
            267 =>array( 'value' => '{$Duration:1200,x:0.3,y:-0.3,$Delay:40,$Cols:10,$Rows:5,$Opacity:2,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8]},$SlideOut:true,$Assembly:260,$Easing:{$Left:$Jease$.$InJump,$Top:$Jease$.$InJump,$Clip:$Jease$.$Swing},$Round:{$Left:0.8,$Top:0.8}}', 'name' => 'Dodge Inside out Random' ),
            268 =>array( 'value' => '{$Duration:1200,x:0.3,y:-0.3,$Delay:40,$Cols:10,$Rows:5,$Opacity:2,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8]},$SlideOut:true,$Assembly:260,$ChessMode:{$Column:15,$Row:15},$Easing:{$Left:$Jease$.$InJump,$Top:$Jease$.$InJump,$Clip:$Jease$.$Swing},$Round:{$Left:0.8,$Top:0.8}}', 'name' => 'Dodge Inside out Random Chess' ),
            269 =>array( 'value' => '{$Duration:1200,x:0.3,y:-0.3,$Delay:20,$Cols:10,$Rows:5,$Opacity:2,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8]},$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Assembly:260,$Easing:{$Left:$Jease$.$InJump,$Top:$Jease$.$InJump,$Clip:$Jease$.$Swing},$Round:{$Left:0.8,$Top:0.8}}', 'name' => 'Dodge Inside in Stairs' ),
            270 =>array( 'value' => '{$Duration:1200,x:0.3,y:-0.3,$Delay:20,$Cols:10,$Rows:5,$Opacity:2,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8]},$Formation:$JssorSlideshowFormations$.$FormationSwirl,$Assembly:260,$Easing:{$Left:$Jease$.$InJump,$Top:$Jease$.$InJump,$Clip:$Jease$.$Swing},$Round:{$Left:0.8,$Top:0.8}}', 'name' => 'Dodge Inside in Swirl' ),
            271 =>array( 'value' => '{$Duration:1200,x:0.3,y:-0.3,$Delay:20,$Cols:10,$Rows:5,$Opacity:2,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8]},$Formation:$JssorSlideshowFormations$.$FormationZigZag,$Assembly:260,$Easing:{$Left:$Jease$.$InJump,$Top:$Jease$.$InJump,$Clip:$Jease$.$Swing},$Round:{$Left:0.8,$Top:0.8}}', 'name' => 'Dodge Inside in ZigZag' ),
            272 =>array( 'value' => '{$Duration:1200,x:0.3,y:-0.3,$Delay:80,$Cols:10,$Rows:5,$Opacity:2,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8]},$Assembly:260,$Easing:{$Left:$Jease$.$InJump,$Top:$Jease$.$InJump,$Clip:$Jease$.$Swing},$Round:{$Left:0.8,$Top:0.8}}', 'name' => 'Dodge Inside in Random' ),
            273 =>array( 'value' => '{$Duration:1200,x:0.3,y:-0.3,$Delay:80,$Cols:10,$Rows:5,$Opacity:2,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8]},$Assembly:260,$ChessMode:{$Column:15,$Row:15},$Easing:{$Left:$Jease$.$InJump,$Top:$Jease$.$InJump,$Clip:$Jease$.$Swing},$Round:{$Left:0.8,$Top:0.8}}', 'name' => 'Dodge Inside in Random Chess' ),
            274 =>array( 'value' => '{$Duration:1200,x:0.3,y:0.3,$Delay:60,$Zoom:1,$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Easing:{$Left:$Jease$.$InJump,$Top:$Jease$.$InJump,$Opacity:$Jease$.$Linear,$Zoom:$Jease$.$Swing},$Opacity:2,$Round:{$Left:0.8,$Top:0.8}}', 'name' => 'Dodge Inside in TL' ),
            275 =>array( 'value' => '{$Duration:1200,x:-0.3,y:0.3,$Delay:60,$Zoom:1,$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Easing:{$Left:$Jease$.$InJump,$Top:$Jease$.$InJump,$Opacity:$Jease$.$Linear,$Zoom:$Jease$.$Swing},$Opacity:2,$Round:{$Left:0.8,$Top:0.8}}', 'name' => 'Dodge Inside in TR' ),
            276 =>array( 'value' => '{$Duration:1200,x:0.3,y:-0.3,$Delay:60,$Zoom:1,$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Easing:{$Left:$Jease$.$InJump,$Top:$Jease$.$InJump,$Opacity:$Jease$.$Linear,$Zoom:$Jease$.$Swing},$Opacity:2,$Round:{$Left:0.8,$Top:0.8}}', 'name' => 'Dodge Inside in BL' ),
            277 =>array( 'value' => '{$Duration:1200,x:-0.3,y:-0.3,$Delay:60,$Zoom:1,$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Easing:{$Left:$Jease$.$InJump,$Top:$Jease$.$InJump,$Opacity:$Jease$.$Linear,$Zoom:$Jease$.$Swing},$Opacity:2,$Round:{$Left:0.8,$Top:0.8}}', 'name' => 'Dodge Inside in BR' ),
            278 =>array( 'value' => '{$Duration:1200,x:0.3,y:0.3,$Delay:60,$Zoom:1,$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Easing:{$Left:$Jease$.$InJump,$Top:$Jease$.$InJump,$Opacity:$Jease$.$Linear,$Zoom:$Jease$.$Swing},$Opacity:2,$Round:{$Left:0.8,$Top:0.8}}', 'name' => 'Dodge Inside out TL' ),
            279 =>array( 'value' => '{$Duration:1200,x:-0.3,y:0.3,$Delay:60,$Zoom:1,$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Easing:{$Left:$Jease$.$InJump,$Top:$Jease$.$InJump,$Opacity:$Jease$.$Linear,$Zoom:$Jease$.$Swing},$Opacity:2,$Round:{$Left:0.8,$Top:0.8}}', 'name' => 'Dodge Inside out TR' ),
            280 =>array( 'value' => '{$Duration:1200,x:0.3,y:-0.3,$Delay:60,$Zoom:1,$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Easing:{$Left:$Jease$.$InJump,$Top:$Jease$.$InJump,$Opacity:$Jease$.$Linear,$Zoom:$Jease$.$Swing},$Opacity:2,$Round:{$Left:0.8,$Top:0.8}}', 'name' => 'Dodge Inside out BL' ),
            281 =>array( 'value' => '{$Duration:1200,x:-0.3,y:-0.3,$Delay:60,$Zoom:1,$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Easing:{$Left:$Jease$.$InJump,$Top:$Jease$.$InJump,$Opacity:$Jease$.$Linear,$Zoom:$Jease$.$Swing},$Opacity:2,$Round:{$Left:0.8,$Top:0.8}}', 'name' => 'Dodge Inside out BR' ),
            // 282 =>array( 'value' => '', 'name' => '----- Flutter Inside Transitions -----' ),
            283 =>array( 'value' => '{$Duration:1200,x:1,$Delay:20,$Cols:10,$Rows:5,$Opacity:2,$Clip:15,$During:{$Left:[0.3,0.7]},$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Assembly:260,$Easing:{$Left:$Jease$.$InOutExpo,$Clip:$Jease$.$InOutQuad},$Round:{$Top:0.8}}', 'name' => 'Flutter Inside in' ),
            284 =>array( 'value' => '{$Duration:1200,x:1,y:0.2,$Delay:20,$Cols:10,$Rows:5,$Opacity:2,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$Reverse:true,$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Assembly:2050,$Easing:{$Left:$Jease$.$InOutSine,$Top:$Jease$.$OutWave,$Clip:$Jease$.$InOutQuad},$Round:{$Top:1.3}}', 'name' => 'Flutter Inside in Wind' ),
            285 =>array( 'value' => '{$Duration:1200,x:1,y:0.2,$Delay:20,$Cols:10,$Rows:5,$Opacity:2,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$Reverse:true,$Formation:$JssorSlideshowFormations$.$FormationSwirl,$Assembly:2050,$Easing:{$Left:$Jease$.$InOutSine,$Top:$Jease$.$OutWave,$Clip:$Jease$.$InOutQuad},$Round:{$Top:1.3}}', 'name' => 'Flutter Inside in Swirl' ),
            286 =>array( 'value' => '{$Duration:1500,x:0.2,y:-0.1,$Delay:150,$Cols:12,$Opacity:2,$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Assembly:260,$Easing:{$Left:$Jease$.$Linear,$Top:$Jease$.$OutWave,$Opacity:$Jease$.$Linear},$Round:{$Top:2}}', 'name' => 'Flutter Inside in Column' ),
            287 =>array( 'value' => '{$Duration:1200,x:1,$Delay:20,$Cols:10,$Rows:5,$Opacity:2,$Clip:15,$During:{$Left:[0.3,0.7]},$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Assembly:260,$Easing:{$Left:$Jease$.$InOutExpo,$Clip:$Jease$.$InOutQuad},$Round:{$Top:0.8}}', 'name' => 'Flutter Inside out' ),
            288 =>array( 'value' => '{$Duration:1200,x:1,y:0.2,$Delay:20,$Cols:10,$Rows:5,$Opacity:2,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$SlideOut:true,$Reverse:true,$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Assembly:2050,$Easing:{$Left:$Jease$.$InOutSine,$Top:$Jease$.$OutWave,$Clip:$Jease$.$InOutQuad},$Round:{$Top:1.3}}', 'name' => 'Flutter Inside out Wind' ),
            289 =>array( 'value' => '{$Duration:1200,x:1,y:0.2,$Delay:20,$Cols:10,$Rows:5,$Opacity:2,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$SlideOut:true,$Reverse:true,$Formation:$JssorSlideshowFormations$.$FormationSwirl,$Assembly:2050,$Easing:{$Left:$Jease$.$InOutSine,$Top:$Jease$.$OutWave,$Clip:$Jease$.$InOutQuad},$Round:{$Top:1.3}}', 'name' => 'Flutter Inside out Swirl' ),
            290 =>array( 'value' => '{$Duration:1800,x:0.2,y:-0.1,$Delay:150,$Cols:12,$Opacity:2,$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Assembly:260,$Easing:{$Left:$Jease$.$Linear,$Top:$Jease$.$OutWave,$Opacity:$Jease$.$Linear},$Round:{$Top:2}}', 'name' => 'Flutter Inside out Column' ),
            // 291 =>array( 'value' => '', 'name' => '----- Compound Transitions -----' ),
            292 =>array( 'value' => '{$Duration:1200,y:-1,$Cols:10,$Rows:5,$Opacity:2,$Clip:15,$During:{$Top:[0.5,0.5],$Clip:[0,0.5]},$Formation:$JssorSlideshowFormations$.$FormationStraight,$ChessMode:{$Column:12},$ScaleClip:0.5}', 'name' => 'Clip &amp; Chess in' ),
            293 =>array( 'value' => '{$Duration:1200,y:-1,$Cols:10,$Rows:5,$Opacity:2,$Clip:15,$During:{$Top:[0.5,0.5],$Clip:[0,0.5]},$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationStraight,$ChessMode:{$Column:12},$ScaleClip:0.5}', 'name' => 'Clip &amp; Chess out' ),
            294 =>array( 'value' => '{$Duration:1200,x:-1,y:-1,$Cols:6,$Rows:6,$Opacity:2,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8],$Clip:[0,0.2]},$Formation:$JssorSlideshowFormations$.$FormationStraight,$ChessMode:{$Column:15,$Row:15},$Easing:{$Left:$Jease$.$InCubic,$Top:$Jease$.$InCubic,$Clip:$Jease$.$Swing},$ScaleClip:0.5}', 'name' => 'Clip &amp; Oblique Chess in' ),
            295 =>array( 'value' => '{$Duration:1200,x:-1,y:-1,$Cols:6,$Rows:6,$Opacity:2,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8],$Clip:[0,0.2]},$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationStraight,$ChessMode:{$Column:15,$Row:15},$Easing:{$Left:$Jease$.$InCubic,$Top:$Jease$.$InCubic,$Clip:$Jease$.$Swing},$ScaleClip:0.5}', 'name' => 'Clip &amp; Oblique Chess out' ),
            296 =>array( 'value' => '{$Duration:4000,x:-1,y:0.45,$Delay:80,$Cols:12,$Opacity:2,$Clip:15,$During:{$Left:[0.35,0.65],$Top:[0.35,0.65],$Clip:[0,0.15]},$Formation:$JssorSlideshowFormations$.$FormationStraight,$Assembly:2049,$Easing:{$Left:$Jease$.$Linear,$Top:$Jease$.$OutWave,$Clip:$Jease$.$OutQuad},$ScaleClip:0.7,$Round:{$Top:4}}', 'name' => 'Clip &amp; Wave in' ),
            297 =>array( 'value' => '{$Duration:4000,x:-1,y:0.45,$Delay:80,$Cols:12,$Opacity:2,$Clip:15,$During:{$Left:[0.35,0.65],$Top:[0.35,0.65],$Clip:[0,0.15]},$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationStraight,$Assembly:2049,$Easing:{$Left:$Jease$.$Linear,$Top:$Jease$.$OutWave,$Clip:$Jease$.$OutQuad},$ScaleClip:0.7,$Round:{$Top:4}}', 'name' => 'Clip &amp; Wave out' ),
            298 =>array( 'value' => '{$Duration:4000,x:-1,y:0.7,$Delay:80,$Cols:12,$Opacity:2,$Clip:11,$Move:true,$During:{$Left:[0.35,0.65],$Top:[0.35,0.65],$Clip:[0,0.1]},$Formation:$JssorSlideshowFormations$.$FormationStraight,$Assembly:2049,$Easing:{$Left:$Jease$.$OutQuad,$Top:$Jease$.$OutJump,$Clip:$Jease$.$OutQuad},$ScaleClip:0.7,$Round:{$Top:4}}', 'name' => 'Clip &amp; Jump in' ),
            299 =>array( 'value' => '{$Duration:4000,x:-1,y:0.7,$Delay:80,$Cols:12,$Opacity:2,$Clip:11,$Move:true,$During:{$Left:[0.35,0.65],$Top:[0.35,0.65],$Clip:[0,0.1]},$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationStraight,$Assembly:2049,$Easing:{$Left:$Jease$.$OutQuad,$Top:$Jease$.$OutJump,$Clip:$Jease$.$OutQuad},$ScaleClip:0.7,$Round:{$Top:4}}', 'name' => 'Clip &amp; Jump out' ),
            // 300 =>array( 'value' => '', 'name' => '----- Wave out Transitions -----' ),
            301 =>array( 'value' => '{$Duration:1500,y:-0.5,$Delay:60,$Cols:16,$Opacity:2,$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Easing:{$Top:$Jease$.$InWave,$Opacity:$Jease$.$Linear},$Round:{$Top:1.5}}', 'name' => 'Wave out' ),
            302 =>array( 'value' => '{$Duration:1200,y:-0.5,$Delay:30,$Cols:15,$Opacity:2,$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationCircle,$Easing:{$Top:$Jease$.$InWave,$Opacity:$Jease$.$Linear},$Round:{$Top:1.5}}', 'name' => 'Wave out Eagle' ),
            303 =>array( 'value' => '{$Duration:1200,x:-1,y:0.5,$Delay:30,$Cols:10,$Rows:5,$Opacity:2,$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationSwirl,$Assembly:260,$Easing:{$Left:$Jease$.$Linear,$Top:$Jease$.$OutWave,$Opacity:$Jease$.$Linear},$Round:{$Top:1.5}}', 'name' => 'Wave out Swirl' ),
            304 =>array( 'value' => '{$Duration:1500,x:-1,y:0.5,$Delay:60,$Cols:10,$Rows:5,$Opacity:2,$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationZigZag,$Assembly:260,$ChessMode:{$Row:3},$Easing:{$Left:$Jease$.$Linear,$Top:$Jease$.$OutWave,$Opacity:$Jease$.$Linear},$Round:{$Top:1.5}}', 'name' => 'Wave out ZigZag' ),
            305 =>array( 'value' => '{$Duration:1500,x:1,y:0.5,$Delay:60,$Cols:10,$Rows:5,$Opacity:2,$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationRectangle,$Assembly:260,$Easing:{$Left:$Jease$.$Linear,$Top:$Jease$.$OutWave,$Opacity:$Jease$.$Linear},$Round:{$Top:1.5}}', 'name' => 'Wave out Rectangle' ),
            306 =>array( 'value' => '{$Duration:1500,x:1,y:0.5,$Delay:60,$Cols:10,$Rows:5,$Opacity:2,$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationCircle,$Assembly:260,$Easing:{$Left:$Jease$.$Linear,$Top:$Jease$.$OutWave,$Opacity:$Jease$.$Linear},$Round:{$Top:1.5}}', 'name' => 'Wave out Circle' ),
            307 =>array( 'value' => '{$Duration:1500,x:1,y:0.5,$Delay:60,$Cols:10,$Rows:5,$Opacity:2,$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationCross,$Assembly:260,$Easing:{$Left:$Jease$.$Linear,$Top:$Jease$.$OutWave,$Opacity:$Jease$.$Linear},$Round:{$Top:1.5}}', 'name' => 'Wave out Cross' ),
            308 =>array( 'value' => '{$Duration:1500,x:1,y:0.5,$Delay:60,$Cols:10,$Rows:5,$Opacity:2,$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationRectangleCross,$Assembly:260,$Easing:{$Left:$Jease$.$Linear,$Top:$Jease$.$OutWave,$Opacity:$Jease$.$Linear},$Round:{$Top:1.5}}', 'name' => 'Wave out Rectangle Cross' ),
            // 309 =>array( 'value' => '', 'name' => '----- Wave in Transitions -----' ),
            310 =>array( 'value' => '{$Duration:1500,y:-0.5,$Delay:60,$Cols:12,$Opacity:2,$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Easing:{$Top:$Jease$.$InWave,$Opacity:$Jease$.$Linear},$Round:{$Top:1.5}}', 'name' => 'Wave in' ),
            311 =>array( 'value' => '{$Duration:1200,y:-0.5,$Delay:30,$Cols:15,$Opacity:2,$Formation:$JssorSlideshowFormations$.$FormationCircle,$Easing:{$Top:$Jease$.$InWave,$Opacity:$Jease$.$Linear},$Round:{$Top:1.5}}', 'name' => 'Wave in Eagle' ),
            312 =>array( 'value' => '{$Duration:1200,x:-1,y:0.5,$Delay:30,$Cols:10,$Rows:5,$Opacity:2,$Formation:$JssorSlideshowFormations$.$FormationSwirl,$Assembly:260,$Easing:{$Left:$Jease$.$Swing,$Top:$Jease$.$InWave,$Opacity:$Jease$.$Linear},$Round:{$Top:1.5}}', 'name' => 'Wave in Swirl' ),
            313 =>array( 'value' => '{$Duration:1500,x:1,y:0.5,$Delay:60,$Cols:10,$Rows:5,$Opacity:2,$Formation:$JssorSlideshowFormations$.$FormationZigZag,$Assembly:260,$ChessMode:{$Row:3},$Easing:{$Left:$Jease$.$Swing,$Top:$Jease$.$InWave,$Opacity:$Jease$.$Linear},$Round:{$Top:1.5}}', 'name' => 'Wave in ZigZag' ),
            314 =>array( 'value' => '{$Duration:1500,x:-1,y:0.5,$Delay:60,$Cols:10,$Rows:5,$Opacity:2,$Formation:$JssorSlideshowFormations$.$FormationRectangle,$Assembly:260,$Easing:{$Left:$Jease$.$Swing,$Top:$Jease$.$InWave,$Opacity:$Jease$.$Linear},$Round:{$Top:1.5}}', 'name' => 'Wave in Rectangle' ),
            315 =>array( 'value' => '{$Duration:1500,x:-1,y:0.5,$Delay:60,$Cols:10,$Rows:5,$Opacity:2,$Formation:$JssorSlideshowFormations$.$FormationCircle,$Assembly:260,$Easing:{$Left:$Jease$.$Swing,$Top:$Jease$.$InWave,$Opacity:$Jease$.$Linear},$Round:{$Top:1.5}}', 'name' => 'Wave in Circle' ),
            316 =>array( 'value' => '{$Duration:1500,x:-1,y:0.5,$Delay:60,$Cols:10,$Rows:5,$Opacity:2,$Formation:$JssorSlideshowFormations$.$FormationCross,$Assembly:260,$Easing:{$Left:$Jease$.$Swing,$Top:$Jease$.$InWave,$Opacity:$Jease$.$Linear},$Round:{$Top:1.5}}', 'name' => 'Wave in Cross' ),
            317 =>array( 'value' => '{$Duration:1500,x:-1,y:0.5,$Delay:60,$Cols:10,$Rows:5,$Opacity:2,$Formation:$JssorSlideshowFormations$.$FormationRectangleCross,$Assembly:260,$Easing:{$Left:$Jease$.$Swing,$Top:$Jease$.$InWave,$Opacity:$Jease$.$Linear},$Round:{$Top:1.5}}', 'name' => 'Wave in Rectangle Cross' ),
            // 318 =>array( 'value' => '', 'name' => '----- Jump out Transitions -----' ),
            319 =>array( 'value' => '{$Duration:1500,x:-1,y:0.5,$Delay:100,$Cols:10,$Rows:5,$Opacity:2,$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationStraight,$Assembly:513,$Easing:{$Left:$Jease$.$Linear,$Top:$Jease$.$OutJump},$Round:{$Top:1.5}}', 'name' => 'Jump out Straight' ),
            320 =>array( 'value' => '{$Duration:1500,x:-1,y:0.5,$Delay:100,$Cols:10,$Rows:5,$Opacity:2,$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationSwirl,$Assembly:260,$Easing:{$Left:$Jease$.$Linear,$Top:$Jease$.$OutJump},$Round:{$Top:1.5}}', 'name' => 'Jump out Swirl' ),
            321 =>array( 'value' => '{$Duration:1500,x:-1,y:0.5,$Delay:100,$Cols:10,$Rows:5,$Opacity:2,$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationZigZag,$Assembly:260,$Easing:{$Left:$Jease$.$Linear,$Top:$Jease$.$OutJump},$Round:{$Top:1.5}}', 'name' => 'Jump out ZigZag' ),
            322 =>array( 'value' => '{$Duration:1500,x:-1,y:0.5,$Delay:800,$Cols:10,$Rows:5,$Opacity:2,$SlideOut:true,$Reverse:true,$Formation:$JssorSlideshowFormations$.$FormationRectangle,$Assembly:260,$Easing:{$Left:$Jease$.$Linear,$Top:$Jease$.$OutJump},$Round:{$Top:1.5}}', 'name' => 'Jump out Rectangle' ),
            323 =>array( 'value' => '{$Duration:1500,x:-1,y:0.5,$Delay:100,$Cols:10,$Rows:5,$Opacity:2,$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationCircle,$Assembly:260,$Easing:{$Left:$Jease$.$Linear,$Top:$Jease$.$OutJump},$Round:{$Top:1.5}}', 'name' => 'Jump out Circle' ),
            324 =>array( 'value' => '{$Duration:1500,x:-1,y:0.5,$Delay:100,$Cols:10,$Rows:5,$Opacity:2,$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationRectangleCross,$Assembly:260,$Easing:{$Left:$Jease$.$Linear,$Top:$Jease$.$OutJump},$Round:{$Top:1.5}}', 'name' => 'Jump out Rectangle Cross' ),
            // 325 =>array( 'value' => '', 'name' => '----- Jump in Transitions -----' ),
            326 =>array( 'value' => '{$Duration:1500,x:-1,y:-0.5,$Delay:50,$Cols:10,$Rows:5,$Opacity:2,$Formation:$JssorSlideshowFormations$.$FormationStraight,$Assembly:513,$Easing:{$Left:$Jease$.$Swing,$Top:$Jease$.$InJump},$Round:{$Top:1.5}}', 'name' => 'Jump in Straight' ),
            327 =>array( 'value' => '{$Duration:1500,x:-1,y:-0.5,$Delay:50,$Cols:10,$Rows:5,$Opacity:2,$Formation:$JssorSlideshowFormations$.$FormationSwirl,$Assembly:260,$Easing:{$Left:$Jease$.$Swing,$Top:$Jease$.$InJump},$Round:{$Top:1.5}}', 'name' => 'Jump in Swirl' ),
            328 =>array( 'value' => '{$Duration:1500,x:-1,y:-0.5,$Delay:50,$Cols:10,$Rows:5,$Opacity:2,$Formation:$JssorSlideshowFormations$.$FormationZigZag,$Assembly:260,$Easing:{$Left:$Jease$.$Swing,$Top:$Jease$.$InJump},$Round:{$Top:1.5}}', 'name' => 'Jump in ZigZag' ),
            329 =>array( 'value' => '{$Duration:1500,x:-1,y:-0.5,$Delay:800,$Cols:10,$Rows:5,$Opacity:2,$Reverse:true,$Formation:$JssorSlideshowFormations$.$FormationRectangle,$Assembly:260,$Easing:{$Left:$Jease$.$Swing,$Top:$Jease$.$InJump},$Round:{$Top:1.5}}', 'name' => 'Jump in Rectangle' ),
            330 =>array( 'value' => '{$Duration:1500,x:-1,y:-0.5,$Delay:50,$Cols:10,$Rows:5,$Opacity:2,$Formation:$JssorSlideshowFormations$.$FormationCircle,$Assembly:260,$Easing:{$Left:$Jease$.$Swing,$Top:$Jease$.$InJump},$Round:{$Top:1.5}}', 'name' => 'Jump in Circle' ),
            331 =>array( 'value' => '{$Duration:1500,x:-1,y:-0.5,$Delay:50,$Cols:10,$Rows:5,$Opacity:2,$Formation:$JssorSlideshowFormations$.$FormationRectangleCross,$Assembly:260,$Easing:{$Left:$Jease$.$Swing,$Top:$Jease$.$InJump},$Round:{$Top:1.5}}', 'name' => 'Jump in Rectangle Cross' ),
            // 332 =>array( 'value' => '', 'name' => '----- Stone Transitions -----' ),
            333 =>array( 'value' => '{$Duration:500,y:1,$Opacity:2,$Easing:$Jease$.$InQuad}', 'name' => 'Slide Down' ),
            334 =>array( 'value' => '{$Duration:400,x:1,$Opacity:2,$Easing:$Jease$.$InQuad}', 'name' => 'Slide Right' ),
            335 =>array( 'value' => '{$Duration:1000,y:1,$Opacity:2,$Easing:$Jease$.$InBounce}', 'name' => 'Bounce Down' ),
            336 =>array( 'value' => '{$Duration:1000,x:1,$Opacity:2,$Easing:$Jease$.$InBounce}', 'name' => 'Bounce Right' )
        );

        $result = array ();
		foreach ( $settingAnimation as $value ) {
			$result [$value ["value"]] = $value ["name"];
		}
        return $result;
    }

    private function getAnimationKenburnsOption(){
        $settingAnimation = array(
            1 => array( 'value' => 'left', 'name' => 'Left' ),
            2 => array( 'value' => 'bottom', 'name' => 'Bottom' ),
            3 => array( 'value' => 'right', 'name' => 'Right' ),
            4 => array( 'value' => 'top', 'name' => 'Top' ),
            5 => array( 'value' => 'bottom left', 'name' => 'Bottom Left' ),
            6 => array( 'value' => 'bottom right', 'name' => 'Bottom Right' ),
            7 => array( 'value' => 'top left', 'name' => 'Top Left' ),
            8 => array( 'value' => 'top right', 'name' => 'Top Right' ),
            9 => array( 'value' => 'center', 'name' => 'Center' ),
            10 => array( 'value' => 'square', 'name' => 'Square'),
            11 => array( 'value' => 'corner', 'name' => 'Corner')
        );

        $result = array ();
		foreach ( $settingAnimation as $value ) {
			$result [$value ["value"]] = $value ["name"];
        }

        return $result;
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
		$canSave = $sUser->isAllow('widget.item.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
    	
    	// Param
        $params = $this->getRequest()->getParams();

    	// Update
    	$model = new Widgetmanage_Model_Item();
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
		$canSave = $sUser->isAllow('widget.item.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถคัดลอกได้';
			$this->_helper->json($result);
		}
    	
    	// Param
        $params = $this->getRequest()->getParams();

        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
		$params['defaultLanguage'] = $defaultLanguage['Code'];

    	// Update
    	$model = new Widgetmanage_Model_Item();
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
		$canSave = $sUser->isAllow('widget.item.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถลบได้';
			$this->_helper->json($result);
		}
    	
    	// Param
        $params = $this->getRequest()->getParams();

    	// Update
    	$model = new Widgetmanage_Model_Item();
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