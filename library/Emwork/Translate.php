<?php
/**
 * EMWORK Library
 *
 * @category   Emwork
 * @package    Emwork
 * @copyright
 * @license
 * @version
 */

/**
 * 
 * @category   Emwork
 * @package    Emwork
 * @copyright
 * @license
 */
class Emwork_Translate
{
	protected static $translate = array();
	
    public static function text($language, $group, $key)
    {
    	if (empty($group) || empty($language)) {
    		return $key;
    	}
    	
    	if (!defined('APPLICATION_PATH')) {
    		return $key;
    	}

    	if (array_key_exists($language, self::$translate)) {
    		if (!array_key_exists($group, self::$translate[$language])) {    
    			
    			self::$translate[$language][$group] = null;
    			
	    		$langFile = APPLICATION_PATH . DS . 'languages' . DS . $group . '.' . $language . '.ini';
	    		if (file_exists($langFile) && file_get_contents($langFile) != '') {
	    			self::$translate[$language][$group] = new Zend_Translate(array('adapter' => 'ini', 'content' => $langFile, 'locale' => $language));
	    		}
    		}
    	} else {
    		self::$translate[$language] = array();
    		self::$translate[$language][$group] = null;
    		
    		$langFile = APPLICATION_PATH . DS . 'languages' . DS . $group . '.' . $language . '.ini';
    		if (file_exists($langFile) && file_get_contents($langFile) != '') {
    			self::$translate[$language][$group] = new Zend_Translate(array('adapter' => 'ini', 'content' => $langFile, 'locale' => $language));
    		}
    	}
    	
    	if (self::$translate[$language][$group] != null) {
    		return self::$translate[$language][$group]->_($key);
    	}
    	return $key;
    	    	
//     	$langFile = APPLICATION_PATH . DS . 'languages' . DS . $group . '.' . $language . '.ini';
//     	if (file_exists($langFile) && file_get_contents($langFile) != '') {
//     		$translate = new Zend_Translate(array('adapter' => 'ini', 'content' => $langFile, 'locale' => $language));    			
//     		return $translate->_($key);
//     	}
//     	return $key;
    }
}
