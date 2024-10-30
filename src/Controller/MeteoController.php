<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Cache\CacheInterface;

class MeteoController extends AbstractController
{
    private string $api_key;

    public function __construct(private HttpClientInterface $client)
    {
        $dotenv = new Dotenv();
        $dotenv->loadEnv(__DIR__ . '/../../.env.local');
        $this->api_key = $_ENV['API_KEY'];
    }

    #[Route('/api/meteo/{city}', name: 'meteo_city', methods: ['GET'])]
    public function getMeteo(string $city): JsonResponse
    {
        $response = $this->getMeteosInfoFromAPI($city);
        if ($response === 'City not found') {
            return new JsonResponse([
                'message' => 'City not found'
            ], 404);
        }

        if ($response) {
            $response = json_decode($response, true);
        }

        return new JsonResponse([
            'message' => $response ?? 'No response'
        ], 200);
    }

    #[Route('/api/meteo/', name: 'meteo_default', methods: ['GET'])]
    public function getDefaultMeteo(): JsonResponse
    {

        $user = $this->getUser();
        if(!$user) {
            return new JsonResponse([
                'message' => 'You are not connected'
            ], 403);
        }
        $data = $user->getCity() ?? $user->getZipCode();
        $response = $this->getMeteosInfoFromAPI($data);
        if ($response === 'City not found') {
            return new JsonResponse([
                'message' => 'City not found'
            ], 404);
        }
        if ($response) {
            $response = json_decode($response, true);
        }

        return new JsonResponse([
            'message' => $response ?? 'No response'
        ], 200);
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
            $coordinates['city'] = $response['name'];
        }

        return $coordinates;
    }
}
