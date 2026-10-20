<?php
/**
 * @copyright (C) 2026 MailChannels
 * @license   GNU General Public License version 2 or later; see LICENSE
 */
namespace MailChannels\Joomla\Candidate\Extension;
defined('_JEXEC') or die;
use Joomla\CMS\Event\Contact\SubmitContactEvent;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Uri\Uri;
use Joomla\Event\SubscriberInterface;
use MailChannels\Joomla\Candidate\ContactDelivery;
use MailChannels\Joomla\Candidate\HttpsSubmitter;
final class MailChannelsContact extends CMSPlugin implements SubscriberInterface {
    protected $autoloadLanguage=true;
    public static function getSubscribedEvents():array {return ['onSubmitContact'=>'submit'];}
    public function submit(SubmitContactEvent $event):void {
        $contact=$event->getContact();
        $ids=array_filter(array_map('trim',explode(',',(string)$this->params->get('contact_ids',''))));
        if(!in_array((string)$contact->id,$ids,true))return;
        $app=$this->getApplication();
        Log::addLogger(['text_file'=>'plg_contact_mailchannelscontact.php'],Log::ALL,['mailchannelscontact']);
        try {
            $senders=array_filter(array_map('trim',explode(',',(string)getenv('MAILCHANNELS_JOOMLA_ALLOWED_SENDERS'))));
            $roots=json_decode((string)getenv('MAILCHANNELS_JOOMLA_ATTACHMENT_ROOTS'),true)??[];
            if(!is_array($roots) || array_filter($roots,static fn($value)=>!is_string($value)))throw new \RuntimeException('MailChannels: invalid server attachment configuration',503);
            $delivery=new ContactDelivery($app,new HttpsSubmitter((string)getenv('MAILCHANNELS_API_KEY')),$senders,$roots);
            $delivery->deliver($contact,$event->getData());
        } catch (\Throwable $e) {
            // Candidate messages are operator text without secrets; other exceptions may quote addresses.
            Log::add(str_starts_with($e->getMessage(),'MailChannels:')?$e->getMessage():'MailChannels: '.get_class($e),Log::ERROR,'mailchannelscontact');
            $app->setUserState('com_contact.contact.data',$event->getData());
            $app->enqueueMessage(Text::_('PLG_CONTACT_MAILCHANNELSCONTACT_FAILED'),'warning');
            // Redirect here: returning would let com_contact clear the form and follow its success redirect.
            // A non-SEF URL reaches the contact form even when no menu item routes to it.
            $app->redirect(Uri::base().'index.php?option=com_contact&view=contact&id='.(int)$contact->id);
        }
        $app->enqueueMessage(Text::_('PLG_CONTACT_MAILCHANNELSCONTACT_ACCEPTED'));
    }
}
