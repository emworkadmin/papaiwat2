<?php
class Graph_Model_Travelplace {
	
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
    
    function findNationalParkMaxYear() {
    	$data = date('Y');
    	if ($this->db == null) {
    		return $data;
    	}
    	try {
    		$sql = "select max(substring(convert(nvarchar, F.DateKey), 1, 4)) Y
						from AreaPlan.FactTouristNationalPark F ";
    		$stmt = $this->db->query($sql);
    		$dataObj = $stmt->fetchObject();
    		
    		$dataFactTouristNationalPark = empty($dataObj->Y) ? date("Y") : $dataObj->Y;
    		
    		$sql = "select max(substring(convert(nvarchar, F.DateKey), 1, 4)) Y
						from Competitive.FactNationalParkSatisfaction F
						where F.AuditKey <> '3504'  ";
    		$stmt = $this->db->query($sql);
    		$dataObj = $stmt->fetchObject();
    		
    		$dataFactNationalParkSatisfaction = empty($dataObj->Y) ? date("Y") : $dataObj->Y;
    		
            $data = min(array($dataFactTouristNationalPark, $dataFactNationalParkSatisfaction));
            
    	} catch ( Exception $e )  {

    	}
    	return $data;
    }

    function nationalparkAmount($year)
    {
		$data = 0;
		if ($this->db == null) {
			return $data;
        }
        
        try {

            $select = $this->db->select()
            ->from(array('D' => 'AreaPlan.DimNationalPark'), '')
            ->columns(array(
                "Amount" => "count(D.NationalParkID)"
            ))
            ->where("D.NationalParkTypeID <> 0")
            ;

			$stmt = $this->db->query($select);
            $data = $stmt->fetchAll();
        } catch (Exception $e) {

        }
        return $data;
    }
    
    function nationalparktouristAmount($year)
    {
		$data = 0;
		if ($this->db == null) {
			return $data;
        }
        try {
            $select = $this->db->select()
            ->from(array('F' => 'AreaPlan.FactTouristNationalPark'), '')
            ->columns(array(
                "Amount" => "sum(F.TouristNumber)"
            ))
            ->where("F.DateKey like '" . $year . "%' ")
            ;
            $stmt = $this->db->query($select);
            $dataObj = $stmt->fetchObject();

            $data = $dataObj->Amount;
        } catch (Exception $e) {
			
        }
        return $data;
    }

    function nationalparkPercent($year)
    {
		$data = 0;
		if ($this->db == null) {
			return $data;
        }
        try {

            $select = $this->db->select()
            ->from(array('F' => 'Competitive.FactNationalParkSatisfaction'), '')
            ->columns(array(
                "Amount" => "avg(F.SatisfactionValue)"
            ))
            ->where("F.DateKey like '" . $year . "%' ")
            ;

            $stmt = $this->db->query($select);
            $dataObj = $stmt->fetchObject();            
            $data = $dataObj->Amount;
        } catch (Exception $e) {

        }
        return $data;
    }

    function nationaltouristAmount($year)
    {
		$data = array();
		if ($this->db == null) {
			return $data;
        }

        try {

            //$year = '2016';

            $select = $this->db->select()
            ->from(array('F' => 'AreaPlan.FactTouristNationalPark'), '')
            ->joinInner(array("D" => "dbo.DimTravelerNationalityType"),"F.CountryID = D.ID", "")
            ->columns(array(
                "Amount" => "sum(F.TouristNumber)",
            	"D.TravelerNationalityTypeNameTH",
            	"F.CountryID"
            ))
            ->where("F.DateKey like '{$year}%'")
            ->group("D.TravelerNationalityTypeNameTH")
            ->group("F.CountryID")
            ;
            
            $stmt = $this->db->query($select);
            $data = $stmt->fetchAll();

        } catch (Exception $e) {

        }
        return $data;
    }

    function nationalparktouristStatic($year)
    {

		$data = 0;
		if ($this->db == null) {
			return $data;
        }

        try {

            $select = $this->db->select()
            ->from(array('F' => 'AreaPlan.FactTouristNationalPark'), '')
            ->columns(array(
                "Amount" => "sum(F.TouristNumber)",
                "M" => "substring(convert(nvarchar, F.DateKey), 5, 2)"
            ))
            ->where("F.DateKey like '{$year}%'")
            ->group("substring(convert(nvarchar, F.DateKey), 5, 2)")
            ;
            $stmt = $this->db->query($select);
            $data = $stmt->fetchAll();
            
        } catch (Exception $e) {

        }
        return $data;
    }

    function mostnationalparktravelAmount($year)
    {
		$data = 0;
		if ($this->db == null) {
			return $data;
        }

        try {

            $select = $this->db->select()
            ->from(array('F' => 'AreaPlan.FactTouristNationalPark'), '')
            ->joinInner(array("D" => "AreaPlan.DimNationalPark"),"F.NationalParkID = D.NationalParkID", "")
            ->columns(array(
                "Amount" => "sum(F.TouristNumber)",
                "D.NationalParkNameTH"
            ))
            ->where("F.DateKey like '{$year}%'")
            ->group("D.NationalParkNameTH")
            ->order("Amount Desc")
            ->limit(10)
            ;
            $stmt = $this->db->query($select);
            $data = $stmt->fetchAll();

        } catch (Exception $e) {

        }
        return $data;
    }

    function findIncomdeMaxYear()
    {
        $data = date('Y');
        if($this->db == nul){
            return $data;
        }

        try {

    		$sql = "select max(substring(convert(nvarchar, F.DateKey), 1, 4)) Y
                        from Assessment.FactLocalIncome F ";
                        
            $stmt = $this->db->query($sql);
            $dataObj = $stmt->fetchObject();

    		$data = empty($dataObj->Y) ? date("Y") : $dataObj->Y;
        } catch (Exception $e) {

        }
        return $data;
    }

    function incomebeforeAmount($year)
    {
		$data = 0;
		if ($this->db == null) {
			return $data;
        }

        try {

            $select = $this->db->select()
            ->from(array('F' => 'Assessment.FactLocalIncome'), '')
            ->columns(array(
                "Amount" => "sum(F.Income)",
            ))
            ->where("F.DateKey = '{$year}'")
            ->where("F.LocalIncomeTypeID = 1")
            ;
            $stmt = $this->db->query($select);
            $data = $stmt->fetchAll();

        } catch (Exception $e) {

        }
        return $data;
    }

    function incomeafterAmount($year)
    {
		$data = 0;
		if ($this->db == null) {
			return $data;
        }

        try {

            $select = $this->db->select()
            ->from(array('F' => 'Assessment.FactLocalIncome'), '')
            ->columns(array(
                "Amount" => "sum(F.Income)",
            ))
            ->where("F.DateKey = '{$year}'")
            ->where("F.LocalIncomeTypeID = 2")
            ;
            $stmt = $this->db->query($select);
            $data = $stmt->fetchAll();

        } catch (Exception $e) {

        }
        return $data;
    }

    function rategrowthincomeAmount($dataselectafter = null, $dataselectbefore = null)
    {

        try {

            if($dataselectafter == null && $dataselectbefore == null){
                $data = 0 ;
            }else{
                $data = ( ( ($dataselectafter) / ($dataselectbefore) ) -1 ) * 100 ;
            }

        } catch (Exception $e) {

        }
        return $data;
    }

    function communitydevelopmentplanData($year)
    {
		$data = 0;
		if ($this->db == null) {
			return $data;
        }

        try {

            $select = "
            select        case when B.Amount = 0 and A.Amount = 0 then 0 else ((B.Amount / A.Amount) - 1) * 100 end AS Amount, A.Y
            from            (select        sum(Income) AS Amount, LocalIncomeTypeID, substring(convert(nvarchar, DateKey), 1, 4) AS Y
                                      from            Assessment.FactLocalIncome AS F
                                      WHERE        (LocalIncomeTypeID = 1)
                                      group by substring(convert(nvarchar, DateKey), 1, 4), LocalIncomeTypeID) AS A inner join
                                         (select        sum(Income) AS Amount, LocalIncomeTypeID, substring(convert(nvarchar, DateKey), 1, 4) AS Y
                                           from            Assessment.FactLocalIncome AS F
                                           WHERE        (LocalIncomeTypeID = 2)
                                           group by substring(convert(nvarchar, DateKey), 1, 4), LocalIncomeTypeID) AS B ON A.Y = B.Y
            ";
            $stmt = $this->db->query($select);
            $data = $stmt->fetchAll();

        } catch (Exception $e) {

        }
        return $data;
    }
}
?>