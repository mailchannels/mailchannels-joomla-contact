<?php
require __DIR__.'/bootstrap.php';
$app->loadLanguage();
$count=0;
function check($ok,$label){global $count;if(!$ok)throw new RuntimeException($label);++$count;echo "PASS $label\n";}
 $app->createExtensionNamespaceMap();
 $plugin=$app->bootPlugin('mailchannelscontact','contact');
 check($plugin instanceof \MailChannels\Joomla\Candidate\Extension\MailChannelsContact,'native plugin discovery creates candidate class');
 $contact=(object)['id'=>41,'params'=>new \Joomla\Registry\Registry(['custom_reply'=>0])];
 $plugin->submit(new \Joomla\CMS\Event\Contact\SubmitContactEvent('onSubmitContact',['subject'=>$contact,'data'=>[]]));
 check(true,'unselected contact returns before delivery');
 $contact->id=42;$failed=false;
 try{$plugin->submit(new \Joomla\CMS\Event\Contact\SubmitContactEvent('onSubmitContact',['subject'=>$contact,'data'=>[]]));}catch(RuntimeException $e){$failed=str_contains($e->getMessage(),'custom reply');}
 check($failed,'selected contact requires deliberate core suppression before delivery');
echo "JOOMLA_PLUGIN_DISCOVERY_COMPLETE $count checks\n";
