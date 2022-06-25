<?php
/**
 * Created by PhpStorm.
 * User: hezha
 * Date: 10/29/18
 * Time: 2:55 AM
 */

namespace Account;

if(!defined('NOT_DIRECT_ACCESS'))
	die('its not defined');

use CustomException\LoginException;
use Lib\Database;
use Util\Encryption;

class BlockedUser
{
	/**
	 * @var bool
	 */
	private $_isBlocked = true;

	/**
	 * @var string
	 */
	private $_fullName;

	/**
	 * @var string
	 */
	private $_reason;

	/**
	 * @var string
	 */
	private $_date ;

	/**
	 * @var Database
	 */
	private $_db;

	/**
	 * BlockedUser constructor.
	 * @param Database $db
	 */
	public function __construct(Database $db )
	{
		$this->_db = $db;
	}

	/**
	 * @param $macAddress
	 * @throws LoginException
	 */
	public function blockValidation($macAddress) {
		$encryptedMacAddress =Encryption::encrypt($macAddress, Database::getEncryptionKey());
		$this->searchDatabase($encryptedMacAddress);
		if($this->isBlocked()) {
			throw new LoginException("device blocked by {$this->_fullName} on {$this->getDate()} {$this->_reason}",'your device is suspended');
		}
	}

	/**
	 * @param $encryptedMacAddress
	 */
	private function searchDatabase($encryptedMacAddress) {
		$query = 'SELECT  * FROM  block_mac WHERE mac_address = :mac_address';
		$result = $this->_db->select($query, array('mac_address' => $encryptedMacAddress));
		if(empty($result)) {
			$this->_isBlocked = false;
			return;
		}
		$this->setValue($result[0]);
	}

	/**
	 * @param array $dbInfo
	 */
	private function setValue(array $dbInfo)
	{
		$this->_isBlocked = true;
		$this->_fullName    = $dbInfo['full_name'];
		$this->_date        = $dbInfo['date'];
		$this->_reason      = $dbInfo['reason'];
	}

	/**
	 * @return bool
	 */
	public function isBlocked()
	{
		return $this->_isBlocked;
	}

	/**
	 * @return string
	 */
	public function getDate()
	{
		return date('Y-m-d', strtotime($this->_date));
	}
}