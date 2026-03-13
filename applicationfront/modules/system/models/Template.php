<?php
class System_Model_Template{
	
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
			// setup database
			$this->db = Zend_Db_Table::getDefaultAdapter ();
			
			// do the authentication
			$authobj = Zend_Auth::getInstance ();
			$this->auth = $authobj->getIdentity ();
			$this->UserID = isset ( $this->auth->UserID ) ? $this->auth->UserID : '';
		} catch ( Exception $e ) {
			echo $e->getMessage ();
		}
	}
	
	function getDefault() {
		$id = null;
		try {			
			$select = $this->db->select ()
			->from ( array ('t' => 'sys_template'), array("ID"))
			->where ( "t.IsDefault = 'Y' ");
			$stmt = $this->db->query ( $select );
			$data = $stmt->fetchObject ();
			$id = $data->ID;
		} catch ( Exception $e ) {
		}
		return $id;
	}
	
	function getIDByMenu($menuID) {
		$id = null;
		try {
			$select = $this->db->select ()
			->from ( array ('m' => 'menu_info'), array("TemplateID"))
			->where ( "m.ID = ?", $menuID);
			$stmt = $this->db->query ( $select );
			$data = $stmt->fetchObject ();
			$id = $data->TemplateID;
		} catch ( Exception $e ) {
			
		}
		return $id;
	}

	function getInfoByID($id) {
		$data = new stdClass ();
		try {
			
			$select = $this->db->select ()
			->from ( array ('t' => 'sys_template'), "" )
			->joinLeft( array ('p1' => 'file_info' ), " t.FaviconImageID = p1.ID ", array ('' ))
			->joinLeft( array ('p2' => 'file_info' ), " t.LogoImageID = p2.ID ", array ('' ))
			->joinLeft( array ('p3' => 'file_info' ), " t.BackgroundImageID = p3.ID", array ('' ))
			->columns(array(
					't.ID',
					't.MenuGroupID',
					't.DisplayMenuGroupType',
					't.Name',
					"t.ShowFontSize",
					"t.ShowVisitAmount",
					'FaviconImageName'=>'p1.Name',
					'FaviconImageFileSize'=>'p1.FileSize',
					'FaviconImageFileType'=>'p1.FileType',
					'FaviconImageHashName'=>'p1.HashName',
					
					'LogoImageName'=>'p2.Name',
					'LogoImageFileSize'=>'p2.FileSize',
					'LogoImageFileType'=>'p2.FileType',
					'LogoImageHashName'=>'p2.HashName',
					
					't.BackgroundRepeat',
					't.BackgroundSize',
					't.BackgroundAttachment',
					't.BackgroundPosition',
					't.BackgroundColor',
					'BackgroundImageName'=>'p3.name',
					'BackgroundImageFileSize'=>'p3.FileSize',
					'BackgroundImageFileType'=>'p3.FileType',
					'BackgroundImageHashName'=>'p3.HashName'
			) )
			->where ( "t.ID = ? ", $id );
			$stmt = $this->db->query ( $select );
			$data = $stmt->fetchObject ();
		} catch ( Exception $e ) {
		}
		return $data;
	}
	
	function getCoding ($id) {
		$data = new stdClass();
		try {
			
				$select = $this->db->select()
				->from( array('t' => 'sys_template' ), "" )
				->columns( array( 	
					'ID',
					'IsUseGoogleAnalytic',
					'GoogleAnanlyticCode',
				    'GoogleAnanlyticScript',
					'IsUseTruehit',
					'TruehitCode',
				    'TruehitScript',
					'IsUseCustomCss',
					'CustomCss',
					'IsUseCustomJs',
					'CustomJs'
				))
				->where ( "t.ID = ? ", $id );
				
				$stmt = $this->db->query( $select );
				$data = $stmt->fetchObject();
		} catch (Exception $e) {			
			
		}
		return $data;
	}
	
	function getLayout($id) {
		$data = new stdClass();
		try {
			
			$select = $this->db->select()
			->from( array('t' => 'sys_template' ), "" )
			->columns( array(
					'ID',
					'LayoutParams'
			))
			->where ( "t.ID = ? ", $id );
			
			$stmt = $this->db->query( $select );
			$data = $stmt->fetchObject();
		} catch (Exception $e) {
			
		}
		return $data;
	}
	
	function getTheme($id) {
		$data = new stdClass();
		try {
			
			$select = $this->db->select()
			->from( array('t' => 'sys_template' ), "" )
			->columns( array(
					'ID',
					'ThemeParams'
			))
			->where ( "t.ID = ? ", $id );
			
			$stmt = $this->db->query( $select );
			$data = $stmt->fetchObject();
		} catch (Exception $e) {
			
		}
		return $data;
	}
}

?>
