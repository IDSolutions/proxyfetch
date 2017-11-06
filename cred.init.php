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

DEFINE('SOURCES', array(
    'Vidyo',
    'Polycom',
    'LifeSize'));

DEFINE('ENDPOINTS', array(
        'portal.idsflame.com',
        '10.0.0.10',
        '10.0.0.11'));

DEFINE('ENDPOINTS_DETAILS', array(
    array('username'=> 'root', 'password' => 'password'),
    array('type' =>'G500', 'username'=> 'IDS\username', 'password' => 'pass'),
    array('username'=> 'admin', 'password'=>'password', 'port'=>'22', 'type' => 'icon'))); // type: icon, room


