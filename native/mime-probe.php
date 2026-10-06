<?php
require __DIR__.'/bootstrap.php';require '/candidate/candidate/src/ApiMail.php';
use MailChannels\Joomla\Candidate\ApiMail;
$app->set('mailfrom','sender@example.com');$app->set('fromname','Fixture');$app->set('mailonline',1);
$count=0;$root='/app/tmp/visibility-attachments';mkdir($root);file_put_contents($root.'/fixture.txt','local bytes');file_put_contents('/app/tmp/visibility-outside.txt','outside');symlink('/app/tmp/visibility-outside.txt',$root.'/escape.txt');
function check($ok,$label){global $count;if(!$ok)throw new RuntimeException($label);++$count;echo "PASS $label\n";}
function mailer($roots=[]){$m=new ApiMail(static fn($p)=>true,['sender@example.com'],$roots);$m->addRecipient('to@example.com');$m->setSubject('Fixture');$m->setBody('Body');return $m;}
try {
 $m=mailer();$m->addStringAttachment("\x00\xffbinary",'binary.bin');$p=$m->payload();
 check(base64_decode($p['attachments'][0]['content'],true)==="\x00\xffbinary",'binary string attachment round trip');
 check($p['attachments'][0]['filename']==='binary.bin' && !isset($p['attachments'][0]['content_id']),'download filename and disposition preserved');
 $m=mailer();$m->isHtml(true);$m->setBody('<img src="cid:logo">');$m->addStringEmbeddedImage('image bytes','logo','logo.png','base64','image/png');$p=$m->payload();
 check($p['attachments'][0]['content_id']==='logo' && $p['attachments'][0]['type']==='image/png','inline content ID and MIME type preserved');
 $m=mailer([$root]);$m->addAttachment($root.'/fixture.txt','friendly.txt');$p=$m->payload();
 check(base64_decode($p['attachments'][0]['content'])==='local bytes' && $p['attachments'][0]['filename']==='friendly.txt','approved local attachment read');
 $m=mailer();$m->addCustomHeader('List-Unsubscribe','<https://example.com/unsubscribe>');$m->addCustomHeader('X-Correlation-ID','fixture');
 check(count($m->payload()['headers'])===2,'permitted custom headers retained');
 foreach(['unapproved-file','symlink-escape','duplicate-cid','duplicate-header','reserved-header','oversize','bad-filename'] as $case){
  $m=mailer($case==='symlink-escape'?[$root]:[]);
  if($case==='unapproved-file')$m->addAttachment($root.'/fixture.txt');
  if($case==='symlink-escape')$m->addAttachment($root.'/escape.txt');
  if($case==='duplicate-cid'){$m->addStringEmbeddedImage('a','same','a.png');$m->addStringEmbeddedImage('b','same','b.png');}
  if($case==='duplicate-header'){$m->addCustomHeader('X-Test','one');$m->addCustomHeader('x-test','two');}
  if($case==='reserved-header')$m->addCustomHeader('Message-ID','<custom@example.com>');
  if($case==='oversize')$m->addStringAttachment(str_repeat('a',15000001),'large.bin');
  if($case==='bad-filename')$m->addStringEmbeddedImage('a','logo','nested/file.png');
  $failed=false;try{$m->payload();}catch(RuntimeException $e){$failed=true;}
  check($failed,$case.' rejected before submission');
 }
} finally {foreach(glob($root.'/*') as $file)unlink($file);rmdir($root);unlink('/app/tmp/visibility-outside.txt');}
echo "JOOMLA_MIME_PROBE_COMPLETE $count checks\n";
