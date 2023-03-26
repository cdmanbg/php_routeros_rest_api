<?php
/*****************************
 *
 * RouterOS PHP REST API class v1.0 / 2023-03-26
 * Author: Svetlin Simeonov <svet@billsoft.eu>
 * Contributors: 
 *
 * https://help.mikrotik.com/docs/display/ROS/REST+API
 *
 ******************************/

#class bs_routeros_rest_api

class bs_routeros_rest_api
{
    var $debug     = false; //  Show debug information
    var $connected = false; //  Connection state 0 = offline, 1=online
    var $port      = 443;  //  Port to connect to (default 443 for https)
    var $timeout   = 3;     //  Connection attempt timeout and data read timeout
    var $min_ver   = 7.7; // mikrotik minimal version support, API is available from  v7.1beta4, but 7.7 is first really stable version.
    var $identity   = 'not_set'; // default mikrotik identity string
    //var $attempts  = 5;     //  Connection attempt count
    //var $delay     = 3;     //  Delay between connection attempts in seconds

    var $error;          // error string
    var $cmd;          //  command for mikrotik
    var $data = array(); // return data array
    var $mkt = array();  // mikrotik login info
    

    
    // Asign variables from class input
    function __construct( $mkt ) {
        $this->rtr_user=$mkt['rtr_user'];
        $this->rtr_pass=$mkt['rtr_pass'];
        $this->rtr_ip=$mkt['rtr_ip'];
        $this->rtr_port=$mkt['rtr_port'];
        $this->rtr_ver=$mkt['rtr_ver'];
    }
          
    // Prepare the option array for cURL                         
    private function options($cmd) {
        
        $url='https://'.$this->rtr_ip.'/rest'.$cmd;
        if(!empty($this->debug)) echo $url."\n";
        
        #CURLOPT_POST => true,
        $options = array( 
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => false,
            CURLOPT_TIMEOUT         => $this->timeout,
            CURLOPT_PORT            => $this->rtr_port,
            CURLOPT_VERBOSE         => $this->debug,
            CURLOPT_HTTPHEADER      => ['Content-Type:application/json','Accept:application/json',],
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_AUTOREFERER => true,
            CURLOPT_HTTPAUTH =>  CURLAUTH_BASIC,
            CURLOPT_USERPWD =>  $this->rtr_user.':'.$this->rtr_pass,
            CURLOPT_URL => $url,            
        );        
        
        if($this->debug) {
            echo "\n\n";
            echo "OPTIONS\n\n";
            print_r($options);
        }
        
        return $options;
    }
    
    // try to connecto to Mikrotik via HTTPS and read /system/package output
    public function connect() {
        
        $out['ok']=0;
        $out['error']='';
        $out['data']=array();
        
        //if($this->rtr_ver<7) {
        //    $out['error']="REST Api version supported only in RoS 7+";
        //    return $out;
        //}
        
        $res=$this->bs_mkt_rest_api_get('/system/package?name=routeros');
        #print_r($res['data']);
        if($res['ok'] and !empty($res['data']) and !empty($res['data'][0]['version'])) {
            if($res['data'][0]['version']>=$this->min_ver) {
                #print_r($res['data'][0]);
                $this->connected=true;
                $out['ok']=1;
                $out['data']=$res['data'][0];
            } else {
                $out['error']="RoS version ".$res['data'][0]['version']." is not supported, minimal version ".$this->min_ver;
            }
            
            return $out;
                
        } else {
            if(!empty($res['error'])) {
                $out['error']=$res['error'];
            }
            return $out;
        }
    } 
    
    // Get mikrotik identity string.
    public function identity() {
        $out['ok']=0;
        $out['identity']='not_set';
        
        if(!$this->connected) {
            $out['error']="Router is not connected";
            return $out;
        } else {
            $res=$this->bs_mkt_rest_api_get('/system/identity');
            #print_r($res['data']);
            if($res['ok'] and !empty($res['data']) and !empty($res['data']['name'])) {
                $out['ok']=1;
                $out['identity']=$res['data']['name'];
            }
            return $out;
        }
    }

    // Get information from Mikrotik
    public function bs_mkt_rest_api_get($cmd) {
        
        $out['ok']=0;
        $out['error']='';
        $out['data']=array();
        
        $ch = curl_init();
        curl_setopt_array($ch , $this->options($cmd));
        
        $responseData = curl_exec($ch);
        $responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if(!empty($this->debug)) {
            echo 'CODE: '.$responseCode."\n";
            echo "\n\n";
            echo "RESPONSE: ";
            print_r($responseData);
            echo "\n\n";
        }

        
        if($responseCode==0) {
            $out['error']='No output from mikrotik';
            return $out;
        }

        $response = ['code' => $responseCode, 'data' => @json_decode($responseData, true)];
        
        if(!empty($response['data'])) {
            $out['data'] = $response['data'];
        }

        // we expect code 200
        if( ($response['code'] == 200) && isset($response['data']) && empty($response['data']['error']) ) {
            $out['ok']=1;
        }
        #$out['response_debug'] = $response;

        return $out;
        
    }
    
    // Delete single record in mikrotik by ID
    function bs_mkt_rest_api_del($cmd) {
        
        $out['ok']=0;
        $out['error']='';

        $ch = curl_init();                              
        curl_setopt_array($ch , $this->options($cmd));
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        
        $responseData = curl_exec($ch);
        $responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        
        if(!empty($this->debug)) {
            echo 'CODE: '.$responseCode."\n";
            echo "\n\n";
            echo "RESPONSE: ";
            print_r($responseData);
            echo "\n\n";
        }
        
        if($responseCode==0) {
            $out['error']='ERROR: no output from router';
            return $out;
        }

        $response = ['code' => $responseCode, 'data' => @json_decode($responseData, true)];

        // we expect code 204 - all OK but no output
        if( ($response['code'] == 204) && empty($response['data']) ) {
            $out['ok']=1;
        } else {
            if(!empty($response['data']) and !empty($response['data']['message'])) {
                $out['error']='['.$response['data']['error'].'] '.$response['data']['message'];
                
                if(!empty($response['data']['detail'])) {
                    $out['error'].=', '.$response['data']['detail'];
                }
            }
        }

        return $out;
    }

    // Update single record data in Mikrotik
    function bs_mkt_rest_api_upd($cmd,$data) {
        
        $out['ok']=0;
        $out['error']='';
        
        $ch = curl_init();
        curl_setopt_array($ch , $this->options($cmd));
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        
        $responseData = curl_exec($ch);
        $responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if(!empty($mkt['debug'])) {
            echo "\n\n";
            echo 'CODE: '.$responseCode;
            echo "\n\n";
            echo "RESPONSE: ";
            print_r($responseData);
            echo "\n\n";
        }
        
        if($responseCode==0) {
            $out['error']='ERROR: no output from router';
            return $out;
        }

        $response = ['code' => $responseCode, 'data' => @json_decode($responseData, true)];

        // we expect code 200
        if( ($response['code'] == 200) && !empty($response['data']) and !empty($response['data']['.id']) ) {
            $out['ok']=1;
            $out['data']=$response['data'];
        } else {
            if(!empty($response['data']) and !empty($response['data']['message'])) {
                $out['error']='['.$response['data']['error'].'] '.$response['data']['message'];          
                
                if(!empty($response['data']['detail'])) {
                    $out['error'].=', '.$response['data']['detail'];
                }             
            }
            
           
        }

        return $out;
    }

    
    // Add single record data in Mikrotik
    function bs_mkt_rest_api_add($cmd,$data) {
        
        $out['ok']=0;
        $out['error']='';
        
        $ch = curl_init();
        curl_setopt_array($ch , $this->options($cmd));
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        
        $responseData = curl_exec($ch);
        $responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if(!empty($mkt['debug'])) {
            echo "\n\n";
            echo 'CODE: '.$responseCode."\n";
            echo "\n\n";
            echo "RESPONSE: ";
            print_r($responseData);
            echo "\n\n";
        }
        
        if($responseCode==0) {
            $out['error']='ERROR: no output from router';
            return $out;
        }

        $response = ['code' => $responseCode, 'data' => @json_decode($responseData, true)];

        // we expect code 201 - all OK and Created
        if( ($response['code'] == 201) && !empty($response['data']) and !empty($response['data']['.id']) ) {
            $out['ok']=1;
            $out['data']=$response['data'];
        } else {
            if(!empty($response['data']) and !empty($response['data']['message'])) {
                $out['error']='['.$response['data']['error'].'] '.$response['data']['message'];          
                
                if(!empty($response['data']['detail'])) {
                    $out['error'].=', '.$response['data']['detail'];
                }             
            }
            
           
        }

        return $out;
    }
}

?>