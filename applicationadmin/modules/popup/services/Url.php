<?php
class Popup_Service_Url {
    
    function __construct() {
        try {
            
        } catch ( Exception $e ) {
            
        }
    }
    public static function generate($controller, $params = array()) {
        
        $url = "/popup";
        switch ($controller) {
            case "Popup" :
                $url .= "/index/index";
                // if (isset($params["category"])) {
                //     $url .= "/id/" . $params["category"];
                // }
                break;
        }
        return $url;
    }
}

?>