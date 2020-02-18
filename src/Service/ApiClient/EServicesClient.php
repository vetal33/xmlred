<?php


namespace App\Service\ApiClient;


use GuzzleHttp\{Client, RequestOptions};
use GuzzleHttp\Exception\ClientException;


class EServicesClient
{
    /** @var Client $client */
    private $client;

    /** @var array $headers */
    private $headers = [];

    /** @var string $lastErrorMessage */
    private $lastErrorMessage;

    /**
     * @var
     */
    private $isConnect;

    /**
     * EServicesClient constructor.
     * @param $baseUri
     */
    public function __construct($baseUri)
    {
        $this->client = new Client([
            'base_uri' => $baseUri
        ]);
    }

    /**
     * @return null|string
     */
    public function getLastErrorMessage(): ?string
    {
        return $this->lastErrorMessage;
    }


    /**
     * @param string $clientId
     * @param string $clientSecret
     * @return bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function setAccessToken(string $clientId, string $clientSecret)
    {
        try {
            $response = $this->client->request('POST', '/oauth/v2/token', [
                RequestOptions::FORM_PARAMS => [
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                    'grant_type' => 'client_credentials'
                ],
                RequestOptions::VERIFY => false
            ]);

            $contents = json_decode($response->getBody()->getContents(), true);

            if (is_array($contents) && array_key_exists('access_token', $contents)) {
                $this->headers['Authorization'] = sprintf('Authorization: Bearer %s', $contents['access_token']);

                return $this->isConnect = true;
            }

            $this->lastErrorMessage = $response->getBody()->getContents();

        } catch (ClientException $e) {

            $this->lastErrorMessage = $e->getMessage();

        } catch (\Exception $e) {

            $this->lastErrorMessage = $e->getMessage();
        }

        return $this->isConnect = false;
    }

    /**
     * @return mixed
     */
    public function isConnect()
    {
        return $this->isConnect;
    }


    /**
     * @param array $data
     * @return bool|mixed
     */
    public function getParcelsInPoint(array $data)
    {
        try {
            $response = $this->client->post('/api/mapcut/check/get_parcels_in_point/json', [
                RequestOptions::HEADERS => $this->headers,
                RequestOptions::JSON => $data,
                RequestOptions::SYNCHRONOUS => true,
                RequestOptions::VERIFY => false,
            ]);

            $contents = json_decode($response->getBody()->getContents(), true);

            if (is_array($contents)) {
                return $contents;
            }

        } catch (ClientException  $exception) {
            $msg = $exception->getResponse()->getBody()->getContents();
            $msg = json_decode($msg, true);
            $this->lastErrorMessage = $msg['error_description'];

        } catch (\Exception $exception) {

            $this->lastErrorMessage = 'Виникла критична помилка.';
        }

        return false;
    }

}