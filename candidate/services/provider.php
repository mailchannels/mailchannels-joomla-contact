<?php
/** @license GPL-2.0-or-later */
defined('_JEXEC') or die;
return new class implements \Joomla\DI\ServiceProviderInterface {
    public function register(\Joomla\DI\Container $container) {
        $container->set(\Joomla\CMS\Extension\PluginInterface::class,static function($container){
            $plugin=new \MailChannels\Joomla\Candidate\Extension\MailChannelsContact((array)\Joomla\CMS\Plugin\PluginHelper::getPlugin('contact','mailchannelscontact'));
            $plugin->setApplication(\Joomla\CMS\Factory::getApplication());return $plugin;
        });
    }
};
