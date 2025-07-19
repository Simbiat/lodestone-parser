<?php
declare(strict_types = 1);

namespace Simbiat\FFXIV\LodestoneModules;

/**
 * Class to make HTTP requests
 */
class HttpRequest
{
    #cURL options
    protected static array $curl_options = [
        \CURLOPT_POST => false,
        \CURLOPT_HEADER => true,
        \CURLOPT_RETURNTRANSFER => true,
        #Allow caching and reuse of already open connections
        \CURLOPT_FRESH_CONNECT => false,
        \CURLOPT_FORBID_REUSE => false,
        #Let cURL determine appropriate HTTP version
        \CURLOPT_HTTP_VERSION => \CURL_HTTP_VERSION_NONE,
        \CURLOPT_CONNECTTIMEOUT => 10,
        \CURLOPT_TIMEOUT => 30,
        \CURLOPT_FOLLOWLOCATION => true,
        \CURLOPT_MAXREDIRS => 3,
        \CURLOPT_HTTPHEADER => ['Content-type: text/html; charset=utf-8', 'Accept-Language: en'],
        \CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.4515.159 Safari/537.36 Edg/92.0.902.84',
        \CURLOPT_ENCODING => '',
        \CURLOPT_SSL_VERIFYPEER => true,
    ];
    
    public static \CurlHandle|null|false $curl_handle = null;
    
    /**
     * Main constructor
     * @param string $user_agent User-agent to use
     */
    public function __construct(string $user_agent = '')
    {
        if (!empty($user_agent)) {
            self::$curl_options[\CURLOPT_USERAGENT] = $user_agent;
        }
        #Check if the handle already created
        if (empty(self::$curl_handle)) {
            self::$curl_handle = \curl_init();
            if (self::$curl_handle === false) {
                throw new \RuntimeException('Failed to initiate cURL handle');
            }
            if (!\curl_setopt_array(self::$curl_handle, self::$curl_options)) {
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
        $url = \str_ireplace(' ', '+', $url);
        
        \curl_setopt(self::$curl_handle, \CURLOPT_URL, $url);
        // handle response
        $response = \curl_exec(self::$curl_handle);
        $curl_error = \curl_error(self::$curl_handle);
        $header_length = \curl_getinfo(self::$curl_handle, \CURLINFO_HEADER_SIZE);
        $http_code = \curl_getinfo(self::$curl_handle, \CURLINFO_HTTP_CODE);
        if ($response === false) {
            throw new \RuntimeException($curl_error, $http_code);
        }
        $data = mb_substr($response, $header_length, null, 'UTF-8');
        
        // specific conditions to return code on
        $http_code = (int)$http_code;
        if ($http_code === 404) {
            throw new \RuntimeException('Requested page was not found, '.$http_code, $http_code);
        }
        if ($http_code === 503) {
            throw new \RuntimeException('Lodestone not available, '.$http_code, $http_code);
        }
        if ($http_code === 403) {
            #Get the message from Lodestone
            $message = \preg_replace('/(.*<h1 class="error__heading">)([^<]+)(<\/h1>\s*<p class="error__text">)([^<]+)(<\/p>.*)/muis', '$2: $4', $data ?? '');
            throw new \RuntimeException((empty($message) ? 'No access, possibly private entity' : $message).', '.$http_code, $http_code);
        }
        if ($http_code === 0) {
            throw new \RuntimeException($curl_error, $http_code);
        }
        if ($http_code < 200 || $http_code > 308) {
            if ($http_code === 429 || \preg_match('/The server is experiencing unusually heavy traffic/ui', $data ?? '') === 1) {
                throw new \RuntimeException('Lodestone has throttled the request, '.$http_code, $http_code);
            }
            \file_put_contents(__DIR__.'/html.txt', $data ?? '');
            #Get the message from Lodestone
            $message = \preg_replace('/(.*?<h1 class="(error|maintenance)__heading">)([^<]+)(<\/h1>\s*<p class="(error|maintenance)__text">)([^<]+)(<\/p>.*)/muis', '$3: $6', $data ?? '');
            throw new \RuntimeException((empty($message) ? 'Requested page is not available' : $message).', '.$http_code, $http_code);
        }
        
        
        // check that data is not empty
        if (empty($data)) {
            throw new \RuntimeException('Requested page is empty');
        }
        
        return $data;
    }
}