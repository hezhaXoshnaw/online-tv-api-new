<?php

namespace Account;

if(!defined('NOT_DIRECT_ACCESS'))
	die('its not defined');

use CustomException\GeneralException;
use Lib\Database;
use Lib\Log;
use Util\Encryption;
use Util\Functions;
use Util\UserInfo;

abstract class User
{
	/**
	 * @var int
	 */
	protected  $_packageID;

	/**
	 * @var bool
	 */
	protected $_tokenIPSkip = false;

	/**
	 * @var \Util\UserInfo
	 */
	private $_userInfo ;

	/**
	 * @var string
	 */
	protected $_ipAddress = '';

	/**
	 * @var string 2 char letter code country
	 */
	protected $_country = '';
	/**
	 * @var int
	 */
	protected $_isAuto = 0;
	/**
	 * @var int
	 */
	protected $_userID = 0;

	/**
	 * @var array
	 */
	protected static $_userResponse = [
		'reset'   => 'this code been reseted by your agent',
		'alreadyInUse'    => 'this account already in use',
		'macNotSame' => 'this account is used by another device',
		're-validate'   => 'Please wait revalidating your account',
		'expire'        => 'this account is expire',
		'suspend'       => 'your account has been suspended',
		'fail'          => 'please try again later.',
		'loginCount'    => 'your account has been suspended temporary'
	];
	/**
	 * @var array
	 */
	protected $_updateParams = array();
	/**
	 * @var string
	 */
	protected $_codeID;

	/**
	 * @var string
	 */
	protected $_macAddress;

	/**
	 * @var int
	 */
	protected $_keyLoginChangeTime = 0;

	/**
	 * @var int
	 */
	protected $_keyLoginChangeValue = 0;

	/**
	 * @var string
	 */
	private $_keyLogin = '';

	/**
	 * @var int
	 */
	private $_secondKeyLogin = 0;

	/**
	 * @var Database
	 */
	protected $_db;

	/**
	 * User constructor.
	 * @param Database $db
	 * @param UserInfo $userInfo
	 */
	protected function __construct(Database $db = null,UserInfo $userInfo = null)
	{
		$this->setUserInfo($userInfo);
		$this->_db = $db;
	}

	/**
	 * if there is no argument mean its for new key login
	 * @param string $keyLogin
	 * @param bool $isEncrypt
	 */
	public function setKeyLogin($keyLogin = '', $isEncrypt = false)
	{
		Log::setDebugLogSteps('set new new key login', $this->getUserInfo()->isDebugOutputModeOn());
		if ($keyLogin == '') {
			$this->_keyLogin = uniqid() ;
			return;
		}
		if ($isEncrypt) {
			$this->_keyLogin = Encryption::decrypt($keyLogin, Database::getEncryptionKey()) ;
			return;
		}

		$this->_keyLogin = $keyLogin;
	}

	/**
	 * @param bool $needCount
	 * @return $this
	 * @throws GeneralException
	 */
	public function newSecondKeyLogin($needCount = false)
	{

		$this->setSecondKeyLogin();
		$this->_updateParams['second_key_login']  = $this->getSecondKeyLogin();
		Log::setDebugLogSteps('set new new second key login' . ($this->getSecondKeyLogin()), $this->getUserInfo()->isDebugOutputModeOn());
		if($needCount){
			Log::setDebugLogSteps('change counter for second key login change time' , $this->getUserInfo()->isDebugOutputModeOn());
			$this->setKeyLoginChange();
		}

		return $this;
	}

	/**
	 * update the values if its in same day if its not increment
	 */
	private function setKeyLoginChange()
	{
		if ($this->_keyLoginChangeTime != 0
			&& Functions::getTodayDate() == Functions::getTodayDateByTimeStamp($this->_keyLoginChangeTime)) {
			$this->_updateParams['key_login_change_value'] = $this->getKeyLoginChangeValue() + 1;
			return;
		}
		$this->_updateParams['key_login_change_time'] = Functions::getCurrentTimeStamp();
		$this->_updateParams['key_login_change_value'] = 1;


		 if (Functions::getTodayDate() == Functions::getTodayDateByTimeStamp($this->getSecondKeyLogin())) {
			$this->_updateParams['key_login_change_value'] = $this->_keyLoginChangeValue + 1;
			return;
		}

		$this->_updateParams['key_login_change_value'] = 1;
	}

	/**
	 * @param bool $encrypt
	 * @return bool|string
	 * @throws GeneralException
	 */
	public function getKeyLogin($encrypt = true)
	{
		if($this->_keyLogin == '' || $this->_keyLogin == false) {
			throw new GeneralException("tried to get key login and its {$this->_keyLogin}" , self::$_userResponse['fail']);
		}
		if (!$encrypt)
			return $this->_keyLogin;

		return Encryption::encrypt($this->_keyLogin, Database::getEncryptionKey());
	}

	/**
	 * @param int $secondKeyLogin
	 */
	public function setSecondKeyLogin($secondKeyLogin = 0)
	{
		$this->_secondKeyLogin = $secondKeyLogin == 0 ? Functions::getCurrentTimeStamp() : $secondKeyLogin;
	}

	/**
	 * @return int
	 * @throws GeneralException
	 */
	public function getSecondKeyLogin()
	{
		if($this->_secondKeyLogin == 0 ) {
			throw new GeneralException('tried to get second key and its 0', self::$_userResponse['fail']);
		}
		return $this->_secondKeyLogin;
	}

	/**
	 * @return string
	 * @throws GeneralException
	 */
	public function getFullKeyLoginForUser()
	{
		return $this->getKeyLogin(false) . '-' . $this->getSecondKeyLogin();
	}

	/**
	 * @param bool $encrypt
	 * @return bool|string
	 */
	public function getCodeID($encrypt = true)
	{
		if ($encrypt)
			return $this->_codeID;
		return substr(Encryption::decrypt($this->_codeID, Database::getEncryptionKey()),0,10);
	}

	/**
	 * @param bool $encrypt
	 * @return bool|string
	 */
	public function getMacAddress($encrypt = true)
	{
		if ($encrypt)
			return $this->_macAddress;
		return Encryption::decrypt($this->_macAddress, Database::getEncryptionKey());
	}

	/**
	 * @throws GeneralException
	 */
	public function updateChanges()
	{
		if(!empty($this->_updateParams)){
			Log::setDebugLogSteps('update changes are ' . json_encode($this->_updateParams), $this->getUserInfo()->isDebugOutputModeOn());
			$this->_db->update('user_table' , $this->_updateParams, array('key_login' => $this->getKeyLogin()));
		}


		return $this;
	}

	/**
	 * @param $macAddress
	 * @param bool $isEncrypt
	 */
	protected function setMacAddress($macAddress, $isEncrypt = false)
	{
		if (!$isEncrypt){
			$this->_macAddress = Encryption::encrypt($macAddress, Database::getEncryptionKey());
			return;
		}
		$this->_macAddress = $macAddress;
	}

	/**
	 * @return UserInfo
	 */
	public function getUserInfo()
	{
		return $this->_userInfo;
	}

	/**
	 * @return int
	 */
	public function getKeyLoginChangeTime()
	{
		return $this->_keyLoginChangeTime;
	}

	/**
	 * @return int
	 */
	public function getKeyLoginChangeValue()
	{
		return $this->_keyLoginChangeValue;
	}

	/**
	 * @return bool
	 */
	public function isTokenIPSkip()
	{
		return $this->_tokenIPSkip;
	}

	/**
	 * @param $userInfo
	 */
	public function setUserInfo($userInfo)
	{
		$this->_userInfo = $userInfo;
	}

	/**
	 * @param $macAddress
	 * @return string
	 */
	public function getUserInfoFromDB($macAddress)
	{
		$encryptMacAddress = Encryption::encrypt($macAddress, Database::getEncryptionKey());
		$userInfo = $this->_db->select('select key_login,second_key_login from user_table where mac_address=:mac_address',['mac_address' => $encryptMacAddress]);
		if(empty($userInfo))
			return '';

		$data = array_shift($userInfo);
		return Encryption::decrypt($data['key_login'],Database::getEncryptionKey()) . '-' . $data['second_key_login'];
	}


	/**
	 * @return int
	 */
	public function getPackageID()
	{
		return $this->getUserInfo()->getApplicationPackageID() == 0
			? $this->_packageID
			: $this->getUserInfo()->getApplicationPackageID();
	}



	/**
	 * @param int $packageID
	 */
	public function setPackageID($packageID)
	{
		$this->_packageID = $packageID;
	}

}