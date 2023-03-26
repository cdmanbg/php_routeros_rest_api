# PHP Class for Mikrotik Router OS 7.xx REST API

Since I am using mikrotik from a long time, and till v 7.xx I used old API ( tcp socket ). 

I found PHP Class for that here: https://github.com/BenMenking/routeros-api

It worked very well for me, so now on version 7, I wanted to go for the new REST API over HTTPS. 
Searched a little but did not found any ready to use PHP Class, and using samples from Mikrotik Page: 
https://help.mikrotik.com/docs/display/ROS/REST+API
I creted this little class for GET/ADD/UPDADE/DELETE procedures using the new API. 

You can check the example script: php_rest_api_examples.php for more details

# how to enable REST api in Mikrotik
Just a quick example how to enable fast your REST API With Self Signed Certificate
Change 10.2.4.75 with your router IP

Execute these rows 1 by 1 in your mikrotik terminal

I am using default port 443, but you can change that in your IP->Services if needed. 

/certificate/add name=local-root-cert common-name=local-cert key-usage=key-cert-sign,crl-sign days-valid=8000
/certificate/sign local-root-cert 

/certificate/add name=webfig days-valid=8000 common-name=10.2.4.75
/certificate/sign webfig 

/ip service/set www-ssl certificate=webfig disabled=no 
