<?php 

class Sitemap_IndexController extends Zend_Controller_Action
{

    protected $lang;
    protected $mainCategoryID = '1';

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

    public function indexAction()
	{
		// Log Access
        System_Model_Log::access();
        
        // Zend Filter
        $filterInt = new Zend_Filter_Int();

        // Get Param
        $id = $filterInt->filter($this->_request->getParam('id'));

        $model = new Sitemap_Model_Index();
        $data = $model->getDataSitemapInfo($id, $this->lang);

        $this->view->id = $id;
        $this->view->data = $data;
		$this->view->pageTitle = 'รายการ Sitemap';
        
        $menuID = $this->findMenu("/sitemap/index/index/id/" . $id);
        
		Zend_Registry::set("MenuID", $menuID);        
    }

}

?>