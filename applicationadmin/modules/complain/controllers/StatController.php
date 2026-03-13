<?php
class Complain_StatController extends Zend_Controller_Action
{
    protected $yearOption = array();
    protected $monthOption = array(
        "1" => "มกราคม",
        "2" => "กุมภาพันธ์",
        "3" => "มีนาคม",
        "4" => "เมษายน",
        "5" => "พฤษภาคม",
        "6" => "มิถุนายน",
        "7" => "กรกฏาคม",
        "8" => "สิงหาคม",
        "9" => "กันยายน",
        "10" => "ตุลาคม",
        "11" => "พฤศจิกายน",
        "12" => "ธันวาคม",
    );
  
    public function init()
    {
        $year = array();
		$year[date("Y")] = date("Y") + 543;
		$i;
		for($i = 0;$i < 5; $i++) {
			$year[date("Y") - $i] = date("Y") + 543 - $i;
        }
        $this->yearOption = $year;
    }

    function indexAction()
    {
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
        $canView = $sUser->isAllow("complain.stat.view");
        if (!$canView) {
            $this->_helper->redirector('index', 'index', 'default');
        }


        $months = array();
        for ($i = 0; $i < 12; $i++) {
            $timestamp = mktime(0, 0, 0, date('n') - $i, 1);
            $months[date('n', $timestamp)] = date('F', $timestamp);
        }
        
        $this->view->yearOption = Emwork_Utility::addEmptyOption($this->yearOption);
        $this->view->monthOption = Emwork_Utility::addEmptyOption($this->monthOption);

        $this->view->year = date('Y');
        $this->view->month = date('m');

		$this->view->pageName = "รายงานสถิติการร้องเรียน";
		$this->view->pageIcon = "fa fa-file-text-o";
	    $this->view->subMenus = array();
	    $this->view->breadcrumb = array(
	        array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
            array('name' => 'รายงานสถิติการร้องเรียน')
        );
        System_Model_Log::action("complain.stat", "view", null, null);
    }

    function getstatAction()
    {
		// get Params
		$selectYear = $this->getRequest()->getParam('selectYear');
        $selectMonth = $this->getRequest()->getParam('selectMonth');

		// Model
        $model = new Complain_Model_Stat();

        $getlistComplain = $model->getlistComplain($selectYear, $selectMonth);
        $this->view->data = $getlistComplain;
        $this->view->month = $this->monthOption[$selectMonth];
        $this->view->year = $this->yearOption[$selectYear];

        Zend_Layout::resetMvcInstance();
    }

}
?>