<?php

namespace App\Services;

class CleaningService
{
    /**
     * Normalize a project name for better fuzzy matching.
     */
    public function normalize(string $name): string
    {
        // 1. Lowercase conversion
        $name = strtolower($name);

        // 2. Remove symbols and non-alphanumeric characters, keeping spaces
        $name = preg_replace('/[^a-z0-9\s]/', ' ', $name);

        // 3. Normalize common abbreviations
        $abbreviations = [
            '\bpt\b' => 'perseroan terbatas', // usually kept as 'pt' or we can leave it as 'pt'
            '\bcv\b' => 'commanditaire vennootschap',
            '\btbk\b' => 'terbuka',
            '\bjln\b' => 'jalan',
            '\bjl\b' => 'jalan',
            '\bgd\b' => 'gedung',
            '\bkab\b' => 'kabupaten',
            '\bkec\b' => 'kecamatan',
            '\bkel\b' => 'kelurahan',
            '\bprov\b' => 'provinsi',
        ];

        // Let's actually keep the abbreviations simple as requested: 
        // "PT. MAJU JAYA" -> "pt maju jaya" (meaning we don't expand PT, we just clean it)
        // "Jln Tol Sumatera" -> "jalan tol sumatera" (meaning we DO expand Jln to jalan)
        $abbreviations = [
            '\bjln\b' => 'jalan',
            '\bjl\b' => 'jalan',
            '\bpt\b' => 'pt',
            '\bcv\b' => 'cv',
            '\bgd\b' => 'gedung',
        ];

        foreach ($abbreviations as $pattern => $replacement) {
            $name = preg_replace('/' . $pattern . '/', $replacement, $name);
        }

        // 4. Remove extra spaces and trim
        $name = trim(preg_replace('/\s+/', ' ', $name));

        return $name;
    }
}
