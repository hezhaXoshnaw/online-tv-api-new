<?php

namespace Account;

if(!defined('NOT_DIRECT_ACCESS'))
	die('its not defined');

use CustomException\AccessLoginException;
use CustomException\ActiveCodeException;
use CustomException\ExpireAccountException;
use CustomException\GeneralException;
use CustomException\LoginException;
use CustomException\UserResendException;
use CustomException\ValidationException;
use Lib\Database;
use Lib\Log;
use Util\Encryption;
use Util\Functions;
use Util\UserInfo;

class UserAccess extends User
{


  /**
     * @var bool
     */
    private $_skipUpdateCountry =  false;
    
	/**
	 * @var bool 
	 */
	private $_countrySkip = false;
	
	/**
	 * @var bool
	 */
	private $_originalURL = false;

	
	/**
	 * @var array
	 */
	private $_allUserAccounts = [];

	/**
	 * @var bool
	 */
	private $_httpsSupport = false;

	/**
	 * @var int
	 */
	private $_modelId;

	/**
	 * @var int
	 */
	public static $resendExceptionCode= 2;

	/**
	 * @var string
	 */
	private $_serialNumber;

	/**
	 * @var string
	 */
	private $_dateLogin;

	/**
	 * @var string
	 */
	private $_expireDate;

	/**
	 * @var int tinyint
	 */
	private $_userActive;

	/**
	 * @var int tinyint
	 */
	private $_vodAccess;

	/**
	 * @var string
	 */
	private $_channelTag;

	/**
	 * @var int
	 */
	private $_loginCountToday = 0;

	/**
	 * UserAccess constructor.
	 * @param Database $db
	 * @param UserInfo $userInfo
	 */
	public function __construct(Database $db =null, UserInfo $userInfo = null)
	{
		parent::__construct($db, $userInfo);
	}

	/**
	 * @throws AccessLoginException
	 * @throws LoginException
	 * @throws UserResendException
	 * @throws ValidationException
	 * @throws GeneralException
	 * @throws ExpireAccountException
	 */
	public function login()
	{

		$encryptKeyLogin = Encryption::encrypt($this->getUserInfo()->getKeyLogin(), Database::getEncryptionKey());
		$this->setUserDataFromDB($encryptKeyLogin,$this->getUserInfo()->isAuto())
			->validateMacAddress($this->getUserInfo()->getMacAddress())
			->isActive()
			->validateLoginCountDaily()
			->validateSecondKeyLogin($this->getUserInfo()->getSecondKeyLogin())
			->validateKeyLoginChangeTime()
			->validateCodeID()
			->expireCheck()
			->updateUserCountryInfo()
			->vodAccessCheck()
			->updateLoginDailyCount()
			->newSecondKeyLogin()
//			->setNewIPAddress()
			->checkUserDeviceModel()
			->updateChanges()
//			->countryCheck()
			;
	}

	/**
	 * @param $keyLogin
	 * @param bool $isAuto
	 * @return $this
	 * @throws AccessLoginException
	 */
	public function setUserDataFromDB($keyLogin, $isAuto = false)
	{
		Log::setDebugLogSteps('load user from db', $this->getUserInfo()->isDebugOutputModeOn());

		if (!$result = $this->loadDataFromDB($keyLogin)) {
			Log::setDebugLogSteps('user not found by key ', $this->getUserInfo()->isDebugOutputModeOn());
			$messageForUser = $isAuto ? self::$_userResponse['expire'] : self::$_userResponse['reset'];
			throw new AccessLoginException('account not found', $messageForUser, VOD_ACCESS_DELETE);
		}
		$this->setValues($result);
		Log::setDebugLogSteps('current user data is ', $this->getUserInfo()->isDebugOutputModeOn());
		Log::setDebugLogSteps(json_encode($result) , $this->getUserInfo()->isDebugOutputModeOn());
		return $this;

	}

	/**
	 * @return $this
	 */
	private function updateUserCountryInfo()
	{
		Log::setDebugLogSteps('update country info', $this->getUserInfo()->isDebugOutputModeOn());
		if($this->getUserInfo()->getCountry() != $this->_country && !$this->isSkipUpdateCountry())
		{
			$this->_country = $this->getUserInfo()->getCountry();
			$this->_updateParams['country'] = $this->_country;
		}

		if($this->_ipAddress != $this->getUserInfo()->getIPAddress())
		{
			$this->_ipAddress = $this->getUserInfo()->getIPAddress();
			$this->_updateParams['ip_address'] = $this->_ipAddress;
		}

		return $this;
	}

	/**
	 * @param $keyLogin
	 * @return array| bool
	 */
	public function loadDataFromDB($keyLogin)
	{
		$query = 'SELECT * FROM user_table WHERE key_login=:key_login  ORDER BY user_id DESC';
		$dbResult = $this->_db->select($query, array('key_login' => $keyLogin));
		$this->setAllUserAccount($dbResult);
		return empty($dbResult) ? false : array_shift($dbResult);
	}

	/**
	 * @param array $account
	 */
	private function setAllUserAccount(array $account)
	{
		foreach ($account as $code) {
			$decryptCode = Encryption::decrypt($code['code_id'], Database::getEncryptionKey());
			array_push($this->_allUserAccounts, substr($decryptCode, 0,10));
		}
	}
	/**
	 * @param array $dbResult
	 */
	private function setValues(array $dbResult)
	{
		$this->_httpsSupport    = $dbResult['https_support'] == 1 ? true : false;
		$this->_macAddress      = $dbResult['mac_address'];
		$this->_codeID          = $dbResult['code_id'];
		$this->_packageID       = $dbResult['package_id'];
		$this->setKeyLogin($dbResult['key_login'], true);
		$this->setSecondKeyLogin($dbResult['second_key_login']);
		$this->_expireDate      = $dbResult['expire_date'];
		$this->_dateLogin       = $dbResult['date_login'];
		$this->_keyLoginChangeTime  = $dbResult['key_login_change_time'];
		$this->_keyLoginChangeValue = $dbResult['key_login_change_value'];
		$this->_loginCountToday = $dbResult['login_count_dailly'];
		$this->_vodAccess       = $dbResult['vod_access'];
		$this->_userActive      = $dbResult['user_active'];
		$this->_userID          = $dbResult['user_id'];
		$this->_isAuto          = $dbResult['is_auto'];
		$this->_country         = $dbResult['country'];
		$this->_modelId         = $dbResult['model_id'];
		$this->_tokenIPSkip     = $dbResult['token_ip_skip'] == 1 ? true : false;
		$this->_originalURL     = $dbResult['support_original_url'] == 1 ? true : false;
		$this->_countrySkip    = $dbResult['country_skip'] == 1 ? true : false;
		$this->_channelTag     = isset($dbResult['channel_tag']) ? $dbResult['channel_tag'] : null;
		$this->_skipUpdateCountry= $dbResult['ip_manual_add'] == 0 ? false: true;



	}

	/**
	 * @return $this
	 */
	private function checkUserDeviceModel()
	{
		Log::setDebugLogSteps('checking device model' , $this->getUserInfo()->isDebugOutputModeOn());
		if($this->_modelId != $this->getUserInfo()->getModel()->getID())
		{
			Log::setDebugLogSteps("device model is not same {old:{$this->_updateParams['model_id']},New :  {$this->getUserInfo()->getModel()->getID()}" , $this->getUserInfo()->isDebugOutputModeOn());
			$this->_updateParams['model_id'] = $this->getUserInfo()->getModel()->getID();
		}
		return $this;
	}
	/**
	 * @return $this
	 * @throws LoginException
	 */
	private function countryCheck()
	{
        if(UserInfo::isModeDebug())
            return $this;

        if ($this->_isAuto == 0 && $this->getUserInfo()->getCountry()  != $this->_country) {
			try  {
				$activeCode = new UserCode($this->_db, $this->getUserInfo());
				$activeCode->countryAllowedCheck($this->getUserInfo()->getCountry());
			}
			catch (ActiveCodeException $activeCodeException) {
				throw new LoginException($activeCodeException->getMessage(),  '',VOD_ACCESS_SKIP);
			}

		}
		return $this;
	}

	/**
	 * @param $macAddress
	 * @return $this
	 * @throws ValidationException
	 */
	private function validateMacAddress($macAddress)
	{
		Log::setDebugLogSteps('validating mac address', $this->getUserInfo()->isDebugOutputModeOn());
        if(UserInfo::isModeDebug())
            return $this;

        if ($this->getMacAddress(false) != $macAddress){
			$messageForLog = "user send same key login but its not same macAddress {$this->getMacAddress(false)}";
	        Log::setDebugLogSteps("user send same key login but its not same macAddress {$this->getMacAddress(false)}", $this->getUserInfo()->isDebugOutputModeOn());
			throw new ValidationException($messageForLog, self::$_userResponse['macNotSame']);
		}
		return $this;

	}

	/**
	 * @return $this
	 * @throws LoginException
	 */
	private function isActive()
	{
		Log::setDebugLogSteps('is user active?', $this->getUserInfo()->isDebugOutputModeOn());
        if(UserInfo::isModeDebug())
            return $this;

        if ($this->_userActive == 0){
	        Log::setDebugLogSteps('user is not active', $this->getUserInfo()->isDebugOutputModeOn());
	        throw new LoginException('user account already suspended', self::$_userResponse['suspend'], VOD_ACCESS_SKIP);
        }


		return $this;
	}

	/**
	 * @return $this
	 */
	private function vodAccessCheck()
	{
		Log::setDebugLogSteps('vod access check', $this->getUserInfo()->isDebugOutputModeOn());

		if ($this->_vodAccess == 0) {
			Log::setDebugLogSteps('sending request for vod', $this->getUserInfo()->isDebugOutputModeOn());
			Functions::vodAccess('add', $this->getUserInfo(), 'vod access is 0');
			$this->_updateParams['vod_access'] = 1;
		}
		return $this;
	}

	/**
	 * @param $userSecondKeyLogin
	 * @return $this
	 * @throws UserResendException
	 * @throws GeneralException
	 */
	private function validateSecondKeyLogin($userSecondKeyLogin)
	{
		Log::setDebugLogSteps('validating second keyLogin', $this->getUserInfo()->isDebugOutputModeOn());
        if(UserInfo::isModeDebug())
            return $this;

        if ($userSecondKeyLogin != $this->getSecondKeyLogin()) {
	        Log::setDebugLogSteps('second key login not same ' , $this->getUserInfo()->isDebugOutputModeOn());
			$this->newSecondKeyLogin(true)->updateChanges();
			$logMessage = "user second key login not same {$userSecondKeyLogin} , {$this->getSecondKeyLogin()} changeCount {$this->getKeyLoginChangeValue()}";
			throw new UserResendException($logMessage, self::$_userResponse['re-validate'], self::$resendExceptionCode);
		}

		return $this;
	}

	/**
	 * @return $this
	 * @throws LoginException
	 * @throws GeneralException
	 */
	private function validateKeyLoginChangeTime()
	{
		Log::setDebugLogSteps("checking key login change time {$this->getKeyLoginChangeValue()}" , $this->getUserInfo()->isDebugOutputModeOn());

        if(UserInfo::isModeDebug())
            return $this;

        if($this->getKeyLoginChangeValue() > $this->getUserInfo()->getKeyLoginChangeLimit())
		{
			Log::setDebugLogSteps('exceed second key login change time ' . $this->getKeyLoginChangeValue() , $this->getUserInfo()->isDebugOutputModeOn());
			$this->_updateParams['user_active'] = 0;
			$this->updateChanges();
			$messageForLog = "account just got block change count {$this->getKeyLoginChangeValue()}";
			throw new LoginException($messageForLog, self::$_userResponse['suspend']);
		}
		return $this;
	}

	/**
	 * @return $this
	 * @throws ExpireAccountException
	 */
	public function expireCheck()
	{
		Log::setDebugLogSteps('expire code check', $this->getUserInfo()->isDebugOutputModeOn());
		if (strtotime($this->_expireDate)	<=	strtotime(Functions::getTodayDateTime())) {
			Log::setDebugLogSteps('account expire' , $this->getUserInfo()->isDebugOutputModeOn());
			$expireArchiveData  =   [
				'code_id'				=>$this->getCodeID(),
				'user_id'				=>$this->_userID,
				'mac_address'			=>$this->getMacAddress(),
				'expire_date'			=>$this->getExpireDate(),
				'is_auto'				=>$this->_isAuto,
				'date_login'			=>$this->_dateLogin,
				'expire_date_in_system'	=>Functions::getTodayDateTime(),
			];
			$this->_db->insert('expire_archive',$expireArchiveData);
			throw new ExpireAccountException('this account just got expire', self::$_userResponse['expire']);
		}
		return $this;
	}

	/**
	 * @return $this
	 * @throws UserResendException
	 */
	private function validateCodeID()
	{
		Log::setDebugLogSteps('validating code id', $this->getUserInfo()->isDebugOutputModeOn());
        if(UserInfo::isModeDebug())
            return $this;

        $validCodeID = $this->getCodeID(false);
		if ($validCodeID != $this->getUserInfo()->getCodeID()) {
			Log::setDebugLogSteps('code id is not same ' , $this->getUserInfo()->isDebugOutputModeOn());
			$messageForLog = "user code is not same need re-validate valid code {$validCodeID} request code {$this->getUserInfo()->getCodeID()}";
			$this->getUserInfo()->setCodeID($validCodeID);

			throw new UserResendException( $messageForLog, self::$_userResponse['re-validate'], self::$resendExceptionCode);
		}

		return $this;

	}

	/**
	 * @return $this
	 * @throws GeneralException
	 */
	private function updateLoginDailyCount()
	{
		Log::setDebugLogSteps('update login count daily', $this->getUserInfo()->isDebugOutputModeOn());

		$time = $this->getSecondKeyLogin();
		if (Functions::getTodayDate() == Functions::getTodayDateByTimeStamp($time)) {
			$this->_updateParams['login_count_dailly'] = $this->_loginCountToday + 1;
			Log::setDebugLogSteps('total count ' . ( $this->_loginCountToday + 1 ), $this->getUserInfo()->isDebugOutputModeOn());
		}else {
			$this->_updateParams['login_count_dailly'] = 1;
			Log::setDebugLogSteps('resetting count', $this->getUserInfo()->isDebugOutputModeOn());
		}

		return $this;
	}

	/**
	 * @return $this
	 * @throws LoginException
	 * @throws GeneralException
	 */
	private function validateLoginCountDaily()
	{
		Log::setDebugLogSteps('login count daily', $this->getUserInfo()->isDebugOutputModeOn());
	    if(UserInfo::isModeDebug())
	        return $this;

		if (Functions::getTodayDate() == Functions::getTodayDateByTimeStamp($this->getSecondKeyLogin())
			&& $this->_loginCountToday > $this->getUserInfo()->getLoginDailyLimit()) {
			Log::setDebugLogSteps('exceed login count daily ' . $this->_loginCountToday, $this->getUserInfo()->isDebugOutputModeOn());
			$messageForLog = "exceed the limit login {$this->getUserInfo()->getLoginDailyLimit()} {$this->getLoginCountToday()}";
			throw new LoginException($messageForLog, self::$_userResponse['loginCount'], VOD_ACCESS_SKIP);
		}
		return $this;
	}
	/**
	 * @return $this
	 */
	private function setNewIPAddress()
	{
		//here is
		Log::setDebugLogSteps('setting ip address info', $this->getUserInfo()->isDebugOutputModeOn());
		$this->_updateParams['ip_address']  = $this->getUserInfo()->getIPAddress();
		$this->_updateParams['country']     = $this->getUserInfo()->getCountry();
		$this->_updateParams['region']      = $this->getUserInfo()->getRegion();
		return $this;

	}

	/**
	 * @param bool $fullDate
	 * @return string
	 */
	public function getExpireDate($fullDate = true)
	{
		return $fullDate ? $this->_expireDate : Date('Y-m-d', strtotime($this->_expireDate));
	}

	/**
	 * @param bool $fullDate
	 * @return string
	 */
	public function getLoginDate($fullDate = true)
	{
		return $fullDate ? $this->_dateLogin : Date('Y-m-d' , strtotime($this->_dateLogin));
	}



	/**
	 * @return int
	 */
	public function getLoginCountToday()
	{
		return $this->_loginCountToday;
	}

	/**
	 * @return array
	 * @throws GeneralException
	 */
	public function toString()
	{
		return [
			'mac_address'	    => $this->_macAddress,
			'code_id'	        => $this->_codeID,
			'package_id'	    => $this->_packageID,
			'expire_date'	    => $this->_expireDate,
			'date_login'	    => $this->_dateLogin,
			'key_login_change_time'	=> $this->_keyLoginChangeTime,
			'key_login_change_value'	=> $this->getKeyLoginChangeValue(),
			'login_count_dailly'=> $this->_loginCountToday,
			'vod_access'	    => $this->_vodAccess,
			'user_active'	    => $this->_userActive,
			'user_id'	        => $this->_userID,
			'is_auto'	        => $this->_isAuto,
			'country'	        => $this->_country,
			'model_id'	        => $this->_modelId,
			'key_login'         => $this->getKeyLogin(),
			'second_key_login'  => $this->getSecondKeyLogin()
		];
	}

	/**
	 * @return bool
	 */
	public function isHttpsSupport()
	{
		return $this->_httpsSupport && $this->getUserInfo()->isSupportHTTPS();
	}

	/**
	 * @return array
	 */
	public function getAllUserAccounts()
	{
		return $this->_allUserAccounts;
	}


	/**
	 * @return bool
	 */
	public function isOriginalURL()
	{
		return $this->_originalURL;
	}

	/**
	 * @return bool
	 */
	public function isCountrySkip()
	{
		return $this->_countrySkip;
	}

	/**
	 * @return string
	 */
	public function getChannelTag()
	{
		return $this->_channelTag;
	}


    /**
     * @return bool
     */
    public function isSkipUpdateCountry()
    {
        return $this->_skipUpdateCountry;
    }

    /**
     * @return string
     */
    public function getCountry()
    {
        return $this->_country;
    }

}