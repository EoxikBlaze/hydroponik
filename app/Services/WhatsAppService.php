<?php
namespace App\Services;
use Illuminate\Support\Facades\Http;

class WhatsAppService
{
    protected string $token;
    protected string $url;

    public function __construct()
    {
        $this->token = env('FONNTE_TOKEN', '');
        $this->url   = env('FONNTE_URL', 'https://api.fonnte.com/send');
    }

    public function send(string $target, string $message): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => $this->token,
            ])->post($this->url, [
                'target'  => $target,
                'message' => $message,
            ]);
            return $response->json() ?? [];
        } catch (\Exception $e) {
            return ['status' => false, 'error' => $e->getMessage()];
        }
    }

    public function sendToAll(string $message): int
    {
        $numbers  = \App\Models\PenerimaNotif::validNumbers();
        $berhasil = 0;
        foreach ($numbers as $no) {
            $result = $this->send($no, $message);
            if (! empty($result['status'])) $berhasil++;
        }
        return $berhasil;
    }
}
