<?php


namespace Content;


use Account\User;
use CustomException\RequestAPIException;
use Util\AES;
use Util\UserInfo;

class Stream
{

    /**
     * @var \Redis
     */
    protected $_redis;

    /**
     * @var UserInfo
     */
    private $_userInfo ;

    /**
     * @var User
     */
    private $_userAccess;


    /**
     * @var
     */
    private $_countryInfo ;

    public function __construct(\Redis $redis, UserInfo $userInfo, User $user)
    {
        $this->_redis = $redis;
        $this->_userAccess = $user;
        $this->_userInfo = $userInfo;
        if ($this->getUserAccess()->isSkipUpdateCountry())
        {
            $this->_countryInfo  = $this->getUserAccess()->getCountry() ;
        }
        else
        {
            $this->_countryInfo  = $this->getUserInfo()->getCountry();
        }
    }

    /**
     * @return array|mixed
     * @throws RequestAPIException
     */
    public function getSubLanguage()
    {
        $subLanguageJsonData  =   $this->_redis->get(SUB_LANGUAGE_REDIS_KEY .'-' . $this->getUserInfo()->getRequestApplicationName());
        if($subLanguageJsonData != '') {
            return json_decode($subLanguageJsonData, true);
        }
        $subLanguages = $this->loadSubLanguageFromAPI();

            $allCat = [
                'id'    => in_array($this->getUserInfo()->getRequestApplicationName(), ['imax_2022','imax_h_2022']) ? 65535 : 0 ,
                'language_name' => 'All',
                'flag'  => 'http://flags.updateapihost.com/v3/Arabic_v2.jpg'
            ];

            array_unshift($subLanguages,$allCat);

        if (!empty($subLanguages))
            $this->_redis->set( SUB_LANGUAGE_REDIS_KEY . '-' . $this->getUserInfo()->getRequestApplicationName(),json_encode($subLanguages));

        return $subLanguages;
    }

    /**
     * @return mixed
     * @throws RequestAPIException
     */
    private function loadSubLanguageFromAPI()
    {
        $headers = array('Content-Type' => 'application/json','auth-token'=> STREAM_API_TOKEN);

        $requestResponse = \Requests::get($this->getSubLanguageApi(), $headers);
        if (!in_array($requestResponse->status_code, array(200))) {
            throw new RequestAPIException("could not get sub languages from api {$requestResponse->status_code} ",'fail please try again latter');
        }
        return json_decode($requestResponse->body, true);

    }


    /**
     * @return string
     */
    protected  function getSubLanguageApi()
    {
        return STREAM_API_URL . 'sub-language' ;
    }

    /**
     * @param int $packageID
     * @return string
     */
    protected  function getPackageLanguageApi($packageID)
    {
        return STREAM_API_URL  . 'package/' . $packageID ;
    }

    /**
     * @param array $countryLanguage
     * @param string $userCountry
     * @return bool
     */
    protected function validForCountry(array $countryLanguage, $userCountry)
    {
        //it mean in this array there is a country with !
        $countryWithNotExist =false;


        foreach ($countryLanguage as $key => $value)
        {
            if (strpos($value, '!') === 0)
            {
                $countryWithNotExist = true;
                break;
            }

        }

        if (in_array($userCountry, $countryLanguage))  return true;
        else if (!$countryWithNotExist)  return false;
        else if (in_array('!' . $userCountry, $countryLanguage)) return false;
        else return true;
    }
    /**
     * @param $packageID
     * @return array|bool|mixed
     * @throws RequestAPIException
     */
    private function loadLanguages($packageID)
    {
        if(!$languages = $this->loadLanguageFromRedis($packageID)) {
            $languages = $this->loadLanguageFromAPI($packageID);

            $this->setToRedis($languages, $packageID);
        }
        return $languages;

    }

    /**
     * @param $packageID
     * @return bool|mixed
     */
    private function loadLanguageFromRedis($packageID)
    {
        $languages = $this->_redis->hGet('api_v2_language_info', 'package_'.$packageID );
        if(!$languages) return false;
        return json_decode($languages, true);
    }

    /**
     * @param array $info
     * @param $packageID
     */
    private function setToRedis(array $info, $packageID) {
        $this->_redis->hSet('api_v2_language_info', 'package_' . $packageID, json_encode($info, JSON_UNESCAPED_SLASHES));
    }

    /**
     * @param $packageID
     * @return array
     * @throws RequestAPIException
     */
    private function loadLanguageFromAPI($packageID)
    {
        $headers = array('Content-Type' => 'application/json','auth-token'=> STREAM_API_TOKEN);
        $url = $this->getPackageLanguageApi($packageID);
        $requestResponse = \Requests::get($url, $headers);
        if (!in_array($requestResponse->status_code, array(200))) {
            throw new RequestAPIException("could not get languages from api {$requestResponse->status_code} ",'fail please try again latter');
        }
        return json_decode($requestResponse->body, true);
    }

    /**
     * @param array $language
     * @param $country
     * @param false $isForImaxType
     * @return string
     * @throws \CustomException\GeneralException
     */
    public function getDownloadLink(array $language,$country = false, $isForImaxType =false, $favorite = false ) {
        $queryString = [
            'language_id'   => $language['id'],
            'mac'           => $this->getUserInfo()->getMacAddress(),
            'language'      => $language['name'],
            'is_encrypt'    => isset($language['encrypt']) ? $language['encrypt'] : 1,
            'package_id'    => $this->getUserAccess()->getPackageID(),
            'key_login'     => $this->getUserAccess()->getFullKeyLoginForUser(),
            'app'           => $this->getUserInfo()->getRequestApplicationName(),
            'is_imax_type'  => $isForImaxType ? 1 : 0
        ];
        if ($country)
        {
            $queryString['country'] = $country;
        }

        if ($favorite)
        {
            $queryString['favorite'] = 1;
        }

        if ($this->getUserInfo()->isSupportLocalChannel())
            $queryString['local'] = 1;

        if ($this->getUserAccess()->isHttpsSupport())
            $queryString['ssl'] = 1;

        if ($this->getUserAccess()->isTokenIPSkip())
        $queryString['ip_token_skip'] = 1;

        if (UserInfo::isModeDebug() )
            $queryString['debug'] = 1;


        if ($this->getUserAccess()->isOriginalURL() )
            $queryString['originalUrl'] = 1;


        if ($this->getUserAccess()->getChannelTag() !== null )
            $queryString['channelTag'] = $this->getUserAccess()->getChannelTag();


        $content = AES::encrypt(http_build_query($queryString), AES_IV_LANGUAGE,AES_KEY_LANGUAGE);

        return DOWNLOAD_URL . "stream?content=" . $content ;
        
        // if ($this->getUserInfo()->getMacAddress() === 'c6a9cf6ad650' )
        //     return 'http://1547996885.rsc.cdn77.org/get-download-link/v1/' . "stream?content=" . $content ;
        // else
        // {

        //     if ($this->getUserInfo()->getMacAddress() == '36a43d52b801ac0f')
        //         return  "http://www.logapi.site/get-download-link/v2/stream?content=" . $content ;
        //     else
        //         return DOWNLOAD_URL . "stream?content=" . $content ;
        // }
//        return DOWNLOAD_URL . "download.php?" . http_build_query($queryString) ;

    }


    /**
     * @return UserInfo
     */
    public function getUserInfo()
    {
        return $this->_userInfo;
    }

    /**
     * @return User
     */
    public function getUserAccess()
    {
        return $this->_userAccess;
    }

    /**
     * @return array
     * @throws RequestAPIException
     * @throws \CustomException\GeneralException
     */
    public function getStreamCategories()
    {

        $languages = $this->loadLanguages($this->_userAccess->getPackageID());
        $i = 1;

        $userLanguages = [];

        $favoriteLanguage = array(
            'id'        => 65536,
            'name'      => 'favorite',
        );

        if ($this->getUserInfo()->getRequestApplicationName() != 'snp_v2')
            $userLanguages[]  = [
                'id'                => 65536,
                'name'              => 'Favorites',
                'icon'              => 'http://flags.updateapihost.com/ur-list.jpg',
                'icon_2'            => 'http://flags.updateapihost.com/ur-list.jpg',
                'countTimeShiftOn'  => 0,
                'countStreamOn'     => 0,
                'sub'               => 0,
                'download'          =>$this->getDownloadLink($favoriteLanguage,false,false, true),
            ];

        foreach ($languages as $value)
        {
            if (isset($value['exclusiveCountry']) &&
                !empty($value['exclusiveCountry'])&&
                !in_array('*',$value['exclusiveCountry']) &&
                !$this->validForCountry($value['exclusiveCountry'], $this->_countryInfo ))
                continue;

            if (isset($value['exclusiveCountry']) &&
                !empty($value['exclusiveCountry']) &&
                in_array('*', $value['exclusiveCountry']))
            {
                if ($this->_countryInfo != 'US')
                {
                    continue;
                }


                if (!$this->getUserAccess()->isCountrySkip()) {
                    continue;
                }

            }

            if ($this->getUserInfo()->getRequestApplicationName() == 'voxa_v2' && in_array(['16','40'], $value['id'])  && !in_array($this->getUserInfo()->getMacAddress(), ['5E1122334455','5E1133445566','5E1144556677','5E1155667788','5E1166778899']))
            {
                continue;
            }

            $userLanguages [] = [
                'id'        => $i++,
                'name'      => $value['name'],
                'icon'      => $value['icon'],
                'icon_2'    => $value['iconSquare'],
                'countTimeShiftOn'    => isset($value['countTimeShiftOn']) ? $value['countTimeShiftOn'] : 0,
                'countStreamOn'    => isset($value['countStreamOn']) ? $value['countStreamOn'] : 0,
                'sub'       => $this->getUserInfo()->isUserRequestFreeLogin() ? 0 :  $value['sub'],
                'download'  => $this->getDownloadLink($value, $this->isSentCountryInfo() ? $this->_countryInfo : false),

            ];
        }


        // $allLanguageInfo = array(
        //     'id'        => 0,
        //     'name'      => 'all',
        //     'encrypt'   => 1
        // );

        // $userLanguages[] =
        //     [
        //         'id'        => $i++,
        //         'name'      => 'All',
        //         'icon'      => 'NONE',
        //         'icon_2'    => 'NONE',
        //         'countTimeShiftOn'=> 0,
        //         'countStreamOn'    => 0,
        //         'sub'       => 0,
        //         'download'  =>  $this->getDownloadLink($allLanguageInfo, $this->isSentCountryInfo() ? $this->_countryInfo : false)
        //         ];


        return  $userLanguages;
    }

    /**
     * @return bool
     */
    public function isSentCountryInfo()
    {
        if ($this->getUserAccess()->isSkipUpdateCountry())
        {
            return true;
        }
        return false;
    }

    /**
     * @return mixed
     */
    public function getCountryInfo()
    {
        return $this->_countryInfo;
    }
}