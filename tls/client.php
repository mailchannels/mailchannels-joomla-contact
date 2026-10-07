<?php
require '/candidate/candidate/src/HttpsSubmitter.php';
$submit=new \MailChannels\Joomla\Candidate\HttpsSubmitter('tls-fixture-dummy-key');
$start=microtime(true);
$accepted=$submit(['personalizations'=>[['to'=>[['email'=>'recipient@example.com']]]],'from'=>['email'=>'sender@example.com'],'subject'=>'Fixture','content'=>[['type'=>'text/plain','value'=>'Synthetic fixture']]]);
echo json_encode(['accepted'=>$accepted,'seconds'=>round(microtime(true)-$start,3),'php'=>PHP_VERSION,'curl'=>curl_version()['version'],'tls'=>curl_version()['ssl_version']]);
