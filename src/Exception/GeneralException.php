<?php

namespace CustomException;


class GeneralException extends \Exception implements MyException
{
	/**
	 * @var string
	 */
	private $_userMessage = '';

	/**
	 * GeneralException constructor.
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