<?php
/**
 * Created by PhpStorm.
 * User: hezha
 * Date: 11/4/18
 * Time: 11:20 AM
 */

namespace CustomException;

if(!defined('NOT_DIRECT_ACCESS'))
	die('its not defined');


class ModelNotRegisterException extends \Exception implements MyException
{
	/**
	 * @var string
	 */
	private $_userMessage = '';

	/**
	 * ActiveCodeException constructor.
	 * @param string $message
	 * @param string $messageForUser
	 * @param int $code
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