<?php

class Search_Model_Search {
    
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
            $this->db = Zend_Db_Table::getDefaultAdapter();
            
            // do the authentication
            $authobj = Zend_Auth::getInstance();
            $this->auth = $authobj->getIdentity();
            $this->UserID = isset($this->auth->UserID) ? $this->auth->UserID : '';
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    function getsave($text ,$period ,$start ,$end ,$lang ,$type) {

        // echo($start);exit;
        

        if($this->UserID=''){
            $userid = null ;
        }else{
            $userid = $this->UserID ;
        }

        $modified = date("Y-m-d H:i:s");
        $datestart = str_replace("/","-",$start);
        $dateend = str_replace("/","-",$end);
        $result = new stdClass();
        $data->result = false;

        try {

            $form = array();
            $where = array();
            $form['Lang']  = $lang;
            $form['UserID'] = empty($this->UserID) ? null : $this->UserID;
            $form['TextSearch'] = $text;
            $form['StartDate'] = $datestart;
            $form['EndDate'] = $dateend;
            $form['Type'] = $type;
            $form['Period'] = empty($period) ? null : $period;

            $form['Created'] = $modified;
            $form['CreatedBy'] = empty($this->UserID) ? null : $this->UserID;
            
            $result = $this->db->insert("search_history", $form);

            // echo "0";exit;

            if($result) {
                $data->ID = $this->db->lastInsertId();
                $data->result = true;
            }

        } catch (Exception $e) {
            $data->result = false;
            $data->message = $e->getMessage();
        }
        
    }

    function getListContent($sidx, $sord, $rows, $page, $textstr, $filter, $advancesearch, $lang = 'th' ) {

		try {

            // CONTENT
            $selectcontent = $this->db->select()
			->from(array("tblcontent_info" => "content_article_info"), "")
			->joinLeft(array("tblcontent" => "content_article_detail"), "tblcontent_info.ID = tblcontent.ID and tblcontent.Lang = '{$lang}'", "")
			->columns(array(
                "tblcontent_info.ID",
                "tblcontent.Title",
                "tblcontent.IntroText",
                "tblcontent_info.Created",
                "DataType" => "('content')"
			))
            ->where("tblcontent_info.IsEnable = 'Y' ")
            ->where("tblcontent_info.IsDeleted = 'N' ")
            ;

            if (!empty($advancesearch['period'])) {
                $selectcontent = $selectcontent->where("tblcontent_info.Created >= ?", $advancesearch['period']);
            }
            
            if (!empty($advancesearch['start'])) {
                $value = Emwork_Utility_DateTime::dateThaiToMySQL($advancesearch['start']);
                $selectcontent = $selectcontent->where("tblcontent_info.Created >= ?", $value);
            }

            if (!empty($advancesearch['end'])) {
                $value = Emwork_Utility_DateTime::dateThaiToMySQL($advancesearch['end']);
                $selectcontent = $selectcontent->where("tblcontent_info.Created <= ?", $value . " 23:59:59");
            }

            if(!empty($advancesearch['type'])) {
                if($advancesearch['type'] == 'tag') {
                    $selectcontent = $selectcontent
                    ->joinInner(array('tag' => 'content_article_tags'), 'tblcontent_info.ID = tag.ContentArticleID', '')
                    ->where("tag.Title like " . $this->db->quote("%" . $advancesearch['txtsearch'] . "%"))
                    ->group("tag.ContentArticleID")
                    ;
                } else if ($advancesearch['type'] == 'content') {
                    if(!empty($advancesearch['txtsearch'])) {
                        $selectcontent = $selectcontent
                        ->joinLeft(array("tblcontent_file" => "content_article_file"), 
                            "tblcontent_info.ID = tblcontent_file.ContentArticleID 
                            and tblcontent_file.IsEnable = 'Y' 
                            and tblcontent_file.IsDeleted = 'N'"
                        , "")
                        ->joinLeft(array('fcontent' => 'file_info'), 'tblcontent_file.FileInfoID = fcontent.ID', '')
                        ->where(
                            "( fcontent.Name like " . $this->db->quote("%" . $advancesearch['txtsearch'] . "%" ) . ") OR " . 
                            "( tblcontent.Title like " . $this->db->quote("%" . $advancesearch['txtsearch'] . "%") .  ")"
                        )
                        ->group("tblcontent_info.ID")
                        ;
                    }
                }
            } else {
                if(!empty($advancesearch['txtsearch'])) {
                    $selectcontent = $selectcontent
                    ->joinLeft(array("tblcontent_file" => "content_article_file"),
                        "tblcontent_info.ID = tblcontent_file.ContentArticleID 
                        and tblcontent_file.IsEnable = 'Y' 
                        and tblcontent_file.IsDeleted = 'N'"
                    , "")
                    ->joinLeft(array('fcontent' => 'file_info'), 'tblcontent_file.FileInfoID = fcontent.ID', '')
                    ->where(
                        "( fcontent.Name like " . $this->db->quote("%" . $advancesearch['txtsearch'] . "%" ) . ") OR " . 
                        "( tblcontent.Title like " . $this->db->quote("%" . $advancesearch['txtsearch'] . "%") .  ")"
                    )
                    ->group("tblcontent_info.ID")
                    ;
                }
            }


            // CALENDAR
            $selectevent = $this->db->select()
			->from(array('tblevent_info' => 'calendar_article_info'), '')
			->joinLeft(array("tblevent" => "calendar_article_detail"), "tblevent_info.ID = tblevent.ID and tblevent.Lang = '{$lang}'", "")
			->columns(array(
                "tblevent_info.ID",
                "tblevent.Title",
                "tblevent.IntroText",
                "tblevent_info.Created",
                "DataType" => "('calendar')"
			))
            ->where("tblevent_info.IsEnable = 'Y' ")
            ->where("tblevent_info.IsDeleted = 'N' ")
            ;

            if(!empty($advancesearch['txtsearch'])) {
                $selectevent = $selectevent
                ->joinLeft(array("tblevent_file" => "calendar_article_file"), 
                    "tblevent_info.ID = tblevent_file.CalendarArticleID 
                    and tblevent_file.IsEnable = 'Y' 
                    and tblevent_file.IsDeleted = 'N'"
                , "")
                ->joinLeft(array('fcalendar' => 'file_info'), 'tblevent_file.FileInfoID = fcalendar.ID', '')
                ->where(
                    "( fcalendar.Name like " . $this->db->quote("%" . $advancesearch['txtsearch'] . "%" ) . ") OR " . 
                    "( tblevent.Title like " . $this->db->quote("%" . $advancesearch['txtsearch'] . "%") .  ")"
                )
                ->group("tblevent_info.ID")
                ;
            }
                
            if (!empty($advancesearch['period'])) {
                $selectevent = $selectevent->where("tblevent_info.Created >= ?", $advancesearch['period']);
            }
            
            if (!empty($advancesearch['start'])) {
                $value = Emwork_Utility_DateTime::dateThaiToMySQL($advancesearch['start']);
                $selectevent = $selectevent->where("tblevent_info.Created >= ?", $value);
            }

            if (!empty($advancesearch['end'])) {
                $value = Emwork_Utility_DateTime::dateThaiToMySQL($advancesearch['end']);
                $selectevent = $selectevent->where("tblevent_info.Created <= ?", $value . " 23:59:59");
            }


            // GALLERY
            $selectgallery = $this->db->select()
			->from(array('tblgallery_info' => 'gallery_article_info'), "")
			->joinLeft(array("tblgallery" => "gallery_article_detail"), "tblgallery_info.ID = tblgallery.ID and tblgallery.Lang = '{$lang}'", "")
			->columns(array(
                "tblgallery_info.ID",
                "tblgallery.Title",
                "tblgallery.IntroText",
                "tblgallery_info.Created",
                "DataType" => "('gallery')"
			))
            ->where("tblgallery_info.IsEnable = 'Y' ")
            ->where("tblgallery_info.IsDeleted = 'N' ")
            ->where("tblgallery_info.IsTrash = 'N' ")
            ;

            if(!empty($advancesearch['type'])) {
                if($advancesearch['type'] == 'tag') {
                    $selectgallery = $selectgallery
                    ->joinInner(array('tag' => 'gallery_article_tags'), 'tblgallery_info.ID = tag.GalleryArticleID', '')
                    ->where("tag.Title like " . $this->db->quote("%" . $advancesearch['txtsearch'] . "%"))
                    ->group("tag.GalleryArticleID")
                    ;
                } else if ($advancesearch['type'] == 'gallery') {
                    if(!empty($advancesearch['txtsearch'])) {
                        $selectgallery = $selectgallery
                        ->joinLeft(array("tblgallery_file" => "gallery_article_file"), 
                            "tblgallery_info.ID = tblgallery_file.GalleryArticleID 
                            and tblgallery_file.IsEnable = 'Y' 
                            and tblgallery_file.IsDeleted = 'N'"
                        , "")
                        ->joinLeft(array('fgallery' => 'file_info'), 'tblgallery_file.FileInfoID = fgallery.ID', '')
                        ->where(
                            "( fgallery.Name like " . $this->db->quote("%" . $advancesearch['txtsearch'] . "%" ) . ") OR " . 
                            "( tblgallery.Title like " . $this->db->quote("%" . $advancesearch['txtsearch'] . "%") .  ")"
                        )
                        ->group("tblgallery_info.ID")
                        ;
                    }
                }
            } else {
                
                if(!empty($advancesearch['txtsearch'])) {
                    $selectgallery = $selectgallery
                    ->joinLeft(array("tblgallery_file" => "gallery_article_file"),
                        "tblgallery_info.ID = tblgallery_file.GalleryArticleID 
                        and tblgallery_file.IsEnable = 'Y' 
                        and tblgallery_file.IsDeleted = 'N'"
                    , "")
                    ->joinLeft(array('fgallery' => 'file_info'), 'tblgallery_file.FileInfoID = fgallery.ID', '')
                    ->where(
                        "( fgallery.Name like " . $this->db->quote("%" . $advancesearch['txtsearch'] . "%" ) . ") OR " . 
                        "( tblgallery.Title like " . $this->db->quote("%" . $advancesearch['txtsearch'] . "%") .  ")"
                    )
                    ->group("tblgallery_info.ID")
                    ;
                }
            }

            if (!empty($advancesearch['period'])) {
                $selectgallery = $selectgallery->where("tblgallery_info.Created >= ?", $advancesearch['period']);
            }
            
            if (!empty($advancesearch['start'])) {
                $value = Emwork_Utility_DateTime::dateThaiToMySQL($advancesearch['start']);
                $selectgallery = $selectgallery->where("tblgallery_info.Created >= ?", $value);
            }

            if (!empty($advancesearch['end'])) {
                $value = Emwork_Utility_DateTime::dateThaiToMySQL($advancesearch['end']);
                $selectgallery = $selectgallery->where("tblgallery_info.Created <= ?", $value . " 23:59:59");
            }

            $pageIndex = ($rows*($page-1));

            switch ($advancesearch['type']) {
                case 'content': 
                    $selectAll = $selectcontent;
                    $records = $this->getListCount($selectcontent);
                    $selectAll = "select * , T.ID, abs(".$records.") AS `RECORD` FROM (".$selectAll.") as T Limit ".$pageIndex.",".$rows;
                    break;
                case 'tag':
                    $selectAll = $selectcontent." UNION ".$selectgallery;
                    $records = $this->getListCount($selectcontent) + $this->getListCount($selectgallery);                    
                    $selectAll = "select * , T.ID, abs(".$records.") AS `RECORD` FROM (".$selectAll.") as T Limit ".$pageIndex.",".$rows;
                   break;
                case 'event':
                    $selectAll = $selectevent;
                    $records = $this->getListCount($selectevent);
                    $selectAll = "select * , T.ID, abs(".$records.") AS `RECORD` FROM (".$selectAll.") as T Limit ".$pageIndex.",".$rows;
                    break;
                case 'gallery':
                    $selectAll = $selectgallery;
                    $records = $this->getListCount($selectgallery);                
                    $selectAll = "select * , T.ID, abs(".$records.") AS `RECORD` FROM (".$selectAll.") as T Limit ".$pageIndex.",".$rows;
                    break;
                default:
                    $selectAll = $selectcontent." UNION ".$selectevent." UNION ".$selectgallery;
                    $records = $this->getListCount($selectcontent) + $this->getListCount($selectevent) + $this->getListCount($selectgallery);
                    $selectAll = "select * , T.ID, abs(".$records.") AS `RECORD` FROM (".$selectAll.") as T Limit ".$pageIndex.",".$rows;
                    break;
            }
            
            $stmt = $this->db->query($selectAll);
            $data = $stmt->fetchAll();

			if (count($data) > 0) {
				for ($i = 0; $i < count($data); $i ++) {
					$data[$i]["Created"] = empty($data[$i]["Created"]) ? null : new DateTime($data[$i]["Created"]);
					$data[$i]["Modified"] = empty($data[$i]["Modified"]) ? null : new DateTime($data[$i]["Modified"]);
				}
            }
            
		} catch (Exception $e) {

        }

		return $data;
    }
    
	function getListCount($select) {
		$select = $this->db->select()
		->from(array('T' => $select), array("RECORDS" => "count(T.ID)"))
        ;
		$stmt = $this->db->query($select);
		$data = $stmt->fetchObject();
		return $data->RECORDS;
    }
}

?>
