<?php
require __DIR__.'/bootstrap.php';
$field=$app->bootComponent('com_fields')->getMVCFactory()->createTable('Field','Administrator');
$field->bind(['context'=>'com_contact.mail','type'=>'text','name'=>'visibility-required-team','title'=>'Required team','label'=>'Required team','state'=>1,'access'=>1,'language'=>'*','params'=>'{"showlabel":1,"show_on":1}','fieldparams'=>'{}','default_value'=>'','description'=>'','group_id'=>0,'only_use_in_subform'=>0,'required'=>1,'ordering'=>0]);
if(!$field->check() || !$field->store())throw new RuntimeException('Required field fixture failed');
file_put_contents('/app/tmp/visibility-required-field-id',(string)$field->id);
echo "JOOMLA_REQUIRED_FIELD_READY\n";
