<?php
/** @license GPL-2.0-or-later */
namespace MailChannels\Joomla\Candidate\Extension;
defined('_JEXEC') or die;
use Joomla\CMS\Event\Contact\SubmitContactEvent;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\SubscriberInterface;
use MailChannels\Joomla\Candidate\ContactDelivery;
use MailChannels\Joomla\Candidate\HttpsSubmitter;
final class MailChannelsContact extends CMSPlugin implements SubscriberInterface {
    protected $autoloadLanguage=true;
    public static function getSubscribedEvents():array {return ['onSubmitContact'=>'submit'];}
    public function submit(SubmitContactEvent $event):void {
        $ids=array_filter(array_map('trim',explode(',',(string)$this->params->get('contact_ids',''))));
        if(!in_array((string)$event->getContact()->id,$ids,true))return;
        $app=$this->getApplication();
        $senders=array_filter(array_map('trim',explode(',',(string)getenv('MAILCHANNELS_JOOMLA_ALLOWED_SENDERS'))));
        $roots=json_decode((string)getenv('MAILCHANNELS_JOOMLA_ATTACHMENT_ROOTS'),true)??[];
        if(!is_array($roots) || array_filter($roots,static fn($value)=>!is_string($value)))throw new \RuntimeException('MailChannels: invalid server attachment configuration',503);
        $delivery=new ContactDelivery($app,new HttpsSubmitter((string)getenv('MAILCHANNELS_API_KEY')),$senders,$roots);
        $delivery->deliver($event->getContact(),$event->getData());
        $app->enqueueMessage(\Joomla\CMS\Language\Text::_('PLG_CONTACT_MAILCHANNELSCONTACT_ACCEPTED'));
    }
}
