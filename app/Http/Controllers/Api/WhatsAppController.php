<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class WhatsAppController extends Controller
{
    public function status()
    {
        $base = env('WA_WORKER_URL', 'http://127.0.0.1:3333');
        $r = Http::get("$base/status");
        return response()->json($r->json());
    }

    public function qr()
    {
        $base = env('WA_WORKER_URL', 'http://127.0.0.1:3333');
        $r = Http::get("$base/qr");
        if ($r->failed()) return response()->json(['message' => 'No QR'], 404);
        return response()->json($r->json());
    }

    public function logout()
    {
        $base = env('WA_WORKER_URL', 'http://127.0.0.1:3333');
        $r = Http::post("$base/logout");
        return response()->json($r->json());
    }
}
