<?php
if(!defined (IN_APP) ) die("can't access file directly.");

/* request last 10 records
    check last date and make request to grab all after certain date
    api request to insert into remote host
*/


/*
 * Polycom:
 *
 * group 500 (support ssh, with web interface but not cdr records) do things like reboot, stop, etc.. but not cdr
 *
 * resource manager by polycom. get cdr records that are registered in it.
 *
 *
 * DMA, behaves like resource manager. api very much the same.
 * contain cdr records but with extra information. dont use DMA and resource manager at the same time.
 *
 * type: G500, RM, DMA
 *
 *
 *
 * */


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
    $last_join_date = date("Y-m-d", strtotime("-1 days"));

// grab all new records
$from = $last_join_date;
$cdr_rows = []; // to be filled

    $polycom_url = "https://".$config['endpoint']."/".$config['query'];
    $data = array('from-date' => $from);
    if($to !== null)
        $data[]=array('to-date' => $to);
    $polycom_url = $polycom_url."?".http_build_query($data);

    $tmp_file_path = tempnam(sys_get_temp_dir(), "polycom");

 $response = $client->request('GET', $polycom_url, ['verify' => false, 'auth' => [$config['username'], $config['password']] ]);

    $status_code = $response->getStatusCode(); // hopefully 200
    file_put_contents ($tmp_file_path , $response->getBody()->getContents());


    $zipper = new \Chumper\Zipper\Zipper();

    $files_arr = $zipper->make($tmp_file_path)->listFiles();

    $endpoint_cdr_detail_all_csv = "";
    $conflist_detail_all_csv = "";

    foreach ($files_arr as $file) {
        if(strpos($file, "CONFLIST_DETAIL_ALL_CSV") !== false) {
            $conflist_detail_all_csv = $zipper->getFileContent($file);
        }

        if(strpos($file, "ENDPOINT_CDR_DETAIL_ALL_CSV") !== false) {
            $endpoint_cdr_detail_all_csv = $zipper->getFileContent($file);
        }
    }

    if(!$endpoint_cdr_detail_all_csv) {
        $lines = explode("\n", $endpoint_cdr_detail_all_csv);
        foreach ($lines as $index => $line) {
            if($index !=0) {
                // skip first line
                $cdr_row = [];

                $cdr_line= str_getcsv($line);

                if(is_array($cdr_line) && count($cdr_line) >=27) {
                    $cdr_row['name'] =  $cdr_line[0]; // ClareyMcKayRP-Desktop
                    $cdr_row['serial_number'] = $cdr_line[1]; //uuid or some string id
                    $cdr_row['start_date'] = $cdr_line[2]; //mm-dd-YYYY
                    $cdr_row['start_time'] = $cdr_line[3]; //1:25 PM
                    $cdr_row['end_date'] = $cdr_line[4];
                    $cdr_row['end_time'] = $cdr_line[5];
                    $cdr_row['call_duration'] = $cdr_line[6]; //00:02:43
                    // account number always missing
                    $cdr_row['remote_system_name'] = $cdr_line[8]; // long string
                    $cdr_row['call_number_1'] = $cdr_line[9];
                    $cdr_row['transport_type'] = $cdr_line[11]; // SIP H_323
                    $cdr_row['call_rate'] = $cdr_line[12]; // number
                    $cdr_row['call_direction'] = $cdr_line[14]; // OUTGOING INCOMING
                    $cdr_row['call_id'] = $cdr_line[16]; // number
                    $cdr_row['endpoint_transport_address'] = $cdr_line[21]; // email or id or username@host or ip
                    $cdr_row['audio_protocol_tx'] = $cdr_line[22];
                    $cdr_row['audio_protocol_rx'] = $cdr_line[23];
                    $cdr_row['video_protocol_tx'] = $cdr_line[24];
                    $cdr_row['video_protocol_rx'] = $cdr_line[25];
                    $cdr_row['video_format_tx'] = $cdr_line[26]; // resolution
                    $cdr_row['video_format_rx'] = $cdr_line[27]; // resolution
                    $cdr_rows[]= $cdr_row;
                }
            }
        }
    }



$response = $client->request("get", REMOTE_API."/records/insertRecords", ['json' => [
    "key" => KEY,
    "proxy_id" => PROXY_ID,
    "endpoint" => $config['endpoint'],
    "records" => $cdr_row,
]]);

echo $response;