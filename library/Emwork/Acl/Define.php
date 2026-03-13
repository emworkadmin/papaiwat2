<?php 
Class Emwork_Acl_Define {
	public $Resource = array() ;
	public $page = array() ;
	public $permission = array() ;
	public $Role = array();
	public $userRole = null;


	public function defineRole(){
		$config = new Zend_Config_Ini('../application/configs/application.ini', 'oracle');
		$params = array('username' => $config->db->params->username,
				'password'=>$config->db->params->password,
				'dbname'=>$config->db->params->dbname);
		// setup database
		$db = Zend_Db::factory('Oracle',$params);
		$select = $db->select()
		->from(array('P' => 'PERMISSION'),array('*'))
		->where(" ISENABLE = 'Y' AND ISDELETED = 'N' " );
		$stmt = $db->query($select);
		$data = $stmt->fetchAll();

		foreach($data as $item):
		$this->Role[] = $item['LDAPNAME'];
		endforeach;
		/*$this->Role = array('Administrator','BackOfficeOfficer',
		 'BackOfficeReporter','RegionReporter',
				'OfficerDirector','RegionDirector');*/
	}

	public function setUserRole($userRole){
		$this->userRole = $userRole;
	}

	public function getRole(){
		emdefine::defineRole();
		return $this->Role;
	}

	public function definePage($selectPage)
	{
		$config = new Zend_Config_Ini('../application/configs/application.ini', 'oracle');
		$params = array( 	'username' => $config->db->params->username,
				'password'=>$config->db->params->password,
				'dbname'=>$config->db->params->dbname);
		// setup database
		$db = Zend_Db::factory('Oracle',$params);
		$select = $db->select()
		->from(array('M' => 'MENU'),array('*'))
		->where(" URLPATH = '". $selectPage ."'" );
		$stmt = $db->query($select);
		$data = $stmt->fetchObject();



		$this->page = array();
		if(!empty($data->HASADD))
			$this->page[$selectPage][] = 'add';
		if(!empty($data->HASEDIT))
			$this->page[$selectPage][] = 'edit';
		if(!empty($data->HASDEL))
			$this->page[$selectPage][] = 'delete';
		if(!empty($data->HASVIEW))
			$this->page[$selectPage][] = 'list';
		if(!empty($data->HASSVIEW))
			$this->page[$selectPage][] = 'slist';

		//$this->page['menu'] = array('liste','add','edit','grouplist','groupadd','groupedit');



		$select = $db->select()
		->from(array('M' => 'MENU'),array('URLPATH'))
		->joinLeft(array('PM' => 'PERMISSIONMAPPING'),' M.MENUID = PM.MENUID ',array('*'))
		->where(" M.URLPATH = '". $selectPage ."'" );
		$stmt = $db->query($select);
		$data = $stmt->fetchObject();

		$this->permission = array();
		if(!empty($data->HASADD))
			$this->permission[$selectPage][$this->userRole][] = 'add';
		if(!empty($data->HASEDIT))
			$this->permission[$selectPage][$this->userRole][] = 'edit';
		if(!empty($data->HASDEL))
			$this->permission[$selectPage][$this->userRole][] = 'delete';
		if(!empty($data->HASVIEW))
			$this->permission[$selectPage][$this->userRole][] = 'list';
		if(!empty($data->HASSVIEW))
			$this->permission[$selectPage][$this->userRole][] = 'slist';


		/*
		 if($selectPage == 'menu'){
		$this->page['menu'] = array('list','add','edit','grouplist','groupadd','groupedit');
		$this->permission['menu']['Administrator'] = array('list','add','edit','grouplist','groupadd','groupedit');
		}else if($selectPage == 'power'){
		$this->page['power'] = array('list','add','edit');
		$this->permission['power']['Administrator'] = array('list','add','edit');
		}else if($selectPage == 'evidence'){
		$this->page['evidence'] = array('list','add','edit');
		$this->permission['evidence']['Administrator'] = array('list','add','edit');
		}else if($selectPage == 'narcotic'){
		$this->page['narcotic'] = array('list','add','edit');
		$this->permission['narcotic']['Administrator'] = array('list','add','edit');
		}else if($selectPage == 'appointdocpass'){
		$this->page['appointdocpass'] = array('list','update','print');
		$this->permission['appointdocpass']['Administrator'] = array('list','update','print');
		}else if($selectPage == 'appointdocreview'){
		$this->page['appointdocreview'] = array('list','update','print');
		$this->permission['appointdocreview']['Administrator'] = array('list','update','print');
		}else if($selectPage == 'appointregionconfirm'){
		$this->page['appointregionconfirm'] = array('list','update','print');
		$this->permission['appointregionconfirm']['Administrator'] = array('list','update','print');
		}else if($selectPage == 'appointsecretaryhold'){
		$this->page['appointsecretaryhold'] = array('list','update','print');
		$this->permission['appointsecretaryhold']['Administrator'] = array('list','update','print');
		}else if($selectPage == 'appointsecretaryapprove'){
		$this->page['appointsecretaryapprove'] = array('list','update','print');
		$this->permission['appointsecretaryapprove']['Administrator'] = array('list','update','print');
		}else if($selectPage == 'appointboardapprove'){
		$this->page['appointboardapprove'] = array('list','update','print');
		$this->permission['appointboardapprove']['Administrator'] = array('list','update','print');
		}else if($selectPage == 'appointboardpass'){
		$this->page['appointboardpass'] = array('list','update','print');
		$this->permission['appointboardpass']['Administrator'] = array('list','update','print');
		}else if($selectPage == 'appointsendcard'){
		$this->page['appointsendcard'] = array('list','update','print');
		$this->permission['appointsendcard']['Administrator'] = array('list','update','print');
		}else if($selectPage == 'appointcoursepass'){
		$this->page['appointcoursepass'] = array('list','update','print');
		$this->permission['appointcoursepass']['Administrator'] = array('list','update','print');
		}else if($selectPage == 'appointsecretarysign'){
		$this->page['appointsecretarysign'] = array('list','update','print');
		$this->permission['appointsecretarysign']['Administrator'] = array('list','update','print');
		}else if($selectPage == 'person'){
		$this->page['person'] = array('listofficer','update','print');
		$this->permission['person']['Administrator'] = array('listofficer','update','print');
		}else if($selectPage == 'cancelwait'){
		$this->page['cancelwait'] = array('list','update','print');
		$this->permission['cancelwait']['Administrator'] = array('list','update','print');
		}else if($selectPage == 'cancelsecretaryapprove'){
		$this->page['cancelsecretaryapprove'] = array('list','update','print');
		$this->permission['cancelsecretaryapprove']['Administrator'] = array('list','update','print');
		}else if($selectPage == 'cancelsendregion'){
		$this->page['cancelsendregion'] = array('list','update','print');
		$this->permission['cancelsendregion']['Administrator'] = array('list','update','print');
		}else if($selectPage == 'cancelsendback'){
		$this->page['cancelsendback'] = array('list','update','print');
		$this->permission['cancelsendback']['Administrator'] = array('list','update','print');
		}else if($selectPage == 'renewwait'){
		$this->page['renewwait'] = array('list','update','print');
		$this->permission['renewwait']['Administrator'] = array('list','update','print');
		}else if($selectPage == 'renewsecretary'){
		$this->page['renewsecretary'] = array('list','update','print');
		$this->permission['renewsecretary']['Administrator'] = array('list','update','print');
		}else if($selectPage == 'renewcard'){
		$this->page['renewcard'] = array('list','update','print');
		$this->permission['renewcard']['Administrator'] = array('list','update','print');
		}else if($selectPage == 'renewdue'){
		$this->page['renewdue'] = array('list','update','print');
		$this->permission['renewdue']['Administrator'] = array('list','update','print');
		}else if($selectPage == 'renewsixmonth'){
		$this->page['renewsixmonth'] = array('list','update','print');
		$this->permission['renewsixmonth']['Administrator'] = array('list','update','print');
		}
		*/

	}

	public function getResource($selectPage) {
		emdefine::definePage($selectPage);

		return $this->page[$selectPage];
	}


	public function getPermission($selectPage,$userRole){
		emdefine::definePage($selectPage);
		return $this->permission[$selectPage][$userRole];
	}

}
?>