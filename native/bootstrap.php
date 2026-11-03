<?php
$_SERVER['HTTP_HOST']='visibility-joomla-http';$_SERVER['REQUEST_URI']='/index.php';$_SERVER['SCRIPT_NAME']='/index.php';
define('_JEXEC',1);define('JPATH_BASE','/app');
require JPATH_BASE.'/includes/defines.php';require JPATH_BASE.'/includes/framework.php';
$container=\Joomla\CMS\Factory::getContainer();
foreach(['session.web','session',\Joomla\CMS\Session\Session::class,\Joomla\Session\Session::class,\Joomla\Session\SessionInterface::class] as $key)$container->alias($key,'session.web.site');
$app=$container->get(\Joomla\CMS\Application\SiteApplication::class);
\Joomla\CMS\Factory::$application=$app;
$app->createExtensionNamespaceMap();
$db=$container->get(\Joomla\Database\DatabaseInterface::class);
