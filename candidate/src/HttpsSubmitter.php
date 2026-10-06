<?php
/** @copyright (C) 2026 MailChannels; @license GPL-2.0-or-later */
declare(strict_types=1);
namespace MailChannels\Joomla\Candidate;

/** One bounded HTTPS attempt. TRUE means API acceptance, never inbox delivery. */
final class HttpsSubmitter {
    public function __construct(private string $apiKey) {}
    public function __invoke(array $payload):bool {
        if (!function_exists('curl_init') || $this->apiKey==='' || preg_match('/[\r\n\x00]/',$this->apiKey)) return false;
        try { $body=json_encode($payload,JSON_THROW_ON_ERROR); } catch (\Throwable $e) { return false; }
        if (strlen($body)>20000000) return false;
        $response='';$handle=curl_init('https://api.mailchannels.net/tx/v1/send');
        if ($handle===false) return false;
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
            ])) return false;
            if(curl_exec($handle)===false || curl_getinfo($handle,CURLINFO_RESPONSE_CODE)!==202)return false;
            $decoded=json_decode($response,true,32,JSON_THROW_ON_ERROR);
            $results=$decoded['results']??null;
            return is_array($results) && count($results)===1 && ($results[0]['index']??null)===0 && ($results[0]['status']??null)==='sent';
        } catch (\Throwable $e) {
            // Never surface response bodies, credentials or underlying transport exceptions.
            return false;
        } finally {curl_close($handle);}
    }
}
