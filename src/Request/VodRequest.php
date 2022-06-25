<?php

namespace Request;

use Util\Country;

class VodRequest
{
    /**
     * @var string
     */
    private $_version = '1.0';

    /**
     * @var
     */
    private $_platform;

	/**
	 * @var bool | string
	 */
	private $_contentID = false;

	/**
	 * @var string
	 */
	private $_macAddress;

	/**
	 * @var int
	 */
	private $_time;


	/**
	 * @var int
	 */
	private $_offset;

	/**
	 * @var int
	 */
	private $_limit;

    /**
     * @var string | boolean
     */
    private $_language;

    /**
     * @var string | boolean
     */
    private $_languageName;

    /**
     * @var string | boolean
     */
    private $_genre;

    /**
     * @var string | boolean
     */
    private $_genreName;

	/**
	 * @var string | boolean
	 */
	private $_popular;

	/**
	 * @var string | boolean
	 */
	private $_favorite;

	/**
	 * @var string | boolean
	 */
	private $_recent;


	/**
	 * @var int
	 */
	private $_userType;


	/**
	 * @var string | bool
	 */
	private $_search;

    /**
     * @var Country
     */
    protected $_countryInfo;
	/**
	 * VodRequest constructor.
	 * @param Country $country
	 */
	public function __construct(Country $country)
	{
		$this->_countryInfo = $country;
	}


	/**
	 * @param array $queryString
	 */
	public function setParams(array $queryString)
	{
		$this->_time        = $queryString['time'];
		$this->_macAddress  = $queryString['macAddress'];
		$this->_offset      = $queryString['offset'];
		$this->_limit       = $queryString['limit'];
		$this->_userType    = isset($queryString['userType']) ? $queryString['userType'] : FREE_USER_TYPE;
		$this->_contentID   = isset($queryString['contentID']) ? $queryString['contentID'] : false;
		$this->_platform    = $queryString['platform'];
		$this->setVersion($queryString['platform']);
	}

    /**
     * @return bool|string
     */
    public function getGenre()
    {
        return $this->_genre;
    }
    /**
	 * @return int
	 */
	public function getTime()
	{
		return $this->_time;
	}

	/**
	 * @return int
	 */
	public function getOffset()
	{
		return $this->_offset;
	}

    /**
     * @return bool|string
     */
    public function getLanguage()
    {
        return $this->_language;
    }


	/**
	 * @return string
	 */
	public function getMacAddress()
	{
		return $this->_macAddress;
	}

	/**
	 * @return int
	 */
	public function getLimit()
	{
		return $this->_limit;
	}

	/**
	 * @return bool|string
	 */
	public function getPopular()
	{
		return $this->_popular;
	}

	/**
	 * @return bool|string
	 */
	public function getRecent()
	{
		return $this->_recent;
	}

    /**
     * @param array $queryString
     */
    public function setGenre(array $queryString)
    {
        $this->_genre = (isset($queryString['genre']) && $queryString['genre'] != '') ? $queryString['genre'] : false ;
    }

    /**
     * @param array $queryString
     */
    public function setGenreName(array $queryString)
    {
        $this->_genreName = (isset($queryString['genreName']) && $queryString['genreName'] != '') ? $queryString['genreName'] : false ;
    }

    /**
     * @param array $queryString
     */
    public function setLanguage(array $queryString)
    {
        $this->_language = (isset($queryString['language']) && $queryString['language'] != '') ? $queryString['language'] : false ;;
    }
    /**
     * @param array $queryString
     */
    public function setLanguageName(array $queryString)
    {
        $this->_languageName = (isset($queryString['languageName']) && $queryString['languageName'] != '') ? $queryString['languageName'] : false ;;
    }


	/**
	 * @param array $queryString
	 */
	public function setPopular(array $queryString)
	{
		$this->_popular = (isset($queryString['popular']) && $queryString['popular'] != '') ? $queryString['popular'] : false ;
	}

	/**
	 * @param array $queryString
	 */
	public function setFavorite(array $queryString)
	{
		$this->_favorite = (isset($queryString['favorite']) && $queryString['favorite'] != '') ? $queryString['favorite'] : false ;
	}

	/**
	 * @return bool|string
	 */
	public function getFavorite()
	{
		return $this->_favorite;
	}
	/**
	 * @param array $queryString
	 */
	public function setRecent(array $queryString)
	{
		$this->_recent = (isset($queryString['recent']) && $queryString['recent'] != '') ? $queryString['recent'] : false ;;
	}

	/**
	 * @return int
	 */
	public function getUserType()
	{
		return $this->_userType;
	}

	/**
	 * @param int $limit
	 */
	public function setLimit($limit)
	{
		$this->_limit = $limit;
	}

	/**
	 * @return string
	 */
	public function getPlatform()
	{
		return $this->_platform;
	}

    /**
     * @param string $version
     */
    public function setVersion($version)
    {
        if (in_array($this->_platform, ['android_api','android_dragon_app_v2']))
            $this->_version = '1.1';
        $this->_version = $version;
    }

	public function getVersion()
    {
        return '1.0';
    }
}
