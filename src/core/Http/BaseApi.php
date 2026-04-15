<?php

namespace Saola\Core\Http;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\ClientException;
use Saola\Core\Magic\Arr;
use Saola\Core\Events\EventMethods;
use Saola\Core\Concerns\MagicMethods;
use Saola\Core\Events\EventDispatcher;
use Throwable;

abstract class BaseApi
{
    use EventMethods, MagicMethods;
    protected $http_code = 200;

    protected $url;

    protected $responseType = 'raw';
    protected $oneTimeType = null;

    protected $exception = null;

    protected $response = null;

    protected $client;

    

    function __construct()
    {
        $this->url = env('API_URL');
        $this->client = new GuzzleClient();
    }

    public function setResponseType($type)
    {
        $this->responseType = strtolower($type);
        return $this;
    }

    /**
     * thiết lập kiểu trả về
     *
     * @param string $type kiểu trả về
     * @param boolean $all áp dụng cho tất cả request
     * @return $this
     */
    public function setOutput($type = null, $all = false)
    {
        $t = strtolower($type);
        if($all){
            $this->responseType = $t;
            $this->oneTimeType = null;
        }
        else{
            $this->oneTimeType = $t;
        }
        return $this;
    }

    /**
     * gửi request đến API server
     * @param string|array $method      [= GET / POST / PUT / PATCH / DELETE / OPTION]
     * @param string $url               là sub url nghĩ là không cần địa chỉ server chỉ cần /module/abc...
     * @param array  $data              mãng data get cũng dùng dc luôn
     * @param array  $headers           Mãng header. cái này tùy chọn
     * 
     * @return \Psr\Http\Message\ResponseInterface|array|string
     */

    protected function send($method, $url = null, array $data = [], array $headers = [])
    {
        if (is_array($method)) {
            $m = array_key_exists('method', $method) ? $method['method'] : 'GET';
            $obj = $method;
            extract($obj, EXTR_PREFIX_INVALID, 'crazy_');
            if (!is_string($method)) $method = $m;
        }
        if (!$url) return null;

        if(!is_array($data)) $data = [];
        $defaultOptions = [];
        $type = $this->oneTimeType ? $this->oneTimeType : $this->responseType;
        $this->oneTimeType = null;
        if ($type == 'json') {
            $defaultOptions['Content-Type'] = 'application/json';
            $defaultOptions['Accept'] = 'application/json';
        }
        try {
            $headerData = array_merge($defaultOptions, (array) $headers);
            $params = [
                'headers' => $headerData,
                //    'form_params' => $data
                // 'body' => json_encode((array) $data),
                'curl' => [
                    CURLOPT_TCP_KEEPALIVE => 1
                ]
            ];
            if(in_array(strtolower($method), ['post', 'put'])){
                $params['body'] = json_encode((array) $data);
            }else{
                $url = url_merge($url, $data);
            }
            $event = new EventDispatcher([
                'method' => $method,
                'url' => $url,
                'data' => $data,
                'headers' => $headers
            ]);
            $event->setContext($this);
            static::_dispatchEvent('beforeSend', [$event]);
            $response = $this->client->request($method, $url, $params);
            
            $this->response = $response;
            if ($type == 'json') {
                return json_decode($response->getBody()->getContents(), true);
            }
            elseif(in_array($type, ['text', 'html', 'string', 'raw'])){
                return $response->getBody()->getContents();
            }
            return $response;
        } catch (ClientException $th) {
            $this->exception = $th;
            $this->response = $th->getResponse();
            return null;
        }catch (BadResponseException $th) {
            $this->exception = $th;
            return null;
        }catch (Throwable $th) {
            $this->exception = $th;
            return null;
        }
    }


    public function getHttpCode()
    {
        return $this->http_code;
    }

    /**
     * Undocumented function
     *
     * @return Throwable
     */
    public function getException()
    {
        return $this->exception;
    }

    /**
     * get response
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function getResponse()
    {
        return $this->response;
    }

}
