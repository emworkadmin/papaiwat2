<?php
/*
 *
*/

class Zend_View_Helper_Template extends Zend_View_Helper_Abstract
{
	protected $db = null;
	
	public function Template()
	{
		$this->db = Zend_Db_Table::getDefaultAdapter();
		
		$data = new stdClass();
		try {
			$select = $this->db->select()->distinct()
			->from(array('tbl' => 'template'), array(
			    'id', 
			    'is_use_custom_css', 
			    'custom_css', 
			    'is_use_custom_js', 
			    'custom_js',
			    'is_use_google_analytic',
			    'google_ananlytic_script',
			    'is_use_truehit',
			    'truehit_script'
			))
			->where("tbl.is_enable = 'Y' ")
			->where("tbl.is_deleted = 'N' ")
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchObject();
		} catch (Exception $e) {
		
		}

		return $data;
	}

}

?>
