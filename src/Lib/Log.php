<?php

namespace Lib;

if(!defined('NOT_DIRECT_ACCESS'))
	die('its not defined');

use Util\UserInfo;

class Log
{
	private static $_debugLogSteps = [];
	/**
	 * @param UserInfo $userInfo
	 * @param string $note
	 * @param int $newKeyLogin
	 */
	public static function writeToFile(UserInfo $userInfo, $note, $newKeyLogin = 0)
	{
		$log = date("Y-m-d H:i:s") . '__' . $userInfo->getIPAddress() .'_'. $userInfo->getCountry() .'_' .$userInfo->getModel()->getName().'_'. $userInfo->getUserDomainName(). '_'.$userInfo->getUserHash().'_'.$userInfo->getCodeID(). '/' . $userInfo->getMacAddress() . '/' . $userInfo->getKeyLogin() . '-' . $userInfo->getSecondKeyLogin(). '_New Key Login : '.$newKeyLogin .'_'.$note." \n";
		file_put_contents(LOG_FILE . date('Y-m-d') . '_access.log', $log, FILE_APPEND);
	}

	/**
	 * @param UserInfo $userInfo
	 * @param string $note
	 * @param int $newKeyLogin
	 */
	public static function todayNewUserLog(UserInfo $userInfo, $note, $newKeyLogin = 0)
	{
		$log = date("Y-m-d H:i:s") . '__' .  $userInfo->getIPAddress() .'__'. $userInfo->getCountry() .'__' .$userInfo->getModel()->getName() .'_'. $userInfo->getUserDomainName(). '__'.$userInfo->getUserHash().'__'.$userInfo->getCodeID(). '/' . $userInfo->getMacAddress() . '/' . $userInfo->getKeyLogin() . '-' . $userInfo->getSecondKeyLogin(). '__New Key Login : '.$newKeyLogin .'__'.$note." \n";
		file_put_contents(LOG_FILE. date('Y-m-d'). '_new_user.log', $log, FILE_APPEND);

	}
	/**
	 * @param array $arrayInfo
	 */
	public static function writeErrorLog(array $arrayInfo)
	{
		$arrayInfo['date'] = date("Y-m-d H:i:s");
		$log = json_encode($arrayInfo)."\n";
		file_put_contents(LOG_FILE .  date('Y-m-d') . '_error.log', $log, FILE_APPEND);

	}

	/**
	 * @param UserInfo $userInfo
	 * @param $logMessage
	 * @param $action
	 */
	public static function vodAccessLog(UserInfo $userInfo, $logMessage, $action)
	{
		$log =  date("Y-m-d H:i:s") . '__' . $userInfo->getIPAddress().'__'.$userInfo->getMacAddress(). '__'.$logMessage .'__' . $action . "\n";
		file_put_contents(LOG_FILE.  date('Y-m-d'). '_vod.log', $log, FILE_APPEND);

	}


	/**
	 * @param $debugLogSteps
	 * @param $debugStatus
	 */
	public static function setDebugLogSteps($debugLogSteps, $debugStatus)
	{
		if($debugStatus)
			self::$_debugLogSteps[]  = $debugLogSteps;
	}

	/**
	 * @return array
	 */
	public static function getDebugLogSteps()
	{
		return self::$_debugLogSteps;
	}
}