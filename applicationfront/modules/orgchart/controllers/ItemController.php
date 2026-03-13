<?php
class Orgchart_ItemController extends Zend_Controller_Action
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

		$id = $filterInt->filter($this->getRequest()->getParam('id'));

		// Set Global
		$menuID = $this->findMenu("/orgchart/item/index/id/" . $id);
		Zend_Registry::set("MenuID", $menuID);
		
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
				$title .= $PersonShowLink == 'Y' ? '<a href="' . $this->view->url(array('module' => 'orgchart', 'controller' => 'item', 'action' => 'detailperson', 'id' => $id, 'itemid' => $dataPerson->ID), null, true) . '" target="' . $PersonLinkTarget . '">' : '';
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
		
		$this->renderScript('index/indexchart.phtml');
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
			$title .= $PersonShowLink == 'Y' ? '<a href="' . $this->view->url(array('module' => 'orgchart', 'controller' => 'item', 'action' => 'detailperson', 'id' => $id, 'itemid' => $dataPerson->ID), null, true) . '" target="' . $PersonLinkTarget . '">' : '';
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
		$menuID = $this->findMenu("/orgchart/item/index/id/" . $id);
		Zend_Registry::set("MenuID", $menuID);

		$modelinfo = new Orgchart_Model_Item();	
		$datainfo = $modelinfo->getInfo($id);
		$dataSetting = new stdClass();
		$dataObjSetting = json_decode($datainfo->Settings);

		$model = new Orgchart_Model_Item();
		$data = $model->getDataPersonByLanguage($itemid, $this->lang);

		$data->imageUrl = $this->view->baseUrl("images/icons/no-image.jpg");
		if (!empty($data->IntroImageHashName)) {
			$data->imageUrl = $this->view->url(array("module" => "default", "controller" => "file", "action" => "get", "file" => $data->IntroImageHashName), null, true);
		}

		$this->view->data = $data;
		$this->view->CSS = $dataObjSetting->PersonDetailCustomCss;

		$extraBreadcrumb[] = array('url' => '/orgchart/item/detailperson/itemid/' . $itemid , 'name' => $data->Title);
		Zend_Registry::set("ExtraBreadcrumb", $extraBreadcrumb);
		$this->renderScript('index/detailperson.phtml');
	}

}
?>