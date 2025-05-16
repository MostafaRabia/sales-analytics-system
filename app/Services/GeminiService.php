<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GeminiService
{
    private static $client;

    public function __construct()
    {
        if (!isset(self::$client)) {
            self::$client = Http::baseUrl('https://generativelanguage.googleapis.com')->acceptJson();
        }
    }

    public function generateText(string $prompt): array
    {
        $response = self::$client->post('/v1beta/models/gemini-2.0-flash:generateContent?key='.config('gemini.key'), [
            'contents' => [
                'parts' => [
                    'text' => $prompt,
                ]
            ],
            'generationConfig' => [
                "responseMimeType" => "application/json",
                'responseSchema' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'name' => [
                                'type' => 'string',
                            ],
                        ],
                    ],
                ],
            ]
        ]);

        if ($response->failed()) {
            throw new \Exception('Gemini API request failed: ' . $response->body());
        }

        return json_decode($response->json()['candidates'][0]['content']['parts'][0]['text'], true);
    }
}
