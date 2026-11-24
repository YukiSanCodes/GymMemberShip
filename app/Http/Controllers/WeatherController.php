<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class WeatherController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $data = $request->validate(['lat' => 'required|numeric|between:-90,90', 'lon' => 'required|numeric|between:-180,180']);
        if (! config('services.openweather.key')) {
            return response()->json(['message' => 'Weather is currently unavailable.'], 503);
        }
        try {
            $weather = Cache::remember('weather:'.round($data['lat'], 3).':'.round($data['lon'], 3), 300, function () use ($data) {
                $response = Http::connectTimeout(3)->timeout(8)->get('https://api.openweathermap.org/data/2.5/weather', ['lat' => $data['lat'], 'lon' => $data['lon'], 'units' => 'metric', 'appid' => config('services.openweather.key')])->throw()->json();

                return ['place' => $response['name'] ?? 'Selected location', 'temperature' => $response['main']['temp'] ?? null, 'description' => $response['weather'][0]['description'] ?? null, 'humidity' => $response['main']['humidity'] ?? null];
            });

            return response()->json($weather);
        } catch (ConnectionException|RequestException $e) {
            return response()->json(['message' => 'Weather is currently unavailable.'], 503);
        }
    }
}
