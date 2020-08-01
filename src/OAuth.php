<?php
declare(strict_types=1);
namespace Patreon;

use ParagonIE\Certainty\RemoteFetch;
use ParagonIE\HiddenString\HiddenString;
use Patreon\Exceptions\CurlException;

/**
 * Class OAuth
 * @package Patreon
 */
class OAuth
{
    /** @var HiddenString $client_id */
    private $client_id;

    /** @var HiddenString $client_secret */
    private $client_secret;

    /** @var string $caCertDir */
    protected $caCertDir;

    /**
     * OAuth constructor.
     *
     * @param string|HiddenString $client_id
     * @param string|HiddenString $client_secret
     */
    public function __construct($client_id, $client_secret, $cacert_dir = '')
    {
        if (!($client_id instanceof HiddenString)) {
            $client_id = new HiddenString($client_id);
        }
        if (!($client_secret instanceof HiddenString)) {
            $client_secret = new HiddenString($client_secret);
        }
        $this->client_id = $client_id;
        $this->client_secret = $client_secret;
        if (!empty($cacert_dir)) {
            $this->caCertDir = $cacert_dir;
        }
    }

    /**
     * @return string
     */
    public function getCaBundleDir(): string
    {
        return $this->caCertDir;
    }

    /**
     * @param string $path
     * @return self
     */
    public function setCaBundleDir(string $path): self
    {
        $this->caCertDir = $path;
        return $this;
    }

    /**
     * @param string $code
     * @param string $redirect_uri
     * @return array
     * @throws CurlException
     */
    public function get_tokens(string $code, string $redirect_uri)
    {
        return $this->__update_token([
            "grant_type" => "authorization_code",
            "code" => $code,
            "client_id" => $this->client_id->getString(),
            "client_secret" => $this->client_secret->getString(),
            "redirect_uri" => $redirect_uri
        ]);
    }

    /**
     * @param string $refresh_token
     * @return array
     * @throws CurlException
     */
    public function refresh_token(string $refresh_token)
    {
        return $this->__update_token([
            "grant_type" => "refresh_token",
            "refresh_token" => $refresh_token,
            "client_id" => $this->client_id->getString(),
            "client_secret" => $this->client_secret->getString()
        ]);
    }

    /**
     * @param array $params
     * @return array
     * @throws CurlException
     */
    private function __update_token(array $params): array
    {
        $api_endpoint = "https://api.patreon.com/oauth2/token";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, "Patreon-PHP, version 1.0.0, platform ".php_uname('s').'-'.php_uname('r'));
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));

        // Strict TLS verification
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        if (!defined('CURLOPT_SSL_VERIFYPEER')) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        }
        curl_setopt($ch, CURLE_SSL_CACERT, $this->getLatestCaCerts());

        $response = curl_exec($ch);
        if (!is_string($response)) {
            throw new CurlException('No response returned from Patreon server');
        }

        return (array) json_decode($response, true);
    }

    /**
     * Can be overridden in derived classes.
     *
     * @return string
     * @throws \ParagonIE\Certainty\Exception\CertaintyException
     * @throws \SodiumException
     */
    public function getLatestCaCerts(): string
    {
        return (new RemoteFetch($this->caCertDir))
            ->getLatestBundle()
            ->getFilePath();
    }
}
