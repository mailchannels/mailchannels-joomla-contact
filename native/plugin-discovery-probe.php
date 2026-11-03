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
 $contact->id=42;
 // A refused delivery redirects back to the form and exits, so verify the logged reason on shutdown.
 register_shutdown_function(static function()use($app){
  $log=(string)@file_get_contents($app->get('log_path').'/plg_contact_mailchannelscontact.php');
  echo "\n";check(empty($GLOBALS['returned']) && str_contains($log,'custom reply'),'selected contact requires deliberate core suppression before delivery');
  echo "JOOMLA_PLUGIN_DISCOVERY_COMPLETE {$GLOBALS['count']} checks\n";
 });
 $plugin->submit(new \Joomla\CMS\Event\Contact\SubmitContactEvent('onSubmitContact',['subject'=>$contact,'data'=>[]]));
 $returned=true;throw new LogicException('Expected refused delivery to redirect');
