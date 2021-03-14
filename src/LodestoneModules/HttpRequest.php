<?php
declare(strict_types=1);
namespace Simbiat\LodestoneModules;

/**
 * Class HttpRequest
 */
class HttpRequest
{
    /**
     * curl options
     */
    protected array $CURL_OPTIONS = [
        CURLOPT_POST => false,
        CURLOPT_BINARYTRANSFER => false,
        CURLOPT_HEADER => true,
        CURLOPT_RETURNTRANSFER => true,
        #Allow cahing and reuse of already open connections
        CURLOPT_FRESH_CONNECT => false,
        CURLOPT_FORBID_REUSE => false,
        #Let cURL determine appropriate HTTP version
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_NONE,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 3,
        CURLOPT_HTTPHEADER => ['Content-type: text/html; charset=utf-8', 'Accept-Language: en'],
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/87.0.4280.67 Safari/537.36 Edg/87.0.664.55',
        CURLOPT_ENCODING => '',
        CURLOPT_SSL_VERIFYPEER => false,
    ];
    
    const HTTP_OK = 200;
    const HTTP_PERM_REDIRECT = 308;
    const HTTP_SERVICE_NOT_AVAILABLE = 503;
    const HTTP_FORBIDDEN = 403;
    const HTTP_NOT_FOUND = 404;
    
    public static \CurlHandle|null|false $curlHandle = null;
    
    public function __construct(string $useragent = '')
    {
        if (!empty($useragent)) {
            $this->CURL_OPTIONS[CURLOPT_USERAGENT] = $useragent;
        }
        #Check if handle already created
        if (empty(self::$curlHandle)) {
            self::$curlHandle = curl_init();
            if (self::$curlHandle === false) {
                throw new \Exception('Failed to initiate cURL handle');
            } else {
                if(!curl_setopt_array(self::$curlHandle, $this->CURL_OPTIONS)) {
                    throw new \Exception('Failed to set cURL handle options');
                }
            }
        }
    }
    
    #Get URL
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
            throw new \Exception($curlerror, $httpCode);
        } else {
            $data = substr($response, $hlength);
        }

        // specific conditions to return code on
        if ($httpCode == self::HTTP_NOT_FOUND) {
            throw new \Exception('Requested page was not found, '.$httpCode, $httpCode);
        } elseif ($httpCode == self::HTTP_SERVICE_NOT_AVAILABLE) {
            throw new \Exception('Lodestone not available, '.$httpCode, $httpCode);
        } elseif ($httpCode == self::HTTP_FORBIDDEN) {
            throw new \Exception('Requests are (temporary) blocked, '.$httpCode, $httpCode);
        } elseif ($httpCode == 0) {
            throw new \Exception($curlerror, $httpCode);
        } elseif ($httpCode < self::HTTP_OK || $httpCode > self::HTTP_PERM_REDIRECT) {
            throw new \Exception('Requested page is not available, '.$httpCode, $httpCode);
        }
        
         
        // check that data is not empty
        if (empty($data)) {
            throw new \Exception('Requested page is empty');
        }

        return $data;
    }
    
    public function check($object, $name, $id = null): self
    {
        $this->object = $object;
        $this->name = $name;
        $this->id = $id;
        return $this;
    }

    /**
     * @return $this
     */
    public function isNotEmpty(): self
    {
        if (empty($this->object)) {
            throw Exceptions::emptyValidation($this);
        }

        return $this;
    }
}
?>