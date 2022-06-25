<?php

namespace Account;

if(!defined('NOT_DIRECT_ACCESS'))
	die('its not defined');

use CustomException\DeviceNotRegisterException;
use \Lib\Database;
use \CustomException\ActiveCodeException;
use \CustomException\UserResendException;
use \Util\UserInfo;
use \Util\Encryption;

class AutoCode extends ActiveCode
{

	/**
	 * @var string
	 */
	private $_macAddress;

	/**
	 * @var int
	 */
	protected  $_period;

	/**
	 * @var int
	 */
	protected  $_previousPeriod;

	/**
	 * @var int
	 */
	protected $_extraPeriod;

	/**
	 * @var string
	 */
	private $_isLoginAgain = '';

	/**
	 * @var \Lib\Database
	 */
	protected $_db;

	/**
	 * @var UserInfo
	 */
	private $_userInfo ;

	/**
	 * AutoCode constructor.
	 * @param Database $db
	 * @param UserInfo $userInfo
	 * @throws DeviceNotRegisterException
	 */
	public function __construct(Database $db,UserInfo $userInfo)
	{
		$this->_db = $db;
		$this->_userInfo = $userInfo;
		$this->getDetail($userInfo);
	}

	/**
	 * @param UserInfo $userInfo
	 * @return mixed|void
	 * @throws DeviceNotRegisterException
	 */
	protected function getDetail(UserInfo $userInfo)
	{
		$dbResult = $this->getCodeDetailFromDB($userInfo);
		if(empty($dbResult)) {
			throw new DeviceNotRegisterException(self::$_userResponse['notRegister']);
		}
		$this->setValues($dbResult);
	}

	/**
	 * @param UserInfo $userInfo
	 * @return array
	 */
	private function getCodeDetailFromDB(UserInfo $userInfo)
	{
		$query ='SELECT ac.*,acc.country_allow,acc.period,acc.package_id,acc.force_package FROM auto_code_mac_address ac INNER JOIN auto_code_mac_address_collaction acc using(collaction_id) 
				WHERE mac_address = :mac_address AND application_id =:application_id AND acc.available = 1 ';
		$encryptMacAddress = Encryption::encrypt($userInfo->getMacAddress(), Database::getEncryptionKey());


		$dbResult = $this->_db->select($query, array(
			'application_id' => $userInfo->getMainApplicationID(),
			'mac_address'    => $encryptMacAddress
		));
		return empty($dbResult) ? array() : array_shift($dbResult);
	}

	/**
	 * set values from db
	 * @param array $dbResult
	 */
	private function setValues(array $dbResult)
	{
		$this->setMacAddress($dbResult['mac_address']);
		$this->_countyList      = $dbResult['country_allow'] ? explode(',', $dbResult['country_allow']) : array();
		$this->_previousPeriod  = $dbResult['previouse_period'];
		$this->_extraPeriod     = $dbResult['extra_period'];
		$this->_packageID       = $dbResult['package_id'];
		$this->_accountStatus   = $dbResult['available'];
		$this->_isExpire        = $dbResult['is_expire'];
		$this->_keyLogin        = $dbResult['key_login'];
		$this->_isLoginAgain    = $dbResult['is_login_again'];
		$this->_period          = $dbResult['period'];
		$this->forceUsePackage  = $dbResult['force_package']  == 1 ? true : false;
	}

	/**
	 * @param string $userCountry
	 * @throws ActiveCodeException
	 * @throws UserResendException
	 * @throws \CustomException\ExpireAccountException
	 */
	public function isAccountValidForRegister($userCountry)
	{
		$this->activeCodeStatusCheck()
			->isUserHaveAnotherAccount()
			->countryAllowedCheck($userCountry)
			->expireCheck()
			->accountUsageCheck();
	}

	/**
	 * @return $this
	 * @throws UserResendException
	 */
	private function isUserHaveAnotherAccount()
	{
		if ($this->_isLoginAgain != '') {
			throw new UserResendException(self::$_userResponse['re-validate'], '',self::$resendExceptionCode);
		}
		return $this;

	}

	/**
	 * @return $this
	 * @throws UserResendException
	 */
	protected function accountUsageCheck()
	{
		if ($this->_keyLogin != '') {
			throw new UserResendException(self::$_userResponse['alreadyInUser'], '',self::$resendExceptionCode);
		}
		return $this;
	}

	/**
	 * expire account that have 0 or less  period
	 */
	protected function manualExpire()
	{
		$this->_db->update('auto_code_mac_address',array('is_expire' =>1), array('mac_address' => $this->getMacAddress()));
	}

	/**
	 * @return int
	 */
	public function getPeriod()
	{
		$period = $this->_period;
		if ($this->getUserInfo()->getCountry()  == 'MA')
			$period = 365;
		return $period + abs($this->_extraPeriod) - abs($this->_previousPeriod);

	}

	/**
	 * @param bool $encrypt
	 * @return string
	 */
	public function getCodeID($encrypt = true)
	{
		$autoCode = 'auto_code_' . $this->getMacAddress(false);
		if(!$encrypt)
			return $autoCode;

		return Encryption::encrypt($autoCode, Database::getEncryptionKey());
	}

	/**
	 * @param bool $encrypt
	 * @return string
	 */
	public function getMacAddress($encrypt = true)
	{
		if(!$encrypt)
			return Encryption::decrypt($this->_macAddress, Database::getEncryptionKey());
		return $this->_macAddress;
	}

	/**
	 * @param $macAddress
	 * @param bool $encrypt
	 */
	private function setMacAddress($macAddress, $encrypt = true)
	{
		if(!$encrypt) {
			$this->_macAddress = Encryption::encrypt($macAddress, Database::getEncryptionKey());
			return;
		}
		$this->_macAddress = $macAddress;
	}

	/**
	 * @return array
	 */
	public function toString()
	{
		return [
			'mac_address'       => $this->getMacAddress(),
			'mac_address_dec'   => $this->getMacAddress(false),
			'previous_period'   => $this->_previousPeriod,
			'extra_period'      => $this->_extraPeriod,
			'package_id'        => $this->_packageID,
			'is_expire'         => $this->_isExpire,
			'key_login'         => $this->_keyLogin,
			'is_login_again'    => $this->_isLoginAgain,
			'period'            => $this->getPeriod(),
			'force_package'     => $this->isForceUsePackage() ? 'true' : 'false'
		];
	}

	/**
	 * @return UserInfo
	 */
	public function getUserInfo()
	{
		return $this->_userInfo;
	}
}
