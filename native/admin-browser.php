<?php
require __DIR__.'/bootstrap.php';
$port=filter_var($argv[1] ?? null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1024,'max_range'=>65535]]);
if(!$port)throw new RuntimeException('Invalid loopback review port');
$config=file_get_contents('/app/configuration.php');
$updated=str_replace("http://visibility-joomla-http:18379","http://127.0.0.1:$port",$config,$replacements);
if($replacements!==1)throw new RuntimeException('Unexpected review live_site configuration');
file_put_contents('/app/configuration.php',$updated);
$id=(int)file_get_contents('/app/tmp/visibility-admin-extension-id');
$db->setQuery("SELECT enabled FROM #__extensions WHERE extension_id=".$id);
if((int)$db->loadResult()!==0)throw new RuntimeException('Browser review requires disabled plugin');
echo "JOOMLA_BROWSER_READY /administrator/index.php?option=com_plugins&task=plugin.edit&extension_id=$id\n";
