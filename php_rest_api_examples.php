<?php
/*****************************
 *
 * RouterOS PHP REST API examples 
 * Author: Svetlin Simeonov <svet@billsoft.eu>
 * 
 * https://help.mikrotik.com/docs/display/ROS/REST+API
 *
 ******************************/
 
include 'bs_routeros_rest_api.php';

#define the mikrotik credentials array
$mkt['rtr_ver']=7;
$mkt['rtr_user']='api_user';
$mkt['rtr_pass']='api_pass';
$mkt['rtr_ip']='10.2.4.75';
$mkt['rtr_port']=443;


$mm = new bs_routeros_rest_api($mkt);

#if you want to activate debug mode uncomment bellow
#$res=$mm->debug=1;

# lets connect to router
$res=$mm->connect();

if($res['ok']) {
    echo "INFO - Connected to ".$mm->rtr_ip.', '.$res['data']['name'].' version '.$res['data']['version'];
    
    # lets read router /system/identity string
    $res2=$mm->identity(); 
    
    if($res2['ok']) {
        echo ', identity = '.$res2['identity'];
    }
    
    echo "\n";
    # Now when we have connection, lets try some queries.
    
    echo "\n\n ADD new address list - test_php_list with IP = 192.168.7.2\n";
    $res2=$mm->bs_mkt_rest_api_add('/ip/firewall/address-list',array('list'=>'test_php_list','address'=>'192.168.7.2'));

    if($res2['ok']) { echo " - INFO, show result in data array\n"; print_r($res2['data']); } 
    else { echo " - ERROR: ".$res2['error']."\n"; }
    
    /*
    sleep(2);
    echo "\n\n GET all ip->firewall->addres-list\n";
    $res2=$mm->bs_mkt_rest_api_get('/ip/firewall/address-list');
    if($res2['ok']) { echo " - INFO, show result in data array\n"; print_r($res2['data']); } 
    else { echo " - ERROR: ".$res2['error']."\n"; }
    */
    
    sleep(2);
    
    echo "\n\n Lets get ip->firewall->addres-list but only for list=test_php_list\n";
    $res2=$mm->bs_mkt_rest_api_get('/ip/firewall/address-list?list=test_php_list');
    if($res2['ok']) { echo " - INFO, show result in data array\n"; print_r($res2['data']); } 
    else { echo " - ERROR: ".$res2['error']."\n"; }
    
    $test_php_list=$res2['data'];
        
    sleep(2);
    
    echo "\n\n UPDATE comment on all lines in address-list=test_php_list\n";
    
    foreach($test_php_list as $r) {
        $res2=$mm->bs_mkt_rest_api_upd('/ip/firewall/address-list/'.$r['.id'],array('comment'=>'test_comment_'.date('Y-m-d H:i:s')));
        if($res2['ok']) { echo " - Change done for ROW_ID = ".$r['.id']; } 
        else { echo " - ERROR: ".$res2['error']."\n"; }
    }
    
    sleep(2);
    
    echo "\n\n DELETE ALL rows in address-list=test_php_list\n";
    
    foreach($test_php_list as $r) {
        $res2=$mm->bs_mkt_rest_api_del('/ip/firewall/address-list/'.$r['.id']);
        if($res2['ok']) { echo " - DELETE ROW_ID = ".$r['.id']; } 
        else { echo " - ERROR: ".$res2['error']."\n"; }
    }
    
    echo "\n\n ALL DONE\n";
    
} else {
    echo "ERROR - ".$res['error']."\n";
}

?>