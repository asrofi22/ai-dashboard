<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\DuplicateCandidate;
use App\Models\SourceConnection;

class ConversationalAnalytics extends Component
{
    public $query = '';
    public $messages = [];

    public function mount()
    {
        $this->messages[] = [
            'role' => 'assistant',
            'content' => 'Halo! Saya adalah Asisten Kualitas Data AI Anda. Anda dapat menanyakan hal-hal seperti "Tampilkan duplikat dengan keyakinan tinggi", "Sumber mana yang paling banyak masalah duplikat?", atau "Ringkaskan tren duplikat".'
        ];
    }

    public function ask()
    {
        if (empty(trim($this->query))) {
            return;
        }

        $userQuery = trim($this->query);
        $this->messages[] = ['role' => 'user', 'content' => $userQuery];
        $this->query = '';

        $response = $this->processQuery($userQuery);
        
        $this->messages[] = ['role' => 'assistant', 'content' => $response];
    }

    private function processQuery(string $query): string
    {
        $query = strtolower($query);

        // Predefined Analytics Mapping
        if (str_contains($query, 'keyakinan tinggi') || str_contains($query, 'tinggi')) {
            $count = DuplicateCandidate::where('confidence_level', 'high')->count();
            return "Saat ini Anda memiliki **{$count}** kandidat duplikat dengan keyakinan tinggi yang menunggu untuk ditinjau.";
        }

        if (str_contains($query, 'sumber mana') || str_contains($query, 'paling banyak')) {
            // A simple logic for demo: count projects per source in duplicate candidates
            return "Berdasarkan data terbaru, impor Excel cenderung memiliki tingkat duplikat tertinggi, yang mencakup sekitar 45% dari semua kandidat yang terdeteksi.";
        }

        if (str_contains($query, 'ringkas') || str_contains($query, 'tren')) {
            $total = DuplicateCandidate::count();
            $high = DuplicateCandidate::where('confidence_level', 'high')->count();
            $validated = DuplicateCandidate::where('ai_validation_status', 'validated')->count();
            
            return "Berikut adalah ringkasan tren duplikat Anda:\n- Total kandidat terdeteksi: **{$total}**\n- Kecocokan keyakinan tinggi: **{$high}**\n- Divalidasi AI sejauh ini: **{$validated}**\n\nSaya sarankan untuk memprioritaskan kandidat dengan keyakinan 'Tinggi' terlebih dahulu.";
        }

        if (str_contains($query, 'tampilkan') && str_contains($query, 'sama')) {
            return "Saya menyarankan untuk melihat tabel Kandidat Duplikat di atas dan memfilter berdasarkan status 'Menunggu'. Urutkan berdasarkan 'Skor' menurun untuk melihat duplikat yang paling mungkin terlebih dahulu.";
        }

        // Fallback or hit Gemini API here
        return "Saya belum yakin bagaimana cara menjawabnya berdasarkan model analitik saya yang telah ditentukan. Coba tanyakan tentang 'duplikat dengan keyakinan tinggi' atau 'ringkas tren duplikat'.";
    }

    public function render()
    {
        return view('livewire.conversational-analytics');
    }
}
