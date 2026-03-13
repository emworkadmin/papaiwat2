<?php 
require 'emdefine.php';
Class Emwork_Acl_Acl {
	public  $acl = null;
	public  $Module = null;
	public  $userRole = null;
	public  $Resource = null;
	public  $PageType = null;

	public  $Role = array();

	public function __construct($Module,$uerRole,$Resource,$PageType)
	{
		$this->acl = new Zend_Acl();
		 
		$this->userRole =  $uerRole;
		$this->Module =  $Module;
		$this->Resource =  $Resource;
		$this->PageType =  $PageType;
		 
		//$this->Role = emdefine::getRole();

		//emdefine::setUserRole($uerRole);
	}

	private function setModule($Module){
		$this->Module =  $Module;
	}
	 
	private function setRole($Role){
		$this->userRole =  $Role;
	}

	private function setResource($Resource){
		$this->Resource =  $Resource;
	}


	public function checkAcl(){
		//if(!emacl::checkRole())
		//$result = 'ระบบสิทธิ์ไม่ถูกต้อง กรุณาติดต่อผู้ดูแลระบบ';

		//$result = false;


		//if(emacl::checkResource() == 'Y'): //$result = 'ไม่ได้รับการควบคุม';
		//	$result = emacl::setAcl();
		//endif;

		 
		return true;//$result;
	}

	public function checkAction($action){
		//$resultResource = emdefine::getPermission('/'.$this->Module.'/'.$this->Resource, $this->userRole);
		//$result = in_array($action, $resultResource);
		 
		//if(!$result)return 'N';
			
		return 'Y';
	}

	private function checkResource(){
		$resultResource = emdefine::getResource('/'.$this->Module.'/'.$this->Resource);


		 
		$result = in_array($this->PageType, $resultResource);
		 
		if(!$result) return 'N';
		return 'Y';
	}

	private function checkRole(){

		$result = in_array($this->userRole, $this->Role);

		if($result == '')
			return 'N';

		return 'Y';
	}

	private function setACL(){
		//Add Role
		foreach($this->Role as $item):
		$this->acl->addRole(new Zend_Acl_Role($item));
		endforeach;



		//Add Resource
		$resultResource = emdefine::getResource('/'.$this->Module .'/'. $this->Resource );

		 
		foreach($resultResource as $item):
		$this->acl->addResource(new Zend_Acl_Resource( '/'.$this->Module .'/'. $item));
		endforeach;

		//Permisssion
		$userPermission = emdefine::getPermission('/'.$this->Module .'/'. $this->Resource,$this->userRole);

		foreach($userPermission as $item):
		$this->acl->allow($this->userRole, '/'.$this->Module .'/'. $item);
		endforeach;
		return $this->acl->isAllowed($this->userRole, '/'.$this->Module .'/'. $this->PageType);
	}
}
?>