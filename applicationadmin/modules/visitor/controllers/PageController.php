<?php
class Visitor_PageController extends Zend_Controller_Action
{   protected $export=array(
        "today"=>"รายงานสถิติวันนี้",
        "tomonth"=>"รายงานสถิติเดือนนี้",
        "lastmonth"=>"รายงานสถิติเดือนที่แล้ว",
        "viewall"=>"รายงานสถิติทั้งหมด"
    );
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
		$canView = $sUser->isAllow('visitor.stat.view');
		if (!$canView) {
			$this->_helper->redirector('index', 'index', 'default');
        }
        
        $languages = System_Model_Setting::getLanguages();
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
        $model = new Visitor_Model_Visitor();

        //Get params
        $params=$this->getRequest()->getParams();

        		// Get Data
		$data = new stdClass();
		$data->VisitorSiteOpenDate = System_Model_Setting::getValue("VisitorSiteOpenDate", "Visitor");
		$data->VisitorBeginVisitor = System_Model_Setting::getValue("VisitorBeginVisitor", "Visitor");
		$data->VisitorSiteModifiedDate = System_Model_Setting::getValue("VisitorSiteModifiedDate", "Visitor");
		$data->VisitorSiteVisitor = System_Model_Setting::getValue("VisitorSiteVisitor", "Visitor");

        $startdate = empty($params['startdate']) ? date("Y-m-d") : Emwork_Utility_DateTime::dateThaiToMySQL(base64_decode($params["startdate"]));
        $enddate = empty($params['enddate']) ? date("Y-m-d") : Emwork_Utility_DateTime::dateThaiToMySQL(base64_decode($params["enddate"]));

        //send to view
        $this->view->datatoday=$datatoday;
        $this->view->data=$data;
        $this->view->export=Emwork_Utility::addEmptyOption ($this->export,"--ส่งออกรายงานสถิติการเยี่ยมชม--");
        $this->view->startdate = Emwork_Utility_DateTime::MySQLToDateThai($startdate);
        $this->view->enddate = Emwork_Utility_DateTime::MySQLToDateThai($enddate);
        // Set View
        $this->view->canAdd = $canAdd;
        $this->view->pageName = "สถิติการเยี่ยมชม";
        $this->view->pageIcon = "fa fa-newspaper-o";
        $this->view->subMenus = array();
        $this->view->breadcrumb = array(
            array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'visitor', 'controller' => 'page', 'action' => 'index'), null, true)),
            array('name' => 'สถิติการเยี่ยมชม')
            
        );
				// Log Acion
		System_Model_Log::action("visitor.page", "view", null, null);
    }
    public function todayAction() { 
        // Get Data
        $data = new stdClass();
        $data->VisitorSiteOpenDate = System_Model_Setting::getValue("VisitorSiteOpenDate", "Visitor");
        $data->VisitorBeginVisitor = System_Model_Setting::getValue("VisitorBeginVisitor", "Visitor");
        $data->VisitorSiteModifiedDate = System_Model_Setting::getValue("VisitorSiteModifiedDate", "Visitor");
        $data->VisitorSiteVisitor = System_Model_Setting::getValue("VisitorSiteVisitor", "Visitor");

        $model = new Visitor_Model_Visitor();
        //Get params
        $params=$this->getRequest()->getParams();

        $startdate = empty($params['startdate']) ? date("Y-m-d") : Emwork_Utility_DateTime::dateThaiToMySQL(base64_decode($params["startdate"]));
        $enddate = empty($params['enddate']) ? date("Y-m-d") : Emwork_Utility_DateTime::dateThaiToMySQL(base64_decode($params["enddate"]));
        
        if($data->VisitorSiteOpenDate > $startdate){
            $startdate=$data->VisitorSiteOpenDate;
        }
        else{
            $startdate=$startdate;
        }
        $data = $model->getLogAccess($startdate,$enddate);

        $datatoday=array();
        $x=1;
        for($i=0;$i<24;$i++){
            if($i<=9){
                $startdateTime = $startdate." 0".$i.":00";
                $enddateTime = $enddate." 0".$x.":00";
                $Rang =$i.":00"."-".$x.":00";
                $tmp = $model->getLogAccessDate($startdateTime,$enddateTime,$Rang);
                $value=array(
                    "Rang"=>$Rang,
                    "Amount"=>$tmp->Amount
                );
                $datatoday[]=$value;
            }
            else{
                $startdateTime=$startdate." ".$i.":00";
                $enddateTime = $enddate." ".$x.":00";
                $Rang =$i.":00"."-".$x.":00";
                $tmp = $model->getLogAccessDate($startdateTime,$enddateTime,$Rang);
                $value=array(
                    "Rang"=>$Rang,
                    "Amount"=>$tmp->Amount
                );
                $datatoday[]=$value;
            }
            $x++;
        }


        //send to view
        $this->view->datatoday=$datatoday;
        $this->view->data=$data;

		Zend_Layout::resetMvcInstance();
    }
    public function tomonthAction() { 
        // Get Data
        $data = new stdClass();
        $data->VisitorSiteOpenDate = System_Model_Setting::getValue("VisitorSiteOpenDate", "Visitor");
        $data->VisitorBeginVisitor = System_Model_Setting::getValue("VisitorBeginVisitor", "Visitor");
        $data->VisitorSiteModifiedDate = System_Model_Setting::getValue("VisitorSiteModifiedDate", "Visitor");
        // $data->VisitorSiteVisitor = System_Model_Setting::getValue("VisitorSiteVisitor", "Visitor");

        $model = new Visitor_Model_Visitor();
        //Get params
        $params=$this->getRequest()->getParams();

        $startdate = empty($params['startdate']) ? date("Y-m-01") : Emwork_Utility_DateTime::dateThaiToMySQL(base64_decode($params["startdate"]));
        $enddate = empty($params['enddate']) ? date("Y-m-t") : Emwork_Utility_DateTime::dateThaiToMySQL(base64_decode($params["enddate"]));

        
        if($data->VisitorSiteOpenDate > $startdate){
            $startdate=$data->VisitorSiteOpenDate;
        }
        else{
            $startdate=$startdate;
        }

        $datatomonth = $model->getLogAccessMonth($startdate,$enddate);
        $data = $model->getLogAccess($startdate,$enddate);
        
        //send to view
        $this->view->datatomonth=$datatomonth;
        $this->view->data=$data;

		Zend_Layout::resetMvcInstance();
    }
    public function lastmonthAction() { 
        // Get Data
        $data = new stdClass();
        $data->VisitorSiteOpenDate = System_Model_Setting::getValue("VisitorSiteOpenDate", "Visitor");
        $data->VisitorBeginVisitor = System_Model_Setting::getValue("VisitorBeginVisitor", "Visitor");
        $data->VisitorSiteModifiedDate = System_Model_Setting::getValue("VisitorSiteModifiedDate", "Visitor");
        $data->VisitorSiteVisitor = System_Model_Setting::getValue("VisitorSiteVisitor", "Visitor");

        $model = new Visitor_Model_Visitor();
        //Get params
        $params=$this->getRequest()->getParams();
        // $date=date("m")."-01";
        $startdate = empty($params['startdate']) ?  date( "Y-m-01", strtotime("-1 month") ) : Emwork_Utility_DateTime::dateThaiToMySQL(base64_decode($params["startdate"]));
        $enddate = empty($params['enddate']) ? date("Y-m-t", strtotime("-1 month") ) : Emwork_Utility_DateTime::dateThaiToMySQL(base64_decode($params["enddate"]));
        
        if($data->VisitorSiteOpenDate > $startdate){
            $startdate=$data->VisitorSiteOpenDate;
        }
        else{
            $startdate=$startdate;
        }

        $datatomonth = $model->getLogAccessMonth($startdate,$enddate);
        $data = $model->getLogAccess($startdate,$enddate);


        //send to view
        $this->view->datatomonth=$datatomonth;
        $this->view->data=$data;

		Zend_Layout::resetMvcInstance();
    }
    public function viewallAction() { 
        // Get Data
        $data = new stdClass();
        $data->VisitorSiteOpenDate = System_Model_Setting::getValue("VisitorSiteOpenDate", "Visitor");
        $data->VisitorBeginVisitor = System_Model_Setting::getValue("VisitorBeginVisitor", "Visitor");
        $data->VisitorSiteModifiedDate = System_Model_Setting::getValue("VisitorSiteModifiedDate", "Visitor");
        // $data->VisitorSiteVisitor = System_Model_Setting::getValue("VisitorSiteVisitor", "Visitor");

        $model = new Visitor_Model_Visitor();
        //Get params
        $params=$this->getRequest()->getParams();

        $startdate = empty($params['startdate']) ? date("Ym") : Emwork_Utility_DateTime::dateThaiToMySQL(base64_decode($params["startdate"]));
        $enddate = empty($params['enddate']) ? date("Ym") : Emwork_Utility_DateTime::dateThaiToMySQL(base64_decode($params["enddate"]));
   
        $startdate = date("Ym", strtotime($data->VisitorSiteOpenDate));
        $enddate = date("Ym", strtotime($enddate));

        $datatomonth = $model->getLogAccessviewAll($startdate,$enddate);

        $data = $model->getLogAccessAll($startdate,$enddate);
        
        //send to view
        $this->view->datatomonth=$datatomonth;
        $this->view->data=$data;

		Zend_Layout::resetMvcInstance();
    }
    public function exporttodayAction() {     
        // Get Data
        $data = new stdClass();
        $data->VisitorSiteOpenDate = System_Model_Setting::getValue("VisitorSiteOpenDate", "Visitor");
        $data->VisitorBeginVisitor = System_Model_Setting::getValue("VisitorBeginVisitor", "Visitor");
        $data->VisitorSiteModifiedDate = System_Model_Setting::getValue("VisitorSiteModifiedDate", "Visitor");
        // Model
        $model = new Visitor_Model_Visitor();

        //Get params
        $params=$this->getRequest()->getParams();

        $startdate = empty($params['startdate']) ? date("Y-m-d") : Emwork_Utility_DateTime::dateThaiToMySQL(base64_decode($params["startdate"]));
        $enddate = empty($params['enddate']) ? date("Y-m-d") : Emwork_Utility_DateTime::dateThaiToMySQL(base64_decode($params["enddate"]));
        
        if($data->VisitorSiteOpenDate > $startdate){
            $startdate=$data->VisitorSiteOpenDate;
        }
        else{
            $startdate=$startdate;
        }
        $data = $model->getLogAccess($startdate,$enddate);
        $datatoday=array();
        $x=1;
        for($i=0;$i<24;$i++){
            if($i<=9){
                $startdateTime = $startdate." 0".$i.":00";
                $enddateTime = $enddate." 0".$x.":00";
                $Rang =$i.":00"."-".$x.":00";
                $tmp = $model->getLogAccessDate($startdateTime,$enddateTime,$Rang);
                $value=array(
                    "Rang"=>$Rang,
                    "Amount"=>$tmp->Amount
                );
                $datatoday[]=$value;
            }
            else{
                $startdateTime=$startdate." ".$i.":00";
                $enddateTime = $enddate." ".$x.":00";
                $Rang =$i.":00"."-".$x.":00";
                $tmp = $model->getLogAccessDate($startdateTime,$enddateTime,$Rang);
                $value=array(
                    "Rang"=>$Rang,
                    "Amount"=>$tmp->Amount
                );
                $datatoday[]=$value;
            }
            $x++;
        }

    	// Set View
        $this->_helper->layout->disableLayout();
        $this->view->datatoday=$datatoday;
        $this->view->data=$data;
        $this->view->startdate=Emwork_Utility_DateTime::MySQLToDateThai($startdate);
        $this->view->enddate=Emwork_Utility_DateTime::MySQLToDateThai($enddate);
    	 
    	// Set Response
    	header("Content-Type: application/vnd.ms-excel");
    	header("Expires: 0");
    	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    	header("content-disposition: attachment;filename=สถิติการเยี่ยมชมวันนี้.xls");
    }
    public function exporttomonthAction() {     
        // Get Data
        $data = new stdClass();
        $data->VisitorSiteOpenDate = System_Model_Setting::getValue("VisitorSiteOpenDate", "Visitor");
        $data->VisitorBeginVisitor = System_Model_Setting::getValue("VisitorBeginVisitor", "Visitor");
        $data->VisitorSiteModifiedDate = System_Model_Setting::getValue("VisitorSiteModifiedDate", "Visitor");
        
        // Model
        $model = new Visitor_Model_Visitor();
        //Get params
        $params=$this->getRequest()->getParams();

        $startdate = empty($params['startdate']) ? date("Y-m-01") : Emwork_Utility_DateTime::dateThaiToMySQL(base64_decode($params["startdate"]));
        $enddate = empty($params['enddate']) ? date("Y-m-d") : Emwork_Utility_DateTime::dateThaiToMySQL(base64_decode($params["enddate"]));
        
        if($data->VisitorSiteOpenDate > $startdate){
            $startdate=$data->VisitorSiteOpenDate;
        }
        else{
            $startdate=$startdate;
        }
        $data = $model->getLogAccess($startdate,$enddate);
        // $data = $model->getLogAccess();
        $datatomonth = $model->getLogAccessMonth($startdate,$enddate);
    	// Set View
        $this->_helper->layout->disableLayout();
        $this->view->datatomonth=$datatomonth;
        $this->view->data=$data;
        $this->view->startdate=Emwork_Utility_DateTime::MySQLToDateThai($startdate);
        $this->view->enddate=Emwork_Utility_DateTime::MySQLToDateThai($enddate);
    	 
    	// Set Response
    	header("Content-Type: application/vnd.ms-excel");
    	header("Expires: 0");
    	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    	header("content-disposition: attachment;filename=สถิติการเยี่ยมชมเดือนปัจจุบัน.xls");
    }
    public function exportlastmonthAction() {     
        // Get Data
        $data = new stdClass();
        $data->VisitorSiteOpenDate = System_Model_Setting::getValue("VisitorSiteOpenDate", "Visitor");
        $data->VisitorBeginVisitor = System_Model_Setting::getValue("VisitorBeginVisitor", "Visitor");
        $data->VisitorSiteModifiedDate = System_Model_Setting::getValue("VisitorSiteModifiedDate", "Visitor");

        // Model
        $model = new Visitor_Model_Visitor();

        $params=$this->getRequest()->getParams();
        // $date=date("m")."-01";
        $startdate = empty($params['startdate']) ?  date( "Y-m-01", strtotime("-1 month") ) : Emwork_Utility_DateTime::dateThaiToMySQL(base64_decode($params["startdate"]));
        $enddate = empty($params['enddate']) ? date("Y-m-t", strtotime("-1 month") ) : Emwork_Utility_DateTime::dateThaiToMySQL(base64_decode($params["enddate"]));
        
        if($data->VisitorSiteOpenDate > $startdate){
            $startdate=$data->VisitorSiteOpenDate;
        }
        else{
            $startdate=$startdate;
        }

        $datalastmonth = $model->getLogAccessMonth($startdate,$enddate);
        $data = $model->getLogAccess($startdate,$enddate);

    	// Set View
        $this->_helper->layout->disableLayout();
        $this->view->datalastmonth=$datalastmonth;
        $this->view->data=$data;
        $this->view->startdate=Emwork_Utility_DateTime::MySQLToDateThai($startdate);
        $this->view->enddate=Emwork_Utility_DateTime::MySQLToDateThai($enddate);
    	 
    	// Set Response
    	header("Content-Type: application/vnd.ms-excel");
    	header("Expires: 0");
    	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    	header("content-disposition: attachment;filename=สถิติการเยี่ยมชมเดือนที่แล้ว.xls");
    }
    public function exportviewallAction() {     
        
        // Get Data
        $data = new stdClass();
        $data->VisitorSiteOpenDate = System_Model_Setting::getValue("VisitorSiteOpenDate", "Visitor");
        $data->VisitorBeginVisitor = System_Model_Setting::getValue("VisitorBeginVisitor", "Visitor");
        $data->VisitorSiteModifiedDate = System_Model_Setting::getValue("VisitorSiteModifiedDate", "Visitor");

        $model = new Visitor_Model_Visitor();
        //Get params
        $params=$this->getRequest()->getParams();

        $startdate = empty($params['startdate']) ? date("Ym") : Emwork_Utility_DateTime::dateThaiToMySQL(base64_decode($params["startdate"]));
        $enddate = empty($params['enddate']) ? date("Ym") : Emwork_Utility_DateTime::dateThaiToMySQL(base64_decode($params["enddate"]));
   
        $startdate = date("Ym", strtotime($data->VisitorSiteOpenDate));
        $enddate = date("Ym", strtotime($enddate));

        $dataviewall = $model->getLogAccessviewAll($startdate,$enddate);
        $data = $model->getLogAccess($startdate,$enddate);
    	// Set View
        $this->_helper->layout->disableLayout();
        $this->view->dataviewall=$dataviewall;
        $this->view->data=$data;
    	 
    	// Set Response
    	header("Content-Type: application/vnd.ms-excel");
    	header("Expires: 0");
    	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    	header("content-disposition: attachment;filename=สถิติการเยี่ยมชมทั้งหมด.xls");
    }
}
?>