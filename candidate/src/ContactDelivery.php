<?php
/**
 * @copyright (C) 2026 MailChannels
 * Contact template flow adapted from Joomla com_contact:
 * @copyright (C) 2005 Open Source Matters, Inc.
 * @license GNU General Public License version 2 or later; see LICENSE
 */
declare(strict_types=1);
namespace MailChannels\Joomla\Candidate;
use Joomla\CMS\Factory;
use Joomla\CMS\Mail\MailTemplate;
use Joomla\CMS\String\PunycodeHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;

final class ContactDelivery {
    private \Closure $submit;
    public function __construct(private object $app, callable $submit, private array $senders, private array $roots=[]) {$this->submit=\Closure::fromCallable($submit);}
    public function deliver(object $contact,array $data):int {
        if(!$contact->params->get('custom_reply'))throw new \RuntimeException('MailChannels: enable custom reply for this contact before using the API',503);
        $recipient=$contact->email_to;
        if($recipient==='' && $contact->user_id) $recipient=Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($contact->user_id)->email;
        $templateData=['sitename'=>$this->app->get('sitename'),'name'=>$data['contact_name'],'contactname'=>$contact->name,
            'email'=>PunycodeHelper::emailToPunycode($data['contact_email']),'subject'=>$data['contact_subject'],
            'body'=>stripslashes($data['contact_message']),'url'=>Uri::base(),'customfields'=>''];
        if(!empty($data['com_fields']) && $fields=FieldsHelper::getFields('com_contact.mail',$contact,true,$data['com_fields'])) {
            $templateData['customfields']=FieldsHelper::render('com_contact.mail','fields.render',['context'=>'com_contact.mail','item'=>$contact,'fields'=>$fields]) ?: '';
        }
        $templates=['com_contact.mail'=>$recipient];
        if($contact->params->get('show_email_copy',0) && !empty($data['contact_email_copy']))$templates['com_contact.mail.copy']=$templateData['email'];
        $payloads=[];
        // Render/validate every selected template before making the first request.
        foreach($templates as $id=>$to) {
            $mail=new ApiMail(static function($payload)use(&$payloads){$payloads[]=$payload;return true;},$this->senders,$this->roots);
            $template=new MailTemplate($id,$this->app->getLanguage()->getTag(),$mail);
            $template->addRecipient($to);$template->setReplyTo($templateData['email'],$templateData['name']);
            $template->addTemplateData($templateData);$template->addUnsafeTags(['name','email','body']);$template->send();
        }
        foreach($payloads as $payload) {
            try {$accepted=($this->submit)($payload);}catch(\Throwable $e){$accepted=false;}
            if($accepted!==true)throw new \RuntimeException('MailChannels: submission not confirmed; some messages may have been accepted. Do not automatically resend.',503);
        }
        return count($payloads);
    }
}
