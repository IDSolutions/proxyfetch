<?php
DEFINE("IN_APP", "true");
DEFINE('REMOTE_API', 'http://localhost:8000/api');


ini_set('max_execution_time', 3600);
require_once __DIR__."/vendor/autoload.php";
require_once __DIR__."/includes/lib.php";
require_once __DIR__."/cred.php";

use \GuzzleHttp as GuzzleHttp;
foreach (SOURCES as $index=>$source) {

    if($index ==1) {
    $config = array("endpoint" => ENDPOINTS[$index]);

    switch (strtolower($source)) {
        case 'vidyo':

            $config = array_merge($config, array(
                'limit' => 1000,
                "database" => "portal2",
                "table" => "ConferenceCall2",
            ), ENDPOINTS_DETAILS[$index]);

            require ("sources/vidyo.php");
            break;

        case 'polycom':
            $config = array_merge($config, array(
                'limit' => 1000,
                "port" => "8443",
                "query" => "api/rest/billing"
            ), ENDPOINTS_DETAILS[$index]);

            require ("sources/polycom.php");
            break;

        case 'lifesize':
            $config = array_merge($config, ENDPOINTS_DETAILS[$index]);
            require ("sources/lifesize.php");
            break;
    }
        die("first run");

    } // if index ==2


}


die("end of script");