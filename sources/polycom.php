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


function polycom ($config) {
// api call to grab last call
    $client = new GuzzleHttp\Client(['defaults' => ['verify' => false]]);
    $response = $client->request("GET", REMOTE_API."/records/getRecords", [
        "headers" => $config['headers'], "query" => ["key" => KEY,  "proxy_id" => PROXY_ID,  "endpoint" => $config['endpoint'],

        "limit" => 10,]
    ]);


    $result = $response->getBody()->getContents();

    echo substr($result, 0, 100) ;



    $result = json_decode ($result);

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

// if something is wrong
    if($last_join_date == false) {
        $go_back_days = isset($config['go_back_days']) ? $config['go_back_days'] : 1 ;
        $last_join_date = date("Y-m-d H:i:s", strtotime("-".$go_back_days." days"));
    }




// grab all new records
    $from = date("Y-m-d", strtotime($last_join_date));
    $to = null;
    $cdr_rows = []; // to be filled

    echo $polycom_url = "https://".$config['endpoint'].":".$config['port']."/".$config['query'];
    $data = array('from-date' => $from);
    if($to !== null)
        $data[]=array('to-date' => $to);
    $polycom_url = $polycom_url."?".http_build_query($data);

    $tmp_file_path = tempnam(sys_get_temp_dir(), "polycom");


    $response = $client->request('GET', $polycom_url, ['verify' => false, 'auth' => [$config['username'], $config['password']] ]);

    $status_code = $response->getStatusCode(); // hopefully 200


    if($status_code != 200) {
        return "Error Code: ".$status_code . " - ".$response->getBody()->getContents();
    }


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
    if(strlen($endpoint_cdr_detail_all_csv) > 100) {
        $lines = explode("\n", $endpoint_cdr_detail_all_csv);

        foreach ($lines as $index => $line) {
            if($index !=0) {

                // skip first line
                $cdr_row = [];

                $cdr_line= str_getcsv($line);



//echo $csv_date_formated->format('Y-m-d H:i:s');

//die();
                @$last_join_date_formated  = \DateTime::createFromFormat('Y-m-d H:i:s', $last_join_date);
                @$csv_date_formated  = \DateTime::createFromFormat('m-d-Y g:i A', $cdr_line[2]." ". $cdr_line[3]);

                if(is_array($cdr_line) && count($cdr_line) >=27 && $last_join_date_formated < $csv_date_formated) {

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

        if(count($cdr_rows) > 0) {
            echo "submitting ".count($cdr_rows)." to idsuite\n";
            $response = $client->request("POST", REMOTE_API."/records/insertRecords", [
                "headers" => $config['headers'],
                'form_params' => [
                    "key" => KEY,  "proxy_id" => PROXY_ID,  "endpoint" => $config['endpoint'],
                "records" => json_encode($cdr_rows),
            ]]);

            $result = $response->getBody()->getContents();

            echo substr($result, 0, 100) ;

        }else{
            echo $config['endpoint']." nothing to submit.\n";
        }


    }else{
        echo $config['endpoint']." returned empty.\n";
    }




}

