<?php


namespace Content;


use Account\User;
use CustomException\VODRequestApiException;
use Lib\Log;
use Request\VodRequest;
use Util\UserInfo;

class ContentHelper
{


    /**
     * @var \Redis
     */
    private $redis;


    /**
     * @var User
     */
    private $userAccess;

    /**
     * @var UserInfo
     */
    private $userInfo;

    public function __construct(\Redis $redis,User $userAccess,UserInfo $userInfo)
    {
        $this->redis = $redis;
        $this->userAccess =$userAccess;
        $this->userInfo = $userInfo;
    }



    public function getLanguage()
    {
        if ($language = $this->loadFromRedis('language')) return json_decode($language, true);
        try
        {
            $language = $this->loadLanguageFromAPI();
            $this->setToRedis('language',$language);
            return json_decode($language, true);
        }catch (VODRequestApiException $exception)
        {
            $log = [
                'message' => $exception->getMessage()
            ];
            Log::writeErrorLog($log);
            $this->setToRedis('language','none', 60);
            return [];
        }


    }


    /**
     * @return array|mixed
     */
    public function getGenre()
    {
        if ($genre = $this->loadFromRedis('genre')) return json_decode($genre, true);
        try
        {
            $genre = $this->loadGenreFromAPI();
            $this->setToRedis('genre',$genre);
            return json_decode($genre, true);
        }catch (VODRequestApiException $exception)
        {
            $log = [
                'message' => $exception->getMessage()
            ];
            Log::writeErrorLog($log);
            $this->setToRedis('genre','none', 60);
            return [];
        }


    }

    /**
     * @return false|string
     */
    private function loadFromRedis($app)
    {
        $redisKey = self::loadRedisKey($app);
        $data =$this->redis->get($redisKey);
        return $data ? $data : false;
    }

    /**
     * @param $data
     * @param int $timeout
     */
    private function setToRedis($app, $data, $timeout = 0)
    {
        $key = self::loadRedisKey($app);
        if ($timeout == 0)
            $this->redis->set($key,$data);
        else
            $this->redis->set($key,$data, $timeout);
    }

    /**
     * @param $app
     * @return string
     */
    private static function loadRedisKey($app)
    {
        switch($app)
        {
            case 'language':
                return 'vod_language_list';
            case 'genre':
                return 'vod_genre_list';
            default:
                return 'none';
        }
    }

    /**
     * @return string
     * @throws VODRequestApiException
     */
    private function loadLanguageFromAPI()
    {
        $headers = array('Content-Type' => 'application/json');
        $requestResponse = \Requests::get(VOD_API . 'online-tv/language', $headers);

        if (!in_array($requestResponse->status_code, array(200))) {
            throw new VODRequestApiException("could not get languages from api {$requestResponse->status_code} ",'fail to load languages');
        }
        return $requestResponse->body;
    }

    /**
     * @return string
     * @throws VODRequestApiException
     */
    private function loadGenreFromAPI()
    {
        $headers = array('Content-Type' => 'application/json');
        $requestResponse = \Requests::get(VOD_API . 'online-tv/genre', $headers);

        if (!in_array($requestResponse->status_code, array(200))) {
            throw new VODRequestApiException("could not get genre from api {$requestResponse->status_code} ",'fail to load genre');
        }
        return $requestResponse->body;
    }


    /**
     * @return UserInfo
     */
    public function getUserInfo()
    {
        return $this->userInfo;
    }


    /**
     * @return User
     */
    public function getUserAccess()
    {
        return $this->userAccess;
    }


    /**
     * @return \Redis
     */
    public function getRedis()
    {
        return $this->redis;
    }

    /**
     * @param $type
     * @return array
     */
    public function getVodUri($type)
    {
        $userType = FREE_USER_KEY_TO_API;
        if (get_class($this->getUserAccess()) == 'Account\UserAccess' )
            $userType = FULL_USER_KEY_TO_API;


        $vodRequest = new VodRequest($this->getUserInfo()->getCountryConfig());

        $vodRequestParam = [
            'time'      => time(),
            'macAddress'=> $this->getUserInfo()->getMacAddress(),
            'limit'     => 0,
            'offset'    => 0,
            'userType'  => $userType,
            'platform'  => $this->getUserInfo()->getRequestApplicationName(),
        ];

        $vodRequest->setParams($vodRequestParam);

        if ($type == 'series')
        {
            $series = [
                'series-home'    => [
                    ['name' => 'your-list', 'link'         => Series::getSeriesLinkHome($vodRequest,false,false,true)],
                    ['name' => 'recent-series', 'link'     => Series::getSeriesLinkHome($vodRequest,true)],
                    ['name' => 'popular-series', 'link'    => Series::getSeriesLinkHome($vodRequest,false,true)],
                    ['name' => 'English-list', 'link'      => Series::getSeriesLink($vodRequest, false, false, false, ['language' => 7,'languageName' => 'English Series'],[])],
                    ['name' => 'Arabic-list', 'link'       => Series::getSeriesLink($vodRequest, false, false, false, ['language' => 1,'languageName' => 'Arabic Series'],[])],
                    ['name' => 'Action' , 'link'           => Series::getSeriesLink($vodRequest, false, false, false, ['language' => 7,'languageName' => 'English Series'],['genre' => 1,'genreName' => 'Action Series'])],
                    ['name' => 'Animation' , 'link'        => Series::getSeriesLink($vodRequest, false, false, false, ['language' => 7,'languageName' => 'English Series'],['genre' => 15,'genreName' => 'Animation Series'])],
                    ['name' => 'Family' , 'link'           => Series::getSeriesLink($vodRequest, false, false, false, ['language' => 7,'languageName' => 'English Series'],['genre' => 6,'genreName' => 'For Family'])],
                    ['name' => 'Comedy' , 'link'           => Series::getSeriesLink($vodRequest, false, false, false, ['language' => 7,'languageName' => 'English Series'],['genre' => 12,'genreName' => 'Comedy Series'])],
                    ['name' => 'Horror' , 'link'           => Series::getSeriesLink($vodRequest, false, false, false, ['language' => 7,'languageName' => 'English Series'],['genre' => 11,'genreName' => 'Horor Series'])],
                    ['name' => 'Crime' , 'link'            => Series::getSeriesLink($vodRequest, false, false, false, ['language' => 7,'languageName' => 'English Series'],['genre' => 10,'genreName' => 'Crime and Thriller'])],                ],
                'series-list'           => \Util\LinkHelper::GetHomePage( Series::getUrlPath(),$vodRequest),

            ];

            return $series;
        }
        else
        {
            $movie = [
                'movie-home'    => [
                    ['name' => 'your-list', 'link'          => Movie::getMovieLinkHome($vodRequest,false, false, true)],
                    ['name' => 'recent-movies', 'link'      => Movie::getMovieLinkHome($vodRequest,true)],
                    ['name' => 'popular-movies', 'link'     => Movie::getMovieLinkHome($vodRequest, false, true)],
                    ['name' => 'English-list', 'link'       => Movie::getMovieLink($vodRequest, false, false, false, ['language' => 7,'languageName' => 'English Movies'],[])],
                    ['name' => 'French-list', 'link'       => Movie::getMovieLink($vodRequest, false, false, false, ['language' => 8,'languageName' =>  'French Movies'],[])],
                    ['name' => 'Russian-list', 'link'       => Movie::getMovieLink($vodRequest, false, false, false, ['language' => 12,'languageName' => 'Russian Movies'],[])],
                    ['name' => 'Arabic-list', 'link'        => Movie::getMovieLink($vodRequest, false, false, false, ['language' => 1,'languageName' => 'Arabic Movies'],[])],
                    ['name' => 'Action' , 'link'            => Movie::getMovieLink($vodRequest, false, false, false, ['language' => 7,'languageName' => 'English Movies'],['genre' => 1,'genreName' => 'Action Movies'])],
                    ['name' => 'Animation' , 'link'         => Movie::getMovieLink($vodRequest, false, false, false, ['language' => 7,'languageName' => 'English Movies'],['genre' => 15,'genreName' => 'Animation Movies'])],
                    ['name' => 'Family' , 'link'            => Movie::getMovieLink($vodRequest, false, false, false, ['language' => 7,'languageName' => 'English Movies'],['genre' => 6,'genreName' => 'For Family'])],
                    ['name' => 'Comedy' , 'link'            => Movie::getMovieLink($vodRequest, false, false, false, ['language' => 7,'languageName' => 'English Movies'],['genre' => 12,'genreName' => 'Comedy Movies'])],
                    ['name' => 'Horror' , 'link'            => Movie::getMovieLink($vodRequest, false, false, false, ['language' => 7,'languageName' => 'English Movies'],['genre' => 11,'genreName' => 'Horor Movies'])],
                    ['name' => 'Crime' , 'link'             => Movie::getMovieLink($vodRequest, false, false, false, ['language' => 7,'languageName' => 'English Movies'],['genre' => 10,'genreName' => 'Crime and Thriller'])],            ],            'movie-list'            => \Util\LinkHelper::GetHomePage( Movie::getUrlPath(),$vodRequest),
            ];

            return $movie;
        }
    }

}