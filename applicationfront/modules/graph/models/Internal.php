<?php
class Graph_Model_Internal {
	
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
	
	function findMaxYear() {
		$data = date('Y');
		if ($this->db == null) {
			return $data;
		}
		try {
			
			$sql = "select max(substring(convert(nvarchar, F.DateKey), 1, 4)) Y
						from Trend.FactTravelerRevenue F 
						inner join dbo.DimProvince DP on F.ProvinceID = DP.ID 
						where DP.ClusterCode <> -1 and not (F.AuditKey = '110' and F.DateKey like '2016%') ";
			$stmt = $this->db->query($sql);
			$dataObj = $stmt->fetchObject();
			
			$data = empty($dataObj->Y) ? date("Y") : $dataObj->Y;
		} catch ( Exception $e )  {
			
		}
		return $data;
	}

    function touristAmount($year)
    {
		$data = 0;
		if ($this->db == null) {
			return $data;
        }
        
        try {

        	if(empty($year)){
        		$year = 2015;
			}

            $select = $this->db->select()
            ->from(array('F' => 'Trend.FactTravelerRevenue'), '')
            ->columns(array(
                "Amount" => "sum(F.TravelerCount)"
            ))
            ->where("F.DateKey like '{$year}%'")
            ->where("not (F.AuditKey = '110' and F.DateKey like '2016%')")
            ;

			$stmt = $this->db->query($select);
            $dataObj = $stmt->fetchObject();
            
            $data = $dataObj->Amount;
        } catch (Exception $e) {

        }
        return $data;
    }
    
    function travelAmount($year)
    {
		$data = 0;
		if ($this->db == null) {
			return $data;
        }

        try {

        	if(empty($year)){
        		$year = 2015;
			}

            $select = $this->db->select()
            ->from(array('F' => 'Trend.FactTravelerRevenue'), '')
            ->columns(array(
                "Amount" => "sum(F.Revenue)"
            ))
            ->where("F.DateKey like '{$year}%'")
            ;
            
            $stmt = $this->db->query($select);
            $dataObj = $stmt->fetchObject();
            
            $data = $dataObj->Amount;
        } catch (Exception $e) {

        }
        return $data;
    }

    function livetimeAverage($year)
    {
		$data = 0;
		if ($this->db == null) {
			return $data;
        }

        try {

        	$sql = "select 
						        sum(F.TotalLengthOfStayDay) Amount
						from (
						        select
						                (SELECT top 1 (v1.AvgLengthOfStayDay * v1.TravelerCount)
						                FROM 
						                        [Trend].[FactTravelerSubTypeRevenue] v1
						                WHERE 
						                        v1.DateKey = F.[DateKey] AND v1.ProvinceID = F.[ProvinceID] and v1.TravelerNationalityTypeID = F.TravelerNationalityTypeID
						                        and v1.TravelerSubTypeID = 1) as TotalLengthOfStayDay,
						                F.*
						        from Trend.FactTravelerRevenue F
						) F 
						inner join dbo.DimProvince DP on F.ProvinceID = DP.ID
						where F.DateKey like '{year}%'
						and DP.ClusterCode <> -1";
        	$sql = str_replace("{year}", $year, $sql);
        	$stmt = $this->db->query($sql);
        	$dataObj = $stmt->fetchObject();        	
        	$dataTotalLengthOfStayDay = $dataObj->Amount;
        	
        	$sql = "select
						        sum(CASE WHEN F.TravelerSubTypeID = 1 THEN TravelerCount ELSE 0 END) Amount
						from 
						[Trend].[FactTravelerSubTypeRevenue] F
						inner join dbo.DimProvince DP on F.ProvinceID = DP.ID
						where F.DateKey like '{year}%'
						and DP.ClusterCode <> -1 ";
        	$sql = str_replace("{year}", $year, $sql);
        	$stmt = $this->db->query($sql);
        	$dataObj = $stmt->fetchObject();
        	$dataTravelerCount = $dataObj->Amount;
        	
        	$data = empty($dataTravelerCount) ? 0 : $dataTotalLengthOfStayDay / $dataTravelerCount;

        } catch (Exception $e) {
			
        }
        return $data;
    }

    function chargesAverage($year)
    {
		$data = 0;
		if ($this->db == null) {
			return $data;
        }

        try {

        	$sql = "select 
						        case when isnull(sum(F.TotalDays), 0) = 0 then 0 else sum(F.TotalExpenditure) / sum(F.TotalDays) end Amount
						from (
						        select 
						                F.AvgExpenditure *
						                (
						                SELECT top 1 (v1.AvgLengthOfStayDay * v1.TravelerCount) + v2.TravelerCount
						                FROM 
						                        [Trend].[FactTravelerSubTypeRevenue] v1
						                        inner join [Trend].[FactTravelerSubTypeRevenue] v2 on
						                                v1.DateKey = v2.DateKey
						                                and v1.ProvinceID = v2.ProvinceID
						                                and v1.TravelerNationalityTypeID = v2.TravelerNationalityTypeID
						                                and v2.TravelerSubTypeID = 2
						                WHERE 
						                        v1.DateKey = F.[DateKey]
						                        AND v1.ProvinceID = F.[ProvinceID]
						                        and v1.TravelerNationalityTypeID = F.TravelerNationalityTypeID
						                        and v1.TravelerSubTypeID = 1
						                ) as TotalExpenditure,
						                (SELECT top 1 (v1.AvgLengthOfStayDay * v1.TravelerCount) + v2.TravelerCount
						                FROM 
						                        [Trend].[FactTravelerSubTypeRevenue] v1
						                        inner join [Trend].[FactTravelerSubTypeRevenue] v2 on
						                                v1.DateKey = v2.DateKey
						                                and v1.ProvinceID = v2.ProvinceID
						                                and v1.TravelerNationalityTypeID = v2.TravelerNationalityTypeID
						                                and v2.TravelerSubTypeID = 2
						                WHERE 
						                        v1.DateKey = F.[DateKey]
						                        AND v1.ProvinceID = F.[ProvinceID]
						                        and v1.TravelerNationalityTypeID = F.TravelerNationalityTypeID
						                        and v1.TravelerSubTypeID = 1) as TotalDays,
						                F.*
						        from Trend.FactTravelerRevenue F
						) F 
						inner join dbo.DimProvince DP on F.ProvinceID = DP.ID
						where F.DateKey like '{year}%'
						and DP.ClusterCode <> -1 ";
        	$sql = str_replace("{year}", $year, $sql);
        	$stmt = $this->db->query($sql);
        	$dataObj = $stmt->fetchObject();
        	$data = $dataObj->Amount;

        } catch (Exception $e) {

        }
        return $data;
    }

    function incomeprovinceAmount($year)
    {
		$data = 0;
		if ($this->db == null) {
			return $data;
        }

        try {

        	if(empty($year)){
        		$year = 2015;
			}

            $select = $this->db->select()
            ->from(array('F' => 'Trend.FactTravelerRevenue'), '')
            ->joinInner(array("p" => "dbo.DimProvince"),"F.ProvinceID = p.ID", "")
            ->columns(array(
                "Amount" => "sum(F.Revenue)",
                "p.ProvinceNameTH"
            ))
            ->where("F.DateKey like '{$year}%'")
            ->group("p.ProvinceNameTH")
            ->order("Amount Desc")
            ->limit("10")
            ;

            $stmt = $this->db->query($select);
            $data = $stmt->fetchAll();
            
        } catch (Exception $e) {

        }
        return $data;
    }

    function incometravelAmount($year)
    {
		$data = array();
		if ($this->db == null) {
			return $data;
        }

        try {

            $select = $this->db->select()
            ->from(array('F' => 'Trend.FactTravelerRevenue'), '')
            ->columns(array(
                "Amount" => "sum(F.Revenue)",
                "Y" => "substring(convert(nvarchar, F.DateKey), 1, 4)",
            ))
            //->where("substring(convert(nvarchar, F.DateKey), 1, 4) <= '2015' ")
            ->where("not (F.AuditKey = '110' and F.DateKey like '2016%')")
            ->group("substring(convert(nvarchar, F.DateKey), 1, 4)")
            ->order("Y desc")
            ->limit(10)
            ;

            $stmt = $this->db->query($select);
            $data = $stmt->fetchAll();
            
        } catch (Exception $e) {

        }
        return $data;
    }

    function alltouristAmount($year)
    {
		$data = array();
		if ($this->db == null) {
			return $data;
        }

        try {

            $select = $this->db->select()
            ->from(array('F' => 'Trend.FactTravelerRevenue'), '')
            ->columns(array(
                "Amount" => "sum(F.TravelerCount)",
                "Y" => "substring(convert(nvarchar, F.DateKey), 1, 4)",
            ))
            //->where("substring(convert(nvarchar, F.DateKey), 1, 4) <= '2015' ")
            ->where("not (F.AuditKey = '110' and F.DateKey like '2016%')")
            ->group("substring(convert(nvarchar, F.DateKey), 1, 4)")
            ->order("Y desc")
            ->limit(10)
            ;
            
            $stmt = $this->db->query($select);
            $data = $stmt->fetchAll();
            
        } catch (Exception $e) {

        }
        return $data;
    }

}
?>