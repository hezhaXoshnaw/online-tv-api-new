<?php

namespace Util;

if(!defined('NOT_DIRECT_ACCESS'))
	die('its not defined');


use CustomException\ValidationException;

class Validation
{
	private static $_validHash;

	/**
	 * @param UserInfo $userInfo
	 * @return bool
	 */
	public static function isHashValid(UserInfo $userInfo)
	{
		$validHash = self::getValidHash($userInfo);
		if ($validHash == $userInfo->getUserHash()){
			return true;
		}
		self::$_validHash = $validHash;
		return false;
	}

	/**
	 * @param UserInfo $userInfo
	 * @return string
	 */
	public static function getValidHash(UserInfo $userInfo)
	{
		$input = $userInfo->getAPIKey() . ',';
		foreach ($userInfo->getArrayValuesForHash() as $key => $value)
		{
			if ($value != '' )
				$input .= $value.',';
		}
		return md5(rtrim($input,','));
	}
}