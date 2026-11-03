<?php
require __DIR__.'/bootstrap.php';
foreach(['ApiMail','ContactDelivery'] as $class)require '/candidate/candidate/src/'.$class.'.php';
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\User\User;
use Joomla\Registry\Registry;
use MailChannels\Joomla\Candidate\ContactDelivery;
$app->loadLanguage();$app->loadIdentity(new User());$app->getLanguage()->load('com_contact',JPATH_SITE);
$app->set('mailfrom','sender@example.com');$app->set('fromname','Fixture');$app->set('mailonline',1);
$config=ComponentHelper::getParams('com_mails');$oldFolder=$config->get('attachment_folder');
$root='/app/tmp/visibility-template-files';if(file_exists($root))throw new RuntimeException('Existing fixture directory');mkdir($root);file_put_contents($root.'/fixture.txt','configured attachment bytes');
$db->setQuery("SELECT template_id,language,attachments,body FROM #__mail_templates WHERE template_id IN ('com_contact.mail','com_contact.mail.copy')");$templates=$db->loadObjectList();
$count=0;
function check($ok,$label){global $count;if(!$ok)throw new RuntimeException($label);++$count;echo "PASS $label\n";}
try{
 $field=$app->bootComponent('com_fields')->getMVCFactory()->createTable('Field','Administrator');
 $field->bind(['context'=>'com_contact.mail','type'=>'text','name'=>'visibility-fixture-field','title'=>'Fixture field','label'=>'Fixture Team','state'=>1,'access'=>1,'language'=>'*','params'=>'{"showlabel":1}','fieldparams'=>'{}','default_value'=>'','description'=>'','group_id'=>0,'only_use_in_subform'=>0,'required'=>0,'ordering'=>0]);
 check($field->check() && $field->store(),'native custom contact field created');
 $config->set('attachment_folder','tmp/visibility-template-files');
 foreach($templates as $template){
  $value=json_encode([['file'=>'fixture.txt','name'=>'{SUBJECT}.txt']]);
  $db->setQuery('UPDATE #__mail_templates SET body='.$db->quote('Fixture {BODY} {CUSTOMFIELDS}').', attachments='.$db->quote($value).' WHERE template_id='.$db->quote($template->template_id).' AND language='.$db->quote($template->language))->execute();
 }
 $lines=[];$status=0;
 exec(escapeshellarg(PHP_BINARY).' -d disable_functions=mail /candidate/native/custom-template-render-probe.php 2>&1',$lines,$status);
 echo implode("\n",$lines)."\n";
 check($status===0 && in_array('JOOMLA_CUSTOM_RENDER_COMPLETE 10 checks',$lines,true),'fresh request renders configured customizations');
}finally{
 $config->set('attachment_folder',$oldFolder);
 foreach($templates as $template)$db->setQuery('UPDATE #__mail_templates SET body='.$db->quote($template->body).', attachments='.$db->quote($template->attachments).' WHERE template_id='.$db->quote($template->template_id).' AND language='.$db->quote($template->language))->execute();
 if(isset($field) && $field->id)$field->delete($field->id);
 unlink($root.'/fixture.txt');rmdir($root);
}
$db->setQuery("SELECT COUNT(*) FROM #__fields WHERE name='visibility-fixture-field'");check((int)$db->loadResult()===0,'synthetic custom field removed');
foreach($templates as $template){$db->setQuery('SELECT attachments,body FROM #__mail_templates WHERE template_id='.$db->quote($template->template_id).' AND language='.$db->quote($template->language));$restored=$db->loadObject();check($restored->attachments===$template->attachments && $restored->body===$template->body,'original template body and attachments restored');}
echo "JOOMLA_CUSTOM_TEMPLATE_COMPLETE $count checks\n";
