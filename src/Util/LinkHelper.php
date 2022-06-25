<?php

namespace Util;


use Request\VodRequest;

class LinkHelper
{

    /**
     * @param $path
     * @param VodRequest $vodRequest
     * @param bool $contentID
     * @return string
     */
    public static function generateNextPageLink($path, VodRequest $vodRequest, $contentID = false)
    {
        $params = [
            'time'          => time(),
            'userType'      => $vodRequest->getUserType(),
            'macAddress'    => $vodRequest->getMacAddress(),
            'offset'        => $vodRequest->getOffset(),
            'limit'         => $vodRequest->getLimit(),
            'contentID'     => '',
            'platform'      => $vodRequest->getPlatform()

        ];

        $prefix = '';

        if ($vodRequest->getLanguage())
            $prefix .= '&language=' . $vodRequest->getLanguage();

        if ($vodRequest->getGenre())
            $prefix .= '&genre=' . $vodRequest->getGenre();

        if ($vodRequest->getPopular())
            $prefix .= '&popular=' . $vodRequest->getPopular();

        if ($vodRequest->getFavorite()){
            $prefix .= '&favorite=' . $vodRequest->getFavorite();
        }

        if ($vodRequest->getRecent())
            $prefix .= '&recent=' . $vodRequest->getRecent();

        $prefix .='&version='.$vodRequest->getVersion();

//        $content = http_build_query($params);
        $content = AES::encrypt(http_build_query($params),AES_IV_GENERATED_LINK, AES_KEY_GENERATED_LINK);
        return DOWNLOAD_URL . $path . '?content=' . $content . $prefix;
    }


    /**
     * @param $path
     * @param VodRequest $vodRequest
     * @return string
     */
    public static function GetHomePage($path, VodRequest $vodRequest)
    {
        $params = [
            'time'          => time(),
            'userType'      => $vodRequest->getUserType(),
            'macAddress'    => $vodRequest->getMacAddress(),
            'offset'        => $vodRequest->getOffset(),
            'limit'         => $vodRequest->getLimit(),
            'contentID'     => '',
            'platform'      => $vodRequest->getPlatform()

        ];
        $prefix = '';

        if ($vodRequest->getPopular())
            $prefix .= '&popular=' . $vodRequest->getPopular();

        if ($vodRequest->getFavorite()){
            $prefix .= '&favorite=' . $vodRequest->getFavorite();
        }

        if ($vodRequest->getRecent())
            $prefix .= '&recent=' . $vodRequest->getRecent();

        $prefix .= '&version=1.1';

        $content = AES::encrypt(http_build_query($params),AES_IV_GENERATED_LINK, AES_KEY_GENERATED_LINK);
//        $content = http_build_query($params);
        return DOWNLOAD_URL . $path . '?content=' . $content . $prefix;

    }

}