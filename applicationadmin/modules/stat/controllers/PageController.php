<?php
class Stat_PageController extends Zend_Controller_Action
{
    public function indexAction(){
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
        $canView = $sUser->isAllow('stat.stat.view');
        if (!$canView) {
           $this->_helper->redirector('index', 'index', 'default');
        }

        $languages = System_Model_Setting::getLanguages();
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
        $model = new Stat_Model_Stat();
        //Get params
        $params=$this->getRequest()->getParams();
        if($params['startdate'] || $params['enddate']){
			
            $startdate= empty($params['startdate']) ? null : Emwork_Utility_DateTime::dateThaiToMySQL(base64_decode($params["startdate"]));
			$enddate = empty($params['enddate']) ? null : Emwork_Utility_DateTime::dateThaiToMySQL(base64_decode($params["enddate"]));
			$data = $model->getbetweendate($startdate,$enddate);
        } else {
            $data = $model->getlist();
        }

        $dataSum = $model->getsum($startdate,$enddate);
        //send to view
        $this->view->data=$data;
        $this->view->datasum=$dataSum->SumPage;
        $this->view->startdate = Emwork_Utility_DateTime::MySQLToDateThai($startdate);
        $this->view->enddate = Emwork_Utility_DateTime::MySQLToDateThai($enddate);
        // Set View
        $this->view->canAdd = $canAdd;
        $this->view->pageName = "สถิติเพจยอดนิยม";
        $this->view->pageIcon = "fa fa-bar-chart";
        $this->view->subMenus = array();
        $this->view->breadcrumb = array(
            array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'stat', 'controller' => 'page', 'action' => 'index'), null, true)),
            array('name' => 'สถิติเพจยอดนิยม')
            
        );
				// Log Acion
		System_Model_Log::action("stat.page", "view", null, null);
    }
    public function checkdateAction() {

		// Init
		$result = array();
		$result['result'] = false;
		$result['id'] = null;
		$result['message'] = '';

		// Param
        $params=$this->getRequest()->getParams();
        if($params['startdate']){
            $startdate=Emwork_Utility_DateTime::dateThaiToMySQL(base64_decode($params["startdate"]));
            $enddate=Emwork_Utility_DateTime::dateThaiToMySQL(base64_decode($params["enddate"]));
        }
        else{
            $adate = date('Y-m-d');
            $startdate=date("Y-m-d",strtotime("-7 days",strtotime($adate)));
            $enddate=date('Y-m-d');
        }
		// Set Result
		if ($startdate > $enddate) {				
			$result['result'] = false;
			$result['message'] = 'กรุณากรอกวันที่เริ่มต้น น้อยกว่าหรือเท่ากับ วันที่สิ้นสุด';
		} else {
			$result['result'] = true;
			$result['message'] = '';
		}
		
		$this->_helper->json($result);
	}
}
?>