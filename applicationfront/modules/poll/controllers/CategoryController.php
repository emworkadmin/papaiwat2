<?php 


class Poll_CategoryController extends Zend_Controller_Action
{
	protected $lang;
	protected $colorCode = array('#E63935', '#D81A62', '#3F4EA2', '#863B96', '#2899D5', '#0D8A7C', '#7CB342', '#FCD836', '#F68C1E', '#F05323');

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
	
	// Article List	
	public function itemAction()
	{
		
		// Log Access
		System_Model_Log::access();

		// Zend Filter
        $filterInt = new Zend_Filter_Int();
        
        // Get Param
		$id = $filterInt->filter($this->getRequest()->getParam('id'));
		
		if (empty($id)) {
			// Show "Not found data"
			$this->renderScript('category/_alertnotfound.phtml');
			return;
		}
		
		// Model
		$model = new Poll_Model_Category();
		$categoryData = $model->getCategory($id, $this->lang);
		
		// Send Data to view
		$this->view->id = $id;
		$this->view->pageTitle = $categoryData->Title;
		
		// Set Global
		$menuID = $this->findMenu("/poll/category/item/id/" . $id);
		Zend_Registry::set("MenuID", $menuID);
	}
	
	public function getitemAction() {
		
		$filterInt = new Zend_Filter_Int();
		
		$responce = array();
		$responce['total'] = 0;
		$responce['totalPage'] = 0; 
		$responce['pageIndex'] = 0;
		$responce['html'] = '';
				
		// Category
		$id = $filterInt->filter($this->getRequest()->getParam('id'));
		$categoryModel = new Poll_Model_Category();
		$categoryData = $categoryModel->getSettings($id);
		$categorySetting = json_decode($categoryData->Settings);
		$cItemAmountColumnSetting = $categorySetting->ItemAmountColumnSetting;
		$cItemAmountColumn = $categorySetting->ItemAmountColumn;
		$cItemPerPageSetting = $categorySetting->ItemPerPageSetting;
		$cItemPerPage = $categorySetting->ItemPerPage;
		$cItemShowImageSetting = $categorySetting->ItemShowImageSetting;
		$cItemShowImage = $categorySetting->ItemShowImage;
		$cItemShowIntroTextSetting = $categorySetting->ItemShowIntroTextSetting;
		$cItemShowIntroText = $categorySetting->ItemShowIntroText;
		$cItemSortSetting = $categorySetting->ItemSortSetting;
		$cItemSortType = $categorySetting->ItemSortType;
		$cItemSortDirection = $categorySetting->ItemSortDirection;
		
		$mainSiteUrl = System_Model_Setting::getValue("MainSiteUrl", "General");	

		// Setting
		$pollSettingItemAmountColumn = System_Model_Setting::getValue("ItemAmountColumn", "Poll");
		$pollSettingItemAmountColumn = $cItemAmountColumnSetting == "D" ? $pollSettingItemAmountColumn : $cItemAmountColumn;
		$pollSettingItemAmountColumn = empty($pollSettingItemAmountColumn) || $pollSettingItemAmountColumn <= 1 ? 1 : ($pollSettingItemAmountColumn >= 4 ? 4 : $pollSettingItemAmountColumn);
		$pollSettingItemPerPage = System_Model_Setting::getValue("ItemPerPage", "Poll");
		$pollSettingItemPerPage = $cItemPerPageSetting == "D" ? $pollSettingItemPerPage : empty($cItemPerPage) ? 5 : $cItemPerPage;
		$pollSettingItemShowImage = System_Model_Setting::getValue("ItemShowImage", "Poll");
		$pollSettingItemShowImage = $cItemShowImageSetting == "D" ? $pollSettingItemShowImage : empty($cItemShowImage) ? "Y" : $cItemShowImage;
		$pollSettingItemShowIntroText = System_Model_Setting::getValue("ItemShowIntroText", "Poll");
		$pollSettingItemShowIntroText = $cItemShowIntroTextSetting == "D" ? $pollSettingItemShowIntroText : empty($cItemShowIntroText) ? "Y" : $cItemShowIntroText;
		$pollSettingItemSortType = System_Model_Setting::getValue("ItemSortType", "Poll");
		$pollSettingItemSortType = $cItemSortSetting == "D" ? $pollSettingItemSortType : $cItemSortType;
		$pollSettingItemSortDirection = System_Model_Setting::getValue("ItemSortDirection", "Poll");
		$pollSettingItemSortDirection = $cItemSortSetting == "D" ? $pollSettingItemSortDirection : $cItemSortDirection;

		$sortType = 'Created';
		switch ($pollSettingItemSortType) {
			case 'created' :
				$sortType = 'Created';
				break;
			case 'title' :
			case 'name' :
				$sortType = 'Title';
				break;
			case 'startdate' :
				$sortType = 'StartDate';
				break;
			case 'enddate' :
				$sortType = 'EndDate';
				break;
		}
		$sortDirection = in_array($pollSettingItemSortDirection, array('asc', 'desc')) ? $pollSettingItemSortDirection : 'desc';
		
		// Init
		$pageSize = empty($pollSettingItemPerPage) ? 10 : $pollSettingItemPerPage;
		$pageIndex = $this->getRequest()->getParam('pageIndex', 1);
		
		// Search
		$textstr = $this->getRequest()->getParam('search');
		
		$advancesearch = array();
		$advancesearch['PollCategoryID'] = $id;
		
		$model = new Poll_Model_Category();
		$items = $model->getItemList($sortType, $sortDirection, $pageSize, $pageIndex, $textstr, $filter, $advancesearch, $this->lang);

		// Set Setting to view
		$this->view->id = $id;
		$this->view->defaultImageUrl = $this->view->baseUrl("images/other/no-image.jpg");
		$this->view->pollSettingItemAmountColumn = $pollSettingItemAmountColumn;
		$this->view->pollSettingItemShowImage = $pollSettingItemShowImage == 'Y';
		$this->view->pollSettingItemShowIntroText = $pollSettingItemShowIntroText == 'Y';
		// Send Data to view
		$this->view->items = $items;
		if ($pollSettingItemAmountColumn == 1) {
			$responce["html"] = $this->view->render('category/_irows.phtml');
		} else {
			$responce["html"] = $this->view->render('category/_icards.phtml');
		}
		if (count($items) > 0) {
			$responce["total"] = $items[0]["RECORD"];
			$responce['totalPage'] = ceil($responce["total"] / $pageSize);
		}
		$responce['pageIndex'] = $pageIndex;
		$this->_helper->json($responce);
	}

	public function pollAction()
	{
		// Log Access
		System_Model_Log::access();
		
		// Zend Filter
        $filterInt = new Zend_Filter_Int();
        
        // Get Param
		$id = $filterInt->filter($this->_request->getParam('id'));
		$iid = $filterInt->filter($this->_request->getParam('iid'));
		
		// Get Data
		$model = new Poll_Model_Item();
		$data = $model->getDataByLanguage($iid, $this->lang);

		if($data->ID){

			// Get Question List
			$dataQuestion = $model->getQuestionListByLanguage($data->ID, $this->lang);
			if(count($dataQuestion)>0){
				foreach($dataQuestion as $k=>$v){
					$dataOption = $model->getOptionListByLanguage($v['ID'], $this->lang);
					$dataQuestion[$k]['OptionList'] = $dataOption;
				}
			}
			$this->view->dataQuestion = $dataQuestion;

			$request = Zend_Controller_Front::getInstance()->getRequest();
			$request->getServer('REMOTE_ADDR');

			$RemoteIP = null;
			if($data->OneIPPerVote=='Y'){
				$RemoteIP = $request->getServer('REMOTE_ADDR');
			}

			// Get Last Submission
			$dataLastSubmission = $model->getLastSubmission($data->ID, $RemoteIP, $data->OneUserPerVote=='Y');
			
			if($dataLastSubmission->ID && $data->OneUserPerVote=='Y'){
				$this->_helper->redirector('report', 'category', 'poll', array('id'=>$id, 'iid'=>$iid));
			}
			if($dataLastSubmission->ID && $data->OneIPPerVote=='Y'){
				$this->_helper->redirector('report', 'category', 'poll', array('id'=>$id, 'iid'=>$iid));
			}
			if(!empty($dataLastSubmission->ActionDiffHour) && !empty($data->VoteDelay)){
				if($dataLastSubmission->ActionDiffHour < $data->VoteDelay){
					$this->_helper->redirector('report', 'category', 'poll', array('id'=>$id, 'iid'=>$iid));
				}
			}

			// Setting
			$this->view->ShowIntroText = $data->ShowIntroText == 'Y';
			$this->view->ResultDisplay = $data->ResultDisplay;
			// $this->view->ResultPieSetting = json_decode($data->ResultPieSetting);
			// $this->view->ResultBarSetting = json_decode($data->ResultBarSetting);
		}else{
			$this->_helper->redirector('index', 'index', 'default');
		}

		// Case หมดเวลา Vote
		if($data->EndDiff <= 0){
			$this->_helper->redirector('report', 'category', 'poll', array('id'=>$id, 'iid'=>$iid));
		}

		// Send Data to view
		$this->view->id = $id;
		$this->view->iid = $iid;
		$this->view->pageTitle = empty($data->Title) ? '.' : $data->Title;
		$this->view->canVote = $data->CanVote == 1;
		$this->view->startDate = $data->StartDate;

		// Set Global
		$menuID = $this->findMenu("/poll/category/item/id/" . $id);
		Zend_Registry::set("MenuID", $menuID);

		$extraBreadcrumb[] = array('url' => '/poll/category/poll/id/' . $id . 'iid/' . $iid, 'name' => $data->Title);
		Zend_Registry::set("ExtraBreadcrumb", $extraBreadcrumb);
	}

	public function finishAction()
	{
		// Log Access
		System_Model_Log::access();
		
		// Zend Filter
        $filterInt = new Zend_Filter_Int();
        
        // Get Param
		$id = $filterInt->filter($this->_request->getParam('id'));
		$iid = $filterInt->filter($this->_request->getParam('iid'));
		
		$model = new Poll_Model_Item();
		$data = $model->getDataByLanguage($iid, $this->lang);

		// Send Data to view
		$this->view->id = $id;
		$this->view->iid = $iid;
		$this->view->pageTitle = empty($data->Title) ? '.' : $data->Title;
		
		// Set Global
		$menuID = $this->findMenu("/poll/category/item/id/" . $id);
		Zend_Registry::set("MenuID", $menuID);

		$extraBreadcrumb[] = array('url' => '/poll/category/finish/id/' . $id . 'iid/' . $iid, 'name' => $data->Title);
		Zend_Registry::set("ExtraBreadcrumb", $extraBreadcrumb);
	}

	public function reportAction()
	{
		// Log Access
		System_Model_Log::access();
		
		// Zend Filter
        $filterInt = new Zend_Filter_Int();
        
        // Get Param
		$id = $filterInt->filter($this->_request->getParam('id'));
		$iid = $filterInt->filter($this->_request->getParam('iid'));
		
		$model = new Poll_Model_Item();
		$data = $model->getDataByLanguage($iid, $this->lang);

		if($data->ID){

			$dataSubmission = $model->getSubmissionList($data->ID);

			$dataQuestion = $model->getQuestionListByLanguage($data->ID, $this->lang);
			if(count($dataQuestion)>0){
				foreach($dataQuestion as $k=>$v){
					$dataOption = $model->getOptionListByLanguage($v['ID'], $this->lang);
					if(count($dataOption)>0){
						$optionAmount = count($dataOption);
						$index=0;
						foreach($dataOption as $k2=>$v2){
							if(count($dataSubmission)>0){
								foreach($dataSubmission as $k3=>$v3){
									if($v['ID']==$v3['PollQuestionID'] && $v2['ID']==$v3['PollOptionID']){
										$dataOption[$k2]['Amount'] = $v3['Amount'];
										$dataOption[$k2]['Percentage'] = $v3['Percentage'];
										if($index<=9){
											$dataOption[$k2]['Color'] = $this->colorCode[$index];
											$index++;	
										}else{
											$dataOption[$k2]['Color'] = '#'.$this->random_color();
										}
									}
								}
							}
						}
					}
					$dataQuestion[$k]['OptionList'] = $dataOption;
				}
			}

			$this->view->dataQuestion = $dataQuestion;

			// Setting
			$this->view->ShowIntroText = $data->ShowIntroText == 'Y';
			$this->view->ResultDisplay = $data->ResultDisplay;
		}


		// Send Data to view
		$this->view->id = $id;
		$this->view->iid = $iid;
		$this->view->pageTitle = empty($data->Title) ? '.' : $data->Title;
		
		$menuDefault = System_Model_Menu::getHome();
		$this->view->HomeUrl = $menuDefault->Url;
		
		// Set Global
		$menuID = $this->findMenu("/poll/category/item/id/" . $id);
		Zend_Registry::set("MenuID", $menuID);

		$extraBreadcrumb[] = array('url' => '/poll/category/report/id/' . $id . 'iid/' . $iid, 'name' => $data->Title);
		Zend_Registry::set("ExtraBreadcrumb", $extraBreadcrumb);
	}
	
	// Article Detail
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