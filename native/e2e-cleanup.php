<?php
require __DIR__.'/bootstrap.php';
$id=(int)file_get_contents('/app/tmp/visibility-contact-id');
$table=$app->bootComponent('com_contact')->getMVCFactory()->createTable('Contact','Administrator');
if(!$table->delete($id))throw new RuntimeException('Contact cleanup failed');
$db->setQuery("SELECT extension_id FROM #__extensions WHERE type='plugin' AND folder='contact' AND element='mailchannelscontact'");$extension=(int)$db->loadResult();
if(!$extension || !\Joomla\CMS\Installer\Installer::getInstance()->uninstall('plugin',$extension))throw new RuntimeException('Plugin uninstall failed');
foreach(glob('/app/tmp/visibility-contact-*') as $file)unlink($file);
$db->setQuery("SELECT COUNT(*) FROM #__contact_details WHERE id=".$id);if((int)$db->loadResult())throw new RuntimeException('Contact still present');
$db->setQuery("SELECT COUNT(*) FROM #__extensions WHERE extension_id=".$extension);if((int)$db->loadResult())throw new RuntimeException('Plugin still present');
echo "JOOMLA_CONTACT_FIXTURE_CLEANED\n";
