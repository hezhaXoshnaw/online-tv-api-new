<?php

namespace Response;


use Lib\Log;
use Util\UserInfo;

class UserResponse
{

	/**
	 * @param UserInfo $userInfo
	 * @param string $debugPrefix
	 */
	public static function endUserRequest(UserInfo $userInfo, $debugPrefix = '')
	{
		if($userInfo->isDebugOutputModeOn())
		{
			$filePath       = DEBUG_FILE . 'debug_' . $debugPrefix . '_' . $userInfo->getMacAddress() . '_' . time() . '.json' ;
			$debugStepsPath = DEBUG_FILE . 'debug_steps_' . $debugPrefix . '_' . $userInfo->getMacAddress() . '_' . time() . '.json' ;
			file_put_contents($filePath, ob_get_contents());
			file_put_contents($debugStepsPath ,json_encode(Log::getDebugLogSteps()));
//			ob_end_clean();
		}
		exit();
	}

}