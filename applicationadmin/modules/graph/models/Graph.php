<?php

class Graph_Model_Graph {
	
	/**
	 *
	 * @var Zend_Db_Adapter_Abstract
	 */
	protected $db = null;
	protected $UserID = null;
	protected $DeptID = null;
	protected $auth = null;
	protected $serverWH="192.168.1.238";
	protected $databaseWH="";
	protected $usernameWH="sa";
	protected $passwordWH="p@33w0rd";

	
	function __construct() {
		try {
			// setup database
			$this->db = Zend_Db_Table::getDefaultAdapter();
			
			// do the authentication
			$authobj = Zend_Auth::getInstance();
			$this->auth = $authobj->getIdentity();
			$this->UserID = isset($this->auth->UserID) ? $this->auth->UserID : '';
		} catch (Exception $e) {
			
		}
	}
	function getData(){
		$server = $this->serverWH;
		$database = $this->databaseWH;
		$username = $this->usernameWH;
		$password = $this->passwordWH;
		$conn = new PDO( "sqlsrv:server=".$server." ; Database=".$database."", $username, $password);
		$conn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
		$sql = "INSERT INTO [dbo].[MetaFileCollection](
						[FileType]
					   ,[FileMainCategory]
					   ,[FileCategory]
					   ,[FileName]
					   ,[FileUrl]
					   ,[FileOwnerMinistry]
					   ,[FileOwner]
					   ,[FileOwnerBureau]
					   ,[FileYear]
					   ,[FileLanguage]
					   ,[CreatedDate])
				 VALUES
					   ('".$FileType."'
					   ,'".$FileMainCategory."'
					   ,'".$FileCategory."'
					   ,'".$FileName."'
					   ,'".$FileUrl."'
					   ,'".$FileOwnerMinistry."'
					   ,'".$FileOwner."'
					   ,'".$FileOwnerBureau."'
					   ,'".$FileYear."'
					   ,'".$FileLanguage."'
					   ,'".$CreatedDate."');";
		$query = $conn->query($sql);
	}
}

?>
