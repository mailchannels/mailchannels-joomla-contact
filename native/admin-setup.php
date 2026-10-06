<?php
require __DIR__.'/bootstrap.php';
$app->loadLanguage();
$config=file_get_contents('/app/configuration.php');
if(file_exists('/app/tmp/visibility-admin-configuration'))throw new RuntimeException('Existing configuration backup');
file_put_contents('/app/tmp/visibility-admin-configuration',$config);
$updated=str_replace("public \$live_site = '';", "public \$live_site = 'http://visibility-joomla-http:18379';",$config,$replacements);
if($replacements!==1)throw new RuntimeException('Unexpected live_site configuration');
file_put_contents('/app/configuration.php',$updated);
$installer=\Joomla\CMS\Installer\Installer::getInstance();
if(!$installer->install('/candidate/candidate'))throw new RuntimeException('Install failed');
$db->setQuery("SELECT extension_id FROM #__extensions WHERE type='plugin' AND folder='contact' AND element='mailchannelscontact'");
file_put_contents('/app/tmp/visibility-admin-extension-id',(string)$db->loadResult());
$db->setQuery("SELECT id FROM #__users WHERE username='fixture-registered'");
$user=new \Joomla\CMS\User\User((int)$db->loadResult());
$data=['name'=>'Fixture Registered','username'=>'fixture-registered','password'=>'Local-Registered-Fixture-12345!','password2'=>'Local-Registered-Fixture-12345!','email'=>'registered@example.com','groups'=>[2],'block'=>0];
if(!$user->bind($data) || !$user->save())throw new RuntimeException('User fixture failed');
file_put_contents('/app/tmp/visibility-admin-user-id',(string)$user->id);
echo "JOOMLA_ADMIN_READY\n";
