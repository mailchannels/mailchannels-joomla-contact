<?php
require __DIR__.'/bootstrap.php';
require '/candidate/candidate/src/ApiMail.php';
use MailChannels\Joomla\Candidate\ApiMail;
use Joomla\CMS\Mail\MailTemplate;
$count=0;$requests=[];
function check($ok,$label){global $count;if(!$ok)throw new RuntimeException($label);++$count;echo "PASS $label\n";}
$app->set('mailfrom','sender@example.com');$app->set('fromname','Fixture Sender');$app->set('mailonline',1);
$app->loadLanguage();
$app->getLanguage()->load('com_contact',JPATH_SITE);
$send=function($payload)use(&$requests){$requests[]=$payload;return true;};
$data=['sitename'=>'Fixture','name'=>'Visitor <script>','contactname'=>'Contact','email'=>'visitor@example.com','subject'=>'Fixture subject','body'=>'Synthetic body 日本語','url'=>'https://example.com/','customfields'=>''];
foreach(['com_contact.mail'=>'recipient@example.com','com_contact.mail.copy'=>'visitor@example.com'] as $id=>$recipient){
 $mail=new ApiMail($send,['sender@example.com']);$template=new MailTemplate($id,'en-GB',$mail);
 $template->addAttachment('template.txt','template attachment bytes');
 $template->addRecipient($recipient);$template->setReplyTo($data['email'],$data['name']);$template->addTemplateData($data);$template->addUnsafeTags(['name','email','body']);
 check($template->send()===true,"native $id template sends through candidate");
 $p=end($requests);check(base64_decode($p['attachments'][0]['content'])==='template attachment bytes',"$id template attachment retained");check($p['personalizations'][0]['to'][0]['email']===$recipient,"$id recipient preserved");
 check($p['from']['email']==='sender@example.com' && $p['reply_to']['email']==='visitor@example.com',"$id separates sender and visitor reply address");
 check(str_contains(json_encode($p,JSON_UNESCAPED_UNICODE),'日本語'),"$id Unicode body retained");
}
$mail=new ApiMail($send,['sender@example.com']);$mail->addRecipient('to@example.com');$mail->addCc('cc@example.com');$mail->addBcc('bcc@example.com');$mail->setSubject('HTML');$mail->isHtml(true);$mail->setBody('<p>Hello</p>');$mail->AltBody='Hello';$mail->Send();$p=end($requests);
check(count($p['content'])===2 && $p['content'][0]['type']==='text/plain','HTML alternative retained');
check($p['personalizations'][0]['bcc'][0]['email']==='bcc@example.com' && count($p['personalizations'][0]['to'])===1,'Bcc stays separate from visible To');
foreach(['bcc-only','header','multiple-reply','sender','disabled','uncertain'] as $case){
 $before=count($requests);$attempts=0;$m=new ApiMail(function($p)use(&$attempts){++$attempts;return false;},['sender@example.com']);
 if($case==='bcc-only')$m->addBcc('bcc@example.com');else $m->addRecipient('to@example.com');
 $m->setSubject('Fixture');$m->setBody('Body');
 if($case==='header')$m->addCustomHeader('To','other@example.com');
 if($case==='multiple-reply'){$m->addReplyTo('a@example.com');$m->addReplyTo('b@example.com');}
 if($case==='sender')$m->setFrom('other@example.com');
 if($case==='disabled')$app->set('mailonline',0);
 $rejected=false;try{$m->Send();}catch(RuntimeException $e){$rejected=true;}finally{$app->set('mailonline',1);}
 check($rejected && $attempts===($case==='uncertain'?1:0),"$case fails with expected attempt count and no fallback");
}
$m=new ApiMail(static function($p){throw new RuntimeException('private-fixture-key and body');},['sender@example.com']);
$m->addRecipient('to@example.com');$m->setSubject('Fixture');$m->setBody('Body');
try{$m->Send();throw new LogicException('Expected transport failure');}catch(RuntimeException $e){check(!str_contains((string)$e,'private-fixture-key') && $e->getPrevious()===null,'transport exception details redacted');}
check(count($requests)===3,'two core templates and MIME example each submitted once');
echo "JOOMLA_TEMPLATE_PROBE_COMPLETE $count checks\n";
