<?php
require __DIR__.'/bootstrap.php';
$app->loadLanguage();$app->set('mailfrom','sender@example.com');$app->set('fromname','Fixture');$app->set('mailonline',1);
$installer=\Joomla\CMS\Installer\Installer::getInstance();$count=0;
function check($ok,$label){global $count;if(!$ok)throw new RuntimeException($label);++$count;echo "PASS $label\n";}
$db->setQuery("SELECT extension_id FROM #__extensions WHERE type='plugin' AND folder='contact' AND element='mailchannelscontact'");
if($db->loadResult())throw new RuntimeException('Candidate already installed; refuse to alter existing state');
try{
 check($installer->install('/candidate/candidate'),'native Installer accepts candidate manifest');
 $db->setQuery("SELECT extension_id,enabled,params FROM #__extensions WHERE type='plugin' AND folder='contact' AND element='mailchannelscontact'");$record=$db->loadObject();
 check($record && !(int)$record->enabled,'install does not enable candidate automatically');
 $db->setQuery("UPDATE #__extensions SET enabled=1,params=".$db->quote(json_encode(['contact_ids'=>'42']))." WHERE extension_id=".(int)$record->extension_id)->execute();
 $lines=[];$status=0;
 exec(escapeshellarg(PHP_BINARY).' -d disable_functions=mail /candidate/native/plugin-discovery-probe.php 2>&1',$lines,$status);
 echo implode("\n",$lines)."\n";
 check($status===0 && in_array('JOOMLA_PLUGIN_DISCOVERY_COMPLETE 3 checks',$lines,true),'fresh request discovers configured plugin');
}finally{
 if(isset($record) && $record){check($installer->uninstall('plugin',(int)$record->extension_id),'native candidate uninstall succeeds');
 $db->setQuery('SELECT COUNT(*) FROM #__extensions WHERE extension_id='.(int)$record->extension_id);check((int)$db->loadResult()===0,'candidate extension record removed');}
}
echo "JOOMLA_PLUGIN_PROBE_COMPLETE $count checks\n";
