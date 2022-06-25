<?php
/**
 * Created by PhpStorm.
 * User: hezha
 * Date: 6/14/19
 * Time: 9:28 AM
 */

namespace Util;


use Account\User;
use CustomException\UnknownCountryException;
use GeoIp2\Database\Reader;
use Lib\Database;

class Country
{

	/**
	 * @var array
	 */
	private $_config = [];

	/**
	 * @var string
	 */
	private $_country;

	private $_isp;

	/**
	 * @var string
	 */
	private $_continent;
	/**
	 * @var string
	 */
	private $_ipAddress;


	/**
	 * Country constructor.
	 * @param $ipAddress
	 */
	public function __construct($ipAddress)
	{
		$this->setIpAddress($ipAddress);
		$this->setCountry();
	}

	/**
	 * @param string $ipAddress
	 */
	public function setIpAddress($ipAddress)
	{
		$ipList         =   explode(',',$ipAddress);
		if(isset($ipList[1]) && !empty($ipList[1]))
			$this->_ipAddress = trim($ipList[1]);
		else
			$this->_ipAddress = trim($ipList[0]);
	}


	private function setCountry()
    {
        try
        {
            $reader = new Reader(GEO_IP_FILE);
            $record = $reader->country($this->_ipAddress);
            $this->_country = $record->country->isoCode;
            $this->_continent = $record->continent->code;
        }
        catch (\Exception $exception)
        {
            $this->_country = '00';
            $this->_continent = 'NA';
        }

        try
        {
            $ispReader = new Reader(GEO_IP_ISP_FILE);
            $ispRecord = $ispReader->isp($this->_ipAddress);
            $this->_isp = $ispRecord->isp;
        }
        catch (\Exception $exception)
        {
            $this->_isp= '00';
        }
	}

	/**
	 * @return string
	 */
	public function getIpAddress()
	{
		return $this->_ipAddress;
	}

	/**
	 * @return string
	 */
	private function getRedisKey()
	{
		return 'country_config_v2';
	}

	/**
	 * load config from redis if empty load from db and if there is no info add empty json to redis
	 * @param Database $db
	 * @param \Redis $redis
	 */
	public function loadConfig(Database $db, \Redis $redis)
	{
		if(strlen($this->getCountry()) != 2)
			return;

		$config  = $redis->hGet($this->getRedisKey(), $this->getCountry());
		if(!$config )
		{
			$query = 'select * from country_config where api_id = 2 and country_code =:country_code';
			$config = $db->select($query, ['country_code' => $this->getCountry()]);
			if(empty($config)){
				$config = "{}";
			}
			else {
				$config = $config[0]['config'];
			}
			$redis->hSetNx($this->getRedisKey() ,$this->getCountry(), $config );
		}
		$this->_config= json_decode($config , true);
	}

	/**
	 * @param $ipAddress
	 * @return array
	 */
	public static function getCountryDetail($ipAddress) {
		$country = geoip_record_by_name  ($ipAddress);
		return $country;
	}

	/**
	 * @return bool|int
	 */
	public function getPackageID()
	{

		if(isset($this->_config['package_id']) && $this->_config['package_id'] > 0){
			return $this->_config['package_id'];
		}
		return false;
	}

	/**
	 * @param $model
	 * @return bool|int
	 */
	public function getCollection($model)
	{
		if (!isset($this->_config['auto_register']) || empty($this->_config['auto_register'])) return false;
		$collectionData = $this->_config['auto_register'];
		if (isset($collectionData [$model]) && !empty($collectionData [$model])) return $collectionData [$model];
		return false;
	}


	/**
	 * @return string
	 */
	public function getCountry()
	{
		return $this->_country;
	}

    /**
     * @return mixed
     */
    public function getIsp()
    {
        return $this->_isp;
    }

	/**
	 * @return string
	 */
	public function getContinent()
	{
		return $this->_continent;
	}
}