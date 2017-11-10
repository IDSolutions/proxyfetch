<?php
if(!defined (IN_APP) ) die("can't access file directly.");

/* request last 10 records
    check last date and make request to grab all after certain date
    api request to insert into remote host
*/



function vidyo($config) {

//
//    $client = new GuzzleHttp\Client();
//    $response = $client->request("get", "http://localhost:8000/api/records/getRecords", [
//        "headers" => [
//            'Authorization' => 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImp0aSI6IjcxMzQ0MjZhMzYxNTgzZTM2YTg0YjU5YjAzOWU3M2VjM2ExMjNjNGJlYThhM2M1NjY4YjQyZDUxYmU1MDZlYjUzMWM2MzQyOThiN2RjN2IwIn0.eyJhdWQiOiIxIiwianRpIjoiNzEzNDQyNmEzNjE1ODNlMzZhODRiNTliMDM5ZTczZWMzYTEyM2M0YmVhOGEzYzU2NjhiNDJkNTFiZTUwNmViNTMxYzYzNDI5OGI3ZGM3YjAiLCJpYXQiOjE1MTAyNjA5ODYsIm5iZiI6MTUxMDI2MDk4NiwiZXhwIjoxNTQxNzk2OTg2LCJzdWIiOiJVU1I1OWZiNmNhNTNlNDEzIiwic2NvcGVzIjpbXX0.2wyZCDWkTjo56JPKcu-ikTPtlsDUT1L7s3jvBvfWsd82foAUZqFoHS1LYAR6-0M5vrM6qZBBhdnWFdG7mGeRqaFRq3GN5G3okFtzSD5ZslhxbvM1vWGLJ1hjHDdGAXBbQmUzBpWKS0oBhPeyZQ8ZHjniFe_aqpIpdVDutyhQChBB9YoBaCfNiS4ItZsa1cnwX7h0-1klarRTK7jTBbNIYtTNcYlsg9TPlAaRjuPATYeN3Db0-t7wB64Az0JXjutuEaj-46JYjT8RUMnMjZqC3-CX2PwTXtEIidoTKtUyctURa2qNI_CbVLMIb11vPMnqLqdufRxyDR6VcOQ2iGwcwSU_rIZ2qU6V4QiFwJmV6SqoX4fd-fezijznNdyT_BqsgwjmdrBOFE8-ZQdYdQUx743SZq3PIh3DjC_Z1HgP4tbuCz6PIhtPPOLNvjUooAghEmmN2bQ2nN-Z_cyWSYTB-9CP7yR9aK1igUDCSno7PTfg5qA9aaZVJmBazBP_nAB7cpNvER6zXuL0Jz3y2kZcdzMyGpzwX9CmP3QSluDkYBO9caXrhMGpa7EadHuwi2dIP5ncjXrj15JiLOzGVkaQr2VJ_zbTZl9xy9yq2mw6zVzXGCu0Z1n6jf1s3Rxzo2q9Gdu0-A34yqMyqDTxGSblPaSD9YKzs4rkc4rm2yx_r4k',
//            "accept" =>"application/json"
//
//
//    ]] );
//
//    var_dump($response->getBody()->getContents());
//    die();



    ///
    // api call to grab last call

    $client = new GuzzleHttp\Client(['defaults' => ['verify' => false, ]]);
    $response = $client->request("get", REMOTE_API."/records/getRecords",  [
        "headers" => $config['headers'], "key" => KEY,  "proxy_id" => PROXY_ID,  "endpoint" => $config['endpoint'],
        "limit" => 10,
    ]);



    $result = $response->getBody()->getContents();


    var_dump($result);


//if(!$result) return "couldn't fetch";

    echo substr($result, 0, 100) ;
    $counter = 0;
    if(count($result) == 0)
        $last_join_date = false;
        else
        do{
            $last_join_date = $result[$counter]->join_time;

            $date_valid = validateDate($last_join_date);

                $counter ++;

                if($counter >= count($result)) break;
        }while(!$date_valid);

    // assume if something is wrong
    if($last_join_date == false)
        $last_join_date = date("Y-m-d H:i:s", strtotime("-2920 days"));


    // grab all calls after that date
    $dsn = "mysql:host=".$config['endpoint'].";dbname=".$config['database'].";charset=utf8";
    $opt = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, $config['username'], $config['password'], $opt);


    $query = "SELECT * FROM ".$config['table']." WHERE CallState='COMPLETED' AND JoinTime > '".$last_join_date."' ORDER BY CallID ASC LIMIT ".$config['limit'];
    $stmt = $pdo->query($query);
    $rows = $stmt->fetchAll();


    // api call to insert all fetched results
if(count($rows) > 0) {

    echo "submitting ".count($rows)." records\n";

    $response = $client->request("POST", REMOTE_API."/records/insertRecords", [
        "headers" => $config['headers'], "key" => KEY,  "proxy_id" => PROXY_ID,  "endpoint" => $config['endpoint'],
        "records" => json_encode($rows),
    ]);

    //var_dump($response->getBody()->getContents());
    $result = $response->getBody()->getContents();

    echo "IDSuite: ".    substr($result, 0, 100) ;
}else {
    echo "nothing to submit\n";
}



}
