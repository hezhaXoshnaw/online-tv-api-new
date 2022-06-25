<?php
/**
 * Created by Hezha Xoshnaw
 * User: Hezha
 * Date: 10/30/18
 * Time: 5:43 AM
 * this class is for apps that is for free small channel list
 * nothing here is dynamic and check in DB
 */

namespace Account;

if(!defined('NOT_DIRECT_ACCESS'))
	die('its not defined');

use Lib\Database;
use Util\Functions;
use Util\UserInfo;

class UserAccessFree  extends UserAccess
{
	/**
		/**
	 * UserAccessFree constructor.
	 * @param UserInfo $userInfo
	 */
	public function __construct(UserInfo $userInfo)
	{
		parent::__construct(null, $userInfo);
	}
	
	/**
	 * @param bool $fullInfo
	 * @return string
	 */
	public function getLoginDate($fullInfo = false)
	{
		return 'unKnown';
	}

	/**
	 * @param bool $fullInfo
	 * @return string
	 */
	public function getExpireDate($fullInfo = false) {
		return 'unKnown';
	}

	/**
	 * @return int
	 */
	public function getPackageID()
	{
		return $this->getUserInfo()->getFreeLoginPackageID();
	}

	/**
	 * @return string
	 */
	public function getFullKeyLoginForUser()
	{
		return $this->getUserInfo()->getFreeKeyLogin() . '-' . Functions::getCurrentTimeStamp();
	}



}