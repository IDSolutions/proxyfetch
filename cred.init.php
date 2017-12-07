<?php
/**
 * Created by PhpStorm.
 * User: fbreidi
 * Date: 10/19/2017
 * Time: 12:13 PM
 */

/* Secure file*/
if(!defined (IN_APP) ) die("can't access file directly.");


/* Provided by ID Solutions*/
DEFINE('KEY', 'some_long_key_provided_by_idsolutions');
DEFINE('PROXY_ID', 'PRX45345344453');


/* Endpoint Details */
DEFINE('ENDPOINTS', array(
    array("type"  => "Vidyo", "address"=> 'subdomain.hostname.com',
        'details' => array('username'=> 'someuser', 'password' => 'passkey')),
    array("type"  => "Polycom", "address"=> '192.168.0.10',
        'details' => array('type' =>'G500', 'username'=> 'admin', 'password' => 'passkey')),
    array("type"  => "LifeSize", "address"=> '192.168.0.10',
        'details' => array('username'=> 'support', 'password'=>'passkey', 'port'=>'22', 'type' => 'icon')),
    array("type"  => "LifeSize", "address"=> '192.168.0.10',
        'details' => array('username'=> 'auto', 'password'=>'passkey', 'port'=>'22', 'type' => 'room')),

));
