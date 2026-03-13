<?php
/**
 * EMWORK Library
 *
 * @category   Emwork
 * @package    Emwork_Utility_Crypt
 * @copyright
 * @license
 * @version
 */

/**
 * Static methods for Crypt Symmetric
 *
 * @category   Emwork
 * @package    Emwork_Utility_Crypt
 * @copyright
 * @license
 * Ref: http://stackoverflow.com/questions/16600708/how-do-you-encrypt-and-decrypt-a-php-string
 */
class Emwork_Utility_Crypt_Symmetric
{
    /**
	 * Returns an encrypted & utf8-encoded
	 */
	public static function encrypt($pure_string, $encryption_key) {
	    $iv_size = mcrypt_get_iv_size(MCRYPT_BLOWFISH, MCRYPT_MODE_ECB);
	    $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
	    $encrypted_string = mcrypt_encrypt(MCRYPT_BLOWFISH, $encryption_key, utf8_encode($pure_string), MCRYPT_MODE_ECB, $iv);
	    return $encrypted_string;
	}
	
	/**
	 * Returns decrypted original string
	 */
	public static function decrypt($encrypted_string, $encryption_key) {
	    $iv_size = mcrypt_get_iv_size(MCRYPT_BLOWFISH, MCRYPT_MODE_ECB);
	    $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
	    $decrypted_string = mcrypt_decrypt(MCRYPT_BLOWFISH, $encryption_key, $encrypted_string, MCRYPT_MODE_ECB, $iv);
	    return $decrypted_string;
	}
}
