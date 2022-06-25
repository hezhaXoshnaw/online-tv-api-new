<?php

namespace Util;


class AES
{
	/**
	 * AES constructor.
	 */
	private function __construct(){}


	/**
	 * @param $plainText
	 * @param $iv
	 * @param $key
	 * @return string
	 */
	public static function encrypt($plainText,$iv, $key) {
		$td = mcrypt_module_open('rijndael-128', '', 'cbc', $iv);
		mcrypt_generic_init($td, $key, $iv);
		$cipherText = mcrypt_generic($td, $plainText);
		mcrypt_generic_deinit($td);
		mcrypt_module_close($td);

		return bin2hex($cipherText);
	}

	/**
	 * @param $cipherText
	 * @param $iv
	 * @param $key
	 * @return string
	 */
	public static function decrypt($cipherText, $iv, $key) {
		$cipherText = self::hex2bin($cipherText);
		$td = mcrypt_module_open('rijndael-128', '', 'cbc', $iv);
		mcrypt_generic_init($td, $key, $iv);
		$plainText = mdecrypt_generic($td, $cipherText);
		mcrypt_generic_deinit($td);
		mcrypt_module_close($td);

		return utf8_encode(trim($plainText));
	}

	/**
	 * @param $hexData
	 * @return string
	 */
	private static function hex2bin($hexData) {
		$binData = '';

		for ($i = 0; $i < strlen($hexData); $i += 2) {
			$binData .= chr(hexdec(substr($hexData, $i, 2)));
		}

		return $binData;
	}
}