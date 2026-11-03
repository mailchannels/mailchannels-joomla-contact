<?php
require __DIR__.'/bootstrap.php';
$db->setQuery("SELECT extension_id FROM #__extensions WHERE type='plugin' AND folder='captcha' AND element='visibilitycaptcha'");$id=(int)$db->loadResult();
$installer=new \Joomla\CMS\Installer\Installer();$installer->setDatabase($db);
if(!$id || !$installer->uninstall('plugin',$id))throw new RuntimeException('CAPTCHA uninstall failed');
$db->setQuery('SELECT COUNT(*) FROM #__extensions WHERE extension_id='.$id);
if((int)$db->loadResult() || is_dir('/app/plugins/captcha/visibilitycaptcha'))throw new RuntimeException('CAPTCHA remains');
$original=file_get_contents('/app/tmp/visibility-captcha-component');
$db->setQuery("UPDATE #__extensions SET params=".$db->quote($original)." WHERE type='component' AND element='com_contact'")->execute();
$db->setQuery("SELECT params FROM #__extensions WHERE type='component' AND element='com_contact'");
if($db->loadResult()!==$original)throw new RuntimeException('Contact parameters not restored');
unlink('/app/tmp/visibility-captcha-component');
unlink('/app/tmp/visibility-captcha-checks');
echo "JOOMLA_CAPTCHA_CLEANED\n";
