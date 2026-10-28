<?php
require __DIR__.'/bootstrap.php';
foreach(['ApiMail','ContactDelivery'] as $class)require '/candidate/candidate/src/'.$class.'.php';
use MailChannels\Joomla\Candidate\ContactDelivery;
use Joomla\Registry\Registry;
$app->loadLanguage();$app->getLanguage()->load('com_contact',JPATH_SITE);$app->set('mailfrom','sender@example.com');$app->set('fromname','Fixture');$app->set('mailonline',1);
$contact=(object)['id'=>1,'name'=>'Fixture','email_to'=>'recipient@example.com','user_id'=>0,'params'=>new Registry(['custom_reply'=>1,'show_email_copy'=>1])];
$data=['contact_name'=>'Visitor','contact_email'=>'visitor@example.com','contact_subject'=>'Fixture','contact_message'=>'Synthetic body','contact_email_copy'=>1];
$count=0;
function check($ok,$label){global $count;if(!$ok)throw new RuntimeException($label);++$count;echo "PASS $label\n";}
foreach([false,true] as $copy){
 $calls=[];$contact->params->set('show_email_copy',$copy);
 $delivery=new ContactDelivery($app,function($p)use(&$calls){$calls[]=$p;return true;},['sender@example.com']);
 check($delivery->deliver($contact,$data)===($copy?2:1),'configured copy controls request count');
 check($calls[0]['personalizations'][0]['to'][0]['email']==='recipient@example.com','contact destination preserved');
 if($copy)check($calls[1]['personalizations'][0]['to'][0]['email']==='visitor@example.com','sender copy separate from contact message');
}
foreach(['missing-custom-reply','mail-disabled','first-failure','copy-failure','exception'] as $case){
 $calls=0;$contact->params->set('show_email_copy',1);$contact->params->set('custom_reply',$case!=='missing-custom-reply');$app->set('mailonline',$case!=='mail-disabled');
 $delivery=new ContactDelivery($app,function($p)use(&$calls,$case){++$calls;if($case==='exception')throw new RuntimeException('private transport detail');return $case==='copy-failure' && $calls===1;},['sender@example.com']);
 $failed=false;try{$delivery->deliver($contact,$data);}catch(RuntimeException $e){$failed=!str_contains((string)$e,'private transport detail');}
 check($failed && $calls===(in_array($case,['missing-custom-reply','mail-disabled'])?0:($case==='copy-failure'?2:1)),$case.' fails without fallback or repeat');
}
echo "JOOMLA_DELIVERY_PROBE_COMPLETE $count checks\n";
