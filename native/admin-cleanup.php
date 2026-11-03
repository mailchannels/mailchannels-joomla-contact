<?php
require __DIR__.'/bootstrap.php';
$id=(int)file_get_contents('/app/tmp/visibility-admin-extension-id');
$userId=(int)file_get_contents('/app/tmp/visibility-admin-user-id');
$user=new \Joomla\CMS\User\User($userId);
if(!$user->delete() || !\Joomla\CMS\Installer\Installer::getInstance()->uninstall('plugin',$id))throw new RuntimeException('Cleanup failed');
$db->setQuery('SELECT COUNT(*) FROM #__users WHERE id='.$userId);if((int)$db->loadResult())throw new RuntimeException('User remains');
$db->setQuery('SELECT COUNT(*) FROM #__extensions WHERE extension_id='.$id);if((int)$db->loadResult())throw new RuntimeException('Plugin remains');
file_put_contents('/app/configuration.php',file_get_contents('/app/tmp/visibility-admin-configuration'));
if(hash_file('sha256','/app/configuration.php')!==hash_file('sha256','/app/tmp/visibility-admin-configuration'))throw new RuntimeException('Configuration not restored');
foreach(glob('/app/tmp/visibility-admin-*') as $file)unlink($file);
echo "JOOMLA_ADMIN_CLEANED\n";
