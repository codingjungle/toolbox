<?php

/**
* @brief      Oauth Class
* @author     -storm_author-
* @copyright  -storm_copyright-
* @package    IPS Social Suite
* @subpackage toolbox
* @since      5.1.1
* @version    -storm_version-
*/

namespace IPS\toolbox\Api;

use Exception;
use IPS\Http\Url;
use IPS\Http\Response;
use IPS\Patterns\Singleton;


use function json_encode;

if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header(($_SERVER[ 'SERVER_PROTOCOL' ] ?? 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

/**
* Oauth Class
* @mixin \IPS\toolbox\Api\Oauth
*/
abstract class _Oauth extends Singleton
{
    protected static $instance;

    /**
     * @var string client id, configure in setup()
     */
    protected $client;
    /**
     * @var string client secret, configure in setup()
     */
    protected $secret;
    /**
     * @var string token url, configure in setup()
     */
    protected $token;
    /**
     * @var string scopes for the apis, configure in setup()
     */
    protected $scopes;
    /**
     * @var string api url, configure in setup()
     */
    protected $url;
    /**
     * @var string if the api needs a login, configure in setup() or leave null if not needed
     */
    protected $username ;
    /**
     * @var string if the api needs a login, configure in setup() or leave null if not needed
     */
    protected $password = false;
    /**
     * @var string sslChecks, configure in setup(). true to enable, false to disable, null to do nothing
     */
    protected $sslCheck;
    /**
     * @var bool forceTls, configure in setup()
     */
    protected $forceTls = false;
    /**
     * @var Req
     */
    protected $api;

    protected $accessToken;

    /**
    * _Oauth constructor
    *
    */
    public function __construct()
    {
        $this->setup();
        $this->accessToken = $this->authorize();
    }

    abstract protected function setup(): void;

    /**
     * @throws ApiException
     */
    protected function authorize(): string
    {
        if (empty($this->getCredentials()) === false) {
            return $this->getCredentials();
        } else {
            $authorization = Url::external($this->token);
            $request = $authorization->request();
            if ($this->username !== null && $this->password !== null) {
                $request->login($this->username, $this->password);
            }

            if ($this->sslCheck !== null) {
                $request->sslCheck($this->sslCheck);
            }
            if ($this->forceTls) {
                $request->forceTls();
            }

            $response = $request->post([
                    'grant_type' => 'client_credentials',
                    'client_id' => $this->client,
                    'client_secret' => $this->secret,
                    'scope' => $this->scopes
                ]);
            if ($response->isSuccessful()) {
                $content = $response->decodeJson();
                return $this->storeCredentials($content);
            } else {
                $content = $response->decodeJson();
                $cc = json_encode($content) ?? $content;
                $msg = 'ResponseCode: ' . $response->httpResponseCode . "\n";
                $msg .= ' ResponseText: ' . $response->httpResponseText . "\n";
                $msg .= ' ResponseBody: ' . $cc;
                throw new ApiException($msg);
            }
        }
    }

    protected function clearCredentials(): void
    {
    }

    protected function storeCredentials($credentials): mixed
    {
    }

    protected function getCredentials(): ?string
    {
        return null;
    }

    protected function getApi(string $endPoint, array $queryString = [])
    {
        $url = Url::external($this->url . $endPoint);
        if (empty($queryString) === false) {
            $url = $url->setQueryString($queryString);
        }

        $request = $url->request();

        if ($this->username !== null && $this->password !== null) {
            $request->login($this->username, $this->password);
        }

        if ($this->sslCheck !== null) {
            $request->sslCheck($this->sslCheck);
        }

        if ($this->forceTls) {
            $request->forceTls();
        }

        return $request;
    }

    /**
     * @param string $meth - method to us
     * @param string $endPoint - endpoint of the api
     * @param array $qs - query string to add to the api url
     * @param array $headers - any additional headers to be sent
     * @param array $data - any data to be sent
     * @return array
     * @throws ApiException
     */
    public function call(string $method, string $endPoint, array $qs = [], array $headers = [], array $data = []): array
    {
        $api = $this->getApi($endPoint, $qs);
        $accessToken = $this->accessToken;
        $headers['Authorization'] = 'Bearer ' . $accessToken;
        $response = $api->setHeaders($headers)->{$method}($data);
        $content = $response->decodeJson();

        if ($response->isSuccessful()) {
            return $content;
        } else {
            try {
                $ec = $content['errorCode'] ?? null;
                if ($this->retry($response) === true || ($ec !== null && ($content['errorCode'] === '1S290/F'))) {
                    $this->clearCredentials();
                    $this->authorize();
                    return $this->call($method, $endPoint, $qs, $headers, $data);
                } else {
                    throw new Exception();
                }
            } catch (Exception $e) {
                $cc = json_encode($content) ?? $content;
                $msg = 'ResponseCode: ' . $response->httpResponseCode . "\n";
                $msg .= ' ResponseText: ' . $response->httpResponseText . "\n";
                $msg .= ' ResponseBody: ' . $cc;
                throw new ApiException($msg);
            }
        }
    }

    public function retry(Response $response): bool
    {
        return false;
    }

    /**
     * @param array $data array of data to post to the api
     * @param string $endPoint endpoint of the api
     * @param array $queryString additonal querystrings you might need to pass to the url
     * @param $headers headers that need to be sent, Authorization and User-Agent are already sent
     * @return void
     * @throws ApiException
     */
    public function post(array $data, string $endPoint, array $queryString = [], $headers = []): array
    {
        return $this->call('post', $endPoint, $queryString, $headers, $data);
    }

    public function put(array $data, string $endPoint, array $queryString = [], $headers = []): array
    {
        return $this->call('put', $endPoint, $queryString, $headers, $data);
    }

    public function get(string $endPoint, array $queryString = [], array $headers = []): array
    {
        return $this->call('get', $endPoint, $queryString, $headers);
    }

    public function delete(string $endPoint, array $queryString = [], array $headers = []): array
    {
        return $this->call('delete', $endPoint, $queryString, $headers);
    }

    public function head(string $endPoint, array $queryString = [], array $headers = []): array
    {
        return $this->call('head', $endPoint, $queryString, $headers);
    }
}
