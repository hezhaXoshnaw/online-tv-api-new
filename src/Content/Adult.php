<?php


namespace Content;


use Account\User;
use Util\AES;
use Util\UserInfo;

class Adult
{

    public function __construct()
    {
    }

    /**
     * @param UserInfo $userInfo
     * @param User $userAccess
     * @return array
     * @throws \CustomException\GeneralException
     */
    public static function getAdultCategory(UserInfo  $userInfo, User $userAccess)
    {

        return [
            'id'                => 1,
            'name'              => 'All',
            'icon'              => '',
            'icon_2'            => '',
            'countTimeShiftOn'  => 0,
            'countStreamOn'     => 0,
            'sub'               => 0,
            'download'          => self::getDownloadLink($userInfo, $userAccess),
        ];
    }

    /**
     * @param UserInfo $userInfo
     * @param User $userAccess
     * @return string
     * @throws \CustomException\GeneralException
     */
    private static function getDownloadLink(UserInfo  $userInfo, User $userAccess) {
        $queryString = [
            'language_id'   => 1,
            'mac'           => $userInfo->getMacAddress(),
            'language'      => 'all',
            'is_encrypt'    => 1,
            'package_id'    => $userInfo->isUserRequestFreeLogin() ? 0 : 1,
            'key_login'     => $userAccess->getFullKeyLoginForUser(),
            'app'           => $userInfo->getRequestApplicationName(),
            'is_imax_type'  => 0,
        ];

        if (UserInfo::isModeDebug() )
            $queryString['debug'] = 1;



        $content = AES::encrypt(http_build_query($queryString), AES_IV_LANGUAGE,AES_KEY_LANGUAGE);
        return DOWNLOAD_URL . "extra?content=" . $content ;
//        return DOWNLOAD_URL . "download.php?" . http_build_query($queryString) ;

    }
}