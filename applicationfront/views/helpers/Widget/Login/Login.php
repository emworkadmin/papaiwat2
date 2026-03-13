<?php
class Zend_View_Helper_Widget_Login_Login extends Zend_View_Helper_Abstract
{

	protected $params = array();
    protected $lang = array();
    
	public function Widget_Login_Login($params)
	{
        $this->params = $params;
        
        // Find Lang
		if (Zend_Registry::isRegistered('lang')) {
			$this->lang = Zend_Registry::get('lang');
		} else {
			$defaultLanguage = System_Model_Setting::getDefaultLanguage();
			$this->lang = $defaultLanguage["Code"];
		}

		return $this;
    }

    public function display() {

        $auth = Zend_Auth::getInstance();
        $isLogin = $auth->getIdentity();
        $authData = $auth->getIdentity();

        
        $lang = Zend_Registry::isRegistered("lang") ? Zend_Registry::get("lang") : "";
        if (empty($lang)) {
            $defaultLanguage = System_Model_Setting::getDefaultLanguage();
            $lang = $defaultLanguage["Code"];
        }
        
        $authData->UserFullname = $authData->FirstName . ' ' . $authData->LastName;
        $profileFullname = empty($authData->UserFullname) ? '' : $authData->UserFullname;
        
        $profilePicturePath = empty($authData->ProfileImageHashName) ? $this->view->baseUrl('images/logos/user.png') : $this->view->url(array('module' => 'default', 'controller' => 'file', 'action' => 'get', 'file' => $authData->ProfileImageHashName), null, true);

        echo '<style>';
        echo '#widget-login.navbar-nav { margin: 0px; }';
        echo '#widget-login.navbar-nav>li>a { padding: 7px 10px 4px; color: #fff; background: #929090; border-radius: 20px; } ';
        echo '#widget-login.navbar-nav>.user-menu .user-image { float: unset; background: white; margin-right: 5px; } ';
        echo '#widget-login.navbar-nav>.user-menu>.dropdown-menu { min-width: unset; width: 100%; } ';
        echo '</style>';


        echo '<ul class="nav navbar-nav" id="widget-login">';
        if ($isLogin) {
        echo '<li class="dropdown user user-menu">';
        echo '<a href="#" class="dropdown-toggle" data-toggle="dropdown">';

        if(!empty($profilePicturePath)) {
        echo '<img alt="image" src="' . $profilePicturePath . '" class="user-image" alt="User Image">';
        } else {
            echo '&nbsp;<em class="fa fa-user"></em>';
        }

        echo '<span class="">';
        echo $profileFullname;
        echo '</span>';
        echo '</a>';

        echo '<ul class="dropdown-menu"  style="z-index: 10000;" role="menu">';
        echo '<li>';
        echo '<a href="' . $this->view->url(array('module' => 'profile', 'controller' => 'index', 'action' => 'index'), null, true) . '"><em class="fa fa-user"></em> <span class="hidden-xs">ข้อมูลส่วนตัว</span></a>';
        echo '</li>';
        echo '<li>';
        echo '<a href="' . $this->view->url(array('module' => 'default', 'controller' => 'auth', 'action' => 'logout'), null, true) . '"><em class="fa fa-sign-out"></em> <span class="hidden-xs">ออกจากระบบ</span></a>';
        echo '</li>';
        echo '</ul>';
        echo '</li>';
        } else {
        echo '<li>';
        echo '<a id="lnkLogin" href="' . $this->view->url(array('module' => 'default', 'controller' => 'auth', 'action' => 'login'), null, true) . '"><em class="fa fa-user"></em> <span class="hidden-xs">'. Emwork_Translate::text($lang, 'wlogin', 'login') .'</span></a>';
        echo '</li>';
        }
        echo '</ul>';
        echo '<div class="clearfix"></div>';
        
    }

}
?>