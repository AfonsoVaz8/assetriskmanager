<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GlpiApiService
{
    protected $apiUrl;
    protected $appToken;
    protected $userToken;
    protected $sessionToken;

    public function __construct()
    {
        $this->apiUrl = config('services.glpi.url');
        $this->appToken = config('services.glpi.app_token');
        $this->userToken = config('services.glpi.user_token');
    }

    public function initSession()
    {
        $response = Http::withHeaders([
            'App-Token' => $this->appToken,
            'Authorization' => 'user_token ' . $this->userToken,
        ])->get("{$this->apiUrl}/initSession");

        if ($response->successful()) {
            $this->sessionToken = $response->json('session_token');
            return true;
        }

        Log::error('Erro ao iniciar sessão no GLPI: ' . $response->body());
        return false;
    }

    public function killSession()
    {
        if ($this->sessionToken) {
            Http::withHeaders([
                'App-Token' => $this->appToken,
                'Session-Token' => $this->sessionToken,
            ])->get("{$this->apiUrl}/killSession");
        }
    }

    public function getIncidentsByYear($year)
    {
        if (!$this->sessionToken && !$this->initSession()) {
            return collect([]); // Retorna coleção vazia se falhar
        }

        $response = Http::withHeaders([
            'App-Token' => $this->appToken,
            'Session-Token' => $this->sessionToken,
        ])->get("{$this->apiUrl}/Ticket", [
            'expand_drowdowns' => true,
            'range' => '0-1000'
        ]);

        $this->killSession();

        if ($response->successful()) {
            $tickets = collect($response->json());

            return $tickets->filter(function ($ticket) use ($year) {
                return \Carbon\Carbon::parse($ticket['date'])->year == $year;
            })->map(function ($ticket) {
                $openDate = \Carbon\Carbon::parse($ticket['date']);

                $closeDateStr = $ticket['closedate'] ?? $ticket['solvedate'] ?? null;
                $durationInHours = null;
                $durationInDays = null;

                if ($closeDateStr) {
                    $closeDate = \Carbon\Carbon::parse($closeDateStr);
                    $durationInHours = $openDate->diffInHours($closeDate);
                    $durationInDays = $openDate->diffInDays($closeDate);
                }

                return [
                    'id'             => $ticket['id'],
                    'name'           => $ticket['name'],
                    'date'           => $ticket['date'],
                    'closedate'      => $closeDateStr,
                    'status'         => $ticket['status'] ?? null,
                    'duration_hours' => $durationInHours,
                    'duration_days'  => $durationInDays,
                    'raw'            => $ticket
                ];
            });
        }

        return collect([]);
    }
}
