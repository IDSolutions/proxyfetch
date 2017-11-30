<?php
DEFINE("IN_APP", "true");


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



  //  if($index ==0) // 2, 3, ...
    switch (strtolower($source)) {

        case 'vidyo':

            $config = array_merge($config, array(
                'limit' => 2000,
                'go_back_days' => 2920,
                "database" => "portal2",
                "table" => "ConferenceCall2",
            ), ENDPOINTS_DETAILS[$index]);

            echo vidyo($config);
            break;

        case 'polycom':

            $config = array_merge($config, array(
                'limit' => 2000,
                'go_back_days' => 2920, // if we didnt find last record in idsuite go back x days
                "port" => "8443",
                "query" => "api/rest/billing"
            ), ENDPOINTS_DETAILS[$index]);

            echo polycom($config);
            break;

        case 'lifesize':
            $config = array_merge($config, ENDPOINTS_DETAILS[$index]);
            echo lifesize($config);
            break;


    }

    }catch (Exception $e) {

        echo 'Error: ' .$e->getMessage();

    }

} // end foreach


die("****** END OF SCRIPT *******");