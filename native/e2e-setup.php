<?php
require __DIR__.'/bootstrap.php';
$installer=\Joomla\CMS\Installer\Installer::getInstance();
if(!$installer->install('/candidate/candidate'))throw new RuntimeException('Fixture install failed');
$db->setQuery("UPDATE #__extensions SET enabled=1 WHERE type='plugin' AND folder='contact' AND element='mailchannelscontact'")->execute();
$db->setQuery("SELECT id FROM #__categories WHERE extension='com_contact' AND published=1");$cat=(int)$db->loadResult();
if(!$cat)throw new RuntimeException('Missing contact category');
$table=$app->bootComponent('com_contact')->getMVCFactory()->createTable('Contact','Administrator');
$table->bind(['name'=>'Isolated contact fixture','alias'=>'visibility-contact','catid'=>$cat,'published'=>1,'access'=>1,'language'=>'*','email_to'=>'recipient@example.com','params'=>['show_email_form'=>1,'custom_reply'=>1,'show_email_copy'=>1,'validate_session'=>1,'captcha'=>'0'],'metadata'=>[]]);
if(!$table->check() || !$table->store())throw new RuntimeException('Contact fixture create failed');
file_put_contents('/app/tmp/visibility-contact-id',(string)$table->id);
$db->setQuery("UPDATE #__extensions SET params=".$db->quote(json_encode(['contact_ids'=>(string)$table->id]))." WHERE type='plugin' AND folder='contact' AND element='mailchannelscontact'")->execute();
file_put_contents('/app/tmp/visibility-contact-mode','success');
file_put_contents('/app/tmp/visibility-contact-events','');
echo "JOOMLA_CONTACT_FIXTURE_READY id=$table->id\n";
