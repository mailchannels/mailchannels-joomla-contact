<?php
/**
 * @copyright (C) 2026 MailChannels
 * @license   GNU General Public License version 2 or later; see LICENSE
 */
defined('_JEXEC') or die;
return new class implements \Joomla\DI\ServiceProviderInterface {
    public function register(\Joomla\DI\Container $container) {
        $container->set(\Joomla\CMS\Extension\PluginInterface::class,static function($container){
            $plugin=new \MailChannels\Joomla\Candidate\Extension\MailChannelsContact((array)\Joomla\CMS\Plugin\PluginHelper::getPlugin('contact','mailchannelscontact'));
            $plugin->setApplication(\Joomla\CMS\Factory::getApplication());return $plugin;
        });
    }
};
