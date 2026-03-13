<?php

class Eform_CategoryController extends Zend_Controller_Action
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
	
	// Category List
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
		$model = new Eform_Model_Category();
		$categoryData = $model->getCategory($id, $this->lang);
		
		// Send Data to view
		$this->view->id = $id;
		$this->view->pageTitle = $categoryData->Title;
		
		// Set Global
		$menuID = $this->findMenu("/eform/category/item/id/" . $id);
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

		$categoryModel = new Eform_Model_Category();
		$categoryData = $categoryModel->getSettings($id);
		
		$categorySetting = json_decode($categoryData->Settings);
		$EformPerPageSetting = $categorySetting->EformPerPageSetting; // ตั้งค่าจำนวนข้อมูลที่แสดงต่อหน้า
		$EformPerPage = $categorySetting->EformPerPage; // จำนวนข้อมูลที่แสดงต่อหน้า
		$EformAmountColumnSetting = $categorySetting->EformAmountColumnSetting; // ตั้งค่าจำนวนคอลัมน์ที่แสดง
		$EformAmountColumn = $categorySetting->EformAmountColumn; // จำนวนคอลัมน์ที่แสดง
		$EformSortSetting = $categorySetting->EformSortSetting; // ตั้งค่าเงื่อนไขการจัดเรียง
		$EformSortType = $categorySetting->EformSortType; // เงื่อนไขการจัดเรียง
		$EformSortDirection = $categorySetting->EformSortDirection; // รูปแบบการจัดเรียง
		$EformShowNoDataCategorySetting = $categorySetting->EformShowNoDataCategorySetting; // ตั้งค่าการแสดงหมวดหมู่ที่ไม่มีข้อมูล
		$EformShowNoDataCategory = $categorySetting->EformShowNoDataCategory; // ต้องการแสดงหมวดหมู่ที่ไม่มีข้อมูลหรือไม่
		$EformShowImageSetting = $categorySetting->EformShowImageSetting; // ตั้งค่าแสดงรูปภาพ
		$EformShowImage = $categorySetting->EformShowImage; // แสดงรูปภาพ
		$EformShowCategorySetting = $categorySetting->EformShowCategorySetting; // ตั้งค่าแสดงชื่อหมวดหมู่
		$EformShowCategory = $categorySetting->EformShowCategory; // แสดงชื่อหมวดหมู่
		$EformShowIntroTextSetting = $categorySetting->EformShowIntroTextSetting; // ตั้งค่าแสดงข้อความเกริ่นนำ
		$EformShowIntroText = $categorySetting->EformShowIntroText; // แสดงข้อความเกริ่นนำ
		$EformShowAmountSetting = $categorySetting->EformShowAmountSetting; // ตั้งค่าแสดงจำนวนข้อมูลในหมวดหมู่
		$EformShowAmount = $categorySetting->EformShowAmount; // แสดงจำนวนข้อมูลในหมวดหมู่
		$CategoryPerPageSetting = $categorySetting->CategoryPerPageSetting; // ตั้งค่าจำนวนคอลัมน์ที่แสดง
		$CategoryPerPage = $categorySetting->CategoryPerPage; // จำนวนคอลัมน์ที่แสดง	
		$CategoryAmountColumnSetting = $categorySetting->CategoryAmountColumnSetting; // ตั้งค่าจำนวนข้อมูลที่แสดงต่อหน้า
		$CategoryAmountColumn = $categorySetting->CategoryAmountColumn; // จำนวนข้อมูลที่แสดงต่อหน้า
		$CategoryShowImageSetting = $categorySetting->CategoryShowImageSetting; // ตั้งค่าแสดงรูปภาพ
		$CategoryShowImage = $categorySetting->CategoryShowImage; // แสดงรูปภาพ
		$CategoryShowCategorySetting = $categorySetting->CategoryShowCategorySetting; // ตั้งค่าแสดงชื่อหมวดหมู่
		$CategoryShowCategory = $categorySetting->CategoryShowCategory; // แสดงชื่อหมวดหมู่
		$CategoryShowIntroTextSetting = $categorySetting->CategoryShowIntroTextSetting; // ตั้งค่าแสดงข้อความเกริ่นนำ
		$CategoryShowIntroText = $categorySetting->CategoryShowIntroText; // แสดงข้อความเกริ่นนำ
		
		$mainSiteUrl = System_Model_Setting::getValue("MainSiteUrl", "General");
	
		// Setting
		$SettingEformPerPage = System_Model_Setting::getValue("EformPerPage", "Eform"); // ตั้งค่าจำนวนข้อมูลที่แสดงต่อหน้า
		$SettingEformPerPage = $EformPerPageSetting == 'D' ? $SettingEformPerPage : $EformPerPage;
		$SettingEformPerPage = empty($SettingEformPerPage) ? 9 : $SettingEformPerPage ;

		$SettingEformAmountColumn = System_Model_Setting::getValue("EformAmountColumn", "Eform"); // จำนวนคอลัมน์ที่แสดง
		$SettingEformAmountColumn = $EformAmountColumnSetting == 'D' ? $SettingEformAmountColumn : $EformAmountColumn;
		$SettingEformAmountColumn = empty($SettingEformAmountColumn) ? 1 : $SettingEformAmountColumn;

		$SettingEformSortType = System_Model_Setting::getValue("EformSortType", "Eform"); // เงื่อนไขการจัดเรียง
		$SettingEformSortType = $EformAmountColumnSetting == 'D' ? $SettingEformSortType : $EformPerPage;
		$SettingEformSortType = empty($SettingEformSortType) ? "publishdown" : $SettingEformSortType;

		$SettingEformSortDirection = System_Model_Setting::getValue("EformSortDirection", "Eform"); // รูปแบบการจัดเรียง
		$SettingEformSortDirection = $EformSortSetting == 'D' ? $SettingEformSortDirection : $EformSortDirection;
		$SettingEformSortDirection = empty($SettingEformSortDirection) ? 'desc' : $SettingEformSortDirection;

		$SettingEformShowNoDataCategory = System_Model_Setting::getValue("EformShowNoDataCategory", "Eform"); // ต้องการแสดงหมวดหมู่ที่ไม่มีข้อมูลหรือไม่
		$SettingEformShowNoDataCategory = $EformShowNoDataCategorySetting == 'D' ? $SettingEformShowNoDataCategory : $EformShowNoDataCategory;

		$SettingEformShowImage = System_Model_Setting::getValue("EformShowImage", "Eform"); // แสดงรูปภาพ
		$SettingEformShowImage = $EformShowImageSetting == 'D' ? $SettingEformShowImage : $EformShowImage;
		$SettingEformShowImage = empty($SettingEformShowImage) ? 'Y' : $SettingEformShowImage;

		$SettingEformShowCategory = System_Model_Setting::getValue("EformShowCategory", "Eform"); // แสดงชื่อหมวดหมู่
		$SettingEformShowCategory = $EformShowCategorySetting == 'D' ? $SettingEformShowCategory : $EformShowCategory;
		$SettingEformShowCategory = empty($SettingEformShowCategory) ? 'Y' : $SettingEformShowCategory;

		$SettingEformShowIntroText = System_Model_Setting::getValue("EformShowIntroText", "Eform"); // แสดงข้อความเกริ่นนำ
		$SettingEformShowIntroText = $EformShowIntroTextSetting == 'D' ? $SettingEformShowIntroText : $EformShowIntroText;
		$SettingEformShowIntroText = empty($SettingEformShowIntroText) ? 'Y' : $SettingEformShowIntroText;
		
		$SettingEformShowAmount = System_Model_Setting::getValue("EformShowAmount", "Eform"); // แสดงจำนวนข้อมูลในหมวดหมู่
		$SettingEformShowAmount = $EformShowAmountSetting == 'D' ? $SettingEformShowAmount : $EformShowAmount;
		$SettingEformShowAmount = empty($SettingEformShowAmount) ? 'Y' : $SettingEformShowAmount;

		$SettingCategoryPerPage = System_Model_Setting::getValue("CategoryPerPage", "Eform"); // จำนวนคอลัมน์ที่แสดง	
		$SettingCategoryPerPage = $CategoryPerPageSetting == 'D' ? $SettingCategoryPerPage : $CategoryPerPage;

		$SettingCategoryPerPage = empty($SettingCategoryPerPage) ? 10 : $SettingCategoryPerPage;

		$SettingCategoryAmountColumn = System_Model_Setting::getValue("CategoryAmountColumn", "Eform"); // จำนวนข้อมูลที่แสดงต่อหน้า
		$SettingCategoryAmountColumn = $CategoryAmountColumnSetting == 'D' ? $SettingCategoryAmountColumn : $CategoryAmountColumn;

		$SettingCategoryShowImage = System_Model_Setting::getValue("CategoryShowImage", "Eform"); // แสดงรูปภาพ
		$SettingCategoryShowImage = $CategoryShowImageSetting == 'D' ? $SettingCategoryShowImage : $CategoryShowImage;
		$SettingCategoryShowImage = empty($SettingCategoryShowImage) ? "Y" : $SettingCategoryShowImage;

		$SettingCategoryShowCategory = System_Model_Setting::getValue("CategoryShowCategory", "Eform"); // แสดงชื่อหมวดหมู่
		$SettingCategoryShowCategory = $CategoryShowCategorySetting == 'D' ? $SettingCategoryShowCategory : $CategoryShowCategory;
		$SettingCategoryShowCategory = empty($SettingCategoryShowCategory) ? "Y" : $SettingCategoryShowCategory;

		$SettingCategoryShowIntroText = System_Model_Setting::getValue("CategoryShowIntroText", "Eform"); // แสดงข้อความเกริ่นนำ
		$SettingCategoryShowIntroText = $CategoryShowIntroTextSetting == 'D' ? $SettingCategoryShowIntroText : $CategoryShowIntroText;
		$SettingCategoryShowIntroText = empty($SettingCategoryShowIntroText) ? "Y" : $SettingCategoryShowIntroText;

		$sortType = 'Created';
		switch ($SettingEformSortType) {
			case 'created' :
				$sortType = 'Created';
				break;
			case 'title' :
			case 'name' :
				$sortType = 'Title';
				break;
			case 'publishdate' :
				$sortType = 'PublishDate';
				break;
			case 'publishdown' :
				$sortType = 'PublishDown';
				break;				
		}

		$sortDirection = in_array($SettingEformSortDirection, array('asc', 'desc')) ? $SettingEformSortDirection : 'desc';
		
		// Init
		$pageSize = empty($SettingCategoryPerPage) ? 10 : $SettingCategoryPerPage;
		$pageIndex = $this->getRequest()->getParam('pageIndex', 1);
		
		// Search
		$textstr = $this->getRequest()->getParam('search');
		
		$advancesearch = array();
		$advancesearch['EformCategoryID'] = $id;
		
		$model = new Eform_Model_Category();
		$items = $model->getItemList($sortType, $sortDirection, $pageSize, $pageIndex, $textstr, $filter, $advancesearch, $this->lang);
		
		// Set Setting to view
		$this->view->id = $id;
		$this->view->defaultImageUrl = $this->view->baseUrl("images/other/no-image.jpg");
		$this->view->SettingEformAmountColumn = $SettingEformAmountColumn;
		$this->view->SettingEformShowImage = $SettingEformShowImage == 'Y';
		$this->view->SettingEformShowIntroText = $SettingEformShowIntroText == 'Y';

		// Send Data to view
		$this->view->items = $items;
		if ($SettingEformAmountColumn == 1) {
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

	public function formAction()
	{

		// Log Access
		System_Model_Log::access();

		// Zend Filter
		$filterInt = new Zend_Filter_Int();

		$id = $filterInt->filter($this->getRequest()->getParam('id'));
		$iid = $filterInt->filter($this->getRequest()->getParam('iid'));
		
		$model = new Eform_Model_Item();
		$dataDetail = $model->getDataByLanguage($iid, $this->lang);

		if($dataDetail->PublishDate >= date('Y-m-d H:i:s')) {
			$this->renderScript('item/_comming.phtml');
			return;
		}

		if($dataDetail->PublishDown && $dataDetail->PublishDown <= date('Y-m-d H:i:s')) {
			$this->renderScript('item/_timeout.phtml');
			return;
		}
		
		$ReCaptchaSiteKey = System_Model_Setting::getValue("ReCaptchaSiteKey", "General");

		$tmp_EformControlTypeID = array();
		$dataType  = $model->type($dataDetail->ID);
		foreach($dataType as $key => $value) {
			$typeProperty = $model->typeProperty($value['ID']);
			$dataType[$key]['NAME_'] = $typeProperty['NAME'];
			$dataType[$key]['LABEL_'] = $typeProperty['LABEL'];
			$dataType[$key]['PLACEHOLDER_'] = $typeProperty['PLACEHOLDER'];
			$dataType[$key]['DESCRIPTION_'] = $typeProperty['DESCRIPTION'];
			$dataType[$key]['REQUIRED_'] = $typeProperty['REQUIRED'];
			$dataType[$key]['VALIDATIONRULE_'] = $typeProperty['VALIDATIONRULE'];
			$dataType[$key]['SIZE_'] = $typeProperty['SIZE'];
			$dataType[$key]['MAXLENGTH_'] = $typeProperty['MAXLENGTH'];
			$dataType[$key]['VALIDATE_'] = $typeProperty['VALIDATE'];
			$dataType[$key]['COLS_'] = $typeProperty['COLS'];
			$dataType[$key]['ROWS_'] = $typeProperty['ROWS'];
			$dataType[$key]['DROPDOWN_'] = $typeProperty['DROPDOWN'];
			$dataType[$key]['OPTION_'] = $typeProperty['OPTION'];
			$dataType[$key]['FLOW_'] = $typeProperty['FLOW'];
			$dataType[$key]['FILESIZE_'] = $typeProperty['FILESIZE'];
			$dataType[$key]['FILETYPE_'] = $typeProperty['FILETYPE'];
			$dataType[$key]['MULTIPLE_'] = $typeProperty['MULTIPLE'];
			$dataType[$key]['RESETBUTTON_'] = $typeProperty['RESETBUTTON'];
			$dataType[$key]['RESETLABEL_'] = $typeProperty['RESETLABEL'];
			$dataType[$key]['MESSAGE_'] = $typeProperty['MESSAGE'];
			if($value['EformControlTypeID'] == 10 && !$ReCaptchaSiteKey) {
				unset($dataType[$key]);
			}
			array_push($tmp_EformControlTypeID, $dataType[$key]['EformControlTypeID']);
		}
		
		$this->view->dataDetail = $dataDetail;
		$this->view->tmp_EformControlTypeID = $tmp_EformControlTypeID;
		$this->view->ID = $iid;
		$this->view->dataType = $dataType;
		$this->view->pageTitle = $dataDetail->Title;
		$this->view->ItemCustomCss = $dataDetail->CustomCss;
		$this->view->ReCaptchaSiteKey = $ReCaptchaSiteKey;
	
		// Set Global
		$menuID = $this->findMenu("/eform/category/item/id/" . $id);
		Zend_Registry::set("MenuID", $menuID);

		$extraBreadcrumb = array();
		$extraBreadcrumb[] = array('url' => '/eform/category/item/id/' . $id . '/iid/' . $iid, 'name' => $dataDetail->Title);
		Zend_Registry::set("ExtraBreadcrumb", $extraBreadcrumb);

		$this->renderScript('item/index.phtml');
	}
}

?>