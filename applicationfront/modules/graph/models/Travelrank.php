<?php
class Graph_Model_Travelrank {
	
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
    
    
    function findAccommodationMaxYear() {
    	$data = date('Y');
    	if ($this->db == null) {
    		return $data;
    	}
    	try {
    		
    		$sql = "select max(substring(convert(nvarchar, F.DateKey), 1, 4)) Y
						from Trend.FactTravelerAccommodationType F ";
    		$stmt = $this->db->query($sql);
			$dataObj = $stmt->fetchObject();
			
    		$data = empty($dataObj->Y) ? date("Y") : $dataObj->Y;
    	} catch ( Exception $e )  {
    		
    	}
    	return $data;
    }

    function HotelAmount($year)
    {
        $data = 0;
        if ($this->db == null) {
            return $data;
        }
        try {

//             $selectMax = $this->db->select()
//             ->from(array('T' => 'Trend.FactTravelerAccommodationType'), '')
//             ->columns(array(
// 				'D' => 'max(substring(convert(nvarchar, T.DateKey), 1, 4))'
//             ))
//             ;
//             if($year) {
//                 $selectMax = $selectMax->where('T.DateKey like ' . $this->db->quote($year . "%"));
//             }

//             $select = $this->db->select()
//             ->from(array('F' => 'Trend.FactTravelerAccommodationType'), '')
//             ->columns(array(
//                 'Amount' => 'sum(F.NumberOfAccommodation)'
//             ))
//             ->where('substring(convert(nvarchar, F.DateKey), 1, 4) = (' . $selectMax . ')')
//             ->where('F.ProvinceID > 0')            
//             ;

//             $stmt = $this->db->query($select);
//             $dataObj = $stmt->fetchObject();
//             $data = $dataObj->Amount;
        	$sql = "select sum(F.NumberOfAccommodation) Amount
						from Trend.FactTravelerAccommodationType F
						inner join dbo.DimProvince D on F.ProvinceID = D.ID
						where F.DateKey = (select max(T.DateKey) D from Trend.FactTravelerAccommodationType T where T.DateKey like '{year}%')
						and D.ClusterCode > 0 ";
        	$sql = str_replace("{year}", $year, $sql);
        	$stmt = $this->db->query($sql);
        	$dataObj = $stmt->fetchObject();
        	
        	$data = $dataObj->Amount;

        } catch (Exception $e) {

        }
        return $data;
    }
    
    function RoomAmount($year)
    {
        $data = 0;
        if ($this->db == null) {
            return $data;
        }

        try {

//             $selectMax = $this->db->select()
//             ->from(array('T' => 'Trend.FactTravelerAccommodationType'), '')
//             ->columns(array(
// 				'D' => 'max(substring(convert(nvarchar, T.DateKey), 1, 4))'
//             ))
//             ;
//             if($year) {
//                 $selectMax = $selectMax->where('T.DateKey like ' . $this->db->quote($year . "%"));
//             }
//             $select = $this->db->select()
//             ->from(array('F' => 'Trend.FactTravelerAccommodationType'), '')
//             ->columns(array(
//                 'Amount' => 'sum(F.Rooms)'
//             ))
//             ->where('substring(convert(nvarchar, F.DateKey), 1, 4) = (' . $selectMax . ')')
//             ->where('F.ProvinceID > 0')
//             ;
//             $stmt = $this->db->query($select);
//             $dataObj = $stmt->fetchObject();
//             $data = $dataObj->Amount;
        	$sql = "select sum(F.Rooms) Amount
						from Trend.FactTravelerAccommodationType F
						inner join dbo.DimProvince D on F.ProvinceID = D.ID
						where F.DateKey = (select max(T.DateKey) D from Trend.FactTravelerAccommodationType T where T.DateKey like '{year}%')
						and D.ClusterCode > 0 ";
        	$sql = str_replace("{year}", $year, $sql);
        	$stmt = $this->db->query($sql);
        	$dataObj = $stmt->fetchObject();
        	
        	$data = $dataObj->Amount;

        } catch (Exception $e) {

        }
        return $data;
    }

    function ORAmount($year)
    {
        $data = 0;
        if ($this->db == null) {
            return $data;
        }

        
        try {

//             $selectMax = $this->db->select()
//             ->from(array('T' => 'Trend.FactTravelerAccommodationType'), '')
//             ->columns(array(
//                 'D' => 'max(substring(convert(nvarchar, T.DateKey), 1, 4))'
//             ))
//             ;
//             if($year) {
//                 $selectMax = $selectMax->where('T.DateKey like ' . $this->db->quote($year . "%"));
//             }
//             $select = $this->db->select()
//             ->from(array('F' => 'Trend.FactTravelerAccommodationType'), '')
//             ->columns(array(
//                 'Amount' => 'avg(F.OccupancyRate)'
//             ))
//             ->where('substring(convert(nvarchar, F.DateKey), 1, 4) = (' . $selectMax . ')')
//             ->where('F.ProvinceID > 0')
//             ;
//             $stmt = $this->db->query($select);
//             $dataObj = $stmt->fetchObject();
//             $data = $dataObj->Amount;
        	$sql = "select case when isnull(sum(F.Rooms), 0) = 0 then 0 else sum(F.WeightedOccupancyRate) / sum(F.Rooms) end Amount
						from dbo.v_Trend_FactTravelerAccommodationType_OverEndOfQuarter F
						inner join dbo.DimProvince D on F.ProvinceID = D.ID
						where F.DateKey like '{year}%'
						and D.ClusterCode > 0 ";
        	$sql = str_replace("{year}", $year, $sql);
        	$stmt = $this->db->query($sql);
        	$dataObj = $stmt->fetchObject();
        	
        	$data = $dataObj->Amount;

        } catch (Exception $e) {

        }
        return $data;
    }

    function GuestAmount($year)
    {
    	$data = 0;
    	if ($this->db == null) {
    		return $data;
    	}
    	try {
    		$sql = "select sum(F.GuestArrival) Amount
						from v_Trend_FactTravelerAccommodationType_OverEndOfQuarter F
						inner join dbo.DimProvince D on F.ProvinceID = D.ID
						where F.DateKey like '{year}%'
						and D.ClusterCode > 0 ";
    		$sql = str_replace("{year}", $year, $sql);
    		$stmt = $this->db->query($sql);
    		$dataObj = $stmt->fetchObject();
    		
    		$data = $dataObj->Amount;    		
    	} catch (Exception $e) {
    		
    	}
    	return $data;
    }    

    function LOSAmount($year)
    {
    	$data = 0;
    	if ($this->db == null) {
    		return $data;
    	}
    	try {
    		$sql = "select case when isnull(sum(F.GuestArrival), 0) = 0 then 0 else sum(F.WeightedLengthOfStay) / sum(F.GuestArrival) end Amount
						from dbo.v_Trend_FactTravelerAccommodationType_OverEndOfQuarter F
						inner join dbo.DimProvince D on F.ProvinceID = D.ID
						where F.DateKey like '{year}%'
						and D.ClusterCode > 0 ";
    		$sql = str_replace("{year}", $year, $sql);
    		$stmt = $this->db->query($sql);
    		$dataObj = $stmt->fetchObject();
    		
    		$data = $dataObj->Amount;
    	} catch (Exception $e) {
    		
    	}
    	return $data;
    }

    function PersonPerRoom($year)
    {
    	$data = 0;
    	if ($this->db == null) {
    		return $data;
    	}
    	try {
    		$sql = "select case when isnull(sum(F.GuestArrival), 0) = 0 then 0 else sum(F.WeightedPersonPerRoom) / sum(F.GuestArrival) end Amount
						from dbo.v_Trend_FactTravelerAccommodationType_OverEndOfQuarter F
						inner join dbo.DimProvince D on F.ProvinceID = D.ID
						where F.DateKey like '{year}%'
						and D.ClusterCode > 0 ";
    		$sql = str_replace("{year}", $year, $sql);
    		$stmt = $this->db->query($sql);
    		$dataObj = $stmt->fetchObject();
    		
    		$data = $dataObj->Amount;
    	} catch (Exception $e) {
    		
    	}
    	return $data;
    }

    function GuestAndORPerQuarter($year)
    {
        $data = array();
        if ($this->db == null) {
            return $data;
        }

        try {

        } catch (Exception $e) {

        }
        return $data;
    }
    
    function GuestByQuarter($year)
    {
    	$data = array();
    	if ($this->db == null) {
    		return $data;
    	}
    	try {
    		$sql = "select 
						        sum(F.GuestArrival) Amount,
						        case substring(convert(nvarchar, F.DateKey), 5, 2) when '01' then 1 when '02' then 1 when '03' then 1 when '04' then 2 when '05' then 2 when '06' then 2 when '07' then 3 when '08' then 3 when '09' then 3 when '10' then 4 when '11' then 4 when '12' then 4 end Quarter
						from dbo.v_Trend_FactTravelerAccommodationType_OverEndOfQuarter F
						inner join dbo.DimProvince D on F.ProvinceID = D.ID
						where F.DateKey like '{year}%'
						and D.ClusterCode > 0
						group by 
						        case substring(convert(nvarchar, F.DateKey), 5, 2) when '01' then 1 when '02' then 1 when '03' then 1 when '04' then 2 when '05' then 2 when '06' then 2 when '07' then 3 when '08' then 3 when '09' then 3 when '10' then 4 when '11' then 4 when '12' then 4 end 
						order by Quarter";
    		$sql = str_replace("{year}", $year, $sql);
    		$stmt = $this->db->query($sql);
    		$data = $stmt->fetchAll();
    	} catch (Exception $e) {
    		
    	}
    	return $data;
    }
    
    function OrByQuarter($year)
    {
    	$data = array();
    	if ($this->db == null) {
    		return $data;
    	}
    	try {
    		$sql = "select 
						        case when isnull(sum(F.Rooms), 0) = 0 then 0 else sum(F.WeightedOccupancyRate) / sum(F.Rooms) end Amount,
						        case substring(convert(nvarchar, F.DateKey), 5, 2) when '01' then 1 when '02' then 1 when '03' then 1 when '04' then 2 when '05' then 2 when '06' then 2 when '07' then 3 when '08' then 3 when '09' then 3 when '10' then 4 when '11' then 4 when '12' then 4 end Quarter
						from dbo.v_Trend_FactTravelerAccommodationType_OverEndOfQuarter F
						inner join dbo.DimProvince D on F.ProvinceID = D.ID
						where F.DateKey like '{year}%'
						and D.ClusterCode > 0
						group by 
						        case substring(convert(nvarchar, F.DateKey), 5, 2) when '01' then 1 when '02' then 1 when '03' then 1 when '04' then 2 when '05' then 2 when '06' then 2 when '07' then 3 when '08' then 3 when '09' then 3 when '10' then 4 when '11' then 4 when '12' then 4 end
 						order by Quarter";
    		$sql = str_replace("{year}", $year, $sql);
    		$stmt = $this->db->query($sql);
    		$data = $stmt->fetchAll();
    	} catch (Exception $e) {
    		
    	}
    	return $data;
    }
    
    
    /* ------------------------------------ Restaurant -------------------------------------------*/

    function RestaurantAmount($year)
    {
//         $data = 0;
//         if ($this->db == null) {
//             return $data;
//         }        
//         try {
//             $select = $this->db->select()
//             ->from(array('D' => 'AreaPlan.DimRestaurant'), '')
//             ->columns(array(
//                 'Amount' => 'count(*)'   
//             ))
//             ;            
//             $stmt = $this->db->query($select);
//             $dataObj = $stmt->fetchObject();
//             $data = $dataObj->Amount;
//         } catch (Exception $e) {
//         }
//         return $data;
    	
    	$data = 0;
    	if ($this->db == null) {
    		return $data;
    	}
    	try {
    		$sql = "select sum(F.CountDistinctRestaurant) Amount
						from AreaPlan.FactRestaurantFoodType F
						inner join AreaPlan.DimRestaurant R on F.RestaurantID = R.ID
						inner join dbo.DimProvince D on R.ProvinceID = D.ID
						where  D.ClusterCode <> -1 ";
    		$sql = str_replace("{year}", $year, $sql);
    		$stmt = $this->db->query($sql);
    		$dataObj = $stmt->fetchObject();
    		
    		$data = $dataObj->Amount;
    	} catch (Exception $e) {
    		
    	}
    	return $data;
    }

	
	function findfoodMaxYear()
	{
		$data = date('Y');
		if ($this->db == null) {
    		return $data;
    	}
		try {
    		$sql = "select max(substring(convert(nvarchar, F.DateKey), 1, 4)) Y
						from dbo.v_Trend_FactTravelerExpense_LastedFullYear F";
    		$sql = str_replace("{year}", $year, $sql);
    		$stmt = $this->db->query($sql);
			$dataObj = $stmt->fetchObject();
			$data = empty($dataObj->Y) ? date("Y") : $dataObj->Y;

		} catch (Exception $e) {
	
		}
		return $data;
	}
	
	function findLatestYearRestaurant() {
		$selectMax = $this->db->select()
		->from(array("F" => "AreaPlan.DimRestaurant"), array("year"=>"max(Year(F.RowStartDate))"));
		$stmtMax = $this->db->query($selectMax);
		$dataMax = $stmtMax->fetchObject();
		$year = $dataMax->year;
		return $year;
	}

    function AvgFoodCost($year)
    {
    	$data = 0;
    	if ($this->db == null) {
    		return $data;
    	}
    	try {
    		$sql = "select case when isnull(sum(F.TravelerCount), 0) = 0 then 0 else sum(F.WeightedAvgExpense) / sum(F.TravelerCount) end Amount
						from dbo.v_Trend_FactTravelerExpense_LastedFullYear F
						inner join dbo.DimProvince D on F.ProvinceID = D.ID
						where F.DateKey like '{year}%'
						and F.TravelerExpenseTypeID = 2
						and F.YearPeriodTypeID = 1
						and D.ClusterCode <> -1 ";
			$sql = str_replace("{year}", $year, $sql);
			
    		$stmt = $this->db->query($sql);
    		$dataObj = $stmt->fetchObject();
    		
    		$data = $dataObj->Amount;
    	} catch (Exception $e) {
    		
    	}
    	return $data;
    }

    function RestaurantPerProvince($year)
    {
//         $data = array();
//         if ($this->db == null) {
//             return $data;
//         }
//         try {
//             $select = $this->db->select()
//             ->from(array('D' => 'AreaPlan.DimRestaurant'), '')
//             ->joinInner(array('P' => 'dbo.DimProvince'), 'D.ProvinceID = P.ID', '')
//             ->columns(array(
//                 'Amount' => 'count(*)',
//                 'P.ProvinceNameTH'
//             ))
//             ->group('P.ProvinceNameTH')
//             ->order('Amount Desc')
//             ->limit(10)
//             ;
//             $stmt = $this->db->query($select);
//             $data = $stmt->fetchAll();
//         } catch (Exception $e) {
//         }
    	$data = array();
    	if ($this->db == null) {
    		return $data;
    	}
    	try {
    		$sql = "select top(10) sum(F.CountDistinctRestaurant) Amount, D.ProvinceNameTH
						from AreaPlan.FactRestaurantFoodType F
						inner join AreaPlan.DimRestaurant R on F.RestaurantID = R.ID
						inner join dbo.DimProvince D on R.ProvinceID = D.ID
						where  D.ClusterCode <> -1
						group by D.ProvinceNameTH
						order by Amount desc ";
    		//$sql = str_replace("{year}", $year, $sql);
    		$stmt = $this->db->query($sql);
    		$data = $stmt->fetchAll();
    	} catch (Exception $e) {
    		
    	}
    	return $data;
    }
    
    
    
    function findMiceMaxYear() {
    	$data = date('Y');
    	if ($this->db == null) {
    		return $data;
    	}
    	try {
    		
    		$sql = "select max(substring(convert(nvarchar, F.DateKey), 1, 4)) Y
						from AreaPlan.FactMICEStatistics F";
    		$stmt = $this->db->query($sql);
    		$dataObj = $stmt->fetchObject();
    		
    		$data = empty($dataObj->Y) ? date("Y") : $dataObj->Y;
    	} catch ( Exception $e )  {
    		
    	}
    	return $data;
    }

    function MiceIndustry($year)
    {
        $data = array();
        if ($this->db == null) {
            return $data;
        }
        try {

//             $select = $this->db->select()
//             ->from(array('F' => 'AreaPlan.FactMICEStatistics'), '')
//             ->joinInner(array('D' => 'AreaPlan.DimMICEType'), 'F.MICETypeID = D.ID', '')
//             ->columns(array(
//                 'Amount' => 'sum(F.Amount)',
//                 'D.MICETypeEN',
//                 'Y' => 'substring(convert(nvarchar, F.DateKey), 1, 4)'
//             ))
//             ->where("F.AreaName = 'RevenueByQuarter'")
//             ->group('D.MICETypeEN, substring(convert(nvarchar, F.DateKey), 1, 4)')
//             ;
//             $stmt = $this->db->query($select);
//             $dataArray = $stmt->fetchAll();

        	// $sqlMax = "select max(DD.YearFiscalTHOct) Y
			// 			from AreaPlan.FactMICEStatistics F inner join dbo.DimDate DD on F.DateKey = DD.DateKey ";
        	// $stmtMax = $this->db->query($sqlMax);
        	// $dataMaxObj = $stmtMax->fetchObject();
			// $maxYear = empty($dataMaxObj->Y) ? date('Y') : $dataMaxObj->Y;
			
			$year = $year + 543;

        	$sql = "select sum(F.Amount) Amount, D.MICETypeEN, DD.YearFiscalTHOct Y
							from AreaPlan.FactMICEStatistics F
							inner join AreaPlan.DimMICEType D on F.MICETypeID = D.ID
							inner join dbo.DimDate DD on F.DateKey = DD.DateKey
							where F.AreaName = 'RevenueByQuarter'
							and D.MICETypeEN is not null
							and DD.YearFiscalTHOct between '{minYear}' and '{maxYear}'
							group by D.MICETypeEN, DD.YearFiscalTHOct 
							 ";
        	$sql = str_replace("{minYear}", $year - 2, $sql);
			$sql = str_replace("{maxYear}", $year, $sql);
        	$stmt = $this->db->query($sql);
        	$dataArray = $stmt->fetchAll();
        	
        	
            $y = array();
            if(count($dataArray)>0) {
                foreach($dataArray as $k => $v) {

                    $d[$v['MICETypeEN']]['Color'] = array();
                    switch ($v['MICETypeEN']) {
                        case 'Meetings':
                            $d[$v['MICETypeEN']]['Color'] = 'rgba(2, 184, 171, 0.6)';
                            break;
                        case 'Incentives' :
                            $d[$v['MICETypeEN']]['Color'] = 'rgba(254, 149, 102, 0.6)';
                            break;
                        case 'Conventions' :
                            $d[$v['MICETypeEN']]['Color'] = 'rgba(103, 159, 174, 0.6)';
                            break;
                        case 'Exhibitions' :
                            $d[$v['MICETypeEN']]['Color'] = 'rgba(250, 130, 131, 0.6)';
                            break;
                        case 'Mega Events' :
                            $d[$v['MICETypeEN']]['Color'] = 'rgba(254, 200, 15, 0.6)';
                            break;
                        default:
                            $d[$v['MICETypeEN']]['Color'] = 'rgba(2, 184, 171, 0.6)';
                            break;
                    }

                    if(!in_array($v['Y'], $y)) {
                        array_push($y, $v['Y']);
                    }

                    $d[$v['MICETypeEN']][$v['Y']] = array();
                    $d[$v['MICETypeEN']][$v['Y']]['Name'] = $v['MICETypeEN'];
                    $d[$v['MICETypeEN']][$v['Y']]['Amount'] = $v['Amount'];

                }
            }
            $data['Data'] = $d;
            $data['Year'] = $y;

        } catch (Exception $e) {

        }
        return $data;
    }

    function MiceTraveler($year)
    {
        $data = array();
        if ($this->db == null) {
            return $data;
        }

        if($year == null) {
			$year = date('Y');
        }

        try {
        	
        	// $sqlMax = "select max(DD.YearFiscalTHOct) Y
			// 			from AreaPlan.FactMICEStatistics F inner join dbo.DimDate DD on F.DateKey = DD.DateKey ";
        	// $stmtMax = $this->db->query($sqlMax);
        	// $dataMaxObj = $stmtMax->fetchObject();
			// $maxYear = empty($dataMaxObj->Y) ? date('Y') : $dataMaxObj->Y;
			
			$year = $year + 543;
        	
        	$sql = "select sum(F.Amount) Amount, D.MICETypeEN, DD.YearFiscalTHOct Y
						from AreaPlan.FactMICEStatistics F
						inner join AreaPlan.DimMICEType D on F.MICETypeID = D.ID
						inner join dbo.DimDate DD on F.DateKey = DD.DateKey
						where F.AreaName = 'NoOfVisitorsByQuarter'
						and D.MICETypeEN is not null
						and DD.YearFiscalTHOct between '{minYear}' and '{maxYear}'
						group by D.MICETypeEN, DD.YearFiscalTHOct  ";
        	$sql = str_replace("{minYear}", $year - 2, $sql);
        	$sql = str_replace("{maxYear}", $year, $sql);
        	$stmt = $this->db->query($sql);
        	$dataArray = $stmt->fetchAll();

//             $select = $this->db->select()
//             ->from(array('F' => 'AreaPlan.FactMICEStatistics'), '')
//             ->joinInner(array('D' => 'AreaPlan.DimMICEType'), 'F.MICETypeID = D.ID', '')
//             ->columns(array(
//                 'Amount' => 'sum(F.Amount)',
//                 'D.MICETypeEN',
//                 'Y' => 'substring(convert(nvarchar, F.DateKey), 1, 4)'
//             ))
//             ->group('D.MICETypeEN, substring(convert(nvarchar, F.DateKey), 1, 4)')
//             ;
//             $stmt = $this->db->query($select);
//             $dataArray = $stmt->fetchAll();
 
            $y = array();
            if(count($dataArray)>0) {
                foreach($dataArray as $k => $v) {

                    $d[$v['MICETypeEN']]['Color'] = array();
                    switch ($v['MICETypeEN']) {
                        case 'Meetings':
                            $d[$v['MICETypeEN']]['Color'] = 'rgba(2, 184, 171, 0.6)';
                            break;
                        case 'Incentives' :
                            $d[$v['MICETypeEN']]['Color'] = 'rgba(254, 149, 102, 0.6)';
                            break;
                        case 'Conventions' :
                            $d[$v['MICETypeEN']]['Color'] = 'rgba(103, 159, 174, 0.6)';
                            break;
                        case 'Exhibitions' :
                            $d[$v['MICETypeEN']]['Color'] = 'rgba(250, 130, 131, 0.6)';
                            break;
                        case 'Mega Events' :
                            $d[$v['MICETypeEN']]['Color'] = 'rgba(254, 200, 15, 0.6)';
                            break;
                        default:
                            $d[$v['MICETypeEN']]['Color'] = 'rgba(2, 184, 171, 0.6)';
                            break;
                    }

                    if(!in_array($v['Y'], $y)) {
                        array_push($y, $v['Y']);
                    }

                    $d[$v['MICETypeEN']][$v['Y']] = array();
                    $d[$v['MICETypeEN']][$v['Y']]['Name'] = $v['MICETypeEN'];
                    $d[$v['MICETypeEN']][$v['Y']]['Amount'] = $v['Amount'];


                }
            }
            $data['Data'] = $d;
            $data['Year'] = $y;

        } catch (Exception $e) {

        }
        return $data;
    }

    function MiceIncomeActivity($year)
    {
        $data = array();
        if ($this->db == null) {
            return $data;
        }

        try {

//             $selectMax = $this->db->select()
//             ->from(array('F' => 'AreaPlan.FactMICEStatistics'), '')
//             ->joinInner(array('D' => 'AreaPlan.DimMICEType'), 'F.MICETypeID = D.ID', '')
//             ->columns(array(
// 				'D' => 'max(substring(convert(nvarchar, F.DateKey), 1, 4))'
//             ))
//             ;
//             if($year) {
//                 $selectMax = $selectMax->where('T.DateKey like ' . $this->db->quote($year . "%"));
//             }

//             $select = $this->db->select()
//             ->from(array('F' => 'AreaPlan.FactMICEStatistics'), '')
//             ->joinInner(array('D' => 'AreaPlan.DimMICEType'), 'F.MICETypeID = D.ID', '')
//             ->columns(array(
//                 'Amount' => 'sum(F.Amount)',
//                 'D.MICETypeEN'
//             ))
//             ->where("F.AreaName = 'RevenueByQuarter'")
//             ->where('substring(convert(nvarchar, F.DateKey), 1, 4) = (' . $selectMax . ')')
//             ->group('D.MICETypeEN')
//             ;

//             $stmt = $this->db->query($select);
//             $data = $stmt->fetchAll();

        	$sql = "select sum(F.Amount) Amount, D.MICETypeEN
						from AreaPlan.FactMICEStatistics F
						inner join AreaPlan.DimMICEType D on F.MICETypeID = D.ID
						where F.AreaName = 'RevenueByQuarter'
						and F.DateKey like '{year}%'
						group by D.MICETypeEN ";
        	$sql = str_replace("{year}", $year, $sql);
        	$stmt = $this->db->query($sql);
        	$data = $stmt->fetchAll();

        } catch (Exception $e) {

        }
        return $data;
    }

    function MiceTravelerActivity($year)
    {
        $data = array();
        if ($this->db == null) {
            return $data;
        }

        try {

//             $selectMax = $this->db->select()
//             ->from(array('F' => 'AreaPlan.FactMICEStatistics'), '')
//             ->joinInner(array('D' => 'AreaPlan.DimMICEType'), 'F.MICETypeID = D.ID', '')
//             ->columns(array(
// 				'D' => 'max(substring(convert(nvarchar, F.DateKey), 1, 4))'
//             ))
//             ;
//             if($year) {
//                 $selectMax = $selectMax->where('T.DateKey like ' . $this->db->quote($year . "%"));
//             }

//             $select = $this->db->select()
//             ->from(array('F' => 'AreaPlan.FactMICEStatistics'), '')
//             ->joinInner(array('D' => 'AreaPlan.DimMICEType'), 'F.MICETypeID = D.ID', '')
//             ->columns(array(
//                 'Amount' => 'sum(F.Amount)',
//                 'D.MICETypeEN'
//             ))
//             ->where("F.AreaName = 'NoOfVisitorsByQuarter'")
//             ->where('substring(convert(nvarchar, F.DateKey), 1, 4) = (' . $selectMax . ')')
//             ->group('D.MICETypeEN')
//             ;

//             $stmt = $this->db->query($select);
//             $data = $stmt->fetchAll();

        	$sql = "select sum(F.Amount) Amount, D.MICETypeEN
						from AreaPlan.FactMICEStatistics F
						inner join AreaPlan.DimMICEType D on F.MICETypeID = D.ID
						where F.AreaName = 'NoOfVisitorsByQuarter'
						and F.DateKey like '{year}%'
						group by D.MICETypeEN ";
        	$sql = str_replace("{year}", $year, $sql);
        	$stmt = $this->db->query($sql);
        	$data = $stmt->fetchAll();

        } catch (Exception $e) {

        }
        return $data;
    }

    function TourismBusinessTypeAmount($year)
    {
        $data = array();
        if ($this->db == null) {
            return $data;
        }

        try {
        	$sql = "select count(Distinct F.LicenseNo) Amount, DP.PersonTypeTH PersonType
						from AreaPlan.FactGuideTraderBranch F
						inner join AreaPlan.DimGuideCategory DC on F.GuideCategoryID = DC.ID
						inner join AreaPlan.DimGuidePerson DP on F.GuidePersonID = DP.ID
						where DC.LicenseType = 'B'
						group by DP.PersonTypeTH ";
        	//$sql = str_replace("{year}", $year, $sql);
        	$stmt = $this->db->query($sql);
        	$data = $stmt->fetchAll();
        	
        } catch (Exception $e) {

        }
        return $data;
    }

    function TourismBusinessAmount($year)
    {
        $data = 0;
        if ($this->db == null) {
            return $data;
        }

        try {
        	$sql = "select count(Distinct F.LicenseNo) Amount
						from AreaPlan.FactGuideTraderBranch F
						inner join AreaPlan.DimGuideCategory DC on F.GuideCategoryID = DC.ID
						inner join AreaPlan.DimGuidePerson DP on F.GuidePersonID = DP.ID
						where DC.LicenseType = 'B' ";
        	//$sql = str_replace("{year}", $year, $sql);
        	$stmt = $this->db->query($sql);
        	$dataObj = $stmt->fetchObject();
        	
        	$data = $dataObj->Amount;
        } catch (Exception $e) {

        }
        return $data;
    }

    function touragenctAmount($year)
    {
        $data = 0;
        if ($this->db == null) {
            return $data;
        }

        try {
        	$sql = "select count(Distinct F.LicenseNo) Amount
						from AreaPlan.FactGuideTraderBranch F
						inner join AreaPlan.DimGuideCategory DC on F.GuideCategoryID = DC.ID
						where DC.LicenseType = 'L' ";
        	//$sql = str_replace("{year}", $year, $sql);
        	$stmt = $this->db->query($sql);
        	$dataObj = $stmt->fetchObject();
        	
        	$data = $dataObj->Amount;
        } catch (Exception $e) {

        }
        return $data;
    }

    function TouragenctTypeAmount($year)
    {
        $data = array();
        if ($this->db == null) {
            return $data;
        }

        try {
        	$sql = "select count(Distinct F.LicenseNo) Amount, DC.CategoryDescription CategoryDescription
							from AreaPlan.FactGuideTraderBranch F
							inner join AreaPlan.DimGuideCategory DC on F.GuideCategoryID = DC.ID
							where DC.LicenseType = 'L'
							group by DC.CategoryDescription ";
        	//$sql = str_replace("{year}", $year, $sql);
        	$stmt = $this->db->query($sql);
        	$data = $stmt->fetchAll();
        } catch (Exception $e) {

        }
        return $data;
    }

    function GuideAmount($year)
    {
        $data = 0;
        if ($this->db == null) {
            return $data;
        }

        try {
        	$sql = "select count(Distinct F.LicenseNo) Amount
						from AreaPlan.FactGuideTraderBranch F
						inner join AreaPlan.DimGuideCategory DC on F.GuideCategoryID = DC.ID
						where DC.LicenseType = 'G' ";
        	//$sql = str_replace("{year}", $year, $sql);
        	$stmt = $this->db->query($sql);
        	$dataObj = $stmt->fetchObject();
        	
        	$data = $dataObj->Amount;
        } catch (Exception $e) {

        }
        return $data;
    }


    function TouristAmount($year)
    {
        $data = 0; // 90400000;
        if ($this->db == null) {
            return $data;
        }

        try {
        	$sql = "select sum(F.TravelerCount) Amount
						from dbo.v_Trend_FactTourismReceiptInboundsWithDomestic F
						where F.YearPeriodTypeID = 1 ";
        	//$sql = str_replace("{year}", $year, $sql);
        	$stmt = $this->db->query($sql);
        	$dataObj = $stmt->fetchObject();
        	
        	$data = $dataObj->Amount;
        } catch (Exception $e) {

        }
        return $data;
    }

    function GuideTypeAmount($year)
    {
        $data = array();
        if ($this->db == null) {
            return $data;
        }

        try {
        	$sql = "select count(Distinct F.LicenseNo) Amount, DC.CategoryDescription CategoryDescription
						from AreaPlan.FactGuideTraderBranch F
						inner join AreaPlan.DimGuideCategory DC on F.GuideCategoryID = DC.ID
						where DC.LicenseType = 'G'
						group by DC.CategoryDescription ";
        	//$sql = str_replace("{year}", $year, $sql);
        	$stmt = $this->db->query($sql);
        	$data = $stmt->fetchAll();
        } catch (Exception $e) {

        }
        return $data;
    }

    function GuideLanguageAmount($year)
    {
        $data = array();
        if ($this->db == null) {
            return $data;
        }

        try {        	
        	$sql = "select top (10) count(F.AuditKey) Amount, DC.LanguageTH
						from AreaPlan.FactGuideForeignLanguage F
						inner join dbo.DimCountry DC on F.CountryID = DC.ID
						group by DC.LanguageTH
						order by Amount Desc ";
        	//$sql = str_replace("{year}", $year, $sql);
        	$stmt = $this->db->query($sql);
        	$data = $stmt->fetchAll();
        } catch (Exception $e) {

        }
        return $data;
    }
    
    function findGuideLatestYear() {
    	$selectMax = $this->db->select()
    	->from(array("F" => "AreaPlan.DimGuidePerson"), array("year"=>"max(Year(F.RowStartDate))"));
    	$stmtMax = $this->db->query($selectMax);
    	$dataMax = $stmtMax->fetchObject();
    	$year = $dataMax->year;
    	return $year;
    }
}
?>