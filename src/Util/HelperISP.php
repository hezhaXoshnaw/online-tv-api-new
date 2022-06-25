<?php


namespace Util;


class HelperISP
{


    /**
     * @var \Redis
     */
    private $_redis;

    /**
     * @var array
     */
    private static $isp = [];

    /**
     * HelperISP constructor.
     * @param \Redis $redis
     */
    public function __construct(\Redis  $redis)
    {
        $this->_redis = $redis;
        $this->setISP();

    }

    /**
     * set isp from file
     */
    private function setISP()
    {
        $isp = $this->loadFromRedis();
        if ($isp)
        {
            self::$isp = $isp;
            return;
        }
        self::$isp = $this->loadFromFile();
        $this->setToRedis(self::$isp);

    }


    /**
     * @return string
     */
    private function getRedisKey()
    {
        return 'isp';
    }


    /**
     * @return bool|array
     */
    private function loadFromRedis()
    {
        $isp = $this->_redis->get($this->getRedisKey());
        return $isp ? json_decode($isp, true) : false;
    }


    /**
     * @param array $isp
     */
    private function setToRedis(array $isp)
    {
        $this->_redis->set($this->getRedisKey(), json_encode($isp));
    }


    /**
     * @return array
     */
    private function loadFromFile()
    {
        if(!file_exists(SKIP_IP_TOKEN_ISP_FILE))
            return [];
        $isp = file_get_contents(SKIP_IP_TOKEN_ISP_FILE);
        return  json_decode($isp, true);

    }


    /**
     * @param string $userISP
     * @return bool
     */
    public static function doseNeedToSkip($userISP)
    {
        return in_array($userISP, self::$isp);
    }

}