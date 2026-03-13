<?php
class Graph_Model_Guide {
	
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
			$config = new Zend_Config_Ini(APPLICATION_PATH . DS . 'modules' . DS . 'graph' . DS . 'configs' . DS . 'config.ini', 'database');
			$this->db = Zend_Db::factory($config->db->adapter, $config->db->params->toArray());
			
			// do the authentication
			$authobj = Zend_Auth::getInstance ();
			$this->auth = $authobj->getIdentity ();
			$this->UserID = isset ( $this->auth->UserID ) ? $this->auth->UserID : '';
		} catch ( Exception $e ) {
			$this->db = null;
		}
    }

	// _police

    function proliceAmount($year)
    {
		$data = 0;
		if ($this->db == null) {
			return $data;
        }
        
        try {

            $select = $this->db->select()
            ->from(array('F' => 'Risk.FactTouristPoliceManpower'), '')
            ->columns(array(
                "Amount" => "sum(F.NumberOfHold)"
            ))
            ->where("F.PoliceDepartmentID <= 51")
            ;

            $stmt = $this->db->query($select);
			$dataObj = $stmt->fetchObject();
			$data = $dataObj->Amount;
        } catch (Exception $e) {

        }
        return $data;
	}
	
	// function prolicepertouristAmount($year)
	// {
	// 	$data = 0;
	// 	if ($this->db == null) {
	// 		return $data;
    //     }
        
    //     try {

    //     } catch (Exception $e) {

    //     }
    //     return $data;
	// }
    
    function volunteerAmount($year)
    {
		$data = 0;
		if ($this->db == null) {
			return $data;
        }
        
        try {

			$select = $this->db->select()
			->from(array('F' => 'Risk.FactTouristPoliceVolunteer'), '')
			->columns(array(
				"Amount" => "count(*)"
			))
			;

			$stmt = $this->db->query($select);
			$dataObj = $stmt->fetchObject();
			$data = $dataObj->Amount;

        } catch (Exception $e) {

        }
        return $data;
	}
	
	// function volunteerpertouristAmount($year)
	// {
	// 	$data = 0;
	// 	if ($this->db == null) {
	// 		return $data;
    //     }
        
    //     try {

    //     } catch (Exception $e) {

    //     }
    //     return $data;
	// }

	function policeinprovice($year)
	{
		$data = array();
		if ($this->db == null) {
			return $data;
        }
        
        try {

			$select = $this->db->select()
			->from(array('F' => 'Risk.FactTouristPoliceManpower'), '')
			->joinInner(array('D' => 'Risk.DimPoliceDepartment'), 'F.PoliceDepartmentID = D.ID', '')
			->columns(array(
				'Amount' => 'sum(F.NumberOfHold)',
				'D.DepartmentNameTH'
			))
			->where('F.PoliceDepartmentID <= 51')
			->group('D.DepartmentNameTH')
			->order('Amount Desc')
			->limit(10)
			;
			
			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();

        } catch (Exception $e) {

        }
        return $data;
	}

	function volunteerinprovince($year)
	{
		$data = array();
		if ($this->db == null) {
			return $data;
        }
        
        try {

			$select = $this->db->select()
			->from(array('F' => 'Risk.FactTouristPoliceVolunteer'), '')
			->joinInner(array('D' => 'Risk.DimWorkingArea'), 'F.WorkingAreaID = D.ID', '')
			->columns(array(
				'Amount' => 'count(F.FullName)',
				'D.WorkingAreaNameTH'
			))
			->group('D.WorkingAreaNameTH')
			->order('Amount Desc')
			->limit(10)
			;

			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();

		} catch (Exception $e) {

		}
		return $data;
	}

	// _law
	
	function findGuideMaxYear() {
		$data = date('Y');
		if ($this->db == null) {
			return $data;
		}
		try {
			$sql = "select max(substring(convert(nvarchar, F.DateKey), 1, 4)) Y
						from Risk.FactTourInspectReport F ";
			$stmt = $this->db->query($sql);
			$dataObj = $stmt->fetchObject();
			
			$dataInspectReport = empty($dataObj->Y) ? date("Y") : $dataObj->Y;
			
			$sql = "select max(substring(convert(nvarchar, F.DateKey), 1, 4)) Y
						from Risk.FactTourArrestReport F ";
			$stmt = $this->db->query($sql);
			$dataObj = $stmt->fetchObject();
			
			$dataArrestReport = empty($dataObj->Y) ? date("Y") : $dataObj->Y;
			
			$data = max(array($dataInspectReport, $dataArrestReport));
		} catch ( Exception $e )  {
			
		}
		return $data;
	}

	function guideAmount($year)
	{
		if ($this->db == null) {
			return $data;
		}
		try {
			
			$sql = "select sum(F.NumberOfInspected) Amount
						from Risk.FactTourInspectReport F
						where F.DateKey like '{year}%'";
			$sql = str_replace("{year}", $year, $sql);
			$stmt = $this->db->query($sql);
			$dataObj = $stmt->fetchObject();
			
			$data = $dataObj->Amount;

		} catch (Exception $e) {

		}
		return $data;
	}

	function guidearrest($year)
	{
		if ($this->db == null) {
			return $data;
		}
		try {
			
			$sql = "select sum(F.TotalCount) Amount
						from Risk.FactTourArrestReport F
						where F.DateKey like '{year}%' ";
			$sql = str_replace("{year}", $year, $sql);
			$stmt = $this->db->query($sql);
			$dataObj = $stmt->fetchObject();
			
			$data = $dataObj->Amount;
			
		} catch (Exception $e) {
			
		}
		return $data;
	}

	function topicguidearrest($year)
	{
		$data = array ();
		if ($this->db == null) {
			return $data;
		}
		try {
			$sql = "select sum(F.TotalCount) Amount, D.AllegationDetailTH
						from Risk.FactTourArrestReport F
						inner join Risk.DimTourAllegation D on F.AllegationID = D.ID
						where F.DateKey like '{year}%'
						group by D.AllegationDetailTH
						order by Amount desc";
			$sql = str_replace("{year}", $year, $sql);
			$stmt = $this->db->query($sql);
			$data = $stmt->fetchAll();
		}catch ( Exception $e )  {
			
		}
		return $data;
	}

}
?>