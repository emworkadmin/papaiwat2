<?php
class Banner_StatController extends Zend_Controller_Action
{
    public function indexAction() {
        // Log Access
        System_Model_Log::access();

        $sUser = System_Model_SUser::getInstance();
        if (!$sUser->hasData()) {
            $this->_helper->redirector('index', 'index', 'default');
        }
        if ($sUser->getInfo()->UserType != 'B') {
            $this->_helper->redirector('index', 'index', 'default');
        }
        // TODO: Use isAllow
        $canView = $sUser->isAllow("banner.stat.view");
        if (!$canView) {
            $this->_helper->redirector('index', 'index', 'default');
        }

        $languages = System_Model_Setting::getLanguages();
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
        $model = new Banner_Model_Stat();
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
        $this->view->datasum=$dataSum;
        $this->view->startdate = Emwork_Utility_DateTime::MySQLToDateThai($startdate);
        $this->view->enddate = Emwork_Utility_DateTime::MySQLToDateThai($enddate);
        // Set View
        $this->view->canAdd = $canAdd;
        $this->view->pageName = "สถิติ Banner ยอดนิยม";
        $this->view->pageIcon = "fa fa-newspaper-o";
        $this->view->subMenus = array();
        $this->view->breadcrumb = array(
            array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'stat', 'controller' => 'page', 'action' => 'index'), null, true)),
            array('name' => 'สถิติ Banner ยอดนิยม')
            
        );
				// Log Acion
		System_Model_Log::action("banner.stat", "view", null, null);
    }
}
?>