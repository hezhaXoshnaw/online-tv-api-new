<?php

namespace CustomException;


class RequestAPIException extends  \Exception implements MyException
{
	/**
	 * @var string
	 */
	private $_userMessage = '';

	/**
	 * RequestAPIException constructor.
	 * @param string $message
	 * @param string $messageForUser
	 * @param int $code
	 *           1 mean delete the vod
	 *           other values mean skip it
	 * @param \Exception|null $previous
	 */
	public function __construct($message, $messageForUser = '', $code = VOD_ACCESS_DELETE, \Exception $previous = null)
	{
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
