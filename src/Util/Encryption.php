<?php
/**
 * all methods of this class are static
 */

namespace Util;

if(!defined('NOT_DIRECT_ACCESS'))
	die('its not defined');

class Encryption
{
	/**
	 * Encryption constructor.
	 * no need for encryption object
	 */
	private function __construct(){}
	private function __clone(){}

	/**
	 * @param $path
	 * @return string
	 */
	public static function encryptChannelPath($path)
	{
		$encrypted = @openssl_encrypt($path,'des-cbc', 'z4N7cuNZ',false,'fdsawngh');
		return $encrypted;
	}


	/**
	 * @param $data
	 * @param $key
	 * @return string|bool
	 */
	public static function encrypt($data, $key)
	{

		return base64_encode(
			mcrypt_encrypt(
				MCRYPT_RIJNDAEL_128,
				$key ,
				$data,
				MCRYPT_MODE_CBC,
				"\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0"
			));
	}

	/**
	 * @param $data
	 * @param string $key
	 * @return string|bool
	 */
	public static function decrypt($data, $key)
	{
		$decode = base64_decode($data);
		$value =  mcrypt_decrypt(
			MCRYPT_RIJNDAEL_128,
			$key,
			$decode,
			MCRYPT_MODE_CBC,
			"\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0"
		);
		return self::cleanNullChar($value);
	}

	/**
	 * @param $value
	 * @return string
	 */
	private static function cleanNullChar($value)
	{
		$iv 	=	 strpos($value, "\0");
		return $iv !== false ? substr($value, 0, $iv) : $value;
	}
}