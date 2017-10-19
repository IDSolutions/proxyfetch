<?php
if(!defined (IN_APP) ) die("can't access file directly.");

/* request last 10 records
    check last date and make request to grab all after certain date
    api request to insert into remote host
*/

// api call to grab last call
$client = new GuzzleHttp\Client(['defaults' => ['verify' => false]]);
$response = $client->request("GET", REMOTE_API."/records/getRecords", ['json' => [
    "key" => KEY,
    "proxy_id" => PROXY_ID,
    "endpoint" => $config['endpoint'],
    "limit" => 10,
]]);


$result = json_decode($response->getBody()->getContents());

$counter = 0;
do{
    $last_join_date = $result[$counter]->join_time;

    $date_valid = validateDate($last_join_date);

        $counter ++;

        if($counter >= count($result)) break;
}while(!$date_valid);

// assume if something is wrong
if($last_join_date == false)
    $last_join_date = date("Y-m-d H:i:s", strtotime("-10 days"));

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
echo "submitting ".count($rows)." rows...";
// api call to insert all fetched results


$response = $client->request("GET", REMOTE_API."/records/insertRecords", ['json' => [
    "key" => KEY,
    "proxy_id" => PROXY_ID,
    "endpoint" => $config['endpoint'],
    "records" => json_encode($rows),
]]);

var_dump($response->getBody()->getContents());
$result = json_decode($response->getBody()->getContents());

echo $result;