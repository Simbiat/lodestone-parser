<?php
declare(strict_types = 1);

namespace Simbiat\LodestoneModules;

/**
 * Class to make HTTP requests
 */
class HttpRequest
{
    #cURL options
    protected array $CURL_OPTIONS = [
        CURLOPT_POST => false,
        CURLOPT_HEADER => true,
        CURLOPT_RETURNTRANSFER => true,
        #Allow caching and reuse of already open connections
        CURLOPT_FRESH_CONNECT => false,
        CURLOPT_FORBID_REUSE => false,
        #Let cURL determine appropriate HTTP version
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_NONE,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 3,
        CURLOPT_HTTPHEADER => ['Content-type: text/html; charset=utf-8', 'Accept-Language: en'],
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.4515.159 Safari/537.36 Edg/92.0.902.84',
        CURLOPT_ENCODING => '',
        CURLOPT_SSL_VERIFYPEER => true,
    ];
    
    protected const int HTTP_OK = 200;
    protected const int HTTP_PERM_REDIRECT = 308;
    protected const int HTTP_SERVICE_NOT_AVAILABLE = 503;
    protected const int HTTP_FORBIDDEN = 403;
    protected const int HTTP_NOT_FOUND = 404;
    protected const int HTTP_TOO_MANY_REQUESTS = 429;
    
    public static \CurlHandle|null|false $curlHandle = null;
    
    /**
     * Main constructor
     * @param string $useragent User-agent to use
     */
    public function __construct(string $useragent = '')
    {
        if (!empty($useragent)) {
            $this->CURL_OPTIONS[CURLOPT_USERAGENT] = $useragent;
        }
        #Check if handle already created
        if (empty(self::$curlHandle)) {
            self::$curlHandle = curl_init();
            if (self::$curlHandle === false) {
                throw new \RuntimeException('Failed to initiate cURL handle');
            }
            if (!curl_setopt_array(self::$curlHandle, $this->CURL_OPTIONS)) {
                throw new \RuntimeException('Failed to set cURL handle options');
            }
        }
    }
    
    /**
     * Get content from page
     * @throws \Exception
     */
    public function get(string $url): string
    {
        $url = str_ireplace(' ', '+', $url);
        
        curl_setopt(self::$curlHandle, CURLOPT_URL, $url);
        // handle response
        $response = curl_exec(self::$curlHandle);
        $curlerror = curl_error(self::$curlHandle);
        $hlength = curl_getinfo(self::$curlHandle, CURLINFO_HEADER_SIZE);
        $httpCode = curl_getinfo(self::$curlHandle, CURLINFO_HTTP_CODE);
        if ($response === false) {
            throw new \RuntimeException($curlerror, $httpCode);
        }
        $data = mb_substr($response, $hlength, null, 'UTF-8');
        
        // specific conditions to return code on
        $httpCode = (int)$httpCode;
        if ($httpCode === self::HTTP_NOT_FOUND) {
            throw new \RuntimeException('Requested page was not found, '.$httpCode, $httpCode);
        }
        if ($httpCode === self::HTTP_SERVICE_NOT_AVAILABLE) {
            throw new \RuntimeException('Lodestone not available, '.$httpCode, $httpCode);
        }
        if ($httpCode === self::HTTP_TOO_MANY_REQUESTS) {
            throw new \RuntimeException('Lodestone has throttled the request, '.$httpCode, $httpCode);
        }
        if ($httpCode === self::HTTP_FORBIDDEN) {
            #Get message from Lodestone
            $message = preg_replace('/(.*<h1 class="error__heading">)([^<]+)(<\/h1>\s*<p class="error__text">)([^<]+)(<\/p>.*)/muis', '$2: $4', $data ?? '');
            throw new \RuntimeException((empty($message) ? 'No access, possibly private entity' : $message).', '.$httpCode, $httpCode);
        }
        if ($httpCode === 0) {
            throw new \RuntimeException($curlerror, $httpCode);
        }
        if ($httpCode < self::HTTP_OK || $httpCode > self::HTTP_PERM_REDIRECT) {
            file_put_contents(__DIR__.'/html.txt', $data);
            #Get message from Lodestone
            $message = preg_replace('/(.*<h1 class="error__heading">)([^<]+)(<\/h1>\s*<p class="error__text">)([^<]+)(<\/p>.*)/muis', '$2: $4', $data ?? '');
            throw new \RuntimeException((empty($message) ? 'Requested page is not available' : $message).', '.$httpCode, $httpCode);
        }
        
        
        // check that data is not empty
        if (empty($data)) {
            throw new \RuntimeException('Requested page is empty');
        }
        
        return $data;
    }
}