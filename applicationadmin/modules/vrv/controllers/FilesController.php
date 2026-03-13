<?php
class Vrv_FilesController extends Zend_Controller_Action {
	
	public function init() {
		
	}

	public function indexAction() {
		// Log Access
		System_Model_Log::access ();
		
		// Check Auth
		$sUser = System_Model_SUser::getInstance();
		if (!$sUser->hasData()) {
			$this->_helper->redirector('index', 'index', 'default');
		}
		if ($sUser->getInfo()->UserType != "B") {
			$this->_helper->redirector('index', 'index', 'default');
		}
		
		// Check Action Rights
		$canView = $sUser->isAllow('vrv.files.manage');
		$canAdd = $sUser->isAllow('vrv.files.manage');
		if (!$canView) {
			$this->_helper->redirector('index', 'index', 'default');
		}
		
		$InfoSearch = new stdClass ();
		if (Zend_Session::namespaceIsset ( "VrvFilesIndexSearch" )) {
			$infoSearchNamespace = new Zend_Session_Namespace ( "VrvFilesIndexSearch" );
			$InfoSearch->searchText = $infoSearchNamespace->searchText;
			$InfoSearch->tblPage = $infoSearchNamespace->tblPage;
			$InfoSearch->tblRow = $infoSearchNamespace->tblRow;
			$InfoSearch->tblOrderCol= $infoSearchNamespace->tblOrderCol;
			$InfoSearch->tblOrderDir = $infoSearchNamespace->tblOrderDir;
			
			$this->view->InfoSearch = $InfoSearch;
			Zend_Session::namespaceUnset ( "VrvFilesIndexSearch" );
		}
		
		// Set View
		$this->view->canAdd = $canAdd;
		$this->view->pageName = "รายการไฟล์ Virtual Reality";
		$this->view->pageIcon = "glyphicon glyphicon-th-list";
		$this->view->subMenus = array ();
		$this->view->breadcrumb = array (
				array ( 'name' => 'หน้าแรก', 'url' => $this->view->url ( array ( 'module' => 'default', 'controller' => 'index', 'action' => 'index'  ), null, true)), 
				array ( 'name' => 'รายการไฟล์ Virtual Reality'  ) 
		);
	}

	public function getlistAction() {
		
		$filterInt = new Zend_Filter_Int ();
		
		$sUser = System_Model_SUser::getInstance();
		if (!$sUser->hasData()) {
			$this->_helper->json($responce);
		}
		if ($sUser->getInfo()->UserType != "B") {
			$this->_helper->json($responce);
		}
		
		// Check Action Rights
		$canView = $sUser->isAllow('vrv.files.manage');

		// Init
		$_draw = $this->getRequest ()->getParam ( 'draw' );
		$_start = $this->getRequest ()->getParam ( 'start' );
		$_length = $this->getRequest ()->getParam ( 'length' );
		$_order = $this->getRequest ()->getParam ( 'order' );
		$_columns = $this->getRequest ()->getParam ( 'columns' );
		$orderIndex = $_order [0] ['column'];
		$sidx = $_columns [$orderIndex] ['data'];
		$sord = $_order [0] ['dir'];
		$rows = $_length;
		$page = ($_start / $rows) + 1;
		                               
		// Basic Search
		$textstr = $this->getRequest ()->getParam ( 'textstr' );
		$textstr = $textstr;
		$filter = '';
		              
		// Advance Search
		$advancesearch ['searchText'] = $textstr;

		$searchNamespace = new Zend_Session_Namespace("VrvFilesIndexSearch");
		$searchNamespace->searchText = $advancesearch['searchText'];
		$searchNamespace->tblPage = $_start;
		$searchNamespace->tblRow = $_length;
		$searchNamespace->tblOrderCol = $_order[0]['column'];
		$searchNamespace->tblOrderDir = $_order[0]['dir'];
		
		if($canView):
		$searchNamespace->isEnable = $advancesearch['isEnable'];
			$model = new Vrv_Model_Files();
			$data = $model->getList ( $sidx, $sord, $rows, $page, $textstr, $filter, $advancesearch );
		endif;
		
		$i = $_start;
		$responce = array ();
		$responce ['draw'] = $_draw;
		$responce ['recordsTotal'] = 0;
		$responce ['recordsFiltered'] = 0;
		$responce ['data'] = array ();
		if ($data) {
			$responce ['recordsTotal'] = $data [0] ['RECORD'];
			$responce ['recordsFiltered'] = $data [0] ['RECORD'];
			
			foreach ( $data as $item ) {
				$i ++;
				$d = array ();
				$d ['DT_RowId'] = $item ['ID'];
				$d ['NO'] = $i;
				$d ['Name'] = Emwork_Utility::notShowNull ( $item ['Name'] ); 
				$d ['FileSize'] = $this->formatSizeUnits ( $item ['FileSize'] );
				$d ['Created'] = Emwork_Utility::notShowNull ( Emwork_Utility_DateTime::MySQLToDateThai($item ['Created'],'DT') );
				$d ['CreatedBy'] = Emwork_Utility::notShowNull ( $item ['CreatedBy'] );
				$d ['Status'] =Emwork_Utility::notShowNull ( $item ['Status'] );
				$responce ['data'] [] = $d;
			}
		}
		
		$this->_helper->json ( $responce );
	}
	
	public function listdeleteAction() {
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
		$canSave = $sUser->isAllow('vrv.files.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถลบได้';
			$this->_helper->json($result);
		}
    	
    	// Param
        $params = $this->getRequest()->getParams();

    	// Update
		$searchNamespace->isEnable = $advancesearch['isEnable'];
    	$model = new Vrv_Model_Files();
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

	private function formatSizeUnits($bytes) {
        if ($bytes >= 1073741824){
            $bytes = number_format($bytes / 1073741824, 2) . ' GB';
        }elseif ($bytes >= 1048576){
            $bytes = number_format($bytes / 1048576, 2) . ' MB';
        }elseif ($bytes >= 1024){
            $bytes = number_format($bytes / 1024, 2) . ' KB';
        }elseif ($bytes > 1){
            $bytes = $bytes . ' bytes';
        }elseif ($bytes == 1){
            $bytes = $bytes . ' byte';
        }else{
            $bytes = '0 bytes';
        }
        return $bytes;
	}

}

