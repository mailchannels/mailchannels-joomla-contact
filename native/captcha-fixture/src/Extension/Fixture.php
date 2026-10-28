<?php
/** Deterministic TEST ONLY provider; no bot protection. */
namespace MailChannels\Joomla\TestCaptcha\Extension;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\SubscriberInterface;
use Joomla\CMS\Event\Captcha\CaptchaSetupEvent;
use Joomla\CMS\Captcha\CaptchaProviderInterface;
use Joomla\CMS\Form\FormField;
final class Fixture extends CMSPlugin implements SubscriberInterface {
 public static function getSubscribedEvents():array {return ['onCaptchaSetup'=>'setup'];}
 public function setup(CaptchaSetupEvent $event):void {
  $event->getCaptchaRegistry()->add(new class implements CaptchaProviderInterface {
   public function getName():string {return 'visibilitycaptcha';}
   public function display(string $name='',array $attributes=[]):string {
    return '<input data-visibility-captcha="test-only" type="text" name="'.htmlspecialchars($name,ENT_QUOTES).'" id="'.htmlspecialchars($attributes['id']??$name,ENT_QUOTES).'">';
   }
   public function checkAnswer(?string $code=null):bool {
    file_put_contents(JPATH_ROOT.'/tmp/visibility-captcha-checks',"checked\n",FILE_APPEND);
    if($code==='fixture-exception')throw new \RuntimeException('Synthetic CAPTCHA unavailable');
    return $code==='fixture-pass';
   }
   public function setupField(FormField $field,\SimpleXMLElement $element):void {}
  });
 }
}
