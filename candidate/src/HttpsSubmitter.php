<?php
/** @copyright (C) 2026 MailChannels; @license GPL-2.0-or-later */
declare(strict_types=1);
namespace MailChannels\Joomla\Candidate;

use Joomla\CMS\Log\Log;

/** One bounded HTTPS attempt. TRUE means API acceptance, never inbox delivery. */
final class HttpsSubmitter {
    public function __construct(private string $apiKey) {}
    public function __invoke(array $payload):bool {
        if (!function_exists('curl_init')) return $this->fail('curl unavailable');
        if ($this->apiKey==='' || preg_match('/[\r\n\x00]/',$this->apiKey)) return $this->fail('API key missing or invalid');
        try { $body=json_encode($payload,JSON_THROW_ON_ERROR); } catch (\Throwable $e) { return $this->fail('payload encoding failed'); }
        if (strlen($body)>20000000) return $this->fail('payload too large');
        $response='';$handle=curl_init('https://api.mailchannels.net/tx/v1/send');
        if ($handle===false) return $this->fail('curl initialization failed');
        try {
            if (!curl_setopt_array($handle,[
                CURLOPT_POST=>true, CURLOPT_POSTFIELDS=>$body,
                CURLOPT_HTTPHEADER=>['X-Api-Key: '.$this->apiKey,'Content-Type: application/json','Accept: application/json'],
                CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS, CURLOPT_FOLLOWLOCATION=>false,
                CURLOPT_SSL_VERIFYPEER=>true, CURLOPT_SSL_VERIFYHOST=>2,
                CURLOPT_CONNECTTIMEOUT=>5, CURLOPT_TIMEOUT=>15,
                CURLOPT_WRITEFUNCTION=>static function($curl,string $chunk)use(&$response):int {
                    if(strlen($response)+strlen($chunk)>65536)return 0;
                    $response.=$chunk;return strlen($chunk);
                },
            ])) return $this->fail('curl options rejected');
            if(curl_exec($handle)===false)return $this->fail('transport error',0,curl_errno($handle));
            $status=curl_getinfo($handle,CURLINFO_RESPONSE_CODE);
            $decoded=json_decode($response,true,32);
            $requestId=is_array($decoded) && is_string($decoded['request_id']??null) && preg_match('/^[A-Za-z0-9._-]{1,128}$/D',$decoded['request_id'])?$decoded['request_id']:'';
            if($status!==202)return $this->fail('unexpected HTTP status',$status,0,$requestId);
            $results=is_array($decoded)?($decoded['results']??null):null;
            if(!is_array($results) || count($results)!==1 || ($results[0]['index']??null)!==0 || ($results[0]['status']??null)!=='sent')return $this->fail('message not accepted',$status,0,$requestId);
            return true;
        } catch (\Throwable $e) {
            // Never surface response bodies, credentials or underlying transport exceptions.
            return $this->fail('unexpected response handling error');
        }
    }
    /** Log only operational metadata: never the API key, message body or addresses. */
    private function fail(string $reason,int $status=0,int $curlError=0,string $requestId=''):bool {
        if (class_exists(Log::class)) Log::add(sprintf('MailChannels send failed: %s (HTTP %d, curl error %d, request_id %s)',$reason,$status,$curlError,$requestId?:'none'),Log::ERROR,'mailchannelscontact');
        return false;
    }
}
