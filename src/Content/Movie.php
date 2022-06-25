<?php


namespace Content;


use Request\VodRequest;
use Util\LinkHelper;

class Movie
{
    const MOVIE_HOME_LIMIT = 20;
    /**
     * @param VodRequest $vodRequest
     * @param false $recent
     * @param false $isPopular
     * @param false $favorite
     * @return string
     */
    public static function getMovieLinkHome(VodRequest $vodRequest, $recent = false, $isPopular = false, $favorite = false)
    {
        $params = [
            'recent'    => $recent ? 'recent' : '',
            'popular'   => $isPopular ? 'popular' : '',
            'favorite'  => $favorite ? 'favorite' : '',
        ];
        $vodRequest->setLimit(Movie::MOVIE_HOME_LIMIT);
        $vodRequest->setRecent($params);
        $vodRequest->setPopular($params);
        $vodRequest->setFavorite($params);
        $movieLink = \Util\LinkHelper::GetHomePage(Movie::getUrlPath(), $vodRequest);
        return $movieLink ;
    }


    /**
     * @param VodRequest $vodRequest
     * @param false $recent
     * @param false $isPopular
     * @param false $favorite
     * @param array $language
     * @param array $genre
     * @return string
     */
    public static function getMovieLink(VodRequest $vodRequest, $recent = false, $isPopular = false, $favorite = false, $language = array(),$genre = array())
    {
        $params = [
            'recent'    => $recent ? 'recent' : '',
            'popular'   => $isPopular ? 'popular' : '',
            'favorite'  => $favorite ? 'favorite' : '',
        ];
        $vodRequest->setLimit(Movie::MOVIE_HOME_LIMIT);
        $vodRequest->setRecent($params);
        $vodRequest->setPopular($params);
        $vodRequest->setFavorite($params);
        $additional = '';
        if (!empty($language))
        {
            $vodRequest->setLanguage($language);
            $vodRequest->setLanguageName($language);
            $additional = '&languageName=' . $language['languageName'];
        }

        if (!empty($genre))
        {
            $vodRequest->setGenre($genre);
            $vodRequest->setGenreName($genre);
            $additional = '&genreName=' . $genre['genreName'];
        }

        $movieLink = LinkHelper::generateNextPageLink(Movie::getUrlPath(), $vodRequest);
        $additional .= '&version=' . $vodRequest->getVersion();

        return $movieLink . $additional;
    }

    /**
     * @return string
     */
    public static function getUrlPath()
    {
        return 'movie';
    }
}