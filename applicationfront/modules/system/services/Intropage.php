<?php
class System_Service_Intropage {
	
	/**
	 *
	 * @var Zend_Db_Adapter_Abstract
	 */
	protected $db = null;
	protected $UserID = null;
	protected $DeptID = null;
	protected $auth = null;
	function __construct() {
		try {
			
		} catch ( Exception $e ) {
			
		}
	}
	public static function getActive () {
		
		$result = null;
		$db = Zend_Db_Table::getDefaultAdapter ();
		
		$select = $db->select()
		->from(array('m' => 'intropage_info'), array("ID", "Name", "BackgroundColor", "ShowButton", "ButtonTextColor", "ButtonBackgroundColor", "PublishDate", "PublishDown", "Css"))
		->where("m.IsDeleted = ?", "N")
		->where("m.IsEnable = ?", "Y")
		;
		$stmt = $db->query($select);
		$data = $stmt->fetchObject();
		
		if ($data !== false) {
			$result = $data;
			$result->PublishDate = empty($result->PublishDate) ? null : new DateTime($result->PublishDate);
			$result->PublishDown = empty($result->PublishDown) ? null : new DateTime($result->PublishDown);
			
			$result->Details = array();
						
			$select = $db->select()
			->from(array('m' => 'intropage_detail'), array("ID", "Lang", "Detail", "ButtonText", "Button"))
			->where("m.ID = ?", $result->ID)
			;
			$stmt = $db->query($select);
			$dataDetails = $stmt->fetchAll();
			
			if (count($dataDetails) > 0) {
				foreach ($dataDetails as $dataDetail) {
					$result->Details[$dataDetail["Lang"]] = $dataDetail;
				}
			}
		}
		return $result;
	}
}

?>
