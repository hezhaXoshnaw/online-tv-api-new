<?php

namespace CustomException;

if(!defined('NOT_DIRECT_ACCESS'))
	die('its not defined');


class UserResendException extends \Exception implements MyException
{
	/**
	 * @var string
	 */
	private $_userMessage = '';

	/**
	 * UserResendException constructor.
	 * @param string $message
	 * @param string $messageForUser
	 * @param int $code 0 userInfo class
	 *                  1 code_table update second key login and count ,
	 *                  2 user_access update second key login and count ,
	 * @param \Exception|null $previous
	 */
	public function __construct($message, $messageForUser = '', $code = 0, \Exception $previous = null) {
		$this->_userMessage = $messageForUser;
		parent::__construct($message, $code, $previous);
	}

	/**
	 * @return string
	 */
	public function getMessageForUser()
	{
		return $this->_userMessage == '' ? $this->getMessage() : $this->_userMessage;
	}
}

