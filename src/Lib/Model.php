<?php

namespace Lib;

if(!defined('NOT_DIRECT_ACCESS'))
	die('its not defined');


use CustomException\LoginException;
use CustomException\ModelNotRegisterException;

class Model
{
	/**
	 * @var string
	 */
	private $_lastVersion;

	/**
	 * @var bool
	 */
	private $_versionCheck = false;

	/**
	 * @var array
	 */
	private $_versionSupport = [];

	/**
	 * @var \Lib\Database
	 */
	private $_db;

	/**
	 * @var \Lib\MyRedis
	 */
	private $_redis;

	/**
	 * @var int tinyint
	 */
	private $_hashValidation;
	/**
	 * @var string
	 */
	private $_modelName;

	/**
	 * @var string
	 */
	private $_modelID;

	/**
	 * @var int
	 */
	private $_registerStatus;

	/**
	 * @var int
	 */
	private $_loginStatus;

	/**
	 * @var string
	 */
	private $_deactivateMessage = '';

	/**

	/**
	 * Model constructor.
	 * @param Database $db
	 * @param \Redis $redis
	 * @param string $modelName
	 */
	public function __construct(Database $db, \Redis $redis, $modelName)
	{
		$this->_db = $db;
		$this->_redis = $redis;
		$this->_modelName = $modelName;
	}

	/**
	 * @throws ModelNotRegisterException
	 */
	public function setModelByName() {

		if($this->getModelName() == '') throw new ModelNotRegisterException('there is no model in the request','fail please try again latter');

		if(!$model = $this->loadModel())  throw new ModelNotRegisterException('invalid Model', 'fail please try again latter');

		$this->setValues($model);
	}

	/**
	 * @return string
	 */
	public function getRedisKey()
	{
		return 'model';
	}

	/**
	 * @return array|bool
	 */
	private function loadModel( )
	{
		if ($modelInfo = $this->loadFromRedis()) return $modelInfo;

		$modelInfo = $this->getModelFromDB($this->getModelName());
		if (empty($modelInfo)) return false;

		$this->addModelToRedis($modelInfo);
		return $modelInfo;
	}

	/**
	 * @return bool|array
	 */
	private function loadFromRedis() {
		$model = $this->_redis->hget($this->getRedisKey(), $this->getModelName());
		if(!$model) return false;
		return json_decode($model, true);
	}

	/**
	 * @param array $model
	 */
	private function addModelToRedis(array $model)
	{
		$modelJson = json_encode($model);
		$this->_redis->hset($this->getRedisKey(), $this->getModelName(), $modelJson);
	}

	/**
	 * @param array $values
	 */
	private function setValues(array $values)
	{
		$this->_modelName        = $values['model_name'];
		$this->_modelID          = $values['model_id'];
		$this->_registerStatus   = $values['model_active_register'];
		$this->_loginStatus      = $values['model_active_login'];
		$this->_deactivateMessage= $values['deactivate_message'];
		$this->_hashValidation   = $values['skip_hash_validation'];
		$this->_lastVersion      = $values['last_version'];
		$this->_versionCheck     = $values['version_check']  == 1 ? true : false;
		$versions                = explode(',', $values['version_support']);
		$this->_versionSupport   = array_values($versions);
	}

	/**
	 * @param string $modelName
	 * @return array
	 */
	private function getModelFromDB($modelName) {
		$query = 'select * from model where model_name=:model_name';
		$result =  $this->_db->select($query,['model_name' => $modelName]);
		return empty($result ) ? array() :array_shift($result );
	}

	/**
	 * @return string
	 */
	public function getName() {
		return $this->_modelName;
	}

	/**
	 * @return string
	 */
	public function getID() {
		return $this->_modelID;
	}

	/**
	 * @return bool
	 */
	public function isValidForLogin() {
		return $this->_loginStatus != 0;
	}

	/**
	 * @return bool
	 * @throws LoginException
	 */
	public function isValidForRegister() {
		return $this->_registerStatus != 0;
	}

	/**
	 * @return string
	 */
	public function getDeactivateMessage() {
		return $this->_deactivateMessage;
	}

	/**
	 * @return bool
	 */
	public function skipValidate()
	{
		return $this->_hashValidation == 1;
	}

	/**
	 * @return bool
	 */
	public function needVersionCheck()
	{
		return $this->_versionCheck;
	}

	/**
	 * @return array
	 */
	public function getVersionSupport()
	{
		return $this->_versionSupport;
	}

	/**
	 * @return string
	 */
	public function getLastVersion()
	{
		return $this->_lastVersion;
	}

	/**
	 * @return string
	 */
	public function getModelName()
	{
		return $this->_modelName;
	}

	/**
	 * @param bool $versionCheck
	 */
	public function setVersionCheck($versionCheck)
	{
		$this->_versionCheck = $versionCheck;
	}

}