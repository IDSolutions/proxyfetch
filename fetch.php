<?php
DEFINE("IN_APP", "true");
DEFINE('REMOTE_API', 'http://localhost:8000/api');
DEFINE('API_AUTH', "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImp0aSI6IjcxMzQ0MjZhMzYxNTgzZTM2YTg0YjU5YjAzOWU3M2VjM2ExMjNjNGJlYThhM2M1NjY4YjQyZDUxYmU1MDZlYjUzMWM2MzQyOThiN2RjN2IwIn0.eyJhdWQiOiIxIiwianRpIjoiNzEzNDQyNmEzNjE1ODNlMzZhODRiNTliMDM5ZTczZWMzYTEyM2M0YmVhOGEzYzU2NjhiNDJkNTFiZTUwNmViNTMxYzYzNDI5OGI3ZGM3YjAiLCJpYXQiOjE1MTAyNjA5ODYsIm5iZiI6MTUxMDI2MDk4NiwiZXhwIjoxNTQxNzk2OTg2LCJzdWIiOiJVU1I1OWZiNmNhNTNlNDEzIiwic2NvcGVzIjpbXX0.2wyZCDWkTjo56JPKcu-ikTPtlsDUT1L7s3jvBvfWsd82foAUZqFoHS1LYAR6-0M5vrM6qZBBhdnWFdG7mGeRqaFRq3GN5G3okFtzSD5ZslhxbvM1vWGLJ1hjHDdGAXBbQmUzBpWKS0oBhPeyZQ8ZHjniFe_aqpIpdVDutyhQChBB9YoBaCfNiS4ItZsa1cnwX7h0-1klarRTK7jTBbNIYtTNcYlsg9TPlAaRjuPATYeN3Db0-t7wB64Az0JXjutuEaj-46JYjT8RUMnMjZqC3-CX2PwTXtEIidoTKtUyctURa2qNI_CbVLMIb11vPMnqLqdufRxyDR6VcOQ2iGwcwSU_rIZ2qU6V4QiFwJmV6SqoX4fd-fezijznNdyT_BqsgwjmdrBOFE8-ZQdYdQUx743SZq3PIh3DjC_Z1HgP4tbuCz6PIhtPPOLNvjUooAghEmmN2bQ2nN-Z_cyWSYTB-9CP7yR9aK1igUDCSno7PTfg5qA9aaZVJmBazBP_nAB7cpNvER6zXuL0Jz3y2kZcdzMyGpzwX9CmP3QSluDkYBO9caXrhMGpa7EadHuwi2dIP5ncjXrj15JiLOzGVkaQr2VJ_zbTZl9xy9yq2mw6zVzXGCu0Z1n6jf1s3Rxzo2q9Gdu0-A34yqMyqDTxGSblPaSD9YKzs4rkc4rm2yx_r4k");

/*are you brave enough to go beyond this line? */

require_once __DIR__."/vendor/autoload.php";
require_once __DIR__."/includes/lib.php";
require_once __DIR__."/cred.php";

require_once ("sources/vidyo.php");
require_once ("sources/lifesize.php");
require_once ("sources/polycom.php");

use \GuzzleHttp as GuzzleHttp;

ini_set('max_execution_time', 3600);
ini_set('memory_limit', "3072M");
ini_set('upload_max_filesize', "3072M");
ini_set('post_max_size', "3072M");

// authenticate
$credentials = base64_encode('bbriggs@e-idsolutions.com:ids_14701');


$headers = [
  //  'Authorization' => 'Basic ' . $credentials,
    "authorization" => "Bearer " . API_AUTH,
    'Accept'        => 'application/json',
];


foreach (SOURCES as $index=>$source) {
    echo "\n\nFetching ". $source." (".(ENDPOINTS[$index]).")\n-----------------------\n";

    $config = array("endpoint" => ENDPOINTS[$index], "headers" => $headers);


    try {



    if($index ==1) // 2, 3, ...
    switch (strtolower($source)) {
        case 'vidyo':

            $config = array_merge($config, array(
                'limit' => 100,
                "database" => "portal2",
                "table" => "ConferenceCall2",
            ), ENDPOINTS_DETAILS[$index]);

            echo vidyo($config);
            break;

        case 'polycom':
            $config = array_merge($config, array(
                'limit' => 10000,
                'go_back_days' => 2920, // if we didnt find last record in idsuite go back x days
                "port" => "8443",
                "query" => "api/rest/billing"
            ), ENDPOINTS_DETAILS[$index]);

            polycom($config);
            break;

        case 'lifesize':
            $config = array_merge($config, ENDPOINTS_DETAILS[$index]);
            lifesize($config);
            break;
    }

    }catch (Exception $e) {

        echo 'Error: ' .$e->getMessage();

    }

} // end foreach


die("****** END OF SCRIPT *******");