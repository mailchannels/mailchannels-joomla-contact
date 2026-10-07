<?php
require __DIR__.'/bootstrap.php';
foreach(['ApiMail','ContactDelivery'] as $class)require '/candidate/candidate/src/'.$class.'.php';
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\User\User;
use Joomla\Registry\Registry;
use MailChannels\Joomla\Candidate\ContactDelivery;
$app->loadLanguage();$app->loadIdentity(new User());$app->getLanguage()->load('com_contact',JPATH_SITE);
$app->set('mailfrom','sender@example.com');$app->set('fromname','Fixture');$app->set('mailonline',1);
$root='/app/tmp/visibility-template-files';
ComponentHelper::getParams('com_mails')->set('attachment_folder','tmp/visibility-template-files');
$count=0;
function check($ok,$label){global $count;if(!$ok)throw new RuntimeException($label);++$count;echo "PASS $label\n";}
 $db->setQuery("SELECT id FROM #__categories WHERE extension='com_contact' AND published=1");$cat=(int)$db->loadResult();
 $contact=(object)['id'=>0,'catid'=>$cat,'language'=>'*','name'=>'Fixture','email_to'=>'recipient@example.com','user_id'=>0,'params'=>new Registry(['custom_reply'=>1,'show_email_copy'=>1])];
 $data=['contact_name'=>'Visitor','contact_email'=>'visitor@example.com','contact_subject'=>'Report','contact_message'=>'Body','contact_email_copy'=>1,'com_fields'=>['visibility-fixture-field'=>'Project 日本語','unregistered_field'=>'should-not-appear']];
 $calls=[];$delivery=new ContactDelivery($app,function($p)use(&$calls){$calls[]=$p;return true;},['sender@example.com'],[$root]);
 check($delivery->deliver($contact,$data)===2,'customized native templates prepare primary and copy');
 foreach($calls as $index=>$payload){
  $body=implode('\n',array_column($payload['content'],'value'));
  check(str_contains($body,'Project 日本語'),"message $index preserves rendered custom field value");
  check(!str_contains($body,'should-not-appear'),"message $index ignores unregistered field input");
  check(base64_decode($payload['attachments'][0]['content'])==='configured attachment bytes',"message $index includes configured template attachment");
  check($payload['attachments'][0]['filename']==='Report.txt',"message $index renders attachment filename placeholder");
 }
 $attempts=0;$delivery=new ContactDelivery($app,function($p)use(&$attempts){++$attempts;return true;},['sender@example.com']);
 $failed=false;try{$delivery->deliver($contact,$data);}catch(RuntimeException $e){$failed=true;}
 check($failed && $attempts===0,'configured attachment without approved root prevents all submission');
echo "JOOMLA_CUSTOM_RENDER_COMPLETE $count checks\n";
