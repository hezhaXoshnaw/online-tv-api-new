<?php

namespace Response;

use Content\Adult;
use Util\UserInfo;
use Content\ContentHelper;
use Account\User;

class RenderContent
{
	/**
	 * @var UserInfo
	 */
	private $_userInfo ;

	/**
	 * @var User
	 */
	private $_userAccess;

	private $_redis;
	/**
	 * ChannelList constructor
	 * @param \Redis $redis
	 * @param UserInfo $userInfo
	 * @param User $user
	 */
	public function __construct(\Redis $redis, UserInfo $userInfo, User $user)
	{
	    $this->_redis = $redis;
		$this->_userAccess = $user;
		$this->_userInfo = $userInfo;
	}

	/**
	 * @param $message
	 * @throws \CustomException\GeneralException
	 * @throws \Exception
	 */
	public function render($message)
	{
		$expireDate = $this->getUserAccess()->getExpireDate(false);
		$startDate = $this->getUserAccess()->getLoginDate(false);
		$vod = new ContentHelper($this->getRedis(), $this->getUserAccess(), $this->getUserInfo());
		$stream = new \Content\Stream($this->_redis,$this->_userInfo,$this->_userAccess);
         $imaxTypeParam = array(
            'id'        => -1,
            'name'      => 'imax-type',
        );
        $imaxTypeLink = [
            'download'  => $stream->getDownloadLink($imaxTypeParam,$stream->isSentCountryInfo() ? $stream->getCountryInfo() : false,true)
        ];
         $favoriteLanguage = array(
            'id'        => 0,
            'name'      => 'favorite',
        );

		$headerArray = [
			'message'       => 'Success : Start Date is ' . $startDate . ' your account will be expire in ' . $expireDate ,
			'user_code'     => $this->getUserInfo()->getCodeID(),
			'key_login'     => $this->getUserInfo()->getEncryptKeyLogin($this->getUserAccess()->getFullKeyLoginForUser()),
			'expire_date'   => $expireDate,
			'start_date'    => $startDate,
			'title'         => $message,
			'is_delete_key' => 0,
            'is_delete_key_v2'=> 0,
			'packageID'     => $this->_userAccess->getPackageID(),
			'host_status'   => 1,
            'recordTime'    => 86400,
            'CurrentTimeUTC'=> time(),
            'getTime'       => "http://time.hello-update-clf.com/time/?mac=". $this->getUserInfo()->getMacAddress(),
			'test'          => UserInfo::isModeDebug() ? $this->getUserAccess()->getFullKeyLoginForUser() : '',
			'sub_language'  => array_values($stream->getSubLanguage()),
			'app'           => $imaxTypeLink,
			'category'      => $stream->getStreamCategories(),
			'favorite-stream'=>  $stream->getDownloadLink($favoriteLanguage,false,false, true),
            'favorite'      => DOWNLOAD_URL  . 'favorite/',
            'vod-language'  => $vod->getLanguage(),
            'vod-genre'     => $vod->getGenre(),
            'movie'         => $vod->getVodUri('movie'),
            'series'        => $vod->getVodUri('series'),
            'extra'         => Adult::getAdultCategory($this->getUserInfo(),$this->getUserAccess()),
            'box-is-on'		=> 'http://count.update-api-clf.online/box-is-on/'
		];

		echo json_encode($headerArray);
	}

    /**
     * @return User
     */
    public function getUserAccess()
    {
        return $this->_userAccess;
    }


    /**
     * @return UserInfo
     */
    public function getUserInfo()
    {
        return $this->_userInfo;
    }


    /**
     * @return \Redis
     */
    public function getRedis()
    {
        return $this->_redis;
    }

}