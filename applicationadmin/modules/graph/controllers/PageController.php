<?php
class Graph_PageController extends Zend_Controller_Action
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
        $BetweenDate = new stdClass();
        if (Zend_Session::namespaceIsset("GraphBetweenDate")) {
        	$infoSearchNamespace = new Zend_Session_Namespace("GraphBetweenDate");
        	$BetweenDate->txtStartDate = $infoSearchNamespace->txtEndDate;
            $BetweenDate->txtEndDate= $infoSearchNamespace->txtEndDate;
            $this->view->BetweenDate = $BetweenDate;
        	Zend_Session::namespaceUnset("GraphBetweenDate");
        }
        // Check Action Rights
        $canView = true; // $sUser->isAllow('Graph.page.view');
        $canAdd = true; // $sUser->isAllow('Graph.page.manage');
        if (!$canView) {
            $this->_helper->redirector('index', 'index', 'default');
        }
        $languages = System_Model_Setting::getLanguages();
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
        $model = new Graph_Model_Graph();
        //Get params
        $params=$this->getRequest()->getParams();
        if($params['startdate']){
            $startdate=Emwork_Utility_DateTime::dateThaiToMySQL(base64_decode($params["startdate"]));
            $enddate=Emwork_Utility_DateTime::dateThaiToMySQL(base64_decode($params["enddate"]));
            $data=$model->getbetweendate($startdate,$enddate);
        }
        else{
            $startdate='';
            $enddate='';
            $data =$model->getlist();
        }


        $dataSum =$model->getsum($startdate,$enddate);
        foreach($dataSum as $item){

        }
        //send to view
        $this->view->data=$data;
        $this->view->datasum=$item;
        $this->view->startdate=	Emwork_Utility_DateTime::MySQLToDateThai($startdate);
        $this->view->enddate=Emwork_Utility_DateTime::MySQLToDateThai($enddate);
        // Set View
        $this->view->canAdd = $canAdd;
        $this->view->pageName = "สถิติเพจยอดนิยม";
        $this->view->pageIcon = "fa fa-newspaper-o";
        $this->view->subMenus = array();
        $this->view->breadcrumb = array(
            array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'Graph', 'controller' => 'page', 'action' => 'index'), null, true)),
            array('name' => 'สถิติเพจยอดนิยม')
            
        );
				// Log Acion
		System_Model_Log::action("Graph.page", "view", null, null);
    }
}
?>