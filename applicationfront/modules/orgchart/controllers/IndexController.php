<?php
class Orgchart_IndexController extends Zend_Controller_Action
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
    
	protected function findMenu($url)
	{
		$menuID = null;
		$menus = System_Model_Menu::findMenuByUrl($url);
		if (count($menus) > 0) {
			$menuID = $menus[0]["ID"];
		}
		return $menuID;
    }

    function indexAction()
    {
		// Log Access
		System_Model_Log::access();

		// Zend Filter
		$filterInt = new Zend_Filter_Int();

		// Set Global
		$menuID = $this->findMenu("/orgchart/index/index");
		Zend_Registry::set("MenuID", $menuID);

		$this->view->CSS = System_Model_Setting::getValue("ChartCustomCss", "OrgChart");

		$this->view->pageTitle = Emwork_Translate::text($this->lang, 'orgchart', 'chart_list');
	}
	
	public function getlistAction() {
		
		$filterInt = new Zend_Filter_Int();
		
		$responce = array();
		$responce['total'] = 0;
		$responce['totalPage'] = 0;
		$responce['pageIndex'] = 0;
		$responce['html'] = '';
				
		// Category
		$id = $filterInt->filter($this->getRequest()->getParam('id'));
		$mainSiteUrl = System_Model_Setting::getValue("MainSiteUrl", "General");	

		// Setting
		$ChartAmountColumn = System_Model_Setting::getValue("ChartAmountColumn", "OrgChart");
		$ChartShowImage = System_Model_Setting::getValue("ChartShowImage", "OrgChart");
		// $ChartShowAmount = System_Model_Setting::getValue("ChartShowAmount", "OrgChart");
		// $ChartShowNoDataPerson = System_Model_Setting::getValue("ChartShowNoDataPerson", "OrgChart");
		$ChartShowTitle = System_Model_Setting::getValue("ChartShowTitle", "OrgChart");
		$ChartShowIntroText = System_Model_Setting::getValue("ChartShowIntroText", "OrgChart");

		$ChartPerPage = System_Model_Setting::getValue("ChartPerPage", "OrgChart");
		$ChartSortType = System_Model_Setting::getValue("ChartSortType", "OrgChart");
		$ChartSortDirection = System_Model_Setting::getValue("ChartSortDirection", "OrgChart");

		$sortType = 'Created';
		switch ($ChartSortType) {
			case 'created' :
				$sortType = 'Created';
				break;
			case 'title' :
			case 'name' :
				$sortType = 'Title';
				break;
		}
		$sortDirection = in_array($ChartSortDirection, array('asc', 'desc')) ? $ChartSortDirection : 'desc';
		
		// Init
		$pageSize = empty($ChartPerPage) ? 10 : $ChartPerPage;
		$pageIndex = $this->getRequest()->getParam('pageIndex', 1);
		$advancesearch = array();

		$model = new Orgchart_Model_Index();
		$items = $model->getList($sortType, $sortDirection, $pageSize, $pageIndex, $textstr, $filter, $advancesearch, $this->lang);

		// Set Setting to view
		$this->view->id = $id;
		$this->view->defaultImageUrl = $this->view->baseUrl("images/other/no-image.jpg");
		$this->view->ChartAmountColumn = $ChartAmountColumn;
		$this->view->ChartShowImage = $ChartShowImage == 'Y';
		$this->view->ChartShowTitle = $ChartShowTitle == 'Y';
		$this->view->ChartShowIntroText = $ChartShowIntroText == 'Y';

		// Send Data to view
		$this->view->items = $items;
		if ($ChartAmountColumn == 1) {
			$responce["html"] = $this->view->render('index/_irows.phtml');
		} else {
			$responce["html"] = $this->view->render('index/_icards.phtml');
		}
		if (count($items) > 0) {
			$responce["total"] = $items[0]["RECORD"];
			$responce['totalPage'] = ceil($responce["total"] / $pageSize);
		}
		$responce['pageIndex'] = $pageIndex;
		$this->_helper->json($responce);
	}

    function indexchartAction()
    {
		// Log Access
		System_Model_Log::access();

		// Zend Filter
		$filterInt = new Zend_Filter_Int();

		$menuID = $this->findMenu("/orgchart/index/index");
		Zend_Registry::set("MenuID", $menuID);

		$id = $filterInt->filter($this->getRequest()->getParam('id'));

		$model = new Orgchart_Model_Item();	
		$data = $model->getInfo($id);
		$dataDetail = $model->getDetail($id, $this->lang);

		if(!$data->ChartDetail) {
			// .
			$this->renderScript('index/_alertnotfound.phtml');
			return;	
		}

		$dataSetting = new stdClass();
		$dataObjSetting = json_decode($data->Settings);

		$PersonShowImageSetting = $dataObjSetting->PersonShowImageSetting;
		$DefaultPersonShowImage = System_Model_Setting::getValue("PersonShowImage", "OrgChart");
		$DefaultPersonShowImage = empty($DefaultPersonShowImage) ? 'Y' : $DefaultPersonShowImage;
		$PersonShowImage = $PersonShowImageSetting == 'D' ? $DefaultPersonShowImage : empty($dataObjSetting->PersonShowImage) ? $DefaultPersonShowImage : $dataObjSetting->PersonShowImage;

		$PersonShowTitleSetting = $dataObjSetting->PersonShowTitleSetting;
		$DefaultPersonShowTitle = System_Model_Setting::getValue("PersonShowTitle", "OrgChart");
		$DefaultPersonShowTitle = empty($DefaultPersonShowTitle) ? 'Y' : $DefaultPersonShowTitle;
		$PersonShowTitle = $PersonShowTitleSetting == 'D' ? $DefaultPersonShowTitle : empty($dataObjSetting->PersonShowTitle) ? $DefaultPersonShowTitle : $dataObjSetting->PersonShowTitle;
		
		$PersonShowJobSetting = $dataObjSetting->PersonShowJobSetting;
		$DefaultPersonShowJob = System_Model_Setting::getValue("PersonShowJob", "OrgChart");
		$DefaultPersonShowJob = empty($DefaultPersonShowJob) ? 'Y' : $DefaultPersonShowJob;
		$PersonShowJob = $PersonShowJobSetting == 'D' ? $DefaultPersonShowJob : empty($dataObjSetting->PersonShowJob) ? $DefaultPersonShowJob : $dataObjSetting->PersonShowJob;

		$PersonShowWorkgroupSetting = $dataObjSetting->PersonShowWorkgroupSetting;
		$DefaultPersonShowWorkgroup = System_Model_Setting::getValue("PersonShowWorkgroup", "OrgChart");
		$DefaultPersonShowWorkgroup = empty($DefaultPersonShowWorkgroup) ? 'Y' : $DefaultPersonShowWorkgroup;
		$PersonShowWorkgroup = $PersonShowWorkgroupSetting == 'D' ? $DefaultPersonShowWorkgroup : empty($dataObjSetting->PersonShowWorkgroup) ? $DefaultPersonShowWorkgroup : $dataObjSetting->PersonShowWorkgroup;

		$PersonShowDepartmentSetting = $dataObjSetting->PersonShowDepartmentSetting;
		$DefaultPersonShowDepartment = System_Model_Setting::getValue("PersonShowDepartment", "OrgChart");
		$DefaultPersonShowDepartment = empty($DefaultPersonShowDepartment) ? 'Y' : $DefaultPersonShowDepartment;
		$PersonShowDepartment = $PersonShowDepartmentSetting == 'D' ? $DefaultPersonShowDepartment : empty($dataObjSetting->PersonShowDepartment) ? $DefaultPersonShowDepartment : $dataObjSetting->PersonShowDepartment;

		$PersonShowLinkSetting = $dataObjSetting->PersonShowLinkSetting;
		$DefaultPersonShowLink = System_Model_Setting::getValue("PersonShowLink", "OrgChart");
		$DefaultPersonShowLink = empty($DefaultPersonShowLink) ? 'Y' : $DefaultPersonShowLink;
		$PersonShowLink = $PersonShowLinkSetting == 'D' ? $DefaultPersonShowLink : empty($dataObjSetting->PersonShowLink) ? $DefaultPersonShowLink : $dataObjSetting->PersonShowLink;
		$DefaultPersonLinkTarget = System_Model_Setting::getValue("PersonLinkTarget", "OrgChart");
		$DefaultPersonLinkTarget = empty($DefaultPersonLinkTarget) ? '_blank' : $DefaultPersonLinkTarget;
		$PersonLinkTarget = $PersonShowLinkSetting == 'D' ? $DefaultPersonLinkTarget : empty($dataObjSetting->PersonLinkTarget) ? $DefaultPersonLinkTarget : $dataObjSetting->PersonLinkTarget;
		
		$PersonLevelSetting = $dataObjSetting->PersonLevelSetting;
		$DefaultPersonVerticalLevel = System_Model_Setting::getValue("PersonVerticalLevel", "OrgChart");
		$DefaultPersonVerticalLevel = empty($DefaultPersonVerticalLevel) ? 3 : $DefaultPersonVerticalLevel;
		$PersonVerticalLevel = $PersonLevelSetting == 'D' ? $DefaultPersonVerticalLevel : empty($dataObjSetting->PersonVerticalLevel) ? $DefaultPersonVerticalLevel : $dataObjSetting->PersonVerticalLevel;
		$DefaultPersonVisibleLevel = System_Model_Setting::getValue("PersonVisibleLevel", "OrgChart");
		$DefaultPersonVisibleLevel = empty($DefaultPersonVisibleLevel) ? 10 : $DefaultPersonVisibleLevel;
		$PersonVisibleLevel = $PersonLevelSetting == 'D' ? $DefaultPersonVisibleLevel : empty($dataObjSetting->PersonVisibleLevel) ? $DefaultPersonVisibleLevel : $dataObjSetting->PersonVisibleLevel;

		$dataChart = new stdClass();
		$dataObj = null;
		if($data->ChartDetail) {
			$dataObj = json_decode($data->ChartDetail, true);
			$model = new Orgchart_Model_Item();

			if($dataObj['id'][0] == 'w') {
				$dataObj['id'] = substr($dataObj['id'],1);
				$dataWorkgroup = $model->getDataWorkgroupByLanguage($dataObj['id'], $this->lang);
				$imageUrl = "";
				if (!empty($dataWorkgroup->IntroImageHashName)) {
					$imageUrl = $this->view->url(array("module" => "default", "controller" => "file", "action" => "get", "file" => $dataWorkgroup->IntroImageHashName), null, true);
				}
				$title = '';
				$title .= $PersonShowImage == 'Y' ? $imageUrl != "" ? '<img alt="image" src="' . $imageUrl . '">' : '' : '';
				$title .= '<div class="id" style="display:none;">w' . $dataWorkgroup->ID . '</div>';
				$title .= $PersonShowTitle == 'Y' ? '<div class="workgroup_title">' . $dataWorkgroup->Title . '</div>' : '';
				$dataChart->id = $dataWorkgroup->ID;
				$dataChart->title = $title;
			} else {
				$dataObj['id'] = substr($dataObj['id'],1);
				$dataPerson = $model->getDataPersonByLanguage($dataObj['id'], $this->lang);

				$imageUrl = "";
				if (!empty($dataPerson->IntroImageHashName)) {
					$imageUrl = $this->view->url(array("module" => "default", "controller" => "file", "action" => "get", "file" => $dataPerson->IntroImageHashName), null, true);
				}
				$title = '';
				$title .= $PersonShowImage == 'Y' ? $imageUrl != "" ? '<img alt="image" src="' . $imageUrl . '">' : '' : '';
				$title .= $PersonShowLink == 'Y' ? '<a href="' . $this->view->url(array('module' => 'orgchart', 'controller' => 'index', 'action' => 'detailperson', 'id' => $id, 'itemid' => $dataPerson->ID), null, true) . '" target="' . $PersonLinkTarget . '">' : '';
				$title .= '<div class="id" style="display:none;">p' . $dataPerson->ID . '</div>';
				$title .= $PersonShowTitle == 'Y' ? '<div class="person_title">' . $dataPerson->Title . '</div>' : '';
				$title .= $PersonShowJob == 'Y' ? '<div class="person_job">' . Emwork_Translate::text($this->lang, 'orgchart', 'detail_job') . ' ' .$dataPerson->Job . '</div>' : '';
				$title .= $PersonShowWorkgroup == 'Y' ? '<div class="person_workgroup">' . Emwork_Translate::text($this->lang, 'orgchart', 'detail_workgroup') . ' ' . $dataPerson->WorkgroupName . '</div>' : '';
				$title .= $PersonShowDepartment == 'Y' ? '<div class="person_department">' . Emwork_Translate::text($this->lang, 'orgchart', 'detail_department') . ' ' . $dataPerson->Department . '</div>' : '';
				$title .= $PersonShowLink == 'Y' ? '</a>' : '';
				$dataChart->id = $dataPerson->ID;
				$dataChart->title = $title;
			}

			if(count($dataObj['children'])>0) {
				foreach($dataObj['children'] as $k1 => $v1) {
					$dataChart->children[] = $this->getChildren($v1, $this->lang, $PersonShowImage, $PersonShowTitle, $PersonShowJob, $PersonShowWorkgroup, $PersonShowDepartment, $PersonShowLink, $PersonLinkTarget, $id);
				}
			}
		}


		$this->view->data = $data;
		$this->view->dataChart = $dataChart;
		$this->view->pageTitle = $dataDetail->Title;
		$this->view->PersonVerticalLevel = $PersonVerticalLevel;
		$this->view->PersonVisibleLevel = $PersonVisibleLevel;
		$this->view->CSS = $dataObjSetting->PersonCustomCss;

		$extraBreadcrumb[] = array('url' => '/orgchart/index/indexchart/id/' . $id, 'name'=> $dataDetail->Title);
		Zend_Registry::set("ExtraBreadcrumb", $extraBreadcrumb);
	}
	
	private function getChildren($dataObj, $lang = 'th', $PersonShowImage, $PersonShowTitle, $PersonShowJob, $PersonShowWorkgroup, $PersonShowDepartment, $PersonShowLink, $PersonLinkTarget, $id)
	{

		$model = new Orgchart_Model_Item();

		$dataChart = new stdClass();
		if($dataObj['id'][0] == 'w') {
			$dataObj['id'] = substr($dataObj['id'],1);
			$dataWorkgroup = $model->getDataWorkgroupByLanguage($dataObj['id'], $lang);
			$imageUrl = "";
			if (!empty($dataWorkgroup->IntroImageHashName)) {
				$imageUrl = $this->view->url(array("module" => "default", "controller" => "file", "action" => "get", "file" => $dataWorkgroup->IntroImageHashName), null, true);
			}
			$title = '';

			$title .= $PersonShowImage == 'Y' ? $imageUrl != "" ? '<img alt="image" src="' . $imageUrl . '">' : '' : '';
			$title .= '<div class="id" style="display:none;">w' . $dataWorkgroup->ID . '</div>';
			$title .= $PersonShowTitle == 'Y' ? '<div class="workgroup_title">' . $dataWorkgroup->Title . '</div>' : '';
			$dataChart->id = $dataWorkgroup->ID;
			$dataChart->title = $title;
		} else {
			$dataObj['id'] = substr($dataObj['id'],1);
			$dataPerson = $model->getDataPersonByLanguage($dataObj['id'], $lang);
			$imageUrl = "";
			if (!empty($dataPerson->IntroImageHashName)) {
				$imageUrl = $this->view->url(array("module" => "default", "controller" => "file", "action" => "get", "file" => $dataPerson->IntroImageHashName), null, true);
			}
			$title = '';
			$title .= $PersonShowImage == 'Y' ? $imageUrl != "" ? '<img alt="image" src="' . $imageUrl . '">' : '' : '';
			$title .= $PersonShowLink == 'Y' ? '<a href="' . $this->view->url(array('module' => 'orgchart', 'controller' => 'index', 'action' => 'detailperson', 'id' => $id, 'itemid' => $dataPerson->ID), null, true) . '" target="' . $PersonLinkTarget . '">' : '';
			$title .= '<div class="id" style="display:none;">p' . $dataPerson->ID . '</div>';
			$title .= $PersonShowTitle == 'Y' ? '<div class="person_title">' . $dataPerson->Title . '</div>' : '';
			$title .= $PersonShowJob == 'Y' ? '<div class="person_job">' . Emwork_Translate::text($this->lang, 'orgchart', 'detail_job') . ' ' . $dataPerson->Job . '</div>' : '';
			$title .= $PersonShowWorkgroup == 'Y' ? '<div class="person_workgroup">' . Emwork_Translate::text($this->lang, 'orgchart', 'detail_workgroup') . ' ' . $dataPerson->WorkgroupName . '</div>' : '';
			$title .= $PersonShowDepartment == 'Y' ? '<div class="person_department">' . Emwork_Translate::text($this->lang, 'orgchart', 'detail_department') . ' ' . $dataPerson->Department . '</div>' : '';
			$title .= $PersonShowLink == 'Y' ? '</a>' : '';
			$dataChart->id = $dataPerson->ID;
			$dataChart->title = $title;
		}

		if(count($dataObj['children'])>0) {
			foreach($dataObj['children'] as $k1 => $v1) {
				$dataChart->children[] = $this->getChildren($v1, $lang, $PersonShowImage, $PersonShowTitle, $PersonShowJob, $PersonShowWorkgroup, $PersonShowDepartment, $PersonShowLink, $PersonLinkTarget, $id);
			}
		}
		return $dataChart;
	}	

	public function detailpersonAction()
	{
		// Log Access
		System_Model_Log::access();

		// Zend Filter
		$filterInt = new Zend_Filter_Int();

		$id = $filterInt->filter($this->getRequest()->getParam('id'));
		$itemid = $filterInt->filter($this->getRequest()->getParam('itemid'));

		// Set Global
		$menuID = $this->findMenu("/orgchart/index/index");
		Zend_Registry::set("MenuID", $menuID);

		$modelinfo = new Orgchart_Model_Item();	
		$datainfo = $modelinfo->getInfo($id);
		$dataSetting = new stdClass();
		$dataObjSetting = json_decode($datainfo->Settings);

		$model = new Orgchart_Model_Item();
		$dataDetail = $model->getDetail($id, $this->lang);
		
		$data = $model->getDataPersonByLanguage($itemid, $this->lang);

		$data->imageUrl = $this->view->baseUrl("images/icons/no-image.jpg");
		if (!empty($data->IntroImageHashName)) {
			$data->imageUrl = $this->view->url(array("module" => "default", "controller" => "file", "action" => "get", "file" => $data->IntroImageHashName), null, true);
		}

		$this->view->data = $data;
		$this->view->CSS = $dataObjSetting->PersonDetailCustomCss;

		$extraBreadcrumb[] = array('url' => '/orgchart/index/indexchart/id/' . $id , 'name' => $dataDetail->Title);
		$extraBreadcrumb[] = array('url' => '/orgchart/item/detailperson/itemid/' . $itemid , 'name' => $data->Title);
		Zend_Registry::set("ExtraBreadcrumb", $extraBreadcrumb);
	}

}
?>