<?php

/**
 * Description of Member_ReportController
 *
 * @author EMWORK Group
 */
class System_ReportController extends Zend_Controller_Action {
 
    /**
     * accessing our session variable
     * @var Zend_Session_Namespace
     */
    protected $session;
    protected $db;
    protected $auth;
    protected $defaultUrlPath = '/message/index/index';
    protected $rightsUrl = '/message/report/rpt001';
    protected $actionName = 'reportsearch';
    protected $exportType = array("pdf" => "PDF", "excel" => "Excel", "word" => "Word"); 
	protected $pageName = '';
     
    public function init() {
    	
    }

    public function preDispatch() {

    }

    public function indexAction() {
    	
    	
    	// Set View
    	$this->_helper->layout()->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    }  

    public function exportAction()
    {
    	// Set View
    	$this->_helper->layout()->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	
    	// TODO: Check Auth
    	$auth = Zend_Auth::getInstance();
    	if (!$auth->hasIdentity()) {
    		exit;
    	}
    	
    	// TODO: Check Rights
    	$authData = $auth->getIdentity();
    	if ($authData->user_type_id != 'A') {
    		exit;
    	}
    	
    	$type = $this->getRequest()->getParam('type');
    	
    	/* ------------------------- Get Sort ------------------------ */
    	$sortIndex = $this->getRequest()->getParam('sortby');
    	$sortDir = $this->getRequest()->getParam('sortdir');
    	
    	$sortBy = 'fullname';
    	switch($sortIndex) {
    		case 1 : $sortBy = 'email'; break;
    		case 2 : $sortBy = 'fullname'; break;
    		case 3 : $sortBy = 'is_enable'; break;
    		default : $sortBy = 'fullname'; break;
    	}
    
    	/* ---------------------- Basic Search ---------------------- */
    	$textSearch = $this->getRequest()->getParam('textsearch');
    	$textSearch = empty($textSearch) ? '' : $textSearch;    	
    	
     	/* -------------------- Advance Search -------------------- */
    	$status = $this->getRequest()->getParam('status');
    	$status = empty($status) ? '%' : $status;
	    
    	// Report Params
	    $reportParams["sortBy"] =  $sortBy;
	    $reportParams["sortDir"] =  $sortDir;
	    $reportParams["textSearch"] =  $textSearch;
    	$reportParams["isEnable"] =  $status;
    	
    	if ($type == 'excel' || $type == 'csv') {
    		$reportParams["is_ignore_pagination"] = true;
    	}
    	
    	// Report Name
    	$reportFilename = "admin.jrxml";
    	$outputFilename = "Admin";


    	$jasperReport = $this->view->JasperReport($reportFilename, $reportParams);


    	// Render
    	switch ($type) {
    		case "csv" :
    			 
    			header("Content-type: application/vnd.ms-excel");
    			header("Content-Disposition: attachment; filename={$outputFilename}.csv");
    			echo $jasperReport->toCsv();
    			return;
    			break;
    		case "excel" :
    			 
    			header("Content-type: application/vnd.ms-excel");
    			header("Content-Disposition: attachment; filename={$outputFilename}.xls");
    			echo $jasperReport->toExcel();
    			return;
    			break;
    		case "pdf" :
    		default :
    			 
    			header("Content-type: application/pdf");
    			header("Content-Disposition: attachment; filename={$outputFilename}.pdf");
    			echo $jasperReport->toPdf();
    			return;
    			break;
    	}
    } 

}
