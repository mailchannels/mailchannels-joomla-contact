<?php
require __DIR__.'/bootstrap.php';
$id=(int)file_get_contents('/app/tmp/visibility-admin-extension-id');
$db->setQuery('SELECT enabled,params FROM #__extensions WHERE extension_id='.$id);
echo 'ADMIN_STATE '.json_encode($db->loadObject())."\n";
