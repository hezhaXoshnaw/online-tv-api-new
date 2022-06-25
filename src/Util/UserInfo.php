<?php

namespace Util;

if(!defined('NOT_DIRECT_ACCESS'))
    die('its not defined');


use CustomException\DomainBlockedException;
use CustomException\UserResendException;
use CustomException\ValidationException;
use Lib\Log;
use Lib\Model;
use Lib\MyRedis;

class UserInfo
{
    /**
     * @var string
     */
    private $_userDomainName ='*';

    /**
     * @var bool
     */
    private $_needSkipLanguage = false;

    /**
     * @var bool
     */
    private $_supportLocalChannel = false;

    /**
     * @var bool
     */
    protected $_debugOutputModeOn = false;

    /**
     * @var bool
     */
    private $_skipIpToken = true;

    /**
     * @var bool
     */
    private static $_modeDebug = false;

    /**
     * @var array
     */
    private $_filterChannelLanguageNames = [];

    /**
     * @var bool
     */
    private $_supportEncryptChannelList = false;

    /**
     * @var int
     */
    private $_noScrambleChannelList = 50;

    /**
     * @var bool
     */
    private $_newUserScramble = false;

    /**
     * @var array
     */
    private $_arrayHashValues = array(
        'code_id'       => '',
        'mac_address'   => '',
        'app_name'      => '',
        'key_login'     => '',
    );

    /**
     * @var bool
     */
    private $_skipValidation = false;
    /**
     * @var int
     */
    private $_keyLoginChangeLimit = 140;

    /**
     * @var int
     */
    private $_loginDailyLimit =140;

    /**
     * free key login
     */
    const FREE_KEY_LOGIN ='TWbqH8FTmdbYC';


    /**
     * @var bool
     */
    private $_isAuto = false;

    /**
     * target app status
     * @var bool
     */
    private $_applicationStatus = true;

    /**
     * @var string
     */

    private $_hash;
    /**
     * @var string
     */
    private $_codeID;

    /**
     * @var string
     */
    private $_macAddress;

    /**
     * @var string
     */
    private $_serialNumber;

    /**
     * @var string
     */
    private $_keyLogin;

    /**
     * @var int
     */
    private $_secondKeyLogin;

    /**
     * @var array
     */
    private $_supportedApplicationID = [];

    /**
     * @var int
     */
    private $_mainApplicationID = 0;

    /**
     * @var string
     */
    private $_action = 'add';

    /**
     * @var string
     */
    private $_apiKey = '';

    /**
     * @var string url
     */
    private $vodAccessURL = 'http://validate.vod-validation-api.com/vod_access_new_p/vod_access.php';

    /**
     * @var int
     */
    public static $resendExceptionCode = 0;

    /**
     * @var bool
     */
    private $_forAccess = true;

    /**
     * @var array
     */
    const AUTO_CODE_LIST = array('auto_code_','0000000000','1986077509' ,'1108864117','0554135277');

    /**
     * @var string
     */
    public static $_defaultHeaderType  ='Content-Type: application/json';
    /**
     * @var string
     */
    private $_headerType ;

    /**
     * @var bool
     */
    public $renderAutoCodeToZero = false;

    /**
     * @var int auto mac collation id
     */
    public $_registerCollectionID = 0;

    /**
     * @var bool
     */
    private $_needRegister = false;

    /**
     * for IOS apk they will login free
     * @var bool
     */
    private $_freeLoginAuto = false;

    /**
     * @var int
     */
    private $_applicationPackageID = 0;

    /**
     * @var int
     */
    private  $_freeLoginPackageID = 60;

    /**
     * @var Country
     */
    private $_countryConfig;

    /**
     * @var string
     */
    private $_region;

    /**
     * @var string
     */
    public $userAgent;

    /**
     * @var bool
     */
    private $_httpsSupport = false;

    /**
     * @var bool
     */
    public $flagNeed = true;

    /**
     * @var bool
     */
    public $subLanguageNeed = true;

    /**
     * @var string
     */
    public $renderType = 'json';
    /**
     * @var string
     */
    public $applicationNameForVodAccess ='imx_online_tv';


    /**
     * @var array
     */
    private $_userInputData = [
        'code_id'           => '',
        'mac_address'       => '',
        'serial_number'     => '',
        'application_name'  => '',
        'ip_address'        => '',
        'full_key_login'    => '',
        'hash'              => '',
        'userAgent'         => '',
        'version'           => '',
        'localChannel'      => false,
        'user_version'      => ''
    ];
    /**
     * @var \Lib\Model
     */
    private $_model;

    /**
     * UserInfo constructor.
     * @param array $getRequest
     * @param Country  $countryConfig
     * @param $hash
     * @param $userAgent
     * @param \Lib\Model $model
     */
    public function __construct(array $getRequest, Country $countryConfig, $hash, $userAgent, Model $model)
    {
        $this->_countryConfig = $countryConfig;

        $this->_headerType = self::getDefaultHeaderType();
        $this->_userInputData = [
            'code_id'           => $getRequest['ac'],
            'mac_address'       => $getRequest['mc'],
            'serial_number'     => isset($getRequest['sn']) ? $getRequest['sn'] : '',
            'application_name'  => $getRequest['app'],
            'ip_address'        => $countryConfig,
            'full_key_login'    => isset($getRequest['key']) ? $getRequest['key'] : '',
            'hash'              => $hash,
            'userAgent'         => $userAgent,
            'version'           => isset($getRequest['version']) ? $getRequest['version'] : '',
            'localChannel'      => isset($getRequest['local']) ? true : false,
        ];
        $this->_model = $model;
        Log::setDebugLogSteps('creating userInfo Object', $this->isDebugOutputModeOn());
    }

    /**
     * @throws UserResendException
     * @throws ValidationException
     * @throws \Exception
     */
    public function validate()
    {


        Log::setDebugLogSteps('Validating the user input',$this->isDebugOutputModeOn());
        $this->_serialNumber = $this->_userInputData['serial_number'];
        $this->_supportLocalChannel = $this->_userInputData['localChannel'];
        $this->setLocalVariable($this->_userInputData['application_name']);
        if($this->_userInputData['hash'] == '' && !self::isModeDebug())
            throw new ValidationException('user send empty hash','please update your software');

        $this->_hash = $this->_userInputData['hash'];
        $this->setCodeID($this->_userInputData['code_id']);
        $this->setMacAddress($this->_userInputData['mac_address']);
        $this->setKeyLogin($this->_userInputData['full_key_login']);
        $this->userAgent  = $this->_userInputData['userAgent'];
    }

    /**
     * @param $fullKeyLogin
     * @throws UserResendException
     */
    public function setKeyLogin($fullKeyLogin)
    {
        if ($fullKeyLogin == '') {
            $this->_forAccess = false;
            return;
        }
        $keyValues = explode('-', $fullKeyLogin);
        if (count($keyValues ) != 2) {
            throw new UserResendException("key login is not right {$fullKeyLogin}",'Fail please try again latter');
        }
        $this->_keyLogin        = $keyValues[0];
        $this->_secondKeyLogin  = $keyValues[1];
    }

    /**
     * @param $applicationName
     * @throws \Exception
     */
    private function setLocalVariable($applicationName)
    {

        $this->_arrayHashValues = [
            'code_id'       => $this->_userInputData['code_id'],
            'mac_address'   => $this->_userInputData['mac_address'],
            'app_name'      => $this->_userInputData['application_name'],
            'key_login'     => $this->_userInputData['full_key_login'],
        ] ;


        switch ($applicationName) {

            case 'snp_v2':
            case 'snp_v3':

             {
                $this->_mainApplicationID = ONLINE_TV_APPLICATION_ID;
                $this->_supportedApplicationID = [VOXA_TV_APPLICATION_ID,ONLINE_TV_APPLICATION_ID];
                $this->_newUserScramble = true;
                $this->_supportEncryptChannelList = true;
                $this->_apiKey = SAN_PLUS_API_KEY;
                $this->_needRegister = true;
                $this->_registerCollectionID = 142;
                $this->flagNeed = false;
                $this->subLanguageNeed = false;
                $this->applicationNameForVodAccess = 'san_online_tv';
                break;
            }
            case 'snp_update': 
            case 'snp_update_v3': 
            {
                $this->_mainApplicationID = ONLINE_TV_APPLICATION_ID;
                $this->_supportedApplicationID = [VOXA_TV_APPLICATION_ID,ONLINE_TV_APPLICATION_ID];
                $this->_apiKey = SAN_PLUS_API_KEY;
                $this->_action = 'update';
                break;
            }
            case 'imax_2022' : {
                // self::$_modeDebug = true;
                $this->_needSkipLanguage = true;
                $this->_mainApplicationID = ONLINE_TV_APPLICATION_ID;
                $this->_supportedApplicationID = [VOXA_TV_APPLICATION_ID,ONLINE_TV_APPLICATION_ID];
                $this->_supportEncryptChannelList = true;
                $this->_apiKey = ISTAR_NEW_APPLICATION_API_KEY;
                $this->applicationNameForVodAccess = 'imx_online_tv';
                break;
            }
            case 'imax_h_2022': {
                // self::$_modeDebug = true;
                $this->_needSkipLanguage = true;
                $this->_mainApplicationID = ONLINE_TV_APPLICATION_ID;
                $this->_supportedApplicationID = [VOXA_TV_APPLICATION_ID,ONLINE_TV_APPLICATION_ID];;
                $this->_supportEncryptChannelList = true;
                $this->_apiKey = ISTAR_NEW_APPLICATION_API_KEY;
                $this->applicationNameForVodAccess = 'imx_online_tv';
                break;
            }
            case 'imax_update_2022': {
                $this->_mainApplicationID = ONLINE_TV_APPLICATION_ID;
                $this->_supportedApplicationID = [VOXA_TV_APPLICATION_ID,ONLINE_TV_APPLICATION_ID];;
                $this->_apiKey = ISTAR_NEW_APPLICATION_API_KEY;
                $this->_action = 'update';
                break;
            }
            case 'drg_h_2022': {
                $this->_needSkipLanguage = true;
                $this->_mainApplicationID = DRAGON_TV_APPLICATION_ID;
                $this->_supportedApplicationID = [DRAGON_TV_APPLICATION_ID];
                $this->_supportEncryptChannelList = true;
                $this->_newUserScramble = true;
                $this->_apiKey = NEW_DRAGON_API_KEY;
                $this->applicationNameForVodAccess = 'drg_online_tv';
                $this->_channelTag = 'H';
                break;
            }
            case 'drg_update_2022': {
                $this->_mainApplicationID = DRAGON_TV_APPLICATION_ID;
                $this->_supportedApplicationID = [DRAGON_TV_APPLICATION_ID];
                $this->_newUserScramble = true;
                $this->_apiKey = NEW_DRAGON_API_KEY;
                $this->_action = 'update';
                break;
            }
            case 'drg_geant_h_2022': {
                $this->_needSkipLanguage = true;
                $this->_mainApplicationID = DRAGON_TV_APPLICATION_ID;
                $this->_supportedApplicationID = [DRAGON_TV_APPLICATION_ID];
                $this->_supportEncryptChannelList = true;
                $this->_newUserScramble = true;
                $this->_apiKey = NEW_DRAGON_API_KEY;
                $this->applicationNameForVodAccess = 'drg_online_tv';
                $this->_channelTag = 'H';
                break;
            }

            case 'drg_geant_update_2022': {
                $this->_mainApplicationID = DRAGON_TV_APPLICATION_ID;
                $this->_supportedApplicationID = [DRAGON_TV_APPLICATION_ID];
                $this->_newUserScramble = true;
                $this->_apiKey = NEW_DRAGON_API_KEY;
                $this->_action = 'update';
                break;
            }

            case 'zeeko_api':
            case 'android_api': {
                // $this->_freeLoginAuto = true;
                // self::setModeDebug(true);
                $this->_needRegister = true;
//                $this->_skipValidation = true;
                $this->_registerCollectionID = 323;
                $this->_mainApplicationID = ONLINE_TV_APPLICATION_ID;
                $this->_supportedApplicationID = [VOXA_TV_APPLICATION_ID,ONLINE_TV_APPLICATION_ID];;
                $this->_supportEncryptChannelList = true;
                $this->_apiKey = ISTAR_NEW_APPLICATION_API_KEY;
                $this->renderType ='json';
                break;
            }
            case 'zeeko_api_update':
            case 'android_api_update': {
                $this->_needRegister = true;
                $this->_registerCollectionID = 323;
                $this->getModel()->setVersionCheck(false);
                $this->_mainApplicationID = ONLINE_TV_APPLICATION_ID;
                $this->_supportedApplicationID = [VOXA_TV_APPLICATION_ID,ONLINE_TV_APPLICATION_ID];;
                $this->renderType ='json';
                $this->_action = 'update';
                $this->_apiKey = ISTAR_NEW_APPLICATION_API_KEY;
                $this->renderType ='json';
                break;
            }

            case 'android_pk_mtv': {
                $this->_needRegister = true;
                $this->_registerCollectionID = 248;

                $this->_mainApplicationID = MTV_APPLICATION_ID;
                $this->_supportedApplicationID = [MTV_APPLICATION_ID];;
                $this->_supportEncryptChannelList = true;
                $this->_apiKey = MTV_AND_WWTV_V3_API_KEY;
                $this->renderType ='json';
                break;
            }
            case 'android_pk_mtv_update': {
                $this->_needRegister = true;
                $this->_registerCollectionID = 248;
                $this->getModel()->setVersionCheck(false);
                $this->_mainApplicationID = MTV_APPLICATION_ID;
                $this->_supportedApplicationID = [MTV_APPLICATION_ID];;
                $this->renderType ='json';
                $this->_action = 'update';
                $this->_apiKey = MTV_AND_WWTV_V3_API_KEY;
                $this->renderType ='json';
                break;
            }

            case 'android_tv_v2': {
                $this->_freeLoginAuto = true;
                // $this->_freeLoginAuto = true;
                // self::setModeDebug(true);
                $this->_needRegister = true;
                $this->_registerCollectionID = 323;
                $this->_mainApplicationID = ONLINE_TV_APPLICATION_ID;
                $this->_supportedApplicationID = [VOXA_TV_APPLICATION_ID,ONLINE_TV_APPLICATION_ID];;
                $this->_supportEncryptChannelList = true;
                $this->_apiKey = ISTAR_NEW_APPLICATION_API_KEY;
                $this->renderType ='json';
                break;
            }
            case 'android_tv_update_v2': {
                $this->_needRegister = true;
                $this->_registerCollectionID = 323;
                $this->getModel()->setVersionCheck(false);
                $this->_mainApplicationID = ONLINE_TV_APPLICATION_ID;
                $this->_supportedApplicationID = [VOXA_TV_APPLICATION_ID,ONLINE_TV_APPLICATION_ID];;
                $this->renderType ='json';
                $this->_action = 'update';
                $this->_apiKey = ISTAR_NEW_APPLICATION_API_KEY;
                $this->renderType ='json';
                break;
            }

            case 'android_dragon_app_v2': {
                // $this->_freeLoginAuto = true;
                // self::setModeDebug(true);
                $this->_needRegister = true;
//                $this->_skipValidation = true;
                $this->_registerCollectionID = 333;
                $this->_mainApplicationID = DRAGON_TV_APPLICATION_ID;
                $this->_supportedApplicationID = [DRAGON_TV_APPLICATION_ID];;
                $this->_supportEncryptChannelList = true;
                $this->_apiKey = ISTAR_NEW_APPLICATION_API_KEY;
                $this->renderType ='json';
                break;
            }
            case 'android_dragon_app_update_v2': {
                // $this->_needRegister = true;
                // $this->_registerCollectionID = 333;
                $this->getModel()->setVersionCheck(false);
                $this->_mainApplicationID = DRAGON_TV_APPLICATION_ID;
                $this->_supportedApplicationID = [DRAGON_TV_APPLICATION_ID];;
                $this->renderType ='json';
                $this->_action = 'update';
                $this->_apiKey = ISTAR_NEW_APPLICATION_API_KEY;
                $this->renderType ='json';
                break;
            }
            default : {
                Log::setDebugLogSteps('application name not found', $this->isDebugOutputModeOn());
                throw new \Exception('application name not found');
            }
        }

    }
    /**
     * @return string
     */
    public function getCountry()
    {
        return $this->_countryConfig->getCountry();
    }

    /**
     * @return string
     */
    public function getRegion()
    {
        return $this->_region;
    }

    /**
     * @param string $codeID
     */
    public function setCodeID($codeID)
    {
        if (in_array($codeID ,self::AUTO_CODE_LIST)) {
            $this->_isAuto = true;
            $this->_codeID = 'auto_code_';
            return;
        }
        $this->_codeID = $codeID;
    }
    /**
     * @return string
     */
    public function getCodeID()
    {
        return $this->_codeID;
    }

    /**
     * @return string
     */
    public function getKeyLogin()
    {
        return $this->_keyLogin;
    }

    /**
     * @return int
     */
    public function getSecondKeyLogin()
    {
        return $this->_secondKeyLogin;
    }

    /**
     * @param $macAddress
     * @throws ValidationException
     */
    private function setMacAddress($macAddress)
    {
        if(!ctype_xdigit($macAddress)) {
            throw new ValidationException('user send invalid mac address','please update your software');
        }
        $this->_macAddress = strtolower($macAddress);
    }
    /**
     * @return string
     */
    public function getMacAddress()
    {
        return $this->_macAddress;
    }

    /**
     * @return string
     */
    public function getSerialNumber()
    {
        return $this->_serialNumber;
    }

    /**
     * @return bool
     */
    public function isSupportHTTPS()
    {
        return $this->_httpsSupport;
    }

    /**
     * @return bool
     */
    public function isAuto()
    {
        return $this->_isAuto;
    }

    /**
     * @return string
     */
    public function getIPAddress()
    {
        return $this->_countryConfig->getIpAddress();
    }

    /**
     * @return string
     */
    public function getHeaderType()
    {
        return $this->_headerType;
    }

    /**
     * @return bool
     */
    public function isForAccess()
    {
        return $this->_forAccess && $this->_action != 'update';
    }

    /**
     * @return string
     */
    public function getVodAccessURL()
    {
        return $this->vodAccessURL;
    }

    /**
     * @return array
     */
    public function getArrayValuesForHash()
    {
        return $this->_arrayHashValues;
    }

    /**
     * @return string
     */
    public function getUserHash()
    {
        return  $this->_hash;
    }



    /**
     * @return string
     */
    public function getAPIKey()
    {
        return $this->_apiKey;
    }

    /**
     * @return bool
     */
    public function isNeedRegister()
    {
        return $this->_needRegister;
    }

    /**
     * @return int
     */
    public function getCollectionID()
    {
        return $this->_registerCollectionID;
    }

    /**
     * @return bool
     */
    public function isForRegister()
    {
        return $this->_action == 'add';
    }

    /**
     * @return bool
     */
    public function isForUpdateAccount()
    {
        return $this->_action == 'update';
    }

    /**
     * @return \Lib\Model
     */
    public function getModel() {
        return $this->_model;
    }

    /**
     * @return bool
     */
    public function isFreeLoginAllowed() {
        return $this->_freeLoginAuto;
    }
    /**
     * @return bool
     */
    public function isApplicationEnabled() {
        return $this->_applicationStatus;
    }


    /**of keys:033E this pid :033F this video pid:1FFF this audio
     * @return int
     */
    public function getFreeLoginPackageID() {
        return $this->_freeLoginPackageID;
    }



    /**
     * @return string
     */
    public function getFreeKeyLogin() {
        return self::FREE_KEY_LOGIN;
    }
    /**
     * @return int
     */
    public function getKeyLoginChangeLimit()
    {
        return $this->_keyLoginChangeLimit;
    }

    /**
     * @return string
     */
    public static function getDefaultHeaderType()
    {
        return self::$_defaultHeaderType ;
    }


    /**
     * @return int
     */
    public function getLoginDailyLimit()
    {
        return $this->_loginDailyLimit;
    }


    /**
     * @return bool
     */
    public function isSkipValidation()
    {
        return $this->_skipValidation || UserInfo::isModeDebug();
    }

    /**
     * @param bool $skipValidation
     */
    public function setSkipValidation($skipValidation)
    {

        $this->_skipValidation = $skipValidation;
    }

    /**
     * @return bool
     */
    public function isNewUserScramble()
    {
        return $this->_newUserScramble;
    }

    /**
     * @return int
     */
    public function getNoScrambleChannelList()
    {
        return $this->_noScrambleChannelList;
    }

    /**
     * @param $fullKeyLogin
     * @return string
     * @throws \Exception
     */
    public function getEncryptKeyLogin($fullKeyLogin)
    {
        Log::setDebugLogSteps('return encrypt key login', $this->isDebugOutputModeOn());
        switch ($this->_userInputData['application_name']) {
            case 'imax_2022' :
            case 'imax_h_2022':
            case 'imax_update_2022':
            case 'drg_h_2022':
            case 'drg_h_update_2022':
            case 'drg_update_2022':
            case 'drg_geant_h_2022':
            case 'drg_geant_update_2022':
            {
                $encrypted = AES::encrypt($fullKeyLogin,'DkEeSLcQ3aXbyTDv', '6uEbMD84P34DLu2c');
                return $encrypted;
            }
            case 'snp_v2':
            case 'snp_v3':
            case 'snp_update_v2':
            case 'snp_update_v3': {
                $encrypted = AES::encrypt($fullKeyLogin,'aKr74P7pdP6f8zuE', 'Vcvg29TC8EmhTSNf');
                return $encrypted;
            }
            case 'drg_h_update_app_v2':
            {
                $encrypted = AES::encrypt($fullKeyLogin,'E8nDRMcMcapXhtZp', '37tTnDyRnFhmnJGc');
                return $encrypted;
            }
            case 'android_dragon_app_v2':
            case 'android_dragon_app_update_v2':
            case 'android_api_update':
            case 'android_api' :
            case 'zeeko_api':
            case 'zeeko_api_update':
            case 'android_tv_v2':
            case 'android_tv_update_v2':
            case 'android_pk_mtv_update':
            case 'android_pk_mtv':
            
            {
                $encrypted = AES::encrypt($fullKeyLogin,'65yRJWAwy3ZnWegY', 'VFK6s3LTvP2HyUxs');
                return $encrypted;
            }


            default : {
                throw new \Exception('application name not found for key encrypt');
            }

        }
    }

    /**
     * @return bool
     */
    public function isSupportEncryptChannelList()
    {
        return $this->_supportEncryptChannelList && !self::isModeDebug();
    }

    public function isUserRequestFreeLogin()
    {
        return $this->getKeyLogin() === $this->getFreeKeyLogin();
    }

    /**
     * @return array
     */
    public function getFilterChannelLanguageNames()
    {
        return $this->_filterChannelLanguageNames;
    }

    /**
     * @return bool
     */
    public static function isModeDebug()
    {
        return self::$_modeDebug;
    }

    /**
     * @param bool $modeDebug
     */
    public static function setModeDebug($modeDebug)
    {
        self::$_modeDebug = $modeDebug;
    }

    /**
     * @return bool
     */
    public function isSkipIpToken()
    {
        return $this->_skipIpToken;
    }

    /**
     * @return int
     */
    public function getApplicationPackageID()
    {
        return $this->_applicationPackageID;
    }


    /**
     * @return string
     */
    public function getUserVersion()
    {
        return $this->_userInputData['version'];
    }


    /**
     * @return string
     */
    public function getRequestApplicationName()
    {
        return $this->_userInputData['application_name'];
    }

    /**
     * @return int
     */
    public function getMainApplicationID()
    {
        return $this->_mainApplicationID;
    }

    /**
     * @return array
     */
    public function getSupportedApplicationID()
    {
        return $this->_supportedApplicationID;
    }

    /**
     * @return string
     */
    public function getApplicationIDStringList()
    {
        return implode(',', $this->_supportedApplicationID);
    }

    /**
     * @return bool
     */
    public function isDebugOutputModeOn()
    {
        return $this->_debugOutputModeOn;
    }

    /**
     * @param bool $debugOutputModeOn
     */
    public function setDebugOutputModeOn($debugOutputModeOn)
    {
        if ($debugOutputModeOn)
            ob_start();
        $this->_debugOutputModeOn = $debugOutputModeOn;
    }

    /**
     * @return bool
     */
    public function isSupportLocalChannel()
    {
        return $this->_supportLocalChannel;
    }

    /**
     * @return bool
     */
    public function isNeedSkipLanguage()
    {
        return $this->_needSkipLanguage;
    }

    /**
     * @return array
     */
    public function skipLanguageList()
    {
        if (!$this->isNeedSkipLanguage())
            return [];
        if(
            !in_array($this->_model->getLastVersion(), ['',null]) != '' &&
            $this->getUserVersion() !== $this->_model->getLastVersion()
        )
            return ['Online TV'];
        return [];
    }

    /**
     * @param $userFullUrl
     * @throws DomainBlockedException
     */
    public function setUserDomainName($userFullUrl)
    {
//      if (in_array($userFullUrl,$this->_blockedDomain))
//          throw new DomainBlockedException('domain blocked', 'domain is blocked ');

        $urlInfo  = parse_url($userFullUrl);
        $this->_userDomainName = isset($urlInfo['host']) ? $urlInfo['host'] : '*';
    }
    /**
     * @return string
     */
    public function getUserDomainName()
    {
        return $this->_userDomainName ;
    }

    /**
     * @param Country $countryConfig
     */
    public function setCountryConfig($countryConfig)
    {
        $this->_countryConfig = $countryConfig;
    }
    /**
     * @return Country
     */
    public function getCountryConfig()
    {
        return $this->_countryConfig;
    }
}
