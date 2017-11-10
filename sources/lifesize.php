<?php
if(!defined (IN_APP) ) die("can't access file directly.");



function lifesize ($config) {


// api call to grab last call
$client = new GuzzleHttp\Client(['defaults' => ['verify' => false]]);
$response = $client->request("GET", REMOTE_API."/records/getRecords", [
    "headers" => $config['headers'], "key" => KEY,  "proxy_id" => PROXY_ID,  "endpoint" => $config['endpoint'],

    "limit" => 10,
]);

$result = json_decode($response->getBody()->getContents());

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
    $last_join_date = date("Y-m-d H:i:s", strtotime("-30 days"));

//////////////////////////////////////////
///  lifesize fetch here
//////////////////////////////////////////

$config['port'] = (isset($config['port']) ? $config['port']: 22);

$cdr_records =  cdr_get_lifesize($config['endpoint'], $config['port'], $config['type'], $config['username'], $config['password']);


if($config['type'] == "icon")
    $cdr_records = icon_xml_to_arr($cdr_records, $last_join_date );
elseif($config['type'] == "room")
    $cdr_records = room_csv_to_arr($cdr_records, $last_join_date );

//var_dump($cdr_records);




/// ////////////////////////////////
///
///
///

    if(count($cdr_records) > 0) {
        echo "submitting ".count($cdr_records)." records\n";

        $response = $client->request("POST", REMOTE_API."/records/insertRecords", [
            "headers" => $config['headers'], "key" => KEY,  "proxy_id" => PROXY_ID,  "endpoint" => $config['endpoint'],
            "records" => json_encode($cdr_records),
        ]);

        $result = json_decode($response->getBody()->getContents());

        echo $result;

    }else {
        echo "nothing to submit\n";
    }



/*********end ***************/


}


function cdr_get_lifesize($host_address, $endpoint_port, $node_type, $username, $password) {

    // Connect via SSH
    try{

        $ssh = ssh2_connect($host_address, $endpoint_port);
        ssh2_auth_password($ssh, $username, $password);

    } catch(Exception $e){
        return false;
    }

    // LifeSize Room Nodes
    if($node_type == 'room'){
        // $command = 'status call history -X -f -D '. CDR_DELIMETER // increased wait time for response to 3 seconds allowing -X to function properly, could potentially break with larger responses from Room system
        $command = 'status call history -X -D ;';
        ini_set("default_socket_timeout", 15);
        $stream =  ssh2_exec ($ssh, $command);
        stream_set_blocking($stream, true);
        $stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
        $result = stream_get_contents($stream_out);

        return $result;


    }
    // LifeSize Icon Nodes
    elseif($node_type == 'icon') {

        $result = array();

        // Generate CDR report
        $command = 'CDR getAllCdrs';
        $result[1] = ssh2_exec($ssh, $command);

        // Create file
        $command = 'CDR getFileLocation';
        $stream = ssh2_exec($ssh, $command);
        stream_set_blocking($stream, true);
        $stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
        $tmp_res = stream_get_contents($stream_out);
        $tmp_res=json_decode($tmp_res);
        $file_path = $tmp_res->filePath;

        $result[2] = $tmp_res;

        // Open file for reading in /tmp/download.  This returns the port number and filesize of our results.
        $command =  'Data openFile '.$file_path.',0';
        $stream = ssh2_exec($ssh, $command);
        stream_set_blocking($stream, true);
        $stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
        $tmp_res = stream_get_contents($stream_out);

        // Parse results thus far. Get port number & file size
        $info = json_decode($tmp_res);

        $port = $info->_rv;
        $filesize = $info->filesize;
        $result[3] = $tmp_res;

        // Read file contents from remote server
        if(isset($filesize) && $filesize > 0 && isset($port) && $port > 0) {

            //ssh2_scp_recv($ssh, "/files/temp.tgz", "localfile_tmp.tgz");

            $sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
            socket_connect($sock, $host_address, $port);
            socket_shutdown ($sock,1);

            $out = null;
            while ($out_l = socket_read($sock, 2048)) {
                $out.= $out_l;
            }

            //  socket_send($sock,$bdata,strlen($bdata),MSG_EOR);

            socket_close($sock);

             $tmp_file_path = tempnam(sys_get_temp_dir(), "lifesize");
            file_put_contents ($tmp_file_path , $out);

            $archive = new PharData($tmp_file_path);

            $files = $archive->getFilename();
            $tmp_file_path = sys_get_temp_dir();

            $archive->extractTo($tmp_file_path,null,true);
            $xml_contents = file_get_contents($tmp_file_path.'/'."cdr.xml");


            //exec("netcat ". $host_address . " " . $port . " > ". APP_DIR . "/files/temp.tgz", $output);

            $result[0] = $xml_contents;


            return $xml_contents;


        } else {
            $result[4] = "Filesize or port not defined.";
        }

        ssh2_exec($ssh, 'exit');

        return $result;

    }

}

function room_csv_to_arr($csv_string , $from_date=0)
{

// example: 4738;2;IDS_Noc;172.31.20.8;Conference Room2;172.31.20.13;172.31.20.13;2017-11-03 09:55:04;01:49:57;Out

    $lines = explode("\n", $csv_string);
    $arr = [];


    foreach ($lines as $line) {
        $row = str_getcsv($line, ";");

            if (isset($row[9]) && validateDate($row[7]) && strtotime($from_date) < strtotime($row[7] )) {

                $r = array();

                $r['local_id'] = $row[0];
                $r['conference_id'] = $row[1];
                $r['local_name'] = $row[2];
                $r['local_number'] = $row[3];
                $r['remote_name'] = $row[4];
                $r['remote_number'] = $row[5];
                $r['dialed_digits'] = $row[6];
                $r['protocol'] = null;
                $r['direction'] = $row[9];
                $r['duration'] = $row[8];
                $r['start_time'] = $row[7];
                $r['end_time'] = add_date($row[7], $row[8]);
                $arr[] = $r;

        }



    }


    return $arr;


}



function icon_xml_to_arr($xml_string, $from_date) {

    //define("CDR_DELIMETER", "|");
    $arr = [];
    $xml     = simplexml_load_string($xml_string);

    $record_array = xml2array($xml_string);


    $call_count   = count($xml->call);


    foreach ($record_array['call'] as $index=>$call) {
        $call = $call['@attributes'];

        $r = array();
        if(strtotime($from_date) < strtotime($call['starttime'])) {

            if($index ==$call_count) break; // ignore last line


            if(isset($call['localname']) && isset($call['endtime']) && isset($call['remoteip'])) {
                // Just for the sake of consistency lets name these the same as the feilds from other devices
                $r['local_id']      = $call['id'];
                $r['conference_id'] = $call['callid'];
                $r['local_name']    = $call['localname'];
                $r['local_number']  = $call['localnumber'];
                $r['remote_name']   = $call['remotename'];
                $r['remote_number'] = $call['remoteip'];
                $r['dialed_digits'] = $call['dialdigits'];
                $r['protocol']      = $call['protocol'];
                $r['direction']     = $call['direction'];
                $r['duration']      = $call['duration'];
                $r['start_time']    = date("Y-m-d H:i:s", strtotime($call['starttime']));
                $r['end_time']      = date("Y-m-d H:i:s", strtotime($call['endtime']));
                $arr[]=$r;
            }
        }


    }

    return $arr;

}
