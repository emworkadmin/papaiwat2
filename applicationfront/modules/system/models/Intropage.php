<?php

class System_Model_Intropage
{

	protected $db = null;
	protected $UserID = null;

	/**
	 *
	 */
	function __construct()
	{
		try {
			//setup database
			$this->db = Zend_Db_Table::getDefaultAdapter();
			//do the authentication
			$authobj = Zend_Auth::getInstance();
			if ($authobj) {
				$auth = $authobj->getIdentity();
				$this->UserID = $auth->UserID;
			}
		} catch (Exception $e) {
			echo $e->getMessage();
		} 
	}
	
	/**
	 * 
	 */
	public static function getInfo() {
		$data = null;
		try {
			$db = Zend_Db_Table::getDefaultAdapter();
			
			$select = $db->select()
			->from(array('tbl' => 'intropage'), array('*'))
			;

			$stmt = $db->query($select);
			$data = $stmt->fetchObject();
		} catch (Exception $e) {
			
		}
		return $data;
	}
}
?>
