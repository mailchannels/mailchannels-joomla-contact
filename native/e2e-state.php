<?php
require __DIR__.'/bootstrap.php';
$mode=$argv[1]??'';if(!in_array($mode,['disabled','unselected','no-suppression'],true))throw new RuntimeException('Unknown fixture mode');
$id=(int)file_get_contents('/app/tmp/visibility-contact-id');
$params=['show_email_form'=>1,'custom_reply'=>$mode==='no-suppression'?0:1,'show_email_copy'=>1,'validate_session'=>1,'captcha'=>'0'];
$db->setQuery('UPDATE #__contact_details SET params='.$db->quote(json_encode($params)).' WHERE id='.$id)->execute();
$db->setQuery('UPDATE #__extensions SET enabled='.($mode==='disabled'?0:1).',params='.$db->quote(json_encode(['contact_ids'=>$mode==='unselected'?'':(string)$id]))." WHERE type='plugin' AND folder='contact' AND element='mailchannelscontact'")->execute();
echo "JOOMLA_E2E_STATE $mode\n";
