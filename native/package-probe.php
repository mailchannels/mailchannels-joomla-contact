<?php
require __DIR__.'/bootstrap.php';
use Joomla\CMS\Installer\Installer;
use Joomla\CMS\Installer\InstallerHelper;
$app->loadLanguage();$app->getSession()->start();$count=0;$record=null;$package=null;
function installer(){global $db;$installer=new Installer();$installer->setDatabase($db);return $installer;}
function check($ok,$label){global $count;if(!$ok)throw new RuntimeException($label);++$count;echo "PASS $label\n";}
$db->setQuery("SELECT extension_id FROM #__extensions WHERE type='plugin' AND folder='contact' AND element='mailchannelscontact'");if($db->loadResult())throw new RuntimeException('Candidate already installed');
$configHash=hash_file('sha256','/app/configuration.php');
$zip='/app/tmp/visibility-candidate.zip';if(file_exists($zip))throw new RuntimeException('Existing fixture archive');
try{
 check(copy('/artifacts/mailchannels-contact-candidate.zip',$zip),'copy review artifact into disposable installer staging');
 $package=InstallerHelper::unpack($zip,true);
 check(is_array($package) && $package['type']==='plugin','native unpack identifies plugin ZIP');
 check(installer()->install($package['dir']),'native Installer installs ZIP contents');
 $db->setQuery("SELECT extension_id,enabled,params FROM #__extensions WHERE type='plugin' AND folder='contact' AND element='mailchannelscontact'");$record=$db->loadObject();
 check($record && !(int)$record->enabled,'ZIP installation leaves plugin disabled');
 $z=new ZipArchive();$z->open($zip);$matches=true;
 for($i=0;$i<$z->numFiles;$i++){$name=$z->getNameIndex($i);$target='/app/plugins/contact/mailchannelscontact/'.$name;$matches=$matches && is_file($target) && hash_file('sha256',$target)===hash('sha256',$z->getFromIndex($i));}$z->close();
 check($matches,'every installed file matches reviewed ZIP bytes');
 $language=$app->getLanguage();$base='/app/plugins/contact/mailchannelscontact';
 check($language->load('plg_contact_mailchannelscontact',$base,'en-GB',true) && $language->_('PLG_CONTACT_MAILCHANNELSCONTACT_IDS_LABEL')==='Enabled contact IDs','native language loader resolves plugin configuration label');
 check($language->_('PLG_CONTACT_MAILCHANNELSCONTACT_ACCEPTED')==='Your contact submission was accepted for email processing.','native language loader resolves acceptance message');
 check($language->load('plg_contact_mailchannelscontact.sys',$base,'en-GB',true) && $language->_('PLG_CONTACT_MAILCHANNELSCONTACT')==='MailChannels Contact','native language loader resolves system plugin name');
 $params=json_encode(['contact_ids'=>'42,77','fixture_preserve'=>'yes']);
 $db->setQuery('UPDATE #__extensions SET enabled=1,params='.$db->quote($params).' WHERE extension_id='.(int)$record->extension_id)->execute();
 check(installer()->install($package['dir']),'same-version package reinstall succeeds');
 $db->setQuery('SELECT extension_id,enabled,params FROM #__extensions WHERE extension_id='.(int)$record->extension_id);$after=$db->loadObject();
 check($after && $after->params===$params && (int)$after->enabled===1,'reinstall preserves administrator parameters and enabled state');
 check(hash_file('sha256','/app/configuration.php')===$configHash,'package operations leave global configuration unchanged');
}finally{
 if($record){check(installer()->uninstall('plugin',(int)$record->extension_id),'archive-installed plugin uninstalls');$db->setQuery('SELECT COUNT(*) FROM #__extensions WHERE extension_id='.(int)$record->extension_id);check((int)$db->loadResult()===0,'extension record removed after uninstall');}
 if(is_array($package) && !empty($package['extractdir']))InstallerHelper::cleanupInstall($zip,$package['extractdir']);elseif(file_exists($zip))unlink($zip);
}
check(!file_exists($zip) && (!is_array($package) || !file_exists($package['extractdir'])),'staged archive and extracted files cleaned');
echo "JOOMLA_PACKAGE_PROBE_COMPLETE $count checks\n";
