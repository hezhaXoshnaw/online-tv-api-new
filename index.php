<?php
 ini_set('display_errors', 1);
 ini_set('display_startup_errors', 1);
 error_reporting(E_ALL);

require __DIR__.'/vendor/autoload.php';
require 'config.php';

use \CustomException\ActiveCodeException ;
use \CustomException\UserResendException;
use \CustomException\ValidationException;
use \CustomException\LoginException;
use \CustomException\UpdateAccountException;
use \Response\RenderOutput;
use \Lib\Database;
use \Lib\MyRedis;
use \Lib\Log;
use \Util\UserInfo;
use \Util\Validation;

// if ($_GET['mc']=='d4cff975a12e') die();

$hash = isset($_SERVER['HTTP_CLIENT_MATCH_V2']) ? $_SERVER['HTTP_CLIENT_MATCH_V2'] : @$_GET['hash'];
$ipAddress = isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] :$_SERVER['REMOTE_ADDR'];
$userDomain = isset($_SERVER['HTTP_DNM']) ? $_SERVER['HTTP_DNM'] : '*';
$debugModel =  isset($_GET['debug']) && $_GET['debug'] === DEBUG ? true : false;
$activeCode = null;
$userAccess = null;
$userRegister = null;
$userInfo = null;
$db = new Database(DB_HOST,DB_NAME,DB_USER,DB_PASS,DATABASE_ENCRYPTION_KEY);
$redis = MyRedis::getInstance();
try  {

	// if (in_array($_GET['mc'], ['07cb855fb7db5070','d4cff90ac3aa','5e2516f304c5','5e0927f31019','8f463c2e4e48156c','480611301adb']))
	// {
		
	// 	exit(json_encode(array('last_version'=>'1.1','url'=>'http://get.download-util-software.online/android/api_v2/zina-tv/zina-tv/APP_V_1.0.apk')));
	// }

	RenderOutput::setRedis($redis);
	UserInfo::setModeDebug($debugModel);

	$model = new \Lib\Model($db, $redis, $_GET['model']);
	$model->setModelByName();

	$countryConfig = new \Util\Country($ipAddress);
	$countryConfig->loadConfig($db, $redis);

	$userInfo = new UserInfo($_GET, $countryConfig, $hash, $_SERVER['HTTP_USER_AGENT'], $model);

	$userInfo->setSkipValidation($model->skipValidate());
	
	$userInfo->validate();


	$debugOutput = $redis->hget('debug' , strtolower($_GET['mc']));
	$userInfo->setDebugOutputModeOn($debugOutput ? true : false);

	if(!$userInfo->isApplicationEnabled() && !UserInfo::isModeDebug()) {
		throw new LoginException('this app is disabled','', VOD_ACCESS_SKIP);
	}


	if(
		!$userInfo::isModeDebug() &&
		$model->needVersionCheck() &&
		!empty($model->getVersionSupport()) &&
		!in_array($userInfo->getUserVersion() , $model->getVersionSupport()) )
	{
        Log::setDebugLogSteps('user should update software', $userInfo->isDebugOutputModeOn());

        RenderOutput::updateSoftwareMessage($userInfo, $model);

	}

	if ($_GET['mc'] == '480611301adb')
    {
        Log::setDebugLogSteps('user should update software', $userInfo->isDebugOutputModeOn());

        RenderOutput::updateSoftwareMessage($userInfo, $model);
    }


	$block = new \Account\BlockedUser($db);
	$block->blockValidation($userInfo->getMacAddress());

	if(!$userInfo->isSkipValidation()  && !Validation::isHashValid($userInfo)){
		$messageForLog = 'user hash is invalid valid hash is '.Validation::getValidHash($userInfo);
		throw  new ValidationException($messageForLog, 'please update your software');
	}
		
	if ($userInfo->isForAccess()) {
		if(!$model->isValidForLogin())
			throw new LoginException('this model is disabled', $this->getDeactivateMessage()  , VOD_ACCESS_SKIP);

		Log::setDebugLogSteps('request is for channel list', $userInfo->isDebugOutputModeOn());
		$userAccess = new \Account\UserAccess($db,$userInfo);

		if($userInfo->isUserRequestFreeLogin())
		{
			Log::setDebugLogSteps('checking for other key login to update free key lgoin', $userInfo->isDebugOutputModeOn());
			$keyLogin = $userAccess->getUserInfoFromDB($userInfo->getMacAddress());
			
			if($keyLogin != '')
			{
				Log::setDebugLogSteps('there is new key login '  . $keyLogin , $userInfo->isDebugOutputModeOn());
				$userInfo->setKeyLogin($keyLogin );
			}
		}
		Log::setDebugLogSteps('starting user login validations', $userInfo->isDebugOutputModeOn());
		$userAccess->login();
		Log::writeToFile($userInfo,'login success login count ' . $userAccess->getLoginCountToday(), $userAccess->getSecondKeyLogin());
		if($packageID = $countryConfig->getPackageID())
		{
			Log::setDebugLogSteps('updating user package by country config ' . $packageID, $userInfo->isDebugOutputModeOn());
			$userAccess->setPackageID($packageID);
		}
		Log::setDebugLogSteps('user validation success sending channel list', $userInfo->isDebugOutputModeOn());
		RenderOutput::loginSuccess($userInfo, $userAccess, $redis);
	}
	else {
		Log::setDebugLogSteps('registering user', $userInfo->isDebugOutputModeOn());

		if(!$model->isValidForRegister()) {
			Log::setDebugLogSteps('this model is disabled for registering ', $userInfo->isDebugOutputModeOn());
			throw new LoginException('you cannot register new code here', $this->getDeactivateMessage()  , VOD_ACCESS_SKIP);
		}


		if ($userInfo->isAuto()) {
			Log::setDebugLogSteps('auto code', $userInfo->isDebugOutputModeOn());
			$activeCode = new Account\AutoCode($db, $userInfo);

		} else {
			Log::setDebugLogSteps('active code', $userInfo->isDebugOutputModeOn());
			$activeCode = new Account\UserCode($db, $userInfo);
		}
		$userRegister = new \Account\UserRegister($db, $userInfo);

		if ($userInfo->isForRegister()) {
			Log::setDebugLogSteps('registering', $userInfo->isDebugOutputModeOn());
			$userRegister->newUser($activeCode);
			Log::setDebugLogSteps('registering success resending user', $userInfo->isDebugOutputModeOn());
			RenderOutput::userLoginResend(REVALIDATE_MESSAGE, $userInfo, $userRegister);
		} else if ($userInfo->isForUpdateAccount()) {
			Log::setDebugLogSteps('updating user account', $userInfo->isDebugOutputModeOn());
			$userRegister->updateAccount($activeCode);
			Log::setDebugLogSteps('update user account success', $userInfo->isDebugOutputModeOn());
			Log::writeToFile($userInfo,'user update account '. $activeCode->getPeriod() , 0);
			RenderOutput::updateResponse('success you account successfully updated',$userInfo,true,$userRegister);
		}
		else
		{
			Log::setDebugLogSteps('dont know what to do!!', $userInfo->isDebugOutputModeOn());
			die('not listed');
		}
	}

}
catch (\CustomException\DeviceNotRegisterException $deviceNotRegisterException)
{

	$message = $deviceNotRegisterException->getMessageForUser();
	if(!$userInfo->isFreeLoginAllowed() && $collection = $countryConfig->getCollection($userInfo->getModel()->getName())) {
		$data   =   [
			'mac_address'       => \Util\Encryption::encrypt($userInfo->getMacAddress(), Database::getEncryptionKey()),
			'serial_number'     => 'NONE',
			'collaction_id'     => $collection,
			'available'         => 1,
			'key_login'         => '',
			'is_expire'         => 0,
			'is_login_again'    => '',
			'previouse_period'  => 0,
			'user_date_login'   => \Util\Functions::getTodayDate(),
			'extra_period'      => 0,
		];
		if($db->insert('auto_code_mac_address', $data)) {
			$message = 'please login again ';
		}else{
			$filter = [
				'mac_address' => \Util\Encryption::encrypt($userInfo->getMacAddress(), Database::getEncryptionKey())
			];
			$update = [
				'collaction_id' => $collection
			];
			$db->update('auto_code_mac_address', $update, $filter);
		}
	}
	Log::writeToFile($userInfo , $deviceNotRegisterException->getMessage(), 0);
	RenderOutput::userLoginFail($message,$userInfo);

}

catch (\CustomException\DomainBlockedException $domainBlockedException)
{
	Log::writeToFile($userInfo,$domainBlockedException->getMessage(), 0);
	RenderOutput::forceHostUpdate($userInfo);

}
catch (\CustomException\AccessLoginException $exception) {

	if($exception->getCode() == VOD_ACCESS_DELETE) {
		\Util\Functions::vodAccess('delete', $userInfo, $exception->getMessage());
	}
	Log::writeToFile($userInfo , $exception->getMessage(), 0);
	RenderOutput::userLoginFail($exception->getMessageForUser(),$userInfo);
}
catch (\CustomException\ModelNotRegisterException $exception) {
	$arrayInfo = [
		'model'         => $_GET['model'],
		'macAddress'    => $_GET['mc'],
		'codeID'        => $_GET['ac'],
		'hash'          => $hash,
		'ip'            => $ipAddress,
		'country'       => '',
		'message'       => $exception->getMessage()
	];
	Log::writeErrorLog($arrayInfo);
	RenderOutput::invalidInput($exception->getMessageForUser());
}
catch (UpdateAccountException $updateAccountException) {
	Log::writeToFile($userInfo , $updateAccountException->getMessage(), 0);
	RenderOutput::updateResponse($updateAccountException->getMessageForUser(),$userInfo,false,null);
}
catch (LoginException $loginException)  {
	if($loginException->getCode() == VOD_ACCESS_DELETE) {
		\Util\Functions::vodAccess('delete', $userInfo, $loginException->getMessage());
	}
	Log::writeToFile($userInfo,$loginException->getMessage(), 0);
	RenderOutput::userLoginFail($loginException->getMessageForUser(),$userInfo);
}
catch (\CustomException\ExpireAccountException $expireAccountException )
{
	if($expireAccountException->getCode() == VOD_ACCESS_DELETE) {
		\Util\Functions::vodAccess('delete', $userInfo, $expireAccountException->getMessage());
	}
	Log::writeToFile($userInfo,$expireAccountException->getMessage(), 0);
	$accounts ='';
	if($userAccess != null)
	{
		$accounts = implode(',',$userAccess->getAllUserAccounts());
	}
	RenderOutput::userLoginFail($expireAccountException->getMessageForUser(),$userInfo,'EXPIRE', $accounts);
}
catch (ActiveCodeException $activeCodeException) {

	Log::writeToFile($userInfo,$activeCodeException->getMessage(), 0);
	RenderOutput::userLoginFail($activeCodeException->getMessageForUser(),$userInfo);
}
catch (ValidationException $validationException) {
	$arrayInfo = [
		'model'         => $_GET['model'],
		'macAddress'    => $_GET['mc'],
		'codeID'        => $_GET['ac'],
		'key_login'     => isset($_GET['key']) ? $_GET['key'] : '',
		'hash'          => $hash,
		'ip'            => $ipAddress,
		'country'       => $userInfo->getCountry(),
		'message'       => $validationException->getMessage()
	];
	Log::writeErrorLog($arrayInfo);
	RenderOutput::userLoginFail($validationException->getMessageForUser(),$userInfo);
}
catch (UserResendException $userResendException) {

	if($userResendException->getCode() == \Account\UserAccess::$resendExceptionCode) {
		Log::writeToFile($userInfo,$userResendException->getMessage(), $userAccess->getSecondKeyLogin());
		RenderOutput::userLoginResend($userResendException->getMessageForUser(), $userInfo, $userAccess);
	}
	if($userResendException->getCode() == UserInfo::$resendExceptionCode) {
		$userRegister = new \Account\UserRegister($db, $userInfo);
	}
	try {
		$userRegister->setDataForResendException($userInfo);
		$userInfo->setCodeID($userRegister->getCodeID(false));
		Log::writeToFile($userInfo,'user enter the code without key got the key', $userRegister->getSecondKeyLogin());

		RenderOutput::userLoginResend(REVALIDATE_MESSAGE,$userInfo,$userRegister);
	}catch (\CustomException\GeneralException $exception) {
		Log::writeToFile($userInfo,$exception->getMessage(), 0);
		RenderOutput::userLoginFail($exception->getMessageForUser(),$userInfo);
	}
}catch (\CustomException\GeneralException $generalException)
{
	Log::writeToFile($userInfo,$generalException->getMessage(), 0);
	RenderOutput::userLoginFail($generalException->getMessageForUser(),$userInfo);
}
catch (Exception $ex) {

	Log::writeToFile($userInfo,$ex->getMessage(), 0);
	RenderOutput::userLoginFail('Faild please try again latter',$userInfo);
}


