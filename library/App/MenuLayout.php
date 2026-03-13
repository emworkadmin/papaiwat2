<?php

Class App_MenuLayout {

	protected $URL = '';
	private $defaultMenuGroupUrl = "javascript:void(0);";

	public function __construct() {
		$this->URL = $_SERVER['REDIRECT_URL'];
	}

	/* Generate Menu */
	public function generateMenu($SystemID) {

		$menuStr = $this->generateHome();
		$menuStr .= $this->geneateGroupMenuList(-1, $SystemID);
		return $menuStr;
	}

	private function generateHome() {
		$icon = "";
		$urlPath = "/index/index";
		$menuName = "หน้าหลัก";
		return $drawMenu .= '<li>' . $icon . '<a href="' . $urlPath . '">' . $menuName . '</a></li>';
	}

	private function geneateGroupMenuList($parentMenu, $systemID) {

		Zend_Loader::loadClass('sysmenugroup');
		$model = new sysmenugroup();
		$menuGroupList = $model->generateGroupMenu($parentMenu, $systemID);
		$model->closeConnection();

		$i = 0;
		$drawMenu = '';
		foreach ($menuGroupList as $menuGroup) {
			$drawMenuStr = $subdrawMenu = $subdrawGroupMenu = '';
			// MenuGroup
			$menuGroupID = $menuGroup['MenuGroupID'];
			$menuGroupName = $menuGroup['MenuGroupName'];
			$isShowChildMenu = $menuGroup['IsShowChildMenu'];
			$isShowChildMenuGroup = $menuGroup['IsShowChildMenuGroup'];
			$menuGroupUrl = $this->defaultMenuGroupUrl;
			$menuData = $model->getMenuGroupUrl($menuGroupID, $systemID);
			$model->closeConnection();
			if (!empty($menuData->UrlPath))
				$menuGroupUrl = $menuData->UrlPath;

			$drawMenuStr = "<li><a href='$menuGroupUrl' class='parent' title='$menuGroupName'><span>$menuGroupName</span></a>";
			if ($isShowChildMenu)
				$subdrawMenu .= $this->generateMenuItem($menuGroupID, -1, $systemID);
			if ($isShowChildMenuGroup)
				$subdrawGroupMenu .= $this->geneateGroupMenuList($menuGroupID, $systemID);

			if ($menuGroupUrl != $this->defaultMenuGroupUrl) {
				$drawMenu .= $drawMenuStr;
				if ((!empty($subdrawGroupMenu)) || (!empty($subdrawMenu))) {
					$drawMenu .= '<ul class="sub_menu">';
					$drawMenu .= $subdrawGroupMenu;
					$drawMenu .= $subdrawMenu;
					$drawMenu .= '</ul>';
				}
				$drawMenu .= '</li>';
			} else {
				if ((!empty($subdrawGroupMenu)) || (!empty($subdrawMenu))) {
					$drawMenu .= $drawMenuStr;
					$drawMenu .= '<ul class="sub_menu">';
					$drawMenu .= $subdrawGroupMenu;
					$drawMenu .= $subdrawMenu;
					$drawMenu .= '</ul>';
					$drawMenu .= '</li>';
				}
			}
			$i++;
		}
		return $drawMenu;
	}

	private function generateMenuItem($menuGroupId, $parentMenuId = -1, $systemID) {
		Zend_Loader::loadClass('sysmenugroup');
		$model = new sysmenugroup();
		$menuList = $model->generateMenuItem($menuGroupId, $parentMenuId, $systemID);
		$model->closeConnection();

		$drawMenu = '';

		foreach ($menuList as $menu) {
			$iconstyle = 'padding-left:4px;';
			$urlPath = $menu["UrlPath"];
			$menuName = $menu["MenuName"];
			if (!empty($item['ICONPATH'])):
			//$icon = " background:url( '/uploads/menu/" . $item['ICONPATH'] . "'); width :15px height:15;";
			endif;
			$drawMenu .= "<li style='$icon'><a  href='$urlPath'>$menuName</a></li>";
		}
		return $drawMenu;
	}

	public function getCodePage() {
		//Zend_Loader::loadClass('menu');
		//$model = new menu();
		//$SCREENCODE = $model->getCodePage($this->URL);

		return '-'; //$SCREENCODE;
	}

	public function getNavigator() {
		Zend_Loader::loadClass('sysmenugroup');
		$model = new sysmenugroup();
		$menuObj = $model->getMenuByUrl($this->URL);
		$model->closeConnection();

		$navStr = $this->generateNavigator($menuObj->ParentMenuID, $menuObj->MenuGroupID) . " &gt; <span>" . $menuObj->MenuName . "</span>";

		return $navStr;
	}
	private function generateNavigator($menuId, $menuGroupId){

		Zend_Loader::loadClass('sysmenugroup');
		$model = new sysmenugroup();
		$navStr = "";

		if (empty($menuId)){
			if (empty($menuGroupId)){
				$navStr = "";
			} else {
				$menuGroup = $model->item($menuGroupId);
				$model->closeConnection();
				$navStr = " &gt; <span>" . $menuGroup->MenuGroupName . "</span>" . $navStr;
				$navStr = $this->generateNavigator(null, $menuGroup->ParentMenuGroupID) . $navStr;
			}
		} else {
			$menu = $model->itemMenu($menuId);
			$model->closeConnection();
			$navStr = " &gt; <a href='" . $menu->UrlPath . "' title='" . $menu->MenuName . "'>" . $menu->MenuName . "</a>" . $navStr;
			$navStr = $this->generateNavigator($menu->ParentMenuID, $menu->MenuGroupID) . $navStr;
		}
		$model->closeConnection();
		return $navStr;
	}

	public function getServiceMenu($SystemID) {
		return;
	}

	public function getSystemByUser($userID) {

		Zend_Loader::loadClass('sysuser');
		$modelUser = new sysuser();
		$userRoles = $modelUser->getUserRoles($userID);
		$modelUser->closeConnection();

		Zend_Loader::loadClass('systems');
		$modelSystem = new systems();
		$systemArray = array();
		foreach ($userRoles as $item) {
			$sys = $modelSystem->item($item["SystemID"]);
			$modelSystem->closeConnection();
			$systemArray[] = array("SystemID" => $sys->SystemID, "SystemName" => $sys->SystemName, "FilePath" => $sys->FilePath);
		}
		return $systemArray;
	}
}

?>
