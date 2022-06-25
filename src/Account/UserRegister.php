<?php

namespace Account;

if(!defined('NOT_DIRECT_ACCESS'))
	die('its not defined');


use CustomException\GeneralException;
use CustomException\UpdateAccountException;
use Lib\Database;
use Lib\Log;
use Util\Encryption;
use Util\Functions;
use Util\UserInfo;

class UserRegister extends User
{
	/**
	 * @var string
	 */
	private $_expireDate;

	/**
	 * @var string
	 */
	private $_dateLogin;

	/**
	 * UserRegister constructor.
	 * @param Database $db
	 * @param UserInfo $userInfo
	 */
	public function __construct(Database $db, UserInfo $userInfo)
	{
		parent::__construct($db, $userInfo);
	}

	/**
	 * @param ActiveCode $activeCode
	 * @throws GeneralException
	 * @throws \CustomException\ActiveCodeException
	 * @throws \CustomException\LoginException
	 * @throws \CustomException\UserResendException
	 */
	public function newUser(ActiveCode $activeCode)
	{
		$this->getUserInfo()->getModel()->isValidForRegister();

		$activeCode->isAccountValidForRegister($this->getUserInfo()->getCountry());
		Log::setDebugLogSteps('setting keys ' , $this->getUserInfo()->isDebugOutputModeOn());
		$this->setKeyLogin();
		$this->setSecondKeyLogin();
		$this->_expireDate = $activeCode->expireOn();
		$this->_dateLogin  = Functions::getTodayDateTime();
		$this->setMacAddress($this->getUserInfo()->getMacAddress());
		Log::setDebugLogSteps('getting data for register' , $this->getUserInfo()->isDebugOutputModeOn());
		$registrationInfo = $this->getDataForRegister($activeCode);

		if (!$this->_db->insert('user_table',$registrationInfo)) {
			$messageForLog ='could not insert to user table ' ;
			throw new GeneralException($messageForLog, self::$_userResponse['fail']);
		}
		Log::setDebugLogSteps('registration finish' , $this->getUserInfo()->isDebugOutputModeOn());
		if ($this->getUserInfo()->isNeedRegister())
		{
			Log::setDebugLogSteps('registering auto code' , $this->getUserInfo()->isDebugOutputModeOn());
			$data   =   [
				'mac_address'   => $this->getMacAddress(),
				'serial_number' => 'NONE',
				'collaction_id' => $this->getUserInfo()->getCollectionID(),
				'available'     => 1,
				'key_login'     => '',
				'is_expire'     => 1,
				'is_login_again'=> $this->getKeyLogin(),
				'previouse_period'=> 0,
				'user_date_login'=> Functions::getTodayDate(),
				'extra_period'  =>  0,
			];
			$this->_db->insert('auto_code_mac_address',$data);
		}
		Log::todayNewUserLog($this->getUserInfo(), 'period ' . $activeCode->getPeriod() , $this->getSecondKeyLogin());


	}

	/**
	 * @param ActiveCode $activeCode
	 * @return array
	 * @throws GeneralException
	 */
	private function getDataForRegister(ActiveCode $activeCode)
	{
		return [
			'key_login'     => $this->getKeyLogin(),
			'mac_address'   => $this->_macAddress,
			'code_id'       => $activeCode->getCodeID(),
			'date_login'    => $this->_dateLogin,
			'expire_date'   => $this->_expireDate,
			'is_auto'       => ($this->getUserInfo()->isAuto() ? 1 : 0),
			'package_id'    => $this->getUserInfo()->isNewUserScramble() || $activeCode->isForceUsePackage() || !$this->getUserInfo()->isAuto()
								? $activeCode->getPackageID()
								: $this->getUserInfo()->getNoScrambleChannelList(),
			'user_active'   => 1,
			'ip_address'    => $this->getUserInfo()->getIPAddress(),
			'country'       => $this->getUserInfo()->getCountry(),
			'region'        => $this->getUserInfo()->getRegion(),
			'vod_access'    => 0,
			'second_key_login'=> $this->getSecondKeyLogin(),
			'model_id'      => $this->getUserInfo()->getModel()->getID(),
            'package_change'=> $activeCode->getPackageChange()
		];
	}

	/**
	 * @param UserInfo $userInfo
	 * @throws GeneralException
	 */
	public function setDataForResendException(UserInfo $userInfo)
	{
		$result = $this->getUserDataFromDatabaseByMacAddress($userInfo->getMacAddress());
		if (empty($result)){
			$messageForLog = 'user send code but code working on another device ';
			throw new GeneralException($messageForLog , self::$_userResponse['alreadyInUse']);
		}


		$this->setKeyLogin($result['key_login'],true);
		$this->_keyLoginChangeTime = $result['key_login_change_time'];
		$this->_keyLoginChangeValue = $result['key_login_change_value'];
		$this->newSecondKeyLogin(true)->updateChanges();
		$this->_codeID = $result['code_id'];
		$this->setMacAddress($result['mac_address'],true);
	}

	/**
	 * @param $macAddress
	 * @return array
	 */
	private function getUserDataFromDatabaseByMacAddress($macAddress)
	{
		$encryptedMacAddress = Encryption::encrypt($macAddress, Database::getEncryptionKey());
		$query = 'SELECT * FROM user_table WHERE mac_address=:mac_address ORDER BY user_id DESC LIMIT 1';
		$dbResult = $this->_db->select($query, array('mac_address' => $encryptedMacAddress));
		return empty($dbResult) ? array() : array_shift($dbResult);
	}
	/**
	 * @return string
	 */
	public function getExpireDate()
	{
		return date('Y-m-d', strtotime($this->_expireDate));
	}

	/**
	 * @return string
	 */
	public function getDateLogin()
	{
		return date('Y-m-d', strtotime($this->_dateLogin));
	}

	/**
	 * @param ActiveCode $activeCode
	 * @throws UpdateAccountException
	 * @throws \CustomException\AccessLoginException
	 * @throws \CustomException\ActiveCodeException
	 * @throws \CustomException\UserResendException
	 */
	public function updateAccount(ActiveCode $activeCode)
	{
		
		// try
		// {
		// 	if($this->getUserInfo()->isAuto())
		// 		throw new GeneralException('user try to update with auto code '.$this->getUserInfo()->getCodeID(),'update fail');
		// 	$activeCode->isAccountValidForRegister($this->getUserInfo()->getCountry());
		// 	$keyLogin = $this->getUserInfo()->getKeyLogin();
		// 	$secondKeyLogin = $this->getUserInfo()->getSecondKeyLogin();

		// 	if($keyLogin == '' || $secondKeyLogin == '')
		// 		throw new GeneralException('there is no key login for update account','update fail');

		// 	$this->setKeyLogin($keyLogin);
		// 	$this->setSecondKeyLogin($secondKeyLogin);

		// 	$user = new UserAccess($this->_db,$this->getUserInfo());
		// 	$user->setUserDataFromDB($this->getKeyLogin());
		// 	$user->expireCheck();
		// 	$this->setMacAddress($this->getUserInfo()->getMacAddress());

		// 	$today = Functions::getTodayDate();
		// 	$this->_dateLogin   =   $today;
		// 	$from=date_create(Date('Y-m-d',strtotime($today)));
		// 	$to=date_create(Date('Y-m-d',strtotime($user->getExpireDate())));
		// 	$diff=date_diff($to,$from);

		// 	$this->_expireDate =Date('Y-m-d H:i:s', strtotime('+' . ( $activeCode->getPeriod() + ($diff->days) ) . ' days'));
		// 	$data = $this->getDataForRegister($activeCode);

		// 	if (!$this->_db->insert('user_table', $data)) {
		// 		throw new GeneralException('could not add to user table for update account',self::$_userResponse['fail']);
		// 	}
		// }
		// catch (GeneralException $exception) {
		// 	throw new UpdateAccountException($exception->getMessage(),$exception->getMessageForUser());
		// }
		try
		{
			if($this->getUserInfo()->isAuto())
				throw new GeneralException('user try to update with auto code '.$this->getUserInfo()->getCodeID(),'update fail');

			$activeCode->isAccountValidForRegister($this->getUserInfo()->getCountry());

			$keyLogin = $this->getUserInfo()->getKeyLogin();
			$secondKeyLogin = $this->getUserInfo()->getSecondKeyLogin();

			if($keyLogin == '' || $secondKeyLogin == '')
				throw new GeneralException('there is no key login for update account','update fail');

			try
			{
				$this->setKeyLogin($keyLogin);
				$this->setSecondKeyLogin($secondKeyLogin);


				$user = new UserAccess($this->_db,$this->getUserInfo());

				$user->setUserDataFromDB($this->getKeyLogin());
				$user->expireCheck();
				$this->setMacAddress($this->getUserInfo()->getMacAddress());

				$today = Functions::getTodayDate();
				$this->_dateLogin   =   $today;
				$from=date_create(Date('Y-m-d',strtotime($today)));
				$to=date_create(Date('Y-m-d',strtotime($user->getExpireDate())));
				$diff=date_diff($to,$from);

				$this->_expireDate =Date('Y-m-d H:i:s', strtotime('+' . ( $activeCode->getPeriod() + ($diff->days) ) . ' days'));

			}catch (\Exception $exception)
			{
				$this->setKeyLogin();
				$this->setSecondKeyLogin();
				$today = Functions::getTodayDate();
				$this->_dateLogin   =  $today;
				$this->_expireDate = $activeCode->expireOn();
				$this->_dateLogin  = Functions::getTodayDateTime();
				$this->setMacAddress($this->getUserInfo()->getMacAddress());
				if ($this->getUserInfo()->isNeedRegister())
                {
                    Log::setDebugLogSteps('registering auto code' , $this->getUserInfo()->isDebugOutputModeOn());
                    $data   =   [
                        'mac_address'   => $this->getMacAddress(),
                        'serial_number' => 'NONE',
                        'collaction_id' => $this->getUserInfo()->getCollectionID(),
                        'available'     => 1,
                        'key_login'     => '',
                        'is_expire'     => 1,
                        'is_login_again'=> $this->getKeyLogin(),
                        'previouse_period'=> 0,
                        'user_date_login'=> Functions::getTodayDate(),
                        'extra_period'  =>  0,
                    ];
                    $this->_db->insert('auto_code_mac_address',$data);
                }
			}
			$data = $this->getDataForRegister($activeCode);

			if (!$this->_db->insert('user_table', $data)) {
				throw new GeneralException('could not add to user table for update account',self::$_userResponse['fail']);
			}
			Log::todayNewUserLog($this->getUserInfo(), 'period ' . $activeCode->getPeriod() , $this->getSecondKeyLogin());
		}
		catch (GeneralException $exception) {
			throw new UpdateAccountException($exception->getMessage(),$exception->getMessageForUser());
		}
	}
}