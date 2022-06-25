<?php

namespace Response;

if(!defined('NOT_DIRECT_ACCESS'))
    die('its not defined');


use Account\User;
use Account\UserAccessFree;
use Account\UserRegister;
use CustomException\GeneralException;
use Lib\Model;
use Util\UserInfo;

class RenderOutput
{

    /**
     * @param UserInfo $userInfo
     * @param Model $model
     * @throws \Exception
     */
    public static function updateSoftwareMessage(UserInfo $userInfo,Model $model)
    {
        header($userInfo->getHeaderType());
        header('login-status: VERSION_CHECK_FAIL');
        switch ($userInfo->getRequestApplicationName())
        {
            case 'san_v2': 
            case 'san_v3': 
            case 'imax_2022' :
            case 'imax_h_2022':
            case 'imax_update_2022':
            case 'dragon_geant_h_2022':
            case 'dragon_geant_update_2022':
            case 'dragon_h_2022':

            {
                echo json_encode( [
                        'message'       =>'please update your software',
                        'user_code'     =>'',
                        'key_login'     =>'',
                        'expire_date'   =>"",
                        'is_delete_key' => "1",
                        'is_delete_key_v2'=> 1,
                        'resend_request'  => 0,
                        'start_date'    =>"",
                        'title'         =>"",
                        'host_status'   => 1,
                        'channel_list'  =>array(),
                        'category'      =>array(),
                    ]
                );
                break;
            }
            case 'android_dragon_app_v2' : {
                $lastVersion = $model->getVersionSupport()[0];
                echo json_encode(array('last_version'=>$lastVersion,'url'=>'https://get-v2.download-util-software.online/android/api_v2/zina-tv/dragon/APP_V_5.0.1_v2.apk'));
                break;
            }

            case 'android_api' : {
                $lastVersion = $model->getVersionSupport()[0];
                echo json_encode(array('last_version'=>$lastVersion,'url'=>'http://get-v2.download-util-software.online/android/api_v2/zina-tv/global-app/zina_5.0.1_v2.apk'));
                break;
            }

            case 'android_o3' : {
                $lastVersion = $model->getVersionSupport()[0];
                echo json_encode(array('last_version'=>$lastVersion,'url'=>'http://test.com/name.apk'));
                break;
            }
            case 'voxa_v2' : {
                $lastVersion = $model->getVersionSupport()[0];
                echo json_encode(array('last_version'=>$lastVersion,'url'=>'http://get-v2.download-util-software.online/android/api_v2/voxa/voxa_tv/APP_V_3.1.apk'));
                break;
            }
            case 'android_s7' :
            case 'android_s7_v2' : {
                $lastVersion = $model->getVersionSupport()[0];
                echo json_encode(array('last_version'=>$lastVersion,'url'=>'http://get-v2.download-util-software.online/android/api_v2/android_s7/online-tv/APP_V_1.9.apk'));
                break;
            }

             case 'zeeko_api' : {
                $lastVersion = $model->getVersionSupport()[0];
                echo json_encode(array('last_version'=>$lastVersion,'url'=>'http://get-v2.download-util-software.online/android/api_v2/zeeko/zeeko-android_box-release.apk'));
                break;
            }
            case 'android_zina_tv' : {
                $lastVersion = $model->getVersionSupport()[0];
                echo json_encode(array('last_version'=>$lastVersion,'url'=>'http://get-v2.download-util-software.online/android/api_v2/zina-tv/zina-tv/APP_V_1.2.apk'));
                break;
            }
            case 'android_v2' : {
                $lastVersion = $model->getVersionSupport()[0];
                echo json_encode(array('last_version'=>$lastVersion,'url'=>'http://get-v2.download-util-software.online/android/api_v2/on/online_tv/APP_V_4.8.apk'));
                break;
                // if ($model->getModelName() == 'android_new')
                // {
                // 	$lastVersion = $model->getVersionSupport()[0];
                // 	echo json_encode(array('last_version'=>$lastVersion,'url'=>'http://get-v2.download-util-software.online/android/api_v2/on/online_tv/APP_V_4.4.apk'));

                // }
                // else if ($model->getModelName() == 'android_old') {
                // 	$lastVersion = $model->getVersionSupport()[0];
                // 	echo json_encode(array('last_version'=>$lastVersion,'url'=>'http://get-v2.download-util-software.online/android/api_v2/on/online_tv/APP_V_4.5.apk'));
                // }
                // else {
                // 	$lastVersion = $model->getVersionSupport()[0];
                // 	echo json_encode(array('last_version'=>$lastVersion,'url'=>'http://get-v2.download-util-software.online/android/api_v2/on/online_tv/APP_V_4.4.apk'));
                // }
                break;
            }
            case 'android_pk_ww_v3':
            case 'android_pk_wwtv_ontv': {
                $lastVersion = $model->getVersionSupport()[0];
                echo json_encode(array('last_version'=>$lastVersion,'url'=>'http://get-v2.download-util-software.online/android/api_v2/other/WW-TV/WW_TV_V_6.0.apk'));
                break;

            }
            case 'android_pk_v2' : {
                $lastVersion = $model->getVersionSupport()[0];
                echo json_encode(array('last_version'=>$lastVersion,'url'=>'http://get-v2.download-util-software.online/temp/online_tv_public_V_4.0.apk'));
                break;
            }
            case 'android_pk_voxa': {
                $lastVersion = $model->getVersionSupport()[0];
                echo json_encode(array('last_version'=>$lastVersion,'url'=>'http://get-v2.download-util-software.online/android/api_v2/other/MTV/MTV_V_5.1.apk'));
                break;

            }
            case 'android_pk_ontv_v3' :
            case 'android_pk_all': {
                $lastVersion = $model->getVersionSupport()[0];
                echo json_encode(array('last_version'=>$lastVersion,'url'=>'http://get-v2.download-util-software.online/android/api_v2/other/ONTV/ONTV_V_5.7.apk'));
                break;

            }
            case 'android_pk_mtv_ontv_v3':
            case 'android_pk_mtv_ontv':{
                $lastVersion = $model->getVersionSupport()[0];
                echo json_encode(array('last_version'=>$lastVersion,'url'=>'http://get-v2.download-util-software.online/android/api_v2/other/ONTV-M/MTV_ONTV_6.1.apk'));
                break;
            }
            default : throw new \Exception('application name not found for version check');;
        }
        UserResponse::endUserRequest($userInfo);
    }

    /**
     * @var \Redis
     */
    private static $_redis ;

    /**
     * @param \Redis $redis
     */
    public static function setRedis($redis)
    {
        self::$_redis = $redis;
    }


    /**
     * @param UserInfo $userInfo
     * @throws GeneralException
     */
    private static function freeLogin( UserInfo $userInfo)
    {
        $user = new UserAccessFree($userInfo);
        if ($userInfo->isForAccess()) RenderOutput::loginSuccess($userInfo, $user, self::$_redis);
        else RenderOutput::userLoginResend(REVALIDATE_MESSAGE, $userInfo, $user);

    }

    /**
     * @param $message
     * @param UserInfo $userInfo
     * @param string $headerStatusMessage
     * @param string $account
     * @throws GeneralException
     */
    public static function userLoginFail($message, UserInfo $userInfo, $headerStatusMessage = '',$account = '')
    {
        if($userInfo->isFreeLoginAllowed())
        {
            self::freeLogin($userInfo);
        }
        header($userInfo->getHeaderType());
        header('login-status: '. ($headerStatusMessage == '' ? 'FAIL' : $headerStatusMessage));
        if($account != '')
            header('user-info: ' . $account);

        echo json_encode(array(
            'message'       =>$message,
            'user_code'     =>'',
            'key_login'     =>'',
            'expire_date'   =>"",
            'is_delete_key' => "1",
            'resend_request'  => 0,
            'is_delete_key_v2'=> 1,
            'start_date'    =>"",
            'host_status'   => 1,
            'title'         =>"",
            'channel_list'  =>array(),
            'category'      =>array(),
        ));

        UserResponse::endUserRequest($userInfo);

    }


    public static function forceHostUpdate(UserInfo $userInfo)
    {
        header($userInfo->getHeaderType());
        echo json_encode(array(
            'message'       =>'update host',
            'user_code'     =>'',
            'key_login'     =>'',
            'expire_date'   =>"",
            'is_delete_key' => "1",
            'is_delete_key_v2'=> 1,
            'start_date'    =>"",
            'title'         =>"",
            'host_status'   => 0,
            'channel_list'  =>array(),
            'category'      =>array(),
        ));
        UserResponse::endUserRequest($userInfo);

    }


    /**
     * @param $message
     */
    public static function invalidInput($message)
    {
        echo json_encode(array(
            'message'       => $message,
            'user_code'     =>'',
            'key_login'     =>'',
            'expire_date'   =>"",
            'is_delete_key' => "1",
            'is_delete_key_v2'=> 1,
            'start_date'    =>"",
            'title'         =>"",
            'host_status'   => 1,
            'channel_list'  =>array(),
            'category'      =>array(),
        ));
    }


    /**
     * @param $message
     * @param UserInfo $userInfo
     * @param User $dbUserData
     * @throws GeneralException
     */
    public static function userLoginResend($message,  UserInfo $userInfo,User $dbUserData)
    {
        try
        {
            header('login-status: REDIRECT');
            header($userInfo->getHeaderType());
            $encryptKeyLogin = $userInfo->getEncryptKeyLogin($dbUserData->getFullKeyLoginForUser());
            echo json_encode(array(
                'message'       => $message,
                'user_code'     => $userInfo->getCodeID(),
                'key_login'     => $encryptKeyLogin,
                'key_status'    => ($encryptKeyLogin !== $dbUserData->getFullKeyLoginForUser() ? "1" : "0"),
                'expire_date'   => "",
                'is_delete_key' => "0",
                'resend_request'  => 1,
                'is_delete_key_v2'=> 0,
                'start_date'    => "",
                'host_status'   => 1,
                'title'         => "",
                'test'          => UserInfo::isModeDebug() ? $dbUserData->getFullKeyLoginForUser() : '',
                'channel_list'  => array(),
                'category'      =>array(),
            ));

            UserResponse::endUserRequest($userInfo);
        }
        catch (\Exception $ex) {
            header('login-status: FAIL');
            self::userLoginFail('Unknown Error', $userInfo);
        }
    }


    /**
     * @param UserInfo $userInfo
     * @param User $userAccess
     * @param \Redis $redis
     * @throws \CustomException\GeneralException
     */
    public static function loginSuccess(UserInfo $userInfo, User $userAccess, \Redis $redis)
    {
        header($userInfo->getHeaderType());
        $content = new RenderContent($redis, $userInfo,  $userAccess);
        $content->render('Stay  home for your safety');
        UserResponse::endUserRequest($userInfo);
    }

    /**
     * @param $message
     * @param UserInfo $userInfo
     * @param bool $status
     * @param UserRegister|null $user
     * @throws GeneralException
     */
    public static function updateResponse($message, UserInfo $userInfo, $status = true, UserRegister $user = null)
    {
        header($userInfo->getHeaderType());
        header('login-status: SUCCESS');

        try
        {
            $encryptKeyLogin = '';

            if ($user !== null && $status == true) {
                $encryptKeyLogin = $userInfo->getEncryptKeyLogin($user->getFullKeyLoginForUser());
            }
            echo json_encode(array(
                'message'       =>$message,
                'user_code'     =>$userInfo->getCodeID(),
                'key_login'     =>$encryptKeyLogin,
                'expire_date'   =>"",
                'resend_request'  => 0,
                'host_status'   => 1,
                'start_date'    =>"",
                'test'          => UserInfo::isModeDebug() && $status == true ? $user->getFullKeyLoginForUser() : '',
            ));

            UserResponse::endUserRequest($userInfo);
        }
        catch(\Exception $ex)
        {
            echo json_encode(array(
                'message'       =>'unknow error',
                'user_code'     =>'',
                'key_login'     =>'',
                'expire_date'   =>"",
                'host_status'   => 1,
                'resend_request'  => 0,
                'start_date'    =>"",
                'test'          =>'',
            ));
            $info = [
                'mac'   => $userInfo->getMacAddress(),
                'error' => $ex->getMessage()
            ];
            Log::writeErrorLog($info);

            RenderOutput::userLoginFail($ex->getMessage(),$userInfo);

        }
        UserResponse::endUserRequest($userInfo);
    }

}