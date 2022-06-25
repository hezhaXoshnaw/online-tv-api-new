<?php
/**
 * Created by PhpStorm.
 * User: hezha
 * Date: 5/7/19
 * Time: 12:35 PM
 */

namespace Lib;


use CustomException\DomainBlockedException;
use Util\UserInfo;

class DomainManager
{

	private $_blockedDomain = [

	];
	/**
	'*'  => [
		'o3.validateapihost.com'
	],
	'IQ' => [
		'www.user-api-validator.xyz'
	]
	 */

	/**
	 * @var UserInfo
	 */
	private $_userInfo;

	/**
	 * DomainManager constructor.
	 * @param UserInfo $userInfo
	 */
	public function __construct(UserInfo $userInfo)
	{
		$this->_userInfo = $userInfo;
	}

	/**
	 * @throws DomainBlockedException
	 */
	public function validate()
	{
		Log::setDebugLogSteps('validating user blocked domain', $this->_userInfo->isDebugOutputModeOn());
		$this->blockedDomainCheck();
	}

	/**
	 * @return $this
	 * @throws DomainBlockedException
	 */
	private function blockedDomainCheck()
	{
		$userCountry = $this->_userInfo->getCountry();
		$blockedDomain =$this->loadBlockedDomain($userCountry );
//		echo $this->_userInfo->getUserDomainName();
//		print_r($blockedDomain);
		if(in_array($this->_userInfo->getUserDomainName(),$blockedDomain))
			throw new DomainBlockedException(implode(',',$blockedDomain) . 'blocked domain' , 'fail please try again latter');
		return $this;
	}

	/**
	 * @param $userCountry string
	 * @return array
	 */
	private function loadBlockedDomain($userCountry )
	{
		$wildCardList = isset($this->_blockedDomain['*']) ? $this->_blockedDomain['*'] : [];
		$countryCheck = isset($this->_blockedDomain[$userCountry ]) ? $this->_blockedDomain[$userCountry ] : [];
		return array_merge($wildCardList, $countryCheck);
	}


}