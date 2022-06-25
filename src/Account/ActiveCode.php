<?php
namespace Account;

if(!defined('NOT_DIRECT_ACCESS'))
	die('its not defined');

use CustomException\ActiveCodeException;
use CustomException\ExpireAccountException;
use CustomException\UserResendException;
use \Util\UserInfo;

abstract class ActiveCode
{
	/**
	 * list of countries allowed for this code
	 * @var array
	 */
	protected  $_countyList = array();

	/**
	 * @var bool
	 */
	protected $forceUsePackage = false;

	/**
	 * @var array
	 */
	protected static $_userResponse = [
		'notRegister'   => 'your device is not register',
		'alreadyInUser' => 'this account is used by another device',
		're-validate'   => 'Please wait revalidating your account',
		'expire'        => 'this account is expire',
		'disable'       => 'your active code has been suspended',
		'fail'          => 'please try again later.',
		'notValid'      => 'your code is invalid',
		'country'       => 'active code is wrong',
	];

	/**
	 * @var int tinyint
	 */
	protected $_accountStatus = 0;

	/**
	 * @var int
	 */
	public static $resendExceptionCode= 1;

	/**
	 * @var int tinyint
	 */
	protected $_isExpire;

	/**
	 * @var string keyLogin
	 */
	protected $_keyLogin;

	/**
	 * @var int
	 */
	protected $_packageID;


    /**
     * @var int
     */
    protected $_packageChange = 1;


	/**
	 * @param UserInfo $userInfo
	 * @return mixed
	 */
	protected abstract function getDetail(UserInfo $userInfo);

	/**
	 * @param string $userCountry
	 * @throws ActiveCodeException
	 * @throws UserResendException
	 */
	public abstract function isAccountValidForRegister($userCountry);

	/**
	 * @return $this
	 * @throws ExpireAccountException
	 */
	protected function expireCheck()
	{

		if ($this->_isExpire == 1 ) {
			throw new ExpireAccountException('user account already expire', self::$_userResponse['expire'],VOD_ACCESS_SKIP);
		}
		if($this->getPeriod() <=  0 ) {
			$this->manualExpire();
			throw new ExpireAccountException('user account period is 0 but its not expire', self::$_userResponse['expire'], VOD_ACCESS_SKIP);
		}
		return $this;
	}

	/**
	 * @return $this
	 * @throws UserResendException
	 */
	protected abstract function accountUsageCheck();

	/**
	 * @return $this
	 * @throws ActiveCodeException
	 */
	protected function activeCodeStatusCheck()
	{
		if ($this->_accountStatus != 1) {
			throw new ActiveCodeException(self::$_userResponse['disable']);
		}
		return $this;
	}

	/**
	 * @return int
	 */
	public abstract function getPeriod();

	/**
	 * @return mixed
	 */
	protected  abstract function manualExpire();

	/**
	 * @param bool $encrypt
	 * @return string
	 */
	public abstract function getCodeID($encrypt = true);

	/**
	 * @return string
	 */
	public function expireOn()
	{
		return Date('Y-m-d  H:i:s', strtotime("+" .$this->getPeriod() ." days"));
	}

	/**
	 * @return int
	 */
	public function getPackageID()
	{
		return $this->_packageID;
	}

	/**
	 * @return bool
	 */
	public function isForceUsePackage()
	{
		return $this->forceUsePackage;
	}

	/**
	 * @param $userCountry
	 * @return $this
	 * @throws ActiveCodeException
	 */
	public  function countryAllowedCheck($userCountry)
	{
		if (empty($this->_countyList))
			return $this;
		// if (!in_array(strtoupper($userCountry), $this->_countyList))
		// 	throw new ActiveCodeException('user enter code is for different country ' .implode($this->_countyList, ','), self::$_userResponse['country']);



		$blockedCountry = [];
		$allowedCountry  = [];
		foreach ($this->_countyList as $key => $value) {
			if(strpos($value, '-') === 0 )  $blockedCountry []=ltrim($value, '-');
			else $allowedCountry[] = $value;
		}


		if (!empty($allowedCountry) && !in_array(strtoupper($userCountry), $allowedCountry))
			throw new ActiveCodeException('this country is blocked for this code' .implode($allowedCountry, ','), self::$_userResponse['country']);


		if (!empty($blockedCountry) && in_array(strtoupper($userCountry), $blockedCountry))
			throw new ActiveCodeException('user enter code is for different country ' .implode($blockedCountry , ','), self::$_userResponse['country']);
		

		return $this;
	}

	/**
     * @return int
     */
    public function getPackageChange()
    {
        return $this->_packageChange;
    }
}