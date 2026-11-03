<?php
require __DIR__.'/bootstrap.php';
$installer=new \Joomla\CMS\Installer\Installer();$installer->setDatabase($db);
if(!$installer->install('/candidate/native/captcha-fixture'))throw new RuntimeException('CAPTCHA install failed');
$db->setQuery("UPDATE #__extensions SET enabled=1 WHERE type='plugin' AND folder='captcha' AND element='visibilitycaptcha'")->execute();
$id=(int)file_get_contents('/app/tmp/visibility-contact-id');
$table=$app->bootComponent('com_contact')->getMVCFactory()->createTable('Contact','Administrator');
if(!$table->load($id))throw new RuntimeException('Missing contact');
$params=json_decode($table->params,true);$params['captcha']='visibilitycaptcha';$table->params=json_encode($params);
if(!$table->store())throw new RuntimeException('Contact CAPTCHA save failed');
$db->setQuery("SELECT params FROM #__extensions WHERE type='component' AND element='com_contact'");$original=$db->loadResult();
file_put_contents('/app/tmp/visibility-captcha-component',$original);
$params=json_decode($original,true);$params['captcha']='visibilitycaptcha';
$db->setQuery("UPDATE #__extensions SET params=".$db->quote(json_encode($params))." WHERE type='component' AND element='com_contact'")->execute();
file_put_contents('/app/tmp/visibility-captcha-checks','');
echo "JOOMLA_CAPTCHA_READY\n";
