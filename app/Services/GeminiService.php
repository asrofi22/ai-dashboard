<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class GeminiService
{
    protected string $apiKey;
    protected string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.key', env('GEMINI_API_KEY'));
        // Using gemini-2.5-flash or gemini-pro
        $this->baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent';
    }

    /**
     * Validate if two projects are semantically duplicates.
     */
    public function validateDuplicate(string $projectA, string $projectB): ?array
    {
        if (empty($this->apiKey)) {
            Log::warning('Gemini API key is not configured.');
            return null;
        }

        $prompt = "Anda adalah asisten pemeriksa kualitas data. Tentukan apakah dua nama proyek ini merujuk pada entitas, proyek, atau lokasi yang sama di dunia nyata.\n\n";
        $prompt .= "Proyek A: \"$projectA\"\n";
        $prompt .= "Proyek B: \"$projectB\"\n\n";
        $prompt .= "Berikan respons HANYA dalam format JSON murni tanpa markdown tambahan, dengan kunci berikut:\n";
        $prompt .= "- result: string (harus salah satu dari persis 'SAME', 'POSSIBLY', atau 'DIFFERENT')\n";
        $prompt .= "- confidence_score: float (antara 0.00 dan 1.00)\n";
        $prompt .= "- reasoning: string (penjelasan singkat 1-2 kalimat dalam Bahasa Indonesia)\n";

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '?key=' . $this->apiKey, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.1, // low temperature for consistent evaluation
                    'responseMimeType' => 'application/json',
                ]
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
                
                if ($text) {
                    $decoded = json_decode($text, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        return [
                            'prompt' => $prompt,
                            'response' => $text,
                            'result' => $decoded['result'] ?? 'POSSIBLY',
                            'confidence_score' => $decoded['confidence_score'] ?? 0.5,
                            'reasoning' => $decoded['reasoning'] ?? '',
                        ];
                    }
                }
            }

            Log::error('Gemini API request failed', ['status' => $response->status(), 'body' => $response->body()]);
            return null;

        } catch (Exception $e) {
            Log::error('Gemini Validation Error: ' . $e->getMessage());
            return null;
        }
    }
}
