<?php 


class Poll_ItemController extends Zend_Controller_Action
{
	protected $lang;
	protected $mainCategoryID = '1';
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
	
	public function indexAction()
	{
		// Log Access
		System_Model_Log::access();
		
		// Zend Filter
        $filterInt = new Zend_Filter_Int();
        
        // Get Param
		$id = $filterInt->filter($this->_request->getParam('id'));
		
		// Get Data
		$model = new Poll_Model_Item();
		$data = $model->getDataByLanguage($id, $this->lang);

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
				$this->_helper->redirector('report', 'item', 'poll', array('id'=>$data->ID));
			}
			if($dataLastSubmission->ID && $data->OneIPPerVote=='Y'){
				$this->_helper->redirector('report', 'item', 'poll', array('id'=>$data->ID));
			}
			if(!empty($dataLastSubmission->ActionDiffHour) && !empty($data->VoteDelay)){
				if($dataLastSubmission->ActionDiffHour < $data->VoteDelay){
					$this->_helper->redirector('report', 'item', 'poll', array('id'=>$data->ID));
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
			$this->_helper->redirector('report', 'item', 'poll', array('id'=>$data->ID));
		}

		// Send Data to view
		$this->view->id = $data->ID;
		$this->view->pageTitle = empty($data->Title) ? '.' : $data->Title;
		$this->view->canVote = $data->CanVote == 1;
		$this->view->startDate = $data->StartDate;

		$menuID = $this->findMenu("/poll/item/index/id/" . $id);
		Zend_Registry::set("MenuID", $menuID);
	}

	public function finishAction()
	{
		// Log Access
		System_Model_Log::access();
		
		// Zend Filter
        $filterInt = new Zend_Filter_Int();
        
        // Get Param
		$id = $filterInt->filter($this->_request->getParam('id'));
		
		$model = new Poll_Model_Item();
		$data = $model->getDataByLanguage($id, $this->lang);

		// Send Data to view
		$this->view->id = $data->ID;
		$this->view->pageTitle = empty($data->Title) ? '.' : $data->Title;
		
		$menuID = $this->findMenu("/poll/item/index/id/" . $id);
		Zend_Registry::set("MenuID", $menuID);
	}

	public function reportAction()
	{
		// Log Access
		System_Model_Log::access();
		
		// Zend Filter
        $filterInt = new Zend_Filter_Int();
        
        // Get Param
		$id = $filterInt->filter($this->_request->getParam('id'));
		
		
		$model = new Poll_Model_Item();
		$data = $model->getDataByLanguage($id, $this->lang);
		

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
			// $this->view->ResultPieSetting = json_decode($data->ResultPieSetting);
			// $this->view->ResultBarSetting = json_decode($data->ResultBarSetting);
		}

		// Send Data to view
		$this->view->id = $data->ID;
		$this->view->pageTitle = empty($data->Title) ? '.' : $data->Title;
		
		$menuID = $this->findMenu("/poll/item/index/id/" . $id);
		Zend_Registry::set("MenuID", $menuID);
	}

	public function saveAction() {

		// Init
		$result = array();
		$result['result'] = false;
		$result['id'] = null;
		$result['message'] = '';

		// Get Params
		$params = $this->getRequest()->getParams();
		
		// Save
		$model = new Poll_Model_Item();
		$data = $model->save($params);

		// Set Result
		if ($data->result) {
			$result['result'] = true;
			$result['message'] = '';
			$result['id'] = $data->id;
		} else {
			$result['result'] = false;
			$result['message'] = Emwork_Translate::text($this->lang, 'poll', 'validate_error');
		}
		
		$this->_helper->json($result);
	}

	private function random_color_part() {
		return str_pad( dechex( mt_rand( 0, 255 ) ), 2, '0', STR_PAD_LEFT);
	}
	
	private function random_color() {
		return $this->random_color_part() . $this->random_color_part() . $this->random_color_part();
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