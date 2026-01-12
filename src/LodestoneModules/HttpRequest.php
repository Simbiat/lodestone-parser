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
        \CURLOPT_USERAGENT => 'Lodestone PHP Parser (https://github.com/Simbiat/lodestone-parser)',
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
        if (\preg_match('/^\s*$/u', $user_agent) === 0) {
            self::$curl_options[\CURLOPT_USERAGENT] = $user_agent;
        }
        #Check if the handle already created
        if (self::$curl_handle === null || self::$curl_handle === false) {
            #Create or retrieve a persistent cURL share handle to share data to help speed up connections
            $share = \curl_share_init_persistent([\CURL_LOCK_DATA_DNS, \CURL_LOCK_DATA_SSL_SESSION, \CURL_LOCK_DATA_CONNECT, \CURL_LOCK_DATA_PSL]);
            self::$curl_handle = \curl_init();
            if (self::$curl_handle === false) {
                throw new \RuntimeException('Failed to initiate cURL handle');
            }
            self::$curl_options[\CURLOPT_SHARE] = $share;
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
        #Handle response
        $response = \curl_exec(self::$curl_handle);
        $curl_error = \curl_error(self::$curl_handle);
        $header_length = \curl_getinfo(self::$curl_handle, \CURLINFO_HEADER_SIZE);
        $http_code = \curl_getinfo(self::$curl_handle, \CURLINFO_HTTP_CODE);
        if ($response === false) {
            #While this may not be a true 503 and can be an issue on client side, we treat it as Lodestone being 503
            if (\preg_match('/(Operation timed out after|Could not resolve host|Resolving timed out after)/ui', $curl_error)) {
                throw new \RuntimeException('Lodestone not available, 503', 503);
            }
            throw new \RuntimeException($curl_error, $http_code);
        }
        $data = mb_substr($response, $header_length, null, 'UTF-8');
        
        #Specific conditions to return code on
        $http_code = (int)$http_code;
        if ($http_code === 404) {
            throw new \RuntimeException('Requested page was not found, '.$http_code, $http_code);
        }
        #While different 5xx errors can mean different things, ultimately they mean server-side issue, thus that Lodestone is not available for us
        if ($http_code >= 500) {
            throw new \RuntimeException('Lodestone not available, '.$http_code, $http_code);
        }
        if ($http_code === 403) {
            #Get the message from Lodestone
            $message = \preg_replace('/(.*<h1 class="error__heading">)([^<]+)(<\/h1>\s*<p class="error__text">)([^<]+)(<\/p>.*)/muis', '$2: $4', $data ?? '');
            #If message is same as original data, then it's not a full error page, but a partially blocked page due to privacy settings, so we get the text differently
            if ($message === ($data ?? '')) {
                $message = \preg_replace('/(.*<p class="parts__zero">)([^<]+)(\.?<\/p>.*)/muis', '$2', $data ?? '');
            }
            throw new \RuntimeException((\preg_match('/^\s*$/u', $message) === 0 ? 'No access, possibly private entity' : $message).', '.$http_code, $http_code);
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
            throw new \RuntimeException((\preg_match('/^\s*$/u', $message) === 0 ? 'Requested page is not available' : $message).', '.$http_code, $http_code);
        }
        #Check that data is not empty
        if (\preg_match('/^\s*$/u', $data) !== 0) {
            throw new \RuntimeException('Requested page is empty');
        }
        
        return $data;
    }
}