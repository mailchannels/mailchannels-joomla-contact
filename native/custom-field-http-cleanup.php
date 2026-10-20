<?php
require __DIR__.'/bootstrap.php';
$id=(int)file_get_contents('/app/tmp/visibility-required-field-id');
$field=$app->bootComponent('com_fields')->getMVCFactory()->createTable('Field','Administrator');
if(!$field->delete($id))throw new RuntimeException('Required field cleanup failed');
$db->setQuery('SELECT COUNT(*) FROM #__fields WHERE id='.$id);
if((int)$db->loadResult())throw new RuntimeException('Field remains');
unlink('/app/tmp/visibility-required-field-id');
echo "JOOMLA_REQUIRED_FIELD_CLEANED\n";
