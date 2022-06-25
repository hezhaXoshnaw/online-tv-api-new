<?php

namespace Util;

use Lib\Log;

if(!defined('NOT_DIRECT_ACCESS'))
	die('its not defined');

class Functions
{
	const TODAY_DATE_FORMAT = 'Y-m-d';
	public static function getCountryDetail($ipAddress) {
		$country = geoip_record_by_name  ($ipAddress);
		return $country;
	}

	/**
	 * @return false|string
	 */
	public static function getTodayDateTime()
	{
		return date('Y-m-d H:i:s');
	}

	/**
	 * @return false|string
	 */
	public static function getTodayDate()
	{
		return date(self::TODAY_DATE_FORMAT);
	}

	/**
	 * @param int $timeStamp
	 * @return string
	 */
	public static function getTodayDateByTimeStamp($timeStamp)
	{
		return date(self::TODAY_DATE_FORMAT, $timeStamp);
	}

	/**
	 * @return int
	 */
	public static function getCurrentTimeStamp()
	{
		return time();
	}

	/**
	 * @param $operation
	 * @param UserInfo $userInfo
	 * @param string $logMessage
	 */
	public static function vodAccess($operation, UserInfo $userInfo, $logMessage = '')
	{
		Log::setDebugLogSteps('sending request to user for vod', $userInfo->isDebugOutputModeOn());
		$x=rand(0,9);
		$encryptHeader = Encryption::encrypt($userInfo->getMacAddress().$x,self::initKey($x)).$x;
		$url = $userInfo->getVodAccessURL().'?operation='.$operation.'&mac_address='.$userInfo->getMacAddress().'&target='.$userInfo->applicationNameForVodAccess;
		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_URL             => $url,
			CURLOPT_RETURNTRANSFER  => true,
			CURLOPT_CUSTOMREQUEST   => 'GET',
		));
		$headers = ["client-match: $encryptHeader"];
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		curl_exec($curl);
		curl_close ($curl);
		Log::vodAccessLog($userInfo,$logMessage,  $operation);

	}

	/**
	 * @param $index
	 * @return string  returning the index of the array
	 */
	private static function initKey($index)
	{
		$array_keys[0]="},aNn}>5        ";
		$array_keys[1]="fA:MP~5_        ";
		$array_keys[2]="T[52hPV?        ";
		$array_keys[3]=">^rgMF@(        ";
		$array_keys[4]="9EZz(D~;        ";
		$array_keys[5]="Xc6-M@d,        ";
		$array_keys[6]="\$Dw6.6mu        ";
		$array_keys[7]="_dA<4fDd        ";
		$array_keys[8]="j!2?cHt]        ";
		$array_keys[9]="=q,+!U6p        ";
		return $array_keys[$index];
	}

}