<?php
class Profile_Service_Url {
    
    function __construct() {
        try {
            
        } catch ( Exception $e ) {
            
        }
    }
    public static function generate($controller, $params = array()) {
        
        $url = "/profile";
        switch ($controller) {
            case "auth" :
                $url = "/auth/login";
                break;
            case "forgetpassword" :
            	$url = "/forgetpassword/index";
            	break;
            case "index" :
            	$url .= "/index/index";
            	break;
            case "register" :
            	$url = "/register/index";
            	break;
        }
        return $url;
    }
}

?>