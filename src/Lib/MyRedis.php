<?php

namespace Lib;

if(!defined('NOT_DIRECT_ACCESS'))
	die('its not defined');

use Redis;

class MyRedis
{

	/**
	 * @var \Redis
	 */
	static $instance = null;

	/**
	 * @return Redis
	 */
	public static function getInstance()
	{
		if ( is_null(self::$instance) ) {
			self::$instance = new Redis();
			self::$instance->connect(REDIS_HOST, REDIS_PORT);
		}
		return self::$instance;
	}

}