<?php

namespace Account;

if(!defined('NOT_DIRECT_ACCESS'))
	die('its not defined');

use CustomException\ActiveCodeException;
use CustomException\UserResendException;
use \Lib\Database;
use \Util\UserInfo;
use \Util\Encryption;

class UserCode extends ActiveCode
{
	/**
	 * @var string
	 */
	protected  $_codeID;

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
	 * @var \Lib\Database
	 */
	protected $_db;

	/**
	 * UserCode constructor.
	 * @param Database $db
	 * @param UserInfo $userInfo
	 * @throws ActiveCodeException
	 */
	public function __construct(Database $db,UserInfo $userInfo)
	{
		$this->_db = $db;
		$this->getDetail($userInfo);
	}

	/**
	 * @param UserInfo $userInfo
	 * @throws ActiveCodeException
	 */
	protected function getDetail(UserInfo $userInfo)
	{
		$dbResult = $this->getCodeDetailFromDB($userInfo);
		if(empty($dbResult)) {
			throw new ActiveCodeException(self::$_userResponse['notValid']);
		}
		$this->setValues($dbResult);
	}

	/**
	 * @param UserInfo $userInfo
	 * @return array
	 */
	private function getCodeDetailFromDB(UserInfo $userInfo)
	{
		$query ='SELECT c.*,cd.period,cd.country_allow,cd.force_package,cd.package_change_status FROM code_table c INNER JOIN create_date cd using(create_id) 
				  WHERE c.code_id = :code_id AND cd.application_id in ('.$userInfo->getApplicationIDStringList().') AND cd.active = 1 and confirm = 1';
		$encryptCodeID = Encryption::encrypt($userInfo->getCodeID(), Database::getEncryptionKey());
		$dbResult = $this->_db->select($query, array(
			'code_id'           => $encryptCodeID
		));
		return empty($dbResult) ? array() : array_shift($dbResult);

	}

	/**
	 * set values from db
	 * @param array $dbResult
	 */
	private function setValues(array $dbResult)
	{
		$this->_codeID          = $dbResult['code_id'];
		$this->_countyList      = $dbResult['country_allow'] ? explode(',', $dbResult['country_allow']) : array();
		$this->_previousPeriod  = $dbResult['previouse_period'];
		$this->_extraPeriod     = $dbResult['extra_period'];
		$this->_accountStatus   = $dbResult['active'];
		$this->_packageID       = $dbResult['package_id'];
		$this->_isExpire        = $dbResult['is_expire'];
		$this->_keyLogin        = $dbResult['key_login'];
		$this->_period          = $dbResult['period'];
		$this->forceUsePackage  = $dbResult['force_package']  == 1 ? true : false;
		$this->_packageChange   = $dbResult['package_change_status'];

	}

	/**
	 * @param string $userCountry
	 * @throws ActiveCodeException
	 * @throws UserResendException
	 * @throws \CustomException\ExpireAccountException
	 */
	public function isAccountValidForRegister($userCountry)
	{
		$this->expireCheck()
			->activeCodeStatusCheck()
			->countryAllowedCheck($userCountry)
			->accountUsageCheck();
	}

	/**
	 * @return $this
	 * @throws UserResendException
	 */
	protected   function accountUsageCheck()
	{
		if ($this->_keyLogin != '') {
			throw new UserResendException(self::$_userResponse['alreadyInUser'], '', self::$resendExceptionCode);
		}
		return $this;
	}


	/**
	 * expire account that have 0 or less  period
	 */
	protected function manualExpire()
	{
		$this->_db->update('code_table',array('is_expire' =>1), array('code_id' => $this->_codeID));
	}

	/**
	 * @return int
	 */
	public function getPeriod()
	{
		return $this->_period + abs($this->_extraPeriod) - abs($this->_previousPeriod);

	}

	/**
	 * @param bool $encrypt
	 * @return string
	 */
	public function getCodeID($encrypt = true)
	{
		if(!$encrypt)
			return Encryption::decrypt($this->_codeID, Database::getEncryptionKey());
		return $this->_codeID;
	}

	/**
	 * @return array
	 */
	public function toString()
	{
		return [
			'code_id'           => $this->getCodeID(),
			'code_id_decrypt'   => $this->getCodeID(false),
			'country_allow'     => $this->_countyList,
			'previouse_period'  => $this->_previousPeriod,
			'extra_period'      => $this->_extraPeriod,
			'active'            => $this->_accountStatus,
			'package_id'        => $this->_packageID,
			'is_expire'         => $this->_isExpire,
			'key_login'         => $this->_keyLogin,
			'period'            => $this->getPeriod(),
		];
	}
}