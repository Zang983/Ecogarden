<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class MeteoController extends AbstractController
{
    private string $api_key;

    public function __construct(private HttpClientInterface $client, private CacheInterface $cache)
    {}

    #[Route('/api/meteo/{city}', name: 'meteo_city', methods: [Request::METHOD_GET])]
    #[Route('/api/meteo/', name: 'meteo_default', methods: [Request::METHOD_GET])]
    public function getMeteo(?string $city): JsonResponse
    {
        $this->api_key = $this->getParameter('api_key');
        if (!$city && $this->getUser()) {
            $city = $this->getUser()->getCity() ? $this->getUser()->getCity() : $this->getUser()->getZipCode();
        }
        if(!$city)
        {
            throw new \Exception('City not found');
        }
        // mettre la mise en cache.

        $response = $this->cache->get('meteo_'. $city,function (ItemInterface $item) use($city){
         $item->expiresAfter(3600);
         return $this->getMeteosInfoFromAPI($city);
        });

        if ($response === 'City not found') {
            return new JsonResponse([
                'message' => 'City not found'
            ], Response::HTTP_NOT_FOUND);
        }
        if ($response) {
            $response = json_decode($response, true);
        }
        if (!$response) {
            return new JsonResponse([
                'message' => 'Json error'
            ], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse([
            'message' => $response
        ], Response::HTTP_OK);
    }

    private function getMeteosInfoFromAPI(string $city): string
    {
        $coordinates = $this->getCoordinates($city);
        if (empty($coordinates)) {
            return 'City not found';
        }
        $request = $this->client->request(
            'GET',
            'https://api.openweathermap.org/data/2.5/weather?lat=' . $coordinates['lat'] . '&lon=' . $coordinates['lon'] . '&appid=' . $this->api_key
        );

        return $request->getContent() ?? 'No response';
    }

    private function getCoordinates(string $data): array
    {
        try {
            if (is_numeric($data)) {
                return $this->getCoordinatesByZip($data);
            }
            return $this->getCoordinatesByName($data);
        } catch (\Exception $e) {
            return [];
        }
    }

    private function getCoordinatesByName(string $city): array
    {
        $coordinates = [];
        $request = $this->client->request(
            'GET',
            'http://api.openweathermap.org/geo/1.0/direct?q=' . $city . '&limit=1&appid=' . $this->api_key
        );
        $response = $request->getContent();
        if ($response) {
            $response = json_decode($response, true);
        }
        if (isset($response[0])) {
            $coordinates['lat'] = $response[0]['lat'];
            $coordinates['lon'] = $response[0]['lon'];
        }
        return $coordinates;
    }

    private function getCoordinatesByZip(string $zip): array
    {
        $coordinates = [];
        $request = $this->client->request(
            'GET',
            'http://api.openweathermap.org/geo/1.0/zip?zip=' . $zip . ',FR&appid=' . $this->api_key
        );

        $response = $request->getContent();
        if ($response) {
            $response = json_decode($response, true);
        }
        if (isset($response['lat']) && isset($response['lon'])) {
            $coordinates['lat'] = $response['lat'];
            $coordinates['lon'] = $response['lon'];
        }
        return $coordinates;
    }
}
