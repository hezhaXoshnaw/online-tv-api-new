<?php

define('NOT_DIRECT_ACCESS',true);

define('DEBUG_FILE',"/var/www/html/current/online_tv/online_tv_api_new/log/");
define('LOG_FILE',"/var/www/html/current/online_tv/online_tv_api_new/log/");


define('DB_TYPE', 'mysql');
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_NAME', 'online_tv_login');
define('DB_PASS', 'snap$hot');

//define('DB_NAME', 'online_tv_login');
//define('DB_PASS', '2xmwrk5trZJ6JCPqhg7TRmKtNVNrKcPJ');

define('DATABASE_ENCRYPTION_KEY',"Ah6y4?E2\0\0\0\0\0\0\0\0");

define('REDIS_HOST', 'localhost');
define('REDIS_PORT', '6379');
define('CACHE_DURATION', '21600');

define('REVALIDATE_MESSAGE','Please wait revalidating your account');



//channel list const
define('TOKEN_PERIOD_IN_SECOND', 2629746);
define('DEFAULT_CHANNEL_LIST_ID', 15);
define('CDN_77_KEY', 'S2VgEKjgYqPBDeb25C');
define('FLUSONIC_KEY', 'LDNfp5TP2rKHc2US');

define('SUB_LANGUAGE_REDIS_KEY', 'sub_language_data_v4');

define('VOD_ACCESS_DELETE',1 );
define('VOD_ACCESS_SKIP',0 );
# http://109.236.80.73/online_4zTdZfggj6C2CsqZ/

define('DEBUG', 'TdT4ED2Mf8tP8z22');
//K5mwkeeRG3TgdVmW

define('SAN_PLUS_API_KEY','3aTyqBcRUSSX7qZ2vC3gpg48GbTcHLCz');
define('IMAX_API_KEY','NfZcPGAKyzvU4yTAUqn3jQmwCsqePTPg');
define('GEANT_API_KEY','xFTXsE36Sgj4gWKA4p2HBpKehGyfU5fR');
define('DRAGON_API_KEY','VcYsaHRrrCjrwBh6Ar7R2SFpMrehTj26');
define('IOS_API_KEY','vfs5qST7bxudp8FeDsqMdRDEK3fNsQBC');
define('ANDROID_API_KEY','g4g2szwNK85s3Vy75e25QvHdPzXh7d7N');
define('ANDROID_PUBLIC_API_KEY','63eM2Zj4B8hsUWTUP7MyHpQDR9MGpbj2');
define('ANDROID_O3_API_KEY','dQP5LuaDWZxnwjUqvwMK9fvn55LHBgNf');
define('VOXA_API_KEY','nTv5XxfaukXjRMx8D9vNM48TujFz72GB');
define('PRO_OTT_API_KEY','4F7KEwMrNDEDLCd9JuScJSUqSZeGkQ5A');
define('ANDROID_S7_V2_API_KEY', 'nrLGkSfcXKJgEmWBVZUYCMDbuzARxqHQ');
define('ANDROID_TV_API_KEY', 'BKZ2RqPaTA8SDNfDCgC5HYQgTDfbf2tq');


define('ONLINE_TV_APPLICATION_ID',1);
define('VOXA_TV_APPLICATION_ID',8);
define('MTV_APPLICATION_ID',7);
define('DRAGON_TV_APPLICATION_ID',4);
define('IOS_APPLICATION_ID',6);
define('ANDROID_S7_APPLICATION_ID',9);
define('O3_TV_APPLICATION_ID',5);
define('PRO_OTT_APPLICATION_ID',10);



define('AES_KEY_LANGUAGE','RuGr9F6VkQkPDVYt');
define('AES_IV_LANGUAGE','jaXas48467fEUp2q');



define('GEO_IP_FILE',"/usr/local/GeoIP/GeoIP2-Country.mmdb");
define('GEO_IP_ISP_FILE',"/usr/local/GeoIP/GeoIP2-Country.mmdb");
define('SKIP_IP_TOKEN_ISP_FILE',"/var/www/html/current/online_tv/online_tv_api_old/isp/isp.json");
//define('DOWNLOAD_URL','http://www.logapi.site/get-download-link/v2/');
define('DOWNLOAD_URL','http://localhost/current/online_tv/get-download-link/public/');

//define('STREAM_API_URL','http://localhost:5600/stream-api-v3/api/');
define('STREAM_API_URL','http://stream-v2.api-admin-panel.com:5600/stream-api-v3/api/');
define('STREAM_API_TOKEN','xtSBPYjHtL4crfW7pfnEmtgLHvuxjC6M');


define('MTV_AND_WWTV_V3_API_KEY', 'hrPySZLwUMvPLnaAPALDe8CfDUbKmYNH');
define('ONTV_PUBLIC_V3_API_KEY', 'TxfVFS6d6m9xdxaP2sSswRL55CNHHFtP');



define('VOD_API',"http://www.logapi.site/get-download-link/v1/");
define('FAVORITE_URL','http://www.logapi.site/get-download-link/v1/favorite/');



define('FREE_USER_KEY_TO_API', 1);
define('FULL_USER_KEY_TO_API', 3);



// in this api only the free login get the free user type otherwise its 3
define('FREE_USER_TYPE', 1);
define('NORMAL_USER_TYPE', 2);
define('FULL_USER_TYPE', 3);




define('AES_KEY_GENERATED_LINK','RuGr9F6VkQkPDVYt');
define('AES_IV_GENERATED_LINK','jaXas48467fEUp2q');

define('ISTAR_NEW_APPLICATION_API_KEY','GR3nJpdaNbnmucpmEJtqwPU93C5hBEde');
define('NEW_DRAGON_API_KEY','3SRPuCyR85YP8y6uUgfSZ4dbRuzGeKnJ');


define('GLOBAL_APPLICATION_API_KEY','HjtCs6FKWKm97zKgq5jmXSVuK8pTmQpj');