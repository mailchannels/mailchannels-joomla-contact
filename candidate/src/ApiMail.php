<?php
/** @copyright (C) 2026 MailChannels; @license GPL-2.0-or-later */
declare(strict_types=1);
namespace MailChannels\Joomla\Candidate;

use Joomla\CMS\Factory;
use Joomla\CMS\Mail\Mail;

/** Unreleased template transport candidate; not a global Joomla mail replacement. */
final class ApiMail extends Mail {
    private \Closure $submit;
    public function __construct(callable $submit, private array $allowedSenders, private array $attachmentRoots=[]) {
        parent::__construct(true);
        $this->submit=\Closure::fromCallable($submit);
        $this->CharSet='UTF-8';
        $this->SMTPAutoTLS=false;
        $this->setFrom((string) Factory::getApplication()->get('mailfrom'),(string) Factory::getApplication()->get('fromname'),false);
    }
    public function Send() {
        if (!Factory::getApplication()->get('mailonline',1)) throw new \RuntimeException('MailChannels: site mail disabled');
        $payload=$this->payload();
        // Deliberately never call parent::Send(): Joomla may retry its SMTP path.
        try { $accepted=($this->submit)($payload); } catch (\Throwable $error) {
            // Do not attach a provider exception: it may contain secrets or message data.
            throw new \RuntimeException('MailChannels: acceptance unconfirmed; do not automatically resend');
        }
        if ($accepted!==true) throw new \RuntimeException('MailChannels: acceptance unconfirmed; do not automatically resend');
        return true;
    }
    public function payload():array {
        if (strtolower($this->CharSet)!=='utf-8' || !in_array($this->ContentType,['text/plain','text/html'],true)
            || $this->Ical || $this->ConfirmReadingTo
            || $this->MessageID || $this->DKIM_domain || ($this->Sender!=='' && $this->Sender!==$this->From)) {
            throw new \RuntimeException('MailChannels: unsupported message feature');
        }
        if (!in_array($this->From,$this->allowedSenders,true)) throw new \RuntimeException('MailChannels: sender not allowed');
        $address=static function(array $pair):array {
            if (!filter_var($pair[0],FILTER_VALIDATE_EMAIL) || preg_match('/[\r\n\x00]/',implode('',$pair))) throw new \RuntimeException('MailChannels: invalid address');
            return ['email'=>$pair[0],'name'=>$pair[1]??''];
        };
        $personalization=['to'=>array_map($address,$this->getToAddresses())];
        if (!$personalization['to']) throw new \RuntimeException('MailChannels: To recipient required');
        foreach (['cc'=>$this->getCcAddresses(),'bcc'=>$this->getBccAddresses()] as $type=>$list) if($list)$personalization[$type]=array_map($address,$list);
        if (count($personalization['to'])+count($personalization['cc']??[])+count($personalization['bcc']??[])>1000) throw new \RuntimeException('MailChannels: recipient limit');
        $payload=['personalizations'=>[$personalization],'from'=>$address([$this->From,$this->FromName]),'subject'=>$this->Subject,'content'=>[]];
        $reply=array_values($this->getReplyToAddresses());
        if(count($reply)>1)throw new \RuntimeException('MailChannels: multiple Reply-To unsupported');
        if($reply)$payload['reply_to']=$address($reply[0]);
        if($this->ContentType==='text/html' && $this->AltBody!=='')$payload['content'][]=['type'=>'text/plain','value'=>$this->AltBody];
        $payload['content'][]=['type'=>$this->ContentType,'value'=>$this->Body];
        if($this->Body==='' || preg_match('/[\r\n\x00]/',$this->Subject))throw new \RuntimeException('MailChannels: invalid content');
        $headers=[];$seen=[];
        $reserved=['authentication-results','bcc','cc','content-transfer-encoding','content-type','dkim-signature','from','message-id','received','reply-to','subject','to','return-path'];
        foreach($this->getCustomHeaders() as [$name,$value]) {
            $lower=strtolower($name);
            if(!preg_match('/^[A-Za-z0-9-]+$/D',$name) || preg_match('/[\r\n\x00]/',$value) || isset($seen[$lower]) || in_array($lower,$reserved,true)) throw new \RuntimeException('MailChannels: unsupported header');
            $seen[$lower]=true;$headers[$name]=$value;
        }
        if($headers)$payload['headers']=$headers;
        $attachments=[];$cids=[];$bytes=0;
        foreach($this->getAttachments() as $attachment) {
            [$source,$original,$filename,$encoding,$type,$isString,$disposition,$cid]=$attachment;
            if($filename==='' || preg_match('/[\r\n\x00]/',$filename) || str_contains($filename,'/') || str_contains($filename,chr(92)) || !preg_match('~^[a-zA-Z0-9!#$&^_.+-]+/[a-zA-Z0-9!#$&^_.+-]+$~D',$type) || !in_array($disposition,['attachment','inline'],true)) throw new \RuntimeException('MailChannels: invalid attachment metadata');
            if($isString) {$data=$source;} else {
                $path=realpath($source);$allowed=false;
                foreach($this->attachmentRoots as $root) {
                    $base=realpath($root);
                    if($path!==false && $base!==false && is_dir($base) && str_starts_with($path,rtrim($base,DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR))$allowed=true;
                }
                if(!$allowed || !is_file($path) || !is_readable($path))throw new \RuntimeException('MailChannels: attachment path not allowed');
                $data=@file_get_contents($path,false,null,0,15000001-$bytes);
                if($data===false)throw new \RuntimeException('MailChannels: attachment unreadable');
            }
            $bytes+=strlen($data);
            if($bytes>15000000)throw new \RuntimeException('MailChannels: attachments too large');
            $item=['content'=>base64_encode($data),'filename'=>$filename,'type'=>$type];
            if($disposition==='inline') {
                if($cid==='' || preg_match('/[\s<>\x00]/',$cid) || isset($cids[$cid]))throw new \RuntimeException('MailChannels: invalid attachment content ID');
                $cids[$cid]=true;$item['content_id']=$cid;
            }
            $attachments[]=$item;
        }
        if($attachments)$payload['attachments']=$attachments;
        $encoded=json_encode($payload,JSON_THROW_ON_ERROR);
        if(strlen($encoded)>20000000)throw new \RuntimeException('MailChannels: message too large');
        return $payload;
    }
}
