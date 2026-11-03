<?php
/** Test fixture only. Never distribute with the candidate. */
defined('_JEXEC') or die;
return new class implements \Joomla\DI\ServiceProviderInterface {
 public function register(\Joomla\DI\Container $container) {
  $container->set(\Joomla\CMS\Extension\PluginInterface::class,static function($container){
   $plugin=new \MailChannels\Joomla\TestCaptcha\Extension\Fixture((array)\Joomla\CMS\Plugin\PluginHelper::getPlugin('captcha','visibilitycaptcha'));
   $plugin->setApplication(\Joomla\CMS\Factory::getApplication());return $plugin;
  });
 }
};
